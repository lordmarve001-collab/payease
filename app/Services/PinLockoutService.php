<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class PinLockoutService
{
    public function isLocked(string $type, int $userId): bool
    {
        $lockKey = $this->lockKey($type, $userId);
        $lockedAt = Cache::get($lockKey);

        return $lockedAt && $lockedAt > now()->timestamp;
    }

    public function recordFailedAttempt(string $type, User $user): array
    {
        $maxAttempts = (int) config('lockout.pin.max_attempts', 5);
        $lockoutDuration = (int) config('lockout.pin.lockout_duration', 900);
        $attemptKey = $this->attemptKey($type, $user->id);
        $lockKey = $this->lockKey($type, $user->id);

        $attempts = Cache::increment($attemptKey, 1);
        Cache::put($attemptKey, $attempts, $lockoutDuration);

        if ($attempts >= $maxAttempts) {
            Cache::put($lockKey, now()->addSeconds($lockoutDuration)->timestamp, $lockoutDuration);

            return [
                'locked' => true,
                'message' => 'Too many failed attempts. Your account has been temporarily locked.',
                'attempts' => $attempts,
                'remaining' => 0,
            ];
        }

        $remaining = max(0, $maxAttempts - $attempts);

        return [
            'locked' => false,
            'message' => "Incorrect PIN. {$remaining} attempt(s) remaining.",
            'attempts' => $attempts,
            'remaining' => $remaining,
        ];
    }

    public function clearAttempts(string $type, int $userId): void
    {
        Cache::forget($this->attemptKey($type, $userId));
        Cache::forget($this->lockKey($type, $userId));
    }

    public function getRemainingLockoutSeconds(string $type, int $userId): int
    {
        $lockKey = $this->lockKey($type, $userId);
        $lockedAt = (int) Cache::get($lockKey, 0);

        if ($lockedAt === 0) {
            return 0;
        }

        return max(0, $lockedAt - now()->timestamp);
    }

    protected function attemptKey(string $type, int $userId): string
    {
        return "pin_attempts_{$type}_{$userId}";
    }

    protected function lockKey(string $type, int $userId): string
    {
        return "pin_lock_{$type}_{$userId}";
    }
}
