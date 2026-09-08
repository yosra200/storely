<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:3',
                'max:255',
            ],

            'email' => [
                'required',
                'string',
                'email:rfc,dns',
                'max:255',
                'unique:users,email',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'max:100',
                'confirmed',
                'regex:/[a-zA-Z]/',
                'regex:/[0-9]/',
            ],

            'phone' => [
                'required',
                'string',
                // 'regex:/^[0-9+\-\s()]+$/',
                'min:8',
                'max:20',
                'unique:users,phone',
            ],

            'latitude' => [
                'nullable',
                'numeric',
                'between:-90,90',
            ],

            'longitude' => [
                'nullable',
                'numeric',
                'between:-180,180',
            ],

            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'role' => [
                'required',
                'string',
                'in:customer,delivery,packing,sales,supervisor',
            ],

            'system_type' => [
                'required',
                'string',
                'in:system_one,system_two',
            ],
        ];
    }
}
