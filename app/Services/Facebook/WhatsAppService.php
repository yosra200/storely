<?php

namespace App\Services\Facebook;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class WhatsAppService
{
    protected string $graphUrl = 'https://graph.facebook.com/v26.0';

    public function sendMessage(string $phone, string $message)
    {
        return Http::withToken(config('services.whatsapp.token'))
            ->post(
                $this->graphUrl.'/'.config('services.whatsapp.phone_number_id').'/messages',
                [
                    'messaging_product' => 'whatsapp',
                    'to' => $phone,
                    'type' => 'text',
                    'text' => [
                        'body' => $message,
                    ],
                ]
            );
    }

    public function sendLocationRequest(string $phone, string $orderNumber)
    {
        $response = Http::withToken(config('services.whatsapp.token'))
            ->post(
                $this->graphUrl.'/'.config('services.whatsapp.phone_number_id').'/messages',
                [
                    'messaging_product' => 'whatsapp',
                    'to' => $phone,
                    'type' => 'interactive',
                    'interactive' => [
                        'type' => 'location_request_message',
                        'body' => [
                            'text' => 'من فضلك أرسل موقعك الحالي للطلب.',
                        ],
                        'action' => [
                            'name' => 'send_location',
                        ],
                    ],
                ]
            );

        $messageId = data_get($response->json(), 'messages.0.id');

        if ($response->successful() && $messageId) {
            Cache::put($this->locationRequestCacheKey($messageId), $orderNumber, now()->addDay());
        }

        return $response;
    }

    public function getLocationRequestOrderNumber(?string $messageId): ?string
    {
        if (! $messageId) {
            return null;
        }

        $orderNumber = Cache::get($this->locationRequestCacheKey($messageId));

        return is_string($orderNumber) ? $orderNumber : null;
    }

    public function sendPaymentOptions(string $phone, string $orderNumber)
    {
        return Http::withToken(config('services.whatsapp.token'))
            ->post(
                $this->graphUrl.'/'.config('services.whatsapp.phone_number_id').'/messages',
                [
                    'messaging_product' => 'whatsapp',
                    'to' => $phone,
                    'type' => 'interactive',
                    'interactive' => [
                        'type' => 'button',
                        'body' => [
                            'text' => 'من فضلك اختر طريقة الدفع:',
                        ],
                        'action' => [
                            'buttons' => [
                                [
                                    'type' => 'reply',
                                    'reply' => [
                                        'id' => 'cash:'.$orderNumber,
                                        'title' => 'الدفع عند الاستلام',
                                    ],
                                ],
                                [
                                    'type' => 'reply',
                                    'reply' => [
                                        'id' => 'online:'.$orderNumber,
                                        'title' => 'الدفع الإلكتروني',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            );
    }

    private function locationRequestCacheKey(string $messageId): string
    {
        return 'whatsapp:location-request:'.$messageId;
    }
}
