<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\addCustomerRequest;
use App\Http\Requests\UpdateCustomerStatusRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
class CustomerController extends Controller
{
    use ApiResponse;

    public function store(addCustomerRequest $request)
    {
        if (!$this->canManageCustomers()) {
            return $this->errorResponse(__('messages.unauthorized'), 403);
        }

        $role = $request->input('role', 'customer');

        if (in_array($role, ['supervisor', 'packing'], true) && User::where('role', $role)->exists()) {
            return $this->errorResponse(__('messages.role_already_exists'), 422);
        }

        $data = array_merge(
            $request->validated(),
            ['role' => $role]
        );

        User::create($data);

        return $this->successMessage(__('messages.created_success'));
    }

    public function customer(Request $request)
    {
        if (!$this->canManageCustomers()) {
            return $this->errorResponse(__('messages.unauthorized'), 403);
        }

   $users = User::query()
        ->when($request->filled('name'), function ($query) use ($request) {
            $query->where('name', 'like', '%' . $request->name . '%');
        })
        ->when($request->filled('role'), function ($query) use ($request) {
            $query->where('role', $request->role);
        })
            ->latest()
            ->paginate($request->input('per_page', 10));

        return $this->successResponse(
            UserResource::collection($users),
            __('messages.success')
        );
    }

        public function clients(Request $request)
    {
        if (!$this->canManageCustomers()) {
            return $this->errorResponse(__('messages.unauthorized'), 403);
        }

   $users = User::query()
   ->where('role', 'customer')
        ->when($request->filled('name'), function ($query) use ($request) {
            $query->where('name', 'like', '%' . $request->name . '%');
        })
        ->when($request->filled('phone'), function ($query) use ($request) {
            $query->where('phone', 'like', '%' . $request->phone . '%');
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
        if (!$this->canManageCustomers()) {
            return $this->errorResponse(__('messages.unauthorized'), 403);
        }

        return $this->successResponse(
            new UserResource($user),
            __('messages.show_success')
        );
    }

    public function update(UpdateUserRequest $request, $user)
    {
        if (!$this->canManageCustomers()) {
            return $this->errorResponse(__('messages.unauthorized'), 403);
        }

        $customer = User::find($user);

        if (! $customer) {
            return $this->errorResponse(__('messages.not_found'), 404);
        }

        $role = $request->input('role');

        if ($role && in_array($role, ['supervisor', 'packing'], true) && User::where('role', $role)->whereKeyNot($customer->getKey())->exists()) {
            return $this->errorResponse(__('messages.role_already_exists'), 422);
        }

        $data = $request->validated();

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $customer->update($data);

        return $this->successResponse(
            new UserResource($customer->fresh()),
            __('messages.update_success')
        );
    }

    public function updateStatus(UpdateCustomerStatusRequest $request, User $user)
    {
        if (!$this->canManageCustomers()) {
            return $this->errorResponse(__('messages.unauthorized'), 403);
        }

        $user->update(['is_active' => $request->boolean('is_active')]);

        return $this->successResponse(
            new UserResource($user->fresh()),
            $user->is_active ? __('messages.account_activated') : __('messages.account_deactivated')
        );
    }

    public function destroy($user)
    {
        if (!$this->canManageCustomers()) {
            return $this->errorResponse(__('messages.unauthorized'), 403);
        }

        $customer = User::find($user);

        if (! $customer) {
            return $this->errorResponse(__('messages.not_found'), 404);
        }

        $customer->delete();

        return $this->successResponse(
            null,
            __('messages.success')
        );
    }

    private function canManageCustomers(): bool
    {
        return auth()->user()?->role === 'admin';
    }
}
