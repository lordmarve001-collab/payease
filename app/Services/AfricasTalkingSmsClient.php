<?php

namespace App\Services;

use App\Contracts\SmsClientInterface;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AfricasTalkingSmsClient implements SmsClientInterface
{
    public function __construct(
        protected string $username,
        protected string $apiKey,
        protected string $senderId,
        protected string $environment = 'production',
    ) {
    }

    public function send(string $phoneNumber, string $message): array
    {
        if (blank($this->username) || blank($this->apiKey)) {
            throw new RuntimeException('Africa\'s Talking SMS credentials are not configured.');
        }

        $baseUrl = $this->environment === 'production'
            ? 'https://api.africastalking.com'
            : 'https://api.sandbox.africastalking.com';

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'apiKey' => $this->apiKey,
        ])->asForm()->post("{$baseUrl}/version1/messaging", [
            'username' => $this->username,
            'to' => $phoneNumber,
            'message' => $message,
            'from' => $this->senderId,
        ]);

        if ($response->failed()) {
            return [
                'status' => 'failed',
                'provider_id' => null,
                'error' => $response->body(),
            ];
        }

        $data = $response->json();
        $entries = $data['SMSMessageData']['Recipients'] ?? [];
        $first = $entries[0] ?? null;

        if (!empty($first) && ($first['status'] ?? '') === 'Success') {
            return [
                'status' => 'sent',
                'provider_id' => (string) ($first['messageId'] ?? ''),
            ];
        }

        return [
            'status' => 'failed',
            'provider_id' => null,
            'error' => $first['status'] ?? 'Unknown Africa\'s Talking error.',
        ];
    }
}
