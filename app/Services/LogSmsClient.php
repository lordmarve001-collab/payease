<?php

namespace App\Services;

use App\Contracts\SmsClientInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LogSmsClient implements SmsClientInterface
{
    public function send(string $phoneNumber, string $message): array
    {
        $providerId = 'log-' . Str::uuid();

        Log::info('SMS notification sent', [
            'driver' => 'log',
            'phone_number' => $phoneNumber,
            'message' => $message,
            'provider_id' => $providerId,
        ]);

        return [
            'status' => 'sent',
            'provider_id' => $providerId,
        ];
    }
}
