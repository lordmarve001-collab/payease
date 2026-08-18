<?php

namespace App\Services;

use App\Jobs\SendSmsNotification;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class OtpService
{
    public function sendOtp(User $user, bool $enforceCooldown = true): string
    {
        $phoneNumber = trim((string) $user->phone_number);
        if ($phoneNumber === '') {
            throw new RuntimeException('This account does not have a valid phone number for OTP delivery.');
        }

        if ($enforceCooldown) {
            $cooldown = $this->getCooldownSeconds($user);
            if ($cooldown > 0) {
                throw new RuntimeException('Please wait ' . $cooldown . ' seconds before requesting another OTP.');
            }
        }

        $attempts = $this->getRecentAttempts($phoneNumber);
        if (count($attempts) >= 3) {
            throw new RuntimeException('Too many OTP requests for this number. Please try again in a few minutes.');
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Cache::put($this->otpKey($user), $otp, now()->addMinutes(5));
        Cache::put($this->lastSentKey($user), now()->timestamp, now()->addMinutes(10));
        Cache::put($this->attemptsKey($phoneNumber), array_merge($attempts, [now()->timestamp]), now()->addMinutes(10));

        try {
            SendSmsNotification::dispatch(
                $phoneNumber,
                'Your PayEase OTP is ' . $otp . '. It expires in 5 minutes. -PayEase'
            );
        } catch (\Throwable $e) {
            Cache::forget($this->otpKey($user));
            throw new RuntimeException('Failed to send OTP. Please try again.');
        }

        return $otp;
    }

    public function getCooldownSeconds(User $user): int
    {
        $lastSentAt = (int) Cache::get($this->lastSentKey($user), 0);
        if ($lastSentAt === 0) {
            return 0;
        }

        return max(0, 60 - (now()->timestamp - $lastSentAt));
    }

    public function verifyOtp(User $user, string $otp): bool
    {
        $storedOtp = (string) Cache::get($this->otpKey($user), '');

        return $storedOtp !== '' && hash_equals($storedOtp, trim($otp));
    }

    public function clearOtp(User $user): void
    {
        Cache::forget($this->otpKey($user));
    }

    protected function getRecentAttempts(string $phoneNumber): array
    {
        $stored = Cache::get($this->attemptsKey($phoneNumber), []);
        $windowStart = now()->subMinutes(10)->timestamp;

        return array_values(array_filter((array) $stored, fn ($timestamp): bool => (int) $timestamp >= $windowStart));
    }

    protected function otpKey(User $user): string
    {
        return 'otp_' . $user->id;
    }

    protected function lastSentKey(User $user): string
    {
        return 'otp_last_sent_at_' . $user->id;
    }

    protected function attemptsKey(string $phoneNumber): string
    {
        return 'otp_attempts_' . preg_replace('/\D+/', '', $phoneNumber);
    }
}
