<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Bevatel WhatsApp client (Developer API).
 *
 * Sends approved WhatsApp templates via:
 *   POST {base_url}/developer/api/v1/messages
 *
 * NOTE: this is the Developer API, NOT the old Chatwoot conversation API
 * (/api/v1/accounts/.../messages) which only stores the message in the inbox
 * and never delivers a real WhatsApp message.
 */
class BevatelWhatsApp
{
    /**
     * Send an approved template. Returns true on success, false on any failure.
     * Never throws: WhatsApp delivery must never break the calling job.
     *
     * @param  array<int, string|int|null>  $params  Values for {{1}}, {{2}}, ... in order.
     */
    public function sendTemplate(?string $to, string $template, array $params = [], string $language = 'ar'): bool
    {
        if (! config('bevatel.enabled', true)) {
            return false;
        }

        $phone = $this->toE164($to);
        if (! $phone) {
            Log::warning('Bevatel: invalid phone, skipped', ['to' => $to, 'template' => $template]);
            return false;
        }

        $accountId = config('bevatel.account_id');
        $inboxId = config('bevatel.inbox_id');
        $token = config('bevatel.api_token');
        $baseUrl = rtrim((string) config('bevatel.base_url'), '/');

        if (! $accountId || ! $inboxId || ! $token) {
            Log::warning('Bevatel: env vars missing, skipped', ['template' => $template]);
            return false;
        }

        $payload = [
            'inbox_id' => (int) $inboxId,
            'contact' => ['phone_number' => $phone],
            'message' => [
                'template' => [
                    'name' => $template,
                    'language' => $language,
                    'parameters' => [
                        'body' => array_map(static fn ($v) => (string) ($v ?? ''), array_values($params)),
                    ],
                ],
            ],
        ];

        try {
            $response = Http::withHeaders([
                'api_account_id' => (string) $accountId,
                'api_access_token' => (string) $token,
            ])->acceptJson()->post("{$baseUrl}/developer/api/v1/messages", $payload);

            if ($response->failed()) {
                Log::error('Bevatel send failed', [
                    'template' => $template,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            // A 2xx body can still report a delivery failure for the WhatsApp number.
            if (data_get($response->json(), 'data.status') === 'failed') {
                Log::error('Bevatel delivery failed', [
                    'template' => $template,
                    'error' => data_get($response->json(), 'data.content_attributes.external_error'),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Bevatel exception', ['template' => $template, 'message' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Normalise a raw phone number to E.164 (e.g. 0512345678 -> +966512345678).
     * Returns null when the number is empty or too short to be valid.
     */
    public function toE164(?string $raw): ?string
    {
        if (! $raw) {
            return null;
        }

        $n = preg_replace('/[^\d+]/', '', $raw);
        $n = ltrim($n, '+');

        if (str_starts_with($n, '00')) {
            $n = substr($n, 2);
        }

        $cc = (string) config('bevatel.default_country_code', '966');

        if (str_starts_with($n, '0')) {
            // Local format with leading zero (e.g. 0544113161).
            $n = $cc . substr($n, 1);
        } elseif (! str_starts_with($n, $cc)) {
            // Local format without leading zero (e.g. 544113161) -> add country code.
            $n = $cc . $n;
        }

        if (strlen($n) < 8) {
            return null;
        }

        return '+' . $n;
    }
}
