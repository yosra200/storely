<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddOrderSalesRequest;
use App\Http\Requests\AddOrderSupervisorRequest;
use App\Http\Requests\OrderRequest;
use App\Http\Requests\SendOrderToCustomerRequest;
use App\Http\Requests\ChangeDeliveryOrderStatusRequest;
use App\Http\Requests\updateDeliveryLocationRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\DeliveryTrackingLocationResource;
use App\Events\DeliveryLocationUpdated;
use App\Models\DeliveryTrackingLocation;
use Carbon\Carbon;
use App\Models\Order;
use App\Models\User;
use App\Services\Facebook\WhatsAppService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

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
        $auth = $request->user();

        if (! $auth || ! in_array($auth->role, ['admin', 'manager', 'sales', 'supervisor', 'packing', 'delivery'], true)) {
            return $this->errorResponse(__('messages.unauthorized'), 403);
        }

        $query = Order::with([
            'customer',
            'items',
        ]);

        if (! in_array($auth->role, ['admin', 'manager'], true)) {
            $query->where('created_by', $auth->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query
            ->latest()
            ->paginate($request->get('per_page', 10));

        return $this->successResponse(
            OrderResource::collection($orders),
            __('messages.success')
        );
    }

    public function supervisorOrders(Request $request)
    {
        $auth = $request->user();

        if (! $auth || ! in_array($auth->role, ['admin', 'manager', 'supervisor'], true)) {
            return $this->errorResponse(__('messages.unauthorized'), 403);
        }

        $query = Order::with(['customer', 'items'])
        ->whereNotNull('packing_id')
            ->when(! in_array($auth->role, ['admin', 'manager'], true), function ($query) use ($auth) {
                $query->where('supervisor_id', $auth->id);
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            });

        return $this->successResponse(
            OrderResource::collection(
                $query->latest()->paginate($request->get('per_page', 10))
            ),
            __('messages.success')
        );
    }

    public function packingOrders(Request $request)
    {
        $auth = $request->user();

        if (! $auth || ! in_array($auth->role, ['admin', 'manager', 'packing'], true)) {
            return $this->errorResponse(__('messages.unauthorized'), 403);
        }

        $query = Order::with(['customer', 'items'])
        ->whereNotNull('sales_id')
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            });

        return $this->successResponse(
            OrderResource::collection(
                $query->latest()->paginate($request->get('per_page', 10))
            ),
            __('messages.success')
        );
    }

    public function sendToCustomer(SendOrderToCustomerRequest $request, Order $order, WhatsAppService $whatsapp)
    {
        $auth = $request->user();

        if (! $auth || ! in_array($auth->role, ['admin', 'manager', 'supervisor'], true)) {
            return $this->errorResponse(__('messages.unauthorized'), 403);
        }

        $phone = $request->validated('phone') ?: ($order->customer && $order->customer->phone ? $order->customer->phone : null);

        if (! $phone) {
            return $this->errorResponse(__('messages.not_found'), 404);
        }

        $message = $request->validated('message')
            ?? "أهلاً بك 👋\n\nتم تجهيز طلبك رقم #{$order->order_number}.\nيرجى متابعة حالته من التطبيق.";

        $whatsapp->sendMessage($phone, $message);

        return $this->successResponse(
            [
                'order_id' => $order->id,
                'phone' => $phone,
                'sent' => true,
            ],
            __('messages.success')
        );
    }

    public function sendToAliya(Request $request, Order $order, WhatsAppService $whatsapp)
    {
        $auth = $request->user();

        if (! $auth || ! in_array($auth->role, ['admin', 'manager', 'packing', 'supervisor'], true)) {
            return $this->errorResponse(__('messages.unauthorized'), 403);
        }

        // $phone = $request->input('phone', config('services.whatsapp.aliya_phone', env('WHATSAPP_ALIYA_PHONE')));

        // if (! $phone) {
        //     return $this->errorResponse(__('messages.not_found'), 404);
        // }


        $order->update([
            'status' => 'sent_to_aliya',
            'packing_id' => $auth->id(),
        ]);

        // $whatsapp->sendMessage($phone, $message);

        return $this->successResponse(
            [
                'order_id' => $order->id,
                'sent' => true,
                'status' => $order->fresh()->status,
            ],
            __('messages.success')
        );
    }

    //deliveryOrders
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

    public function deliveryOrder(Order $order)
    {
        $user = auth()->user();

        if ((int) $order->delivery_id !== (int) $user->id) {
            return $this->errorResponse(__('messages.not_found'), 404);
        }

        $order->load([
            'customer',
            'items',
        ]);

        return $this->successResponse(
            new OrderResource($order),
            __('messages.success')
        );
    }

    public function changeDeliveryOrderStatus(
        ChangeDeliveryOrderStatusRequest $request,
        Order $order
    ) {
        $user = $request->user();

        if (! $user || (int) $order->delivery_id !== (int) $user->id) {
            return $this->errorResponse(
                __('messages.unauthorized'),
                403
            );
        }

        $order->update($request->validated());

        // // يبدأ الـ Live Tracking عند استلام الدليفري للأوردر
        // if ($order->status === 'received') {
        //     event(new DeliveryTrackingStarted($order));
        // }

        return $this->successResponse(
            new OrderResource($order->fresh()),
            __('messages.updated_success')
        );
    }


    public function updateDeliveryLocation(
        updateDeliveryLocationRequest $request,
        Order $order
    ) {
        $user = $request->user();

        if (! $user || (int) $order->delivery_id !== (int) $user->id) {
            return $this->errorResponse(
                __('messages.unauthorized'),
                403
            );
        }

        $validated = $request->validated();
        $latitude = (float) $validated['latitude'];
        $longitude = (float) $validated['longitude'];
        $heading = isset($validated['heading'])
            ? (float) $validated['heading']
            : null;
        $speed = isset($validated['speed'])
            ? (float) $validated['speed']
            : null;
        $recordedAt = isset($validated['recorded_at'])
            ? Carbon::parse($validated['recorded_at'])
            : now();

        $order->update([
            'delivery_latitude' => $latitude,
            'delivery_longitude' => $longitude,
            'delivery_heading' => $heading,
        ]);

        $location = DeliveryTrackingLocation::create([
            'order_id' => $order->id,
            'delivery_id' => $user->id,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'heading' => $heading,
            'speed' => $speed,
            'captured_at' => $recordedAt,
        ]);

        broadcast(new DeliveryLocationUpdated(
            $order,
            $latitude,
            $longitude,
            $heading,
            $speed,
            $location->captured_at
        ));

        return $this->successResponse(
            new DeliveryTrackingLocationResource($location),
            __('messages.updated_success')
        );
    }

    public function deliveryLocation(Order $order)
    {
        $latest = DeliveryTrackingLocation::query()
            ->where('order_id', $order->id)
            ->latest('captured_at')
            ->first();

        return $this->successResponse([
            'order_id' => $order->id,
            'lat' => $latest?->latitude,
            'lng' => $latest?->longitude,
            'heading' => $latest?->heading,
            'speed' => $latest?->speed,
            'recorded_at' => $latest?->captured_at?->toIso8601String(),
        ], __('messages.success'));
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
        $orderData['created_by'] = auth()->id();
        $orderData['sales_id'] = auth()->id();

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
        // $whatsapp->sendMessage(
        //     $customer->phone,
        //     "أهلاً بك 👋\n\nتم إنشاء طلبك رقم #{$order->order_number}."
        // );
$whatsapp->sendMessage(
    $customer->phone
);

$locationResponse = $whatsapp->sendLocationRequest(
    $customer->phone,
    $order->order_number
);

// dd([
//     'status' => $locationResponse->status(),
//     'successful' => $locationResponse->successful(),
//     'body' => $locationResponse->body(),
//     'json' => $locationResponse->json(),
// ]);

        return $this->successResponse(
            $order->load('items'),
            __('messages.created_success')
        );
    }


    public function salesAddOrder(AddOrderSalesRequest $request, WhatsAppService $whatsapp)
    {
        $auth = $request->user();

        if (! $auth || ! in_array($auth->role, ['admin', 'sales'], true)) {
            return $this->errorResponse(__('messages.unauthorized'), 403);
        }

        $data = $request->validated();

        $order = Order::create($data + [
            'order_number' => 'ORD-' . strtoupper(uniqid()),
            'sales_id' => $auth->id,
            'status' => $data['status'] ?? 'pending',
        ]);

        return $this->successResponse(
            $order->load('items'),
            __('messages.created_success')
        );
    }

    public function salesOrders(Request $request)
    {
        $auth = $request->user();

        if (! $auth || ! in_array($auth->role, ['admin', 'manager', 'sales'], true)) {
            return $this->errorResponse(__('messages.unauthorized'), 403);
        }

        $query = Order::with(['customer', 'items'])
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            });

        return $this->successResponse(
            OrderResource::collection(
                $query->latest()->paginate($request->get('per_page', 10))
            ),
            __('messages.success')
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
        $orderData['created_by'] = auth()->id();
        $orderData['supervisor_id'] = auth()->id();
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



    public function sales(Request $request)
    {
        $auth = $request->user();

        if (! $auth || ! in_array($auth->role, ['admin', 'manager', 'delivery'], true)) {
            return $this->errorResponse(__('messages.unauthorized'), 403);
        }

        $validated = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:255'],
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:from_date',
            ],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Order::query()
            ->where('status', 'delivered')
            ->when($auth->role === 'delivery', function ($query) use ($auth) {
                $query->where('delivery_id', $auth->id);
            })
            ->with('customer')
            ->when(
                ! empty($validated['customer_name']),
                function ($query) use ($validated) {
                    $customerName = $validated['customer_name'];

                    $query->whereHas('customer', function ($customerQuery) use ($customerName) {
                        $customerQuery->where('name', 'like', "%{$customerName}%");
                    });
                }
            )
            ->when(
                ! empty($validated['from_date']),
                function ($query) use ($validated) {
                    $query->whereDate('created_at', '>=', $validated['from_date']);
                }
            )
            ->when(
                ! empty($validated['to_date']),
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
            'total_sales' => $totalSales,
            'total_orders' => $totalOrders,
            'total_delivery_fees' => $totalDeliveryFees,
            'orders' => OrderResource::collection($orders),
        ], __('messages.success'));
    }
}
