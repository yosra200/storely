<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LiveRequestStoreRequest;
use App\Models\Live;
use App\Models\LiveRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class LiveRequestController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $auth = auth()->user();

        if (!$auth || !in_array($auth->role, ['admin', 'supervisor', 'sales'], true)) {
            return $this->errorResponse(__('messages.unauthorized'), 403);
        }

        $query = LiveRequest::query()
            ->with(['sales', 'live', 'reviewer'])
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->when($request->filled('sales_id'), function ($q) use ($request) {
                $q->where('sales_id', $request->sales_id);
            })
            ->when($request->filled('live_id'), function ($q) use ($request) {
                $q->where('live_id', $request->live_id);
            });

        $items = $query->latest()->paginate($request->input('per_page', 10));

        return $this->successResponse($items, __('messages.success'));
    }

    public function store(LiveRequestStoreRequest $request)
    {
        $auth = auth()->user();

        if (!$auth || $auth->role !== 'sales') {
            return $this->errorResponse(__('messages.unauthorized'), 403);
        }

        $data = $request->validated();


        $requestItem = LiveRequest::create([
            'sales_id' => $auth->id,
            'live_id' => $data['live_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => 'pending',
        ]);

        return $this->successResponse(
            $requestItem->load(['sales', 'live']),
            __('messages.created_success')
        );
    }

    public function show(LiveRequest $liveRequest)
    {
        $auth = auth()->user();

        if (!$auth || !in_array($auth->role, ['admin', 'supervisor', 'sales'], true)) {
            return $this->errorResponse(__('messages.unauthorized'), 403);
        }

        return $this->successResponse(
            $liveRequest->load(['sales', 'live', 'reviewer']),
            __('messages.show_success')
        );
    }

    public function accept(Request $request, LiveRequest $liveRequest)
    {
        $auth = auth()->user();

        if (!$auth || !$auth->role === 'admin') {
            return $this->errorResponse(__('messages.unauthorized'), 403);
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string'],
        ]);

        $liveRequest->update([
            'status' => 'accepted',
            'reviewed_by' => $auth->id,
            'reviewed_at' => now(),
            'notes' => $validated['notes'] ?? null,
        ]);

        return $this->successResponse(
            $liveRequest->fresh()->load(['sales', 'live', 'reviewer']),
            __('messages.update_success')
        );
    }

    public function reject(Request $request, LiveRequest $liveRequest)
    {
        $auth = auth()->user();

        if (!$auth || !$auth->role === 'admin') {
            return $this->errorResponse(__('messages.unauthorized'), 403);
        }

        $validated = $request->validate([
            'notes' => ['required', 'string'],
        ]);

        $liveRequest->update([
            'status' => 'rejected',
            'reviewed_by' => $auth->id,
            'reviewed_at' => now(),
            'notes' => $validated['notes'],
        ]);

        return $this->successResponse(
            $liveRequest->fresh()->load(['sales', 'live', 'reviewer']),
            __('messages.update_success')
        );
    }
}
