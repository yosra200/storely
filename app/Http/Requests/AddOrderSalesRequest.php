<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddOrderSalesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
     'name' => ['required', 'string', 'max:255'],
            'total_amount' => 'required',
            'delivery_fee' => 'required',
            'products' => ['required', 'array', 'min:1'],

            'products.*.product_name' => [
                'required',
                'string',
                'max:255',
            ],

            'products.*.quantity' => [
                'required',
                'integer',
                'min:1',
            ],

            'products.*.price' => [
                'required',
                'numeric',
                'min:0',
            ],        ];
    }
}
