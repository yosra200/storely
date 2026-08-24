<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Models\Order;
use App\Services\Facebook\WhatsAppService;
use App\Services\MyFatoorahService;
use Illuminate\Http\Request;
use Throwable;

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

    public function handle(
        Request $request,
        WhatsAppService $whatsappService,
        MyFatoorahService $myFatoorahService
    ) {
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
        | Get Active Order
        |--------------------------------------------------------------------------
        */

        $order = Order::whereHas('user', function ($query) use ($phone) {
            $query->where('phone', $phone);
        })
            // ->whereIn('status', [
            //     'pending',
            //     'confirmed',
            //     'processing',
            //     'out_for_delivery',
            // ])
            ->latest()
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Location
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

            if (!$order) {
                return response()->json([
                    'success' => true,
                    'message' => 'No active order found',
                ]);
            }

            $order->update([
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Send Payment Options
            |--------------------------------------------------------------------------
            */

            $whatsappService->sendPaymentOptions($phone);

            return response()->json([
                'success' => true,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Interactive Button
        |--------------------------------------------------------------------------
        */

        if (($message['type'] ?? null) === 'interactive') {

            $interactiveType = $message['interactive']['type'] ?? null;

            /*
            |--------------------------------------------------------------------------
            | Reply Button
            |--------------------------------------------------------------------------
            */

            if ($interactiveType === 'button_reply') {

                $buttonId = $message['interactive']['button_reply']['id'] ?? null;

                /*
                |--------------------------------------------------------------------------
                | Online Payment
                |--------------------------------------------------------------------------
                */

                if ($buttonId === 'online') {

                    if (!$order) {

                        $whatsappService->sendMessage(
                            $phone,
                            'عذرًا، لم يتم العثور على طلب نشط.'
                        );

                        return response()->json([
                            'success' => true,
                        ]);
                    }

                    try {

                        /*
                        |--------------------------------------------------------------------------
                        | Create MyFatoorah Payment
                        |--------------------------------------------------------------------------
                        */

                        $payment = $myFatoorahService->createPayment(
                            $order
                        );

                        /*
                        |--------------------------------------------------------------------------
                        | Send Payment URL
                        |--------------------------------------------------------------------------
                        */

                        $whatsappService->sendMessage(
                            $phone,
                            "تم اختيار الدفع الإلكتروني ✅\n\n"
                                . "رقم الطلب: #{$order->id}\n"
                                . "إجمالي الطلب: {$order->total} جنيه\n\n"
                                . "لإتمام الدفع اضغط على الرابط التالي:\n\n"
                                . $payment['payment_url']
                                . "\n\n"
                                . "بعد إتمام الدفع سيتم تأكيد طلبك تلقائيًا."
                        );
                    } catch (Throwable $e) {

                        report($e);

                        $whatsappService->sendMessage(
                            $phone,
                            'حدث خطأ أثناء إنشاء رابط الدفع، من فضلك حاول مرة أخرى.'
                        );
                    }

                    return response()->json([
                        'success' => true,
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Cash On Delivery
                |--------------------------------------------------------------------------
                */

                if ($buttonId === 'cash') {

                    if (!$order) {

                        $whatsappService->sendMessage(
                            $phone,
                            'عذرًا، لم يتم العثور على طلب نشط.'
                        );

                        return response()->json([
                            'success' => true,
                        ]);
                    }

                    $order->update([
                        'payment_method' => 'cash',
                        'payment_status' => 'pending',
                    ]);

                    $whatsappService->sendMessage(
                        $phone,
                        "تم اختيار الدفع عند الاستلام ✅\n\n"
                            . "رقم الطلب: #{$order->id}\n"
                            . "إجمالي الطلب: {$order->total} جنيه\n\n"
                            . "سيتم تجهيز طلبك للتوصيل."
                    );

                    return response()->json([
                        'success' => true,
                    ]);
                }
            }
        }

        return response()->json([
            'success' => true,
        ]);
    }
}
