<?php

namespace App\Jobs;

use App\Contracts\SmsClientInterface;
use App\Models\AuditLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SendSmsNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [60, 180];

    public function __construct(
        public string $phoneNumber,
        public string $message,
    ) {
    }

    public function handle(SmsClientInterface $smsClient): void
    {
        $bare = ltrim($this->phoneNumber, '0');
        $phoneWithPrefix = str_starts_with($bare, '+') ? $bare : '+234' . $bare;
        $result = $smsClient->send($phoneWithPrefix, $this->message);

        if (($result['status'] ?? 'failed') !== 'sent') {
            Log::warning('SMS notification delivery attempt failed', [
                'phone_number' => $this->phoneNumber,
                'message' => $this->message,
                'provider_id' => $result['provider_id'] ?? null,
                'error' => $result['error'] ?? 'Unknown SMS delivery failure.',
            ]);

            throw new RuntimeException((string) ($result['error'] ?? 'SMS delivery failed.'));
        }
    }

    public function failed(?Throwable $exception): void
    {
        AuditLog::create([
            'user_id' => null,
            'action' => 'sms_delivery_failed',
            'entity_type' => 'sms_notification',
            'entity_id' => null,
            'old_values' => null,
            'new_values' => [
                'phone_number' => $this->phoneNumber,
                'message' => $this->message,
                'error' => $exception?->getMessage(),
            ],
            'ip_address' => null,
            'device_id' => null,
        ]);
    }
}
