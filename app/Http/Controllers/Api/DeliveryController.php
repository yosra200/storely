<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use App\Http\Resources\UserResource;
use App\Models\User;

class DeliveryController extends Controller
{
    use ApiResponse;
    public function deliveries(Request $request)
    {
        $auth = auth()->user();

        if ($auth->role != 'manager') {
            return $this->errorResponse(__('messages.unauthorized'), 403);
        }


        $users = User::where('role', 'delivery')
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
