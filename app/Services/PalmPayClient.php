<?php

namespace App\Services;

use App\Contracts\MmoClientInterface;
use App\Exceptions\NotImplementedException;
use App\Models\User;

class PalmPayClient implements MmoClientInterface
{
    public function __construct(
        protected array $credentials = [],
        protected string $environment = 'sandbox',
    ) {
    }

    public function createWallet(User $user): array
    {
        throw new NotImplementedException('PalmPay integration pending - awaiting confirmed API documentation');
    }

    public function credit(string $mmoWalletId, float $amount, string $reference): array
    {
        throw new NotImplementedException('PalmPay integration pending - awaiting confirmed API documentation');
    }

    public function debit(string $mmoWalletId, float $amount, string $reference): array
    {
        throw new NotImplementedException('PalmPay integration pending - awaiting confirmed API documentation');
    }

    public function getBalance(string $mmoWalletId): float
    {
        throw new NotImplementedException('PalmPay integration pending - awaiting confirmed API documentation');
    }

    public function initiateDisbursement(
        string $destinationBankCode,
        string $destinationAccountNumber,
        string $destinationAccountName,
        float $amount,
        string $reference,
        string $narration = '',
    ): array {
        throw new NotImplementedException('PalmPay integration pending - awaiting confirmed API documentation');
    }

    public function completeDisbursementOtp(string $otpReference, string $otp): array
    {
        throw new NotImplementedException('PalmPay integration pending - awaiting confirmed API documentation');
    }

    public function verifyWebhookSignature(?string $signature, string $payload): bool
    {
        return false;
    }

    public function isAllowedWebhookIp(?string $ip): bool
    {
        return false;
    }

    public function resolveBankAccountName(string $bankCode, string $accountNumber): string
    {
        throw new NotImplementedException('PalmPay integration pending - awaiting confirmed API documentation');
    }
}
