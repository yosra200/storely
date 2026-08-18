<?php

namespace App\Services\Facebook;

use Illuminate\Support\Facades\Http;

class WhatsAppService
{
    protected string $graphUrl = 'https://graph.facebook.com/v26.0';

    public function sendMessage(string $phone, string $message)
    {
        return Http::withToken(config('services.whatsapp.token'))
            ->post(
                $this->graphUrl . '/' . config('services.whatsapp.phone_number_id') . '/messages',
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

    public function sendPaymentOptions(string $phone)
    {
        return Http::withToken(config('services.whatsapp.token'))
            ->post(
                $this->graphUrl . '/' . config('services.whatsapp.phone_number_id') . '/messages',
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
                                        'id' => 'cash',
                                        'title' => 'الدفع عند الاستلام',
                                    ],
                                ],
                                [
                                    'type' => 'reply',
                                    'reply' => [
                                        'id' => 'online',
                                        'title' => 'الدفع الإلكتروني',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            );
    }
}
