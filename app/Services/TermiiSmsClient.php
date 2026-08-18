<?php

namespace App\Services;

use App\Contracts\SmsClientInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class TermiiSmsClient implements SmsClientInterface
{
    public function send(string $phoneNumber, string $message): array
    {
        try {
            $baseUrl = rtrim((string) config('services.termii.base_url', 'https://v3.api.termii.com'), '/');
            $host = (string) parse_url($baseUrl, PHP_URL_HOST);
            $curlOptions = [
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            ];

            $forcedIp = trim((string) config('services.termii.force_ip', ''));

            if ($forcedIp !== '' && $host !== '') {
                $curlOptions[CURLOPT_RESOLVE] = ["{$host}:443:{$forcedIp}"];
            }

            $response = Http::timeout(15)
                ->connectTimeout(10)
                ->withOptions([
                    'curl' => $curlOptions,
                ])
                ->post($baseUrl . '/api/sms/send', [
                'api_key' => config('services.termii.api_key'),
                'to' => $this->normalizePhoneNumber($phoneNumber),
                'from' => config('services.termii.sender_id'),
                'sms' => $message,
                'type' => 'plain',
                'channel' => 'generic',
                ]);

            if (!$response->successful()) {
                return [
                    'status' => 'failed',
                    'provider_id' => null,
                    'error' => 'HTTP ' . $response->status() . ': ' . Str::limit($response->body(), 500),
                ];
            }

            $payload = $response->json();
            $providerId = $payload['message_id'] ?? $payload['message_id_str'] ?? $payload['code'] ?? null;
            $status = strtolower((string) ($payload['message'] ?? $payload['status'] ?? ''));
            $wasSuccessful = !in_array($status, ['failed', 'error'], true);

            return [
                'status' => $wasSuccessful ? 'sent' : 'failed',
                'provider_id' => $providerId,
                'error' => $wasSuccessful ? null : ($payload['message'] ?? 'Termii rejected the SMS request.'),
            ];
        } catch (Throwable $throwable) {
            return [
                'status' => 'failed',
                'provider_id' => null,
                'error' => $throwable->getMessage(),
            ];
        }
    }

    protected function normalizePhoneNumber(string $phoneNumber): string
    {
        $digits = preg_replace('/\D+/', '', $phoneNumber) ?? '';

        if (str_starts_with($digits, '0')) {
            return '234' . substr($digits, 1);
        }

        if (str_starts_with($digits, '234')) {
            return $digits;
        }

        return $digits;
    }
}
