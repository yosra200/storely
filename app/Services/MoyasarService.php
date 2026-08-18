<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MoyasarService
{
    public function createInvoice(float $amount, string $description, array $metadata = []): array
    {
        try {
            $response = $this->client()->post($this->baseUrl() . '/invoices', array_filter([
                'amount' => $this->toMinorUnit($amount),
                'currency' => config('services.moyasar.currency', 'SAR'),
                'description' => $description,
                'callback_url' => $this->callbackUrl(),
                'success_url' => config('services.moyasar.success_url'),
                'back_url' => config('services.moyasar.back_url'),
                'metadata' => $metadata,
            ], fn($value) => ! blank($value)));
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Unable to connect to payment gateway.', previous: $exception);
        }

        if ($response->failed()) {
            throw new RuntimeException($response->json('message') ?? 'Unable to create payment invoice.');
        }

        return $response->json();
    }

    public function getInvoice(string $invoiceId): array
    {
        try {
            $response = $this->client()->get($this->baseUrl() . "/invoices/{$invoiceId}");
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Unable to connect to payment gateway.', previous: $exception);
        }

        if ($response->failed()) {
            throw new RuntimeException($response->json('message') ?? 'Unable to fetch payment status.');
        }

        return $response->json();
    }

    public function getPayment(string $paymentId): array
    {
        try {
            $response = $this->client()->get($this->baseUrl() . "/payments/{$paymentId}");
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Unable to connect to payment gateway.', previous: $exception);
        }

        if ($response->failed()) {
            throw new RuntimeException($response->json('message') ?? 'Unable to fetch payment status.');
        }

        return $response->json();
    }

    public function isPaid(array $invoice): bool
    {
        return ($invoice['status'] ?? null) === 'paid';
    }

    private function client(): PendingRequest
    {
        $secretKey = config('services.moyasar.secret_key');

        if (blank($secretKey)) {
            throw new RuntimeException('Moyasar credentials are not configured.');
        }

        return Http::asJson()->withBasicAuth($secretKey, '');
    }

    private function baseUrl(): string
    {
        return rtrim(config('services.moyasar.base_url', 'https://api.moyasar.com/v1'), '/');
    }

    private function callbackUrl(): string
    {
        return config('services.moyasar.callback_url') ?: route('moyasar.invoice.callback');
    }

    private function toMinorUnit(float $amount): int
    {
        return (int) round($amount * 100);
    }

    public function refundPayment(string $paymentId, $total)
    {
        return Http::withBasicAuth(
            config('services.moyasar.secret_key'),
            ''
        )->post(
            $this->baseUrl() . "/payments/{$paymentId}/refund",
            [
                'amount' => $this->toMinorUnit((float) $total),
            ]
        );
    }
}
