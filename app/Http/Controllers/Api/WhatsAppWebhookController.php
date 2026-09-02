<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
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

        if ($mode === 'subscribe' && $token === config('services.whatsapp.webhook_verify_token')) {
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

        if (! $message) {
            return response()->json([
                'success' => true,
            ]);
        }

        $phone = $message['from'] ?? null;

        if (! $phone) {
            return response()->json([
                'success' => true,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Location
        |--------------------------------------------------------------------------
        */

        if (($message['type'] ?? null) === 'location') {

            $latitude = $message['location']['latitude'] ?? null;
            $longitude = $message['location']['longitude'] ?? null;

            if ($latitude === null || $longitude === null) {
                return response()->json([
                    'success' => true,
                ]);
            }

            $locationRequestId = $message['context']['id'] ?? null;
            $orderNumber = $whatsappService->getLocationRequestOrderNumber($locationRequestId);

            $order = $orderNumber
                ? $this->findCustomerOrder($orderNumber, $phone)
                : null;

            if (! $order) {
                $whatsappService->sendMessage(
                    $phone,
                    'عذرًا، تعذر تحديد رقم الطلب. استخدم زر إرسال الموقع الموجود في رسالة الطلب.'
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Order reference is missing or invalid',
                ]);
            }

            $nearestDelivery = $this->findNearestDelivery(
                (float) $latitude,
                (float) $longitude
            );

            $orderData = [
                'delivery_latitude' => $latitude,
                'delivery_longitude' => $longitude,
            ];

            if ($nearestDelivery) {
                $orderData['delivery_id'] = $nearestDelivery->id;
                $orderData['status'] = 'created';
            }

            $order->update($orderData);

            /*
            |--------------------------------------------------------------------------
            | Send Payment Options
            |--------------------------------------------------------------------------
            */

            $whatsappService->sendPaymentOptions($phone, $order->order_number);

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

                $buttonId = $message['interactive']['button_reply']['id'] ?? '';

                if (! preg_match('/^(cash|online):([A-Za-z0-9-]+)$/', $buttonId, $matches)) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Invalid payment button payload',
                    ]);
                }

                $buttonAction = $matches[1];
                $order = $this->findCustomerOrder($matches[2], $phone);

                /*
                |--------------------------------------------------------------------------
                | Online Payment
                |--------------------------------------------------------------------------
                */

                if ($buttonAction === 'online') {

                    if (! $order) {

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
                                ."رقم الطلب: #{$order->order_number}\n"
                                ."إجمالي الطلب: {$order->total_amount} جنيه\n\n"
                                ."لإتمام الدفع اضغط على الرابط التالي:\n\n"
                                .$payment['payment_url']
                                ."\n\n"
                                .'بعد إتمام الدفع سيتم تأكيد طلبك تلقائيًا.'
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

                if ($buttonAction === 'cash') {

                    if (! $order) {

                        $whatsappService->sendMessage(
                            $phone,
                            'عذرًا، لم يتم العثور على طلب نشط.'
                        );

                        return response()->json([
                            'success' => true,
                        ]);
                    }

                    $order->update([
                        'payment_method' => 'cash_on_delivery',
                        'payment_status' => 'pending',
                    ]);

                    $whatsappService->sendMessage(
                        $phone,
                        "تم اختيار الدفع عند الاستلام ✅\n\n"
                            ."رقم الطلب: #{$order->order_number}\n"
                            ."إجمالي الطلب: {$order->total_amount} جنيه\n\n"
                            .'سيتم تجهيز طلبك للتوصيل.'
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

    private function findCustomerOrder(string $orderNumber, string $phone): ?Order
    {
        return Order::where('order_number', $orderNumber)
            ->whereHas('customer', function ($query) use ($phone) {
                $query->where('phone', $phone);
            })
            ->first();
    }

    private function findNearestDelivery(float $latitude, float $longitude): ?User
    {
        return User::query()
            ->where('role', 'delivery')
            ->where('is_active', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get(['id', 'latitude', 'longitude'])
            ->minBy(function (User $delivery) use ($latitude, $longitude) {
                return $this->distanceInKilometers(
                    $latitude,
                    $longitude,
                    (float) $delivery->latitude,
                    (float) $delivery->longitude
                );
            });
    }

    private function  distanceInKilometers(
        float $fromLatitude,
        float $fromLongitude,
        float $toLatitude,
        float $toLongitude
    ): float {
        $earthRadius = 6371;
        $latitudeDelta = deg2rad($toLatitude - $fromLatitude);
        $longitudeDelta = deg2rad($toLongitude - $fromLongitude);

        $haversine = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad($fromLatitude))
            * cos(deg2rad($toLatitude))
            * sin($longitudeDelta / 2) ** 2;

        $haversine = min(1, max(0, $haversine));

        return 2 * $earthRadius * asin(sqrt($haversine));
    }
}
