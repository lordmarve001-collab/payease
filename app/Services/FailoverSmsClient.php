<?php

namespace App\Services;

use App\Contracts\SmsClientInterface;
use Illuminate\Support\Facades\Log;

class FailoverSmsClient implements SmsClientInterface
{
    /**
     * @param array<int, SmsClientInterface> $clients
     */
    public function __construct(
        protected array $clients,
    ) {
    }

    public function send(string $phoneNumber, string $message): array
    {
        $lastError = null;

        foreach ($this->clients as $index => $client) {
            try {
                $result = $client->send($phoneNumber, $message);

                if ($result['status'] === 'sent') {
                    return $result;
                }

                $lastError = $result['error'] ?? 'Provider returned non-success status.';
                Log::channel('sms')->warning('SMS provider failed, trying failover', [
                    'provider_index' => $index,
                    'phone_number' => $phoneNumber,
                    'error' => $lastError,
                ]);
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                Log::channel('sms')->warning('SMS provider threw exception, trying failover', [
                    'provider_index' => $index,
                    'phone_number' => $phoneNumber,
                    'error' => $lastError,
                ]);
            }
        }

        return [
            'status' => 'failed',
            'provider_id' => null,
            'error' => $lastError ?? 'All SMS providers failed.',
        ];
    }
}
