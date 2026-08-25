<?php

namespace App\Services;

use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Facades\Cache;

class TwoFactorService
{
    protected Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    public function generateSecretKey(): string
    {
        return $this->google2fa->generateSecretKey(32);
    }

    public function getQRCodeUrl(string $secretKey, string $email, string $issuer = 'PayEase'): string
    {
        return $this->google2fa->getQRCodeUrl(
            $secretKey,
            $email,
            $issuer,
            200,
            200
        );
    }

    public function verifyKey(string $secretKey, string $code): bool
    {
        return $this->google2fa->verifyKey($secretKey, $code, 1);
    }

    public function getRecoveryCodes(int $count = 8): array
    {
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4)));
        }

        return $codes;
    }
}
