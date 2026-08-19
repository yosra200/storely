<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Exception;

class MyFatoorahService
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('myfatoorah.base_url');
        $this->apiKey = config('myfatoorah.api_key');
    }

    public function createPayment(Order $order): array
    {
        $response = Http::withToken($this->apiKey)
            ->acceptJson()
            ->post($this->baseUrl . '/v2/ExecutePayment', [
                'InvoiceValue' => $order->total,

                // لازم يكون PaymentMethodId متوافق
                // مع طريقة الدفع الموجودة في حسابك
                'PaymentMethodId' => config('myfatoorah.payment_method_id'),

                'CustomerName' => $order->customer_name,

                'CustomerMobile' => $this->formatPhone(
                    $order->phone
                ),

                'DisplayCurrencyIso' => 'EGP',

                'Language' => 'AR',

                'CustomerReference' => (string) $order->id,

                'CallBackUrl' => config(
                    'myfatoorah.callback_url'
                ),

                'ErrorUrl' => config(
                    'myfatoorah.error_url'
                ),

                'WebhookUrl' => config(
                    'myfatoorah.webhook_url'
                ),
            ]);

        if ($response->failed()) {
            throw new Exception(
                'MyFatoorah Error: ' . $response->body()
            );
        }

        $data = $response->json();

        if (
            !isset($data['Data']['PaymentURL']) &&
            !isset($data['Data']['InvoiceURL'])
        ) {
            throw new Exception(
                'MyFatoorah did not return payment URL.'
            );
        }

        $paymentUrl =
            $data['Data']['PaymentURL']
            ?? $data['Data']['InvoiceURL'];

        $invoiceId =
            $data['Data']['InvoiceId']
            ?? null;

        $paymentId =
            $data['Data']['PaymentId']
            ?? null;

        Payment::create([
            'order_id' => $order->id,
            'gateway' => 'myfatoorah',
            'invoice_id' => $invoiceId,
            'payment_id' => $paymentId,
            'payment_url' => $paymentUrl,
            'amount' => $order->total,
            'status' => 'pending',
            'response' => $data,
        ]);

        return [
            'payment_url' => $paymentUrl,
            'invoice_id' => $invoiceId,
            'payment_id' => $paymentId,
        ];
    }

    private function formatPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = '20' . substr($phone, 1);
        }

        return $phone;
    }
}
