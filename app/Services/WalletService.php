<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Str;

class WalletService
{
    public function getCustomerWallet(User $user): ?Wallet
    {
        return $user->wallets()->where('wallet_type', 'customer')->first();
    }

    public function getAgentWallet(User $user): ?Wallet
    {
        return $user->wallets()->where('wallet_type', 'agent')->first();
    }

    public function getBalance(User $user): float
    {
        return (float) ($this->getCustomerWallet($user)?->available_balance ?? 0);
    }

    public function createTierWallet(User $user, int $tier = 1): Wallet
    {
        $existing = $user->wallets()->where('wallet_type', 'customer')->first();
        if ($existing) {
            return $existing;
        }

        $limits = config("tiers.tiers.{$tier}", [
            'daily_limit' => 5000,
            'single_txn_limit' => 2000,
        ]);

        return Wallet::create([
            'user_id' => $user->id,
            'wallet_type' => 'customer',
            'balance' => 0,
            'available_balance' => 0,
            'currency' => 'NGN',
            'status' => 'pending_kyc',
            'daily_limit' => $limits['daily_limit'],
            'single_txn_limit' => $limits['single_txn_limit'],
            'mmo_partner' => 'monnify',
            'mmo_wallet_id' => 'PENDING-' . Str::upper(Str::random(10)),
        ]);
    }

    public function checkLimits(Wallet $wallet, float $amount): bool
    {
        return $this->getLimitViolation($wallet, $amount) === null;
    }

    public function getLimitViolation(Wallet $wallet, float $amount): ?string
    {
        if ($amount > (float) $wallet->single_txn_limit) {
            return 'Amount exceeds your single transaction limit.';
        }

        $todaysTotal = (float) $wallet->fromTransactions()
            ->where('status', 'completed')
            ->whereDate('created_at', today())
            ->sum('amount');

        if (($todaysTotal + $amount) > (float) $wallet->daily_limit) {
            return 'Amount exceeds your remaining daily transfer limit.';
        }

        return null;
    }

    public function applyTierLimits(Wallet $wallet, int $tier): void
    {
        $limits = config("tiers.tiers.{$tier}", [
            'daily_limit' => 5000,
            'single_txn_limit' => 2000,
        ]);

        $wallet->update([
            'daily_limit' => $limits['daily_limit'],
            'single_txn_limit' => $limits['single_txn_limit'],
        ]);
    }

    public function getKycUpgradeMessage(int $currentLevel): ?string
    {
        return match ($currentLevel) {
            0 => 'Your account is not yet verified. Complete your KYC to unlock your wallet account number and transaction limits.',
            1 => "You're on Tier 1 — verify your identity to unlock your full account number and higher limits.",
            2 => 'You\'re on Tier 2 — submit proof of address to unlock the highest limits and full platform access.',
            default => null,
        };
    }

    public function getAccountDisplay(Wallet $wallet): array
    {
        $accountNumber = (string) ($wallet->wallet_account_number ?: $wallet->account_number ?: '');
        $digitsOnly = preg_replace('/\D+/', '', $accountNumber) ?? '';
        $status = strtolower((string) $wallet->status);
        $isPending = in_array($status, ['pending_kyc', 'pending_provisioning'], true) || $accountNumber === '';

        return [
            'account_number' => $accountNumber,
            'formatted_account_number' => $digitsOnly !== '' ? trim(chunk_split($digitsOnly, 4, ' ')) : $accountNumber,
            'wallet_type' => $wallet->wallet_type,
            'partner' => strtoupper((string) $wallet->mmo_partner),
            'provider_reference' => $wallet->provider_reference,
            'status' => $wallet->status,
            'is_pending' => $isPending,
            'is_copyable' => !$isPending && $accountNumber !== '',
            'headline' => $isPending ? 'Account Number Pending' : 'Account Number',
            'message' => match ($status) {
                'pending_kyc' => 'Complete verification to activate your account number.',
                'pending_provisioning' => 'We are still activating your Monnify account number.',
                default => $accountNumber === '' ? 'Your account number will appear here once provisioning completes.' : null,
            },
            'currency' => $wallet->currency,
        ];
    }
}
