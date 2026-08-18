<?php

namespace App\Services;

use App\Contracts\MmoClientInterface;
use App\Models\User;
use App\Models\Wallet;
use RuntimeException;

class MockMmoClient implements MmoClientInterface
{
    public function createWallet(User $user): array
    {
        $this->simulateLatency();
        $this->throwIfConfiguredToFail('create_wallet');

        return [
            'mmo_wallet_id' => 'MMO' . strtoupper(substr(str_replace('-', '', (string) $user->id), 0, 12)),
            'account_number' => '10' . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'wallet_account_number' => '10' . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'provider_reference' => 'PAYEASE-MOCK-' . strtoupper(substr(str_replace('-', '', (string) $user->id), 0, 8)),
            'status' => 'active',
        ];
    }

    public function credit(string $mmoWalletId, float $amount, string $reference): array
    {
        $this->simulateLatency();
        $this->throwIfConfiguredToFail('credit');

        return [
            'status' => 'success',
            'reference' => $reference,
            'amount' => $amount,
            'mmo_transaction_id' => 'MMO-CR-' . strtoupper(substr(md5($reference . $mmoWalletId), 0, 12)),
        ];
    }

    public function debit(string $mmoWalletId, float $amount, string $reference): array
    {
        $this->simulateLatency();
        $this->throwIfConfiguredToFail('debit');

        return [
            'status' => 'success',
            'reference' => $reference,
            'amount' => $amount,
            'mmo_transaction_id' => 'MMO-DB-' . strtoupper(substr(md5($reference . $mmoWalletId), 0, 12)),
        ];
    }

    public function getBalance(string $mmoWalletId): float
    {
        $this->simulateLatency();
        $this->throwIfConfiguredToFail('balance');

        return (float) (Wallet::where('mmo_wallet_id', $mmoWalletId)->value('available_balance') ?? 0);
    }

    public function initiateDisbursement(
        string $destinationBankCode,
        string $destinationAccountNumber,
        string $destinationAccountName,
        float $amount,
        string $reference,
        string $narration = '',
    ): array {
        $this->simulateLatency();
        $this->throwIfConfiguredToFail('disbursement');

        return [
            'status' => 'completed',
            'transaction_reference' => $reference,
            'external_reference' => 'MOCK-DISB-' . strtoupper(substr(md5($reference), 0, 10)),
            'requires_otp' => false,
            'otp_reference' => '',
        ];
    }

    public function completeDisbursementOtp(string $otpReference, string $otp): array
    {
        $this->simulateLatency();

        return [
            'status' => 'completed',
            'transaction_reference' => $otpReference,
            'external_reference' => 'MOCK-OTP-' . strtoupper(substr(md5($otpReference . $otp), 0, 10)),
        ];
    }

    public function verifyWebhookSignature(?string $signature, string $payload): bool
    {
        return true;
    }

    public function isAllowedWebhookIp(?string $ip): bool
    {
        return true;
    }

    public function resolveBankAccountName(string $bankCode, string $accountNumber): string
    {
        $this->simulateLatency();
        $this->throwIfConfiguredToFail('name_enquiry');

        return 'Mock Account Holder';
    }

    protected function simulateLatency(): void
    {
        $min = (int) config('services.mock_mmo.latency_min_ms', 300);
        $max = (int) config('services.mock_mmo.latency_max_ms', 600);

        if ($max <= 0) {
            return;
        }

        if ($min < 0) {
            $min = 0;
        }

        if ($max < $min) {
            $max = $min;
        }

        usleep(random_int($min, $max) * 1000);
    }

    protected function throwIfConfiguredToFail(string $operation): void
    {
        if ($this->shouldFail($operation)) {
            throw new RuntimeException("Mock MMO {$operation} request failed.");
        }
    }

    protected function shouldFail(string $operation): bool
    {
        $forced = $this->resolveForcedFailureMode();

        if (in_array($forced, ['all', $operation], true)) {
            return true;
        }

        $failureRate = (float) config('services.mock_mmo.failure_rate', 0);

        if ($failureRate <= 0) {
            return false;
        }

        return mt_rand(1, 10000) <= (int) round($failureRate * 100);
    }

    protected function resolveForcedFailureMode(): ?string
    {
        $request = request();

        if ($request && $request->hasSession()) {
            $sessionMode = $request->session()->get('mock_mmo_force_fail');
            if (is_string($sessionMode) && $sessionMode !== '') {
                return strtolower($sessionMode);
            }
        }

        if (app()->environment('local')) {
            $queryMode = $request?->query('mmo_fail');
            if (is_string($queryMode) && $queryMode !== '') {
                return strtolower($queryMode);
            }
        }

        $configMode = config('services.mock_mmo.force_fail');

        return is_string($configMode) && $configMode !== '' ? strtolower($configMode) : null;
    }
}
