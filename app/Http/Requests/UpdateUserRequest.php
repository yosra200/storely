<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->getKey();

        return [
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['nullable', 'string', 'min:8', 'max:20', Rule::unique('users', 'phone')->ignore($userId)],
            'role' => ['nullable', Rule::in(['admin', 'supervisor', 'sales', 'delivery', 'packing', 'customer'])],
            'address' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'system_type' => ['nullable', Rule::in(['system_one', 'system_two'])],
            'image' => ['nullable', 'string', 'max:2048'],
        ];
    }
}
