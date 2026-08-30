<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\OrderRequest;
use App\Http\Requests\AddOrderSupervisorRequest;
use App\Http\Requests\AddOrderSalesRequest;
use App\Traits\ApiResponse;
use App\Models\Order;
use App\Services\Facebook\WhatsAppService;
use App\Models\User;
use Illuminate\Validation\Rule;
use App\Http\Resources\OrderResource;

class OrderController extends Controller
{
    use ApiResponse;


    public function show(Order $order)
    {
        $order->load([
            'customer',
            'items',
        ]);

        return $this->successResponse(
            new OrderResource($order),
            __('messages.success')
        );
    }



    public function index(Request $request)
    {
        $orders = Order::with([
            'customer',
            'items',
        ])
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->latest()
            ->paginate($request->get('per_page', 10));

        return $this->successResponse(
            OrderResource::collection($orders),
            __('messages.success')
        );
    }


    public function deliveryOrders(Request $request)
    {
        $user = auth()->user();

        $orders = Order::with([
            'customer',
            'items',
        ])
            ->where('delivery_id', $user->id)
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->latest()
            ->paginate($request->get('per_page', 10));

        return $this->successResponse(
            OrderResource::collection($orders),
            __('messages.success')
        );
    }

    public function store(OrderRequest $request, WhatsAppService $whatsapp)
    {
        $data = $request->validated();

        // Get existing customer or create a new one
        $customer = User::firstOrCreate(
            [
                'phone' => $data['phone'],
            ],
            [
                'name' => $data['name'],
                'role' => 'customer',
            ]
        );

        // Order data
        $orderData = $data;

        unset(
            $orderData['name'],
            $orderData['phone'],
            $orderData['products'] 
        );

        $orderData['customer_id'] = $customer->id;
        $orderData['order_number'] = 'ORD-' . strtoupper(uniqid());

        // Create Order
        $order = Order::create($orderData);

        // Save Order Items
        foreach ($data['products'] as $product) {
            $order->items()->create([
                'product_name' => $product['product_name'],
                'quantity' => $product['quantity'],
                'price' => $product['price'],
            ]);
        }

        // Send WhatsApp
        $whatsapp->sendMessage(
            $customer->phone,
            "أهلاً بك 👋\n\nتم إنشاء طلبك رقم #{$order->order_number}.\n\nمن فضلك أرسل موقعك الحالي 📍."
        );

        return $this->successResponse(
            $order->load('items'),
            __('messages.created_success')
        );
    }

    public function addOrderSupervisor(AddOrderSupervisorRequest $request)
    {
        $data = $request->validated();

        $orderData = $data;

        unset(
            $orderData['products']
        );

        $orderData['order_number'] = 'ORD-' . strtoupper(uniqid());
        $orderData['customer_id'] = null;
        $orderData['subtotal'] = $data['subtotal'] ?? ($data['total_amount'] - ($data['delivery_fee'] ?? 0));
        $orderData['delivery_fee'] = $data['delivery_fee'] ?? 0;
        $orderData['total_amount'] = $data['total_amount'];
        $orderData['status'] = $data['status'] ?? 'pending';
        $orderData['payment_status'] = $data['payment_status'] ?? 'pending';

        $order = Order::create($orderData);

        foreach ($data['products'] ?? [] as $product) {
            if (empty($product['product_name'])) {
                continue;
            }

            $order->items()->create([
                'product_name' => $product['product_name'],
                'quantity' => $product['quantity'] ?? 1,
                'price' => $product['price'] ?? 0,
            ]);
        }

        return $this->successResponse(
            $order->load('items'),
            __('messages.created_success')
        );
    }

    public function addOrderSales(AddOrderSalesRequest $request, WhatsAppService $whatsapp)
    {
        $data = $request->validated();
        $phone = preg_replace('/\D+/', '', $data['phone']);

        $customer = User::firstOrCreate(
            ['phone' => $phone],
            [
                'name' => 'Customer',
                'role' => 'customer',
            ]
        );

        $order = Order::create([
            'order_number' => 'ORD-' . strtoupper(uniqid()),
            'customer_id' => $customer->id,
            'status' => 'pending',
            'payment_status' => 'pending',
            'subtotal' => 0,
            'delivery_fee' => 0,
            'total_amount' => 0,
        ]);

        $whatsapp->sendMessage(
            $customer->phone,
            "أهلاً بك 👋\n\nتم إنشاء طلبك رقم #{$order->order_number}.\n\nمن فضلك أرسل موقعك الحالي 📍."
        );

        return $this->successResponse(
            $order->load('items'),
            __('messages.created_success')
        );
    }


    public function sales(Request $request)
    {
        $auth = auth()->user();

        if (!$auth || $auth->role !== 'manager') {
            return $this->errorResponse(__('messages.unauthorized'), 403);
        }

        $validated = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:255'],
            'from_date'     => ['nullable', 'date_format:Y-m-d'],
            'to_date'       => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:from_date',
            ],
            'per_page'      => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Order::query()
            ->where('status', 'delivered')
            ->with('customer')
            ->when(
                !empty($validated['customer_name']),
                function ($query) use ($validated) {
                    $customerName = $validated['customer_name'];

                    $query->whereHas('customer', function ($customerQuery) use ($customerName) {
                        $customerQuery->where('name', 'like', "%{$customerName}%");
                    });
                }
            )
            ->when(
                !empty($validated['from_date']),
                function ($query) use ($validated) {
                    $query->whereDate('created_at', '>=', $validated['from_date']);
                }
            )
            ->when(
                !empty($validated['to_date']),
                function ($query) use ($validated) {
                    $query->whereDate('created_at', '<=', $validated['to_date']);
                }
            );

        // إجمالي المبيعات بعد تطبيق الفلاتر
        $totalSales = (clone $query)->sum('total_amount');

        // عدد الطلبات بعد تطبيق الفلاتر
        $totalOrders = (clone $query)->count();

        // إجمالي رسوم التوصيل بعد تطبيق الفلاتر
        $totalDeliveryFees = (clone $query)->sum('delivery_fee');

        // الطلبات بعد تطبيق الفلاتر
        $orders = $query
            ->latest()
            ->paginate($validated['per_page'] ?? 10)
            ->withQueryString();

        return $this->successResponse([
            'total_sales'         => $totalSales,
            'total_orders'        => $totalOrders,
            'total_delivery_fees' => $totalDeliveryFees,
            'orders'              => OrderResource::collection($orders),
        ], __('messages.success'));
    }
}
