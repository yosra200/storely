<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Traits\ApiResponse;
use App\Http\Requests\addCustomerRequest;
use App\Http\Resources\UserResource;

class CustomerController extends Controller
{
    use ApiResponse;
    public function store(addCustomerRequest $request)
    {
        User::create($request->validated(), ['role' => 'customer']);
        return $this->successMessage(__('messages.created_success'));
    }
public function customer(Request $request)
{
    $auth = auth()->user();

    if ($auth->role != 'manager') {
        return $this->errorResponse(__('messages.unauthorized'), 403);
    }


$users = User::where('role', 'customer')
    ->when($request->filled('filter'), function ($query) use ($request) {
        $query->where('name', 'like', '%' . $request->filter . '%');
    })
    ->latest()
    ->paginate(
        $request->input('per_page', 10)
    );


    return $this->successResponse(
        UserResource::collection($users),
        __('messages.success')
    );
}
}
