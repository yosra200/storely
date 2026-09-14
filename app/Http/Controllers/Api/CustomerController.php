<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\addCustomerRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;

class CustomerController extends Controller
{
    use ApiResponse;

    public function store(addCustomerRequest $request)
    {
        $data = array_merge(
            $request->validated(),
            ['role' => $request->input('role', 'customer')]
        );

        User::create($data);

        return $this->successMessage(__('messages.created_success'));
    }

    public function customer(Request $request)
    {
        $auth = auth()->user();

        if (!$auth || !in_array($auth->role, ['manager', 'admin'], true)) {
            return $this->errorResponse(__('messages.unauthorized'), 403);
        }

        $users = User::query()
            ->when($request->input('role'), function ($query) use ($request) {
                $query->where('role', $request->input('role'));
            })
            ->when($request->filled('filter'), function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->filter . '%');
            })
            ->latest()
            ->paginate($request->input('per_page', 10));

        return $this->successResponse(
            UserResource::collection($users),
            __('messages.success')
        );
    }

    public function show(User $user)
    {
        $auth = auth()->user();

        if (!$auth || !in_array($auth->role, ['manager', 'admin'], true)) {
            return $this->errorResponse(__('messages.unauthorized'), 403);
        }

        return $this->successResponse(
            new UserResource($user),
            __('messages.show_success')
        );
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $auth = auth()->user();

        if (!$auth || !in_array($auth->role, ['admin', 'supervisor', 'sales', 'delivery', 'packing', 'customer'], true)) {
            return $this->errorResponse(__('messages.unauthorized'), 403);
        }

        $user->update($request->validated());

        return $this->successResponse(
            new UserResource($user->fresh()),
            __('messages.update_success')
        );
    }

    public function destroy(User $user)
    {
        $auth = auth()->user();

        if (!$auth || !in_array($auth->role, ['manager', 'admin'], true)) {
            return $this->errorResponse(__('messages.unauthorized'), 403);
        }

        $user->delete();

        return $this->successResponse(
            null,
            __('messages.success')
        );
    }
}
