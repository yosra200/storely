<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Models\Live;
use App\Services\Facebook\FacebookLiveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class LiveController extends Controller
{
    public function __construct(
        private FacebookLiveService $facebookLiveService
    ) {}

    /**
     * Start Live
     */
    public function start(Request $request)
    {
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $user = $request->user();

        // مثال:
        // هاتي Facebook account المرتبط بالمستخدم
        $facebookAccount = $user->facebookAccount;

        if (!$facebookAccount) {
            return response()->json([
                'status' => false,
                'message' => 'Facebook account is not connected.',
            ], 422);
        }

        DB::beginTransaction();

        try {

            /*
             * 1. Create local Live
             */
            $live = Live::create([
                'user_id' => $user->id,
                'title' => $request->title,
                'description' => $request->description,
                'status' => 'creating',
            ]);

            /*
             * 2. Create Facebook Live
             */
            $facebookLive = $this->facebookLiveService->createLive(
                $facebookAccount->page_id,
                $facebookAccount->page_access_token,
                $request->title,
                $request->description
            );

            /*
             * 3. Save Facebook information
             */
            $live->update([
                'facebook_live_id' => $facebookLive['id'] ?? null,

                'stream_url' => $facebookLive['stream_url'] ?? null,

                'stream_key' => $facebookLive['stream_key'] ?? null,

                'status' => 'live',

                'started_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Live created successfully.',
                'data' => [
                    'live_id' => $live->id,

                    'facebook_live_id' =>
                    $live->facebook_live_id,

                    'stream_url' =>
                    $live->stream_url,

                    'stream_key' =>
                    $live->stream_key,

                    'status' =>
                    $live->status,
                ],
            ]);
        } catch (Throwable $e) {

            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Failed to start live.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function end(
        Request $request,
        Live $live
    ) {
        $user = $request->user();

        if ($live->user_id !== $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        if (!$live->facebook_live_id) {
            return response()->json([
                'status' => false,
                'message' => 'Facebook live not found.',
            ], 404);
        }

        $facebookAccount = $user->facebookAccount;

        if (!$facebookAccount) {
            return response()->json([
                'status' => false,
                'message' => 'Facebook account is not connected.',
            ], 422);
        }

        try {

            $this->facebookLiveService->endLive(
                $live->facebook_live_id,
                $facebookAccount->page_access_token
            );

            $live->update([
                'status' => 'ended',
                'ended_at' => now(),
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Live ended successfully.',
            ]);
        } catch (Throwable $e) {

            return response()->json([
                'status' => false,
                'message' => 'Failed to end live.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function show(Request $request, Live $live)
    {
        return response()->json([
            'status' => true,
            'data' => [
                'id' => $live->id,
                'title' => $live->title,
                'description' => $live->description,
                'status' => $live->status,
                'facebook_live_id' => $live->facebook_live_id,
                'started_at' => $live->started_at,
                'ended_at' => $live->ended_at,
            ],
        ]);
    }
}
