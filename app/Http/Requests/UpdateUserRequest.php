<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email,' . $this->route('user')?->id],
            'role' => ['nullable', 'in:admin,supervisor,sales,delivery,packing,customer'],
        ];
    }
}
