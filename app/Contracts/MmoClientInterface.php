<?php

namespace App\Contracts;

use App\Models\User;

interface MmoClientInterface
{
    public function createWallet(User $user): array;

    public function credit(string $mmoWalletId, float $amount, string $reference): array;

    public function debit(string $mmoWalletId, float $amount, string $reference): array;

    public function getBalance(string $mmoWalletId): float;

    /**
     * Initiate an outbound disbursement to a bank account.
     *
     * @return array{status:string, transaction_reference:string, external_reference:string, requires_otp:bool, otp_reference:string}
     */
    public function initiateDisbursement(
        string $destinationBankCode,
        string $destinationAccountNumber,
        string $destinationAccountName,
        float $amount,
        string $reference,
        string $narration = '',
    ): array;

    /**
     * Complete a disbursement that is pending OTP validation.
     *
     * @return array{status:string, transaction_reference:string, external_reference:string}
     */
    public function completeDisbursementOtp(string $otpReference, string $otp): array;

    public function verifyWebhookSignature(?string $signature, string $payload): bool;

    public function isAllowedWebhookIp(?string $ip): bool;

    /**
     * Resolve a bank account name by bank code and account number.
     */
    public function resolveBankAccountName(string $bankCode, string $accountNumber): string;
}
