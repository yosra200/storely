<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendOrderToCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $phone = $this->input('phone');

        if (! is_string($phone)) {
            return;
        }

        $phone = preg_replace('/[\s()-]/', '', trim($phone));
        $phone = preg_replace('/^\+/', '', $phone);

        if (str_starts_with($phone, '0020')) {
            $phone = substr($phone, 2);
        } elseif (str_starts_with($phone, '00966')) {
            $phone = substr($phone, 2);
        }

        if (str_starts_with($phone, '20') && substr($phone, 2, 1) === '0') {
            $phone = '20'.substr($phone, 3);
        } elseif (str_starts_with($phone, '966') && substr($phone, 3, 1) === '0') {
            $phone = '966'.substr($phone, 4);
        } elseif (preg_match('/^01[0125]\d{8}$/', $phone)) {
            $phone = '20'.substr($phone, 1);
        } elseif (preg_match('/^05\d{8}$/', $phone)) {
            $phone = '966'.substr($phone, 1);
        } elseif (preg_match('/^5\d{8}$/', $phone)) {
            $phone = '966'.$phone;
        }

        $this->merge(['phone' => $phone]);
    }

    public function rules(): array
    {
        return [
            'phone' => [
                'nullable',
                'string',
                'regex:/^(?:20(?:10|11|12|15)\d{8}|9665\d{8})$/',
            ],
            'message' => ['nullable', 'string', 'max:4096'],
        ];
    }
}