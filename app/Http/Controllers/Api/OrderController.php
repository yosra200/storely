<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\OrderRequest;
use App\Traits\ApiResponse;
use App\Models\Order;
use App\Services\Facebook\WhatsAppService;
use App\Models\User;
use App\Http\Resources\OrderResource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Order;
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
