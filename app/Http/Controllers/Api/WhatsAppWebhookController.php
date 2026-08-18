<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if (
            $mode === 'subscribe' &&
            $token === config('services.whatsapp.webhook_verify_token')
        ) {
            return response($challenge, 200);
        }

        return response('Forbidden', 403);
    }

    public function handle(Request $request)
    {
        $data = $request->all();

        $message = $data['entry'][0]['changes'][0]['value']['messages'][0] ?? null;

        if (!$message) {
            return response()->json([
                'success' => true,
            ]);
        }

        $phone = $message['from'] ?? null;

        if (!$phone) {
            return response()->json([
                'success' => true,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | العميل بعت Location
        |--------------------------------------------------------------------------
        */

        if (($message['type'] ?? null) === 'location') {

            $latitude = $message['location']['latitude'] ?? null;
            $longitude = $message['location']['longitude'] ?? null;

            if (!$latitude || !$longitude) {
                return response()->json([
                    'success' => true,
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | هاتي الـ Order الخاص بالعميل
            |--------------------------------------------------------------------------
            */

            $order = Order::whereHas('user', function ($query) use ($phone) {
                $query->where('phone', $phone);
            })
                ->whereIn('status', [
                    'pending',
                    'confirmed',
                    'processing',
                    'out_for_delivery',
                ])
                ->latest()
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => true,
                    'message' => 'No active order found',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Update Location
            |--------------------------------------------------------------------------
            */

            $order->update([
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]);
        }

        return response()->json([
            'success' => true,
        ]);
    }
}
