<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class addCustomerRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'min:8', 'max:20', 'unique:users,phone'],
            'role' => [
                'nullable',
                Rule::in(['admin', 'supervisor', 'sales', 'delivery', 'packing', 'customer']),
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (! in_array($value, ['supervisor', 'packing'], true)) {
                        return;
                    }

                    $query = User::query()->where('role', $value);

                    if ($this->route('user')) {
                        $userId = $this->route('user') instanceof User ? $this->route('user')->getKey() : $this->route('user');
                        $query->whereKeyNot($userId);
                    }

                    if ($query->exists()) {
                        $fail(__('messages.role_already_exists'));
                    }
                },
            ],
            'address' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'system_type' => ['nullable', Rule::in(['system_one', 'system_two'])],
        ];
    }
}
