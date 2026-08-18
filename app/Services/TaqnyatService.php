<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TaqnyatService
{
    protected $token;
    protected $sender;

    public function __construct()
    {
        $this->token = config('services.taqnyat.token');
        $this->sender = config('services.taqnyat.sender');
    }

    public function sendSMS($phone, $message)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Content-Type' => 'application/json',
        ])->post('https://api.taqnyat.sa/v1/messages', [
            "recipients" => [$phone],
            "body" => $message,
            "sender" => $this->sender,
        ]);

        if ($response->failed()) {
            \Log::error('Taqnyat Error', $response->json());
        }

        return $response->json();
    }
}
