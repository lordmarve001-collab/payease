<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class UssdSessionStore
{
    protected const TTL_SECONDS = 300; // 5 minutes

    public function get(string $sessionId, string $default = null): ?string
    {
        return Cache::get($this->key($sessionId), $default);
    }

    public function set(string $sessionId, string $value): void
    {
        Cache::put($this->key($sessionId), $value, self::TTL_SECONDS);
    }

    public function forget(string $sessionId): void
    {
        Cache::forget($this->key($sessionId));
    }

    public function exists(string $sessionId): bool
    {
        return Cache::has($this->key($sessionId));
    }

    public function incrementRetry(string $sessionId, int $maxAttempts = 3): int
    {
        $key = $this->retryKey($sessionId);
        $count = (int) Cache::get($key, 0) + 1;
        Cache::put($key, $count, self::TTL_SECONDS);
        return $count;
    }

    public function getRetryCount(string $sessionId): int
    {
        return (int) Cache::get($this->retryKey($sessionId), 0);
    }

    public function clearRetry(string $sessionId): void
    {
        Cache::forget($this->retryKey($sessionId));
    }

    public function isLocked(string $sessionId, int $maxAttempts = 3): bool
    {
        return $this->getRetryCount($sessionId) >= $maxAttempts;
    }

    public function setTempData(string $sessionId, array $data): void
    {
        Cache::put($this->tempKey($sessionId), $data, self::TTL_SECONDS);
    }

    public function getTempData(string $sessionId, array $default = []): array
    {
        return Cache::get($this->tempKey($sessionId), $default);
    }

    public function clearAll(string $sessionId): void
    {
        Cache::forget($this->key($sessionId));
        Cache::forget($this->retryKey($sessionId));
        Cache::forget($this->tempKey($sessionId));
    }

    protected function key(string $sessionId): string
    {
        return 'ussd:state:' . $sessionId;
    }

    protected function retryKey(string $sessionId): string
    {
        return 'ussd:retry:' . $sessionId;
    }

    protected function tempKey(string $sessionId): string
    {
        return 'ussd:temp:' . $sessionId;
    }
}
