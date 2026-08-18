<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Traits\ApiResponse;
use App\Http\Requests\addCustomerRequest;

class CustomerController extends Controller
{
    use ApiResponse;
    public function store(addCustomerRequest $request)
    {
        User::create($request->validated(), ['role' => 'customer']);
        return $this->successMessage(__('messages.created_success'));
    }
}
