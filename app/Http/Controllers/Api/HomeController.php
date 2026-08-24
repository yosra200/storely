<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\User;
use App\Traits\ApiResponse;
use App\Http\Resources\OrderResource;

class HomeController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $user = auth()->user();
        if ($user->role === 'admin') {

            $deliveryCount = User::where('role', 'delivery')->count();

            $ordersCount = Order::count();

            $latestOrders = Order::with([
                'customer',
                'items',
            ])
                ->latest()
                ->take(3)
                ->get();

            return $this->successResponse(
                [
                    'delivery_count' => $deliveryCount,
                    'orders_count' => $ordersCount,
                    'latest_orders' => OrderResource::collection($latestOrders),
                ],
                __('messages.success')
            );
        }

        if ($user->role === 'delivery') {

            $createdOrders = Order::where('delivery_id', $user->id)
                ->where('status', 'created')
                ->count();

            $deliveredOrders = Order::where('delivery_id', $user->id)
                ->where('status', 'delivered')
                ->count();

            return $this->successResponse(
                [
                    'created_orders' => $createdOrders,
                    'delivered_orders' => $deliveredOrders,
                ],
                __('messages.success')
            );
        }

        return response()->json([
            'status' => false,
            'message' => 'Unauthorized.',
        ], 403);
    }
}
