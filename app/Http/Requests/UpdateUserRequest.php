<?php

namespace App\Http\Requests;

use App\Models\User;
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
        $routeUser = $this->route('user');
        $userId = $routeUser instanceof User ? $routeUser->getKey() : $routeUser;

        return [
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => [
                'nullable',
                Rule::in(['admin', 'supervisor', 'sales', 'delivery', 'packing', 'customer']),
                function (string $attribute, mixed $value, \Closure $fail) use ($userId) {
                    if (! in_array($value, ['supervisor', 'packing'], true)) {
                        return;
                    }

                    $exists = User::query()
                        ->where('role', $value)
                        ->whereKeyNot($userId)
                        ->exists();

                    if ($exists) {
                        $fail(__('messages.role_already_exists'));
                    }
                },
            ],
            'address' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'system_type' => ['nullable', Rule::in(['system_one', 'system_two'])],
            'image' => ['nullable', 'string', 'max:2048'],
        ];
    }
}
