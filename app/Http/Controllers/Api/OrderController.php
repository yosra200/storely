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

        // بيانات العميل
        $customer = User::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'role' => 'customer',
        ]);

        // بيانات الـ Order
        $orderData = $data;

        unset(
            $orderData['name'],
            $orderData['phone']
        );

        $orderData['customer_id'] = $customer->id;
        $orderData['order_number'] = 'ORD-' . strtoupper(uniqid());

        $order = Order::create($orderData);

        // إرسال WhatsApp
        $whatsapp->sendMessage(
            $customer->phone,
            "أهلاً بك 👋\n\nتم إنشاء طلبك رقم #{$order->order_number}.\n\nمن فضلك أرسل موقعك الحالي 📍."
        );

        return $this->successResponse(
            $order,
            __('messages.created_success')
        );
    }
}
