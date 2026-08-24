<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class OrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // 'user_id' => 'required|exists:users,id',
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
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
            ],
        ];
    }
}
