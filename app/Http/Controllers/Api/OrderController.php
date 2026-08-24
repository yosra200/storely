<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\OrderRequest;
use App\Traits\ApiResponse;
use App\Models\Order;
use App\Services\Facebook\WhatsAppService;
use App\Models\User;

class OrderController extends Controller
{
    use ApiResponse;


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
                'item_name' => $product['product_name'],
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
}
