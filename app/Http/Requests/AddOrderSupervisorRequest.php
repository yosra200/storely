<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddOrderSupervisorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'products' => ['nullable', 'array'],
            'products.*.product_name' => ['nullable', 'string', 'max:255'],
            'products.*.quantity' => ['nullable', 'integer', 'min:1'],
            'products.*.price' => ['nullable', 'numeric', 'min:0'],
            'subtotal' => ['nullable', 'numeric', 'min:0'],
            'delivery_fee' => ['nullable', 'numeric', 'min:0'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'delivery_address' => ['nullable', 'string'],
            'payment_method' => ['nullable', 'in:cash_on_delivery,online'],
            'payment_status' => ['nullable', 'in:pending,link_sent,paid,failed,cancelled'],
            'status' => ['nullable', 'in:pending,created,in_delivery,delivered,cancelled'],
            'delivery_id' => ['nullable', 'exists:users,id'],
        ];
    }
}
