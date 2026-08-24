<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\OrderRequest;
use App\Traits\ApiResponse;
use App\Models\Order;
use App\Services\Facebook\WhatsAppService;

class OrderController extends Controller
{
    use ApiResponse;


    public function store(OrderRequest $request, WhatsAppService $whatsapp)
    {
        $data = $request->validated();

        $data['order_number'] = 'ORD-' . strtoupper(uniqid());

        $order = Order::create($data);

        $customer = $order->customer;

        $phone = $customer->phone;

        $whatsapp->sendMessage(
            $phone,
            "أهلاً بك 👋\n\nتم إنشاء طلبك رقم #{$order->order_number}.\n\nمن فضلك أرسل موقعك الحالي 📍."
        );

        return $this->successResponse(
            $order,
            __('messages.created_success')
        );
    }
}
