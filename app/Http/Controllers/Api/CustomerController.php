<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\addCustomerRequest;
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
        $role = $request->input('role', 'customer');

        if (!$auth || !in_array($auth->role, ['manager', 'admin'], true)) {
            return $this->errorResponse(__('messages.unauthorized'), 403);
        }

        $users = User::query()
            ->when($role, function ($query) use ($role) {
                $query->where('role', $role);
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

    public function update(Request $request, User $user)
    {
        $auth = auth()->user();

        if (!$auth || !in_array($auth->role, ['manager', 'admin'], true)) {
            return $this->errorResponse(__('messages.unauthorized'), 403);
        }

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone,' . $user->id],
            'role' => ['nullable', 'in:customer,delivery,manager,admin'],
            'address' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

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
