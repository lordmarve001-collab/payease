<?php

namespace App\Services;

use App\Contracts\MmoClientInterface;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MonnifyWalletProvisioning
{
    public function __construct(
        protected MmoClientInterface $mmoClient,
    ) {
    }

    public function provisionReservedAccount(User $user): ?Wallet
    {
        $wallet = $user->wallets()->where('wallet_type', 'customer')->first();

        if ($wallet && $this->walletIsProvisioned($wallet)) {
            return $wallet;
        }

        if (!$this->userHasRequiredIdentity($user)) {
            return $this->ensurePendingWallet($user);
        }

        if (! $wallet) {
            throw new RuntimeException('No pending customer wallet found for user ' . $user->id . '. Tier 1 wallet must exist before provisioning.');
        }

        try {
            $result = $this->mmoClient->createWallet($user);

            return DB::transaction(function () use ($wallet, $result): Wallet {
                $wallet->update([
                    'mmo_wallet_id' => $result['mmo_wallet_id'],
                    'account_number' => $result['account_number'],
                    'wallet_account_number' => $result['wallet_account_number'] ?? $result['account_number'],
                    'provider_reference' => $result['provider_reference'] ?? '',
                    'status' => 'active',
                    'provider_metadata' => $result['provider_metadata'] ?? [],
                ]);

                return $wallet->fresh();
            });
        } catch (RuntimeException $exception) {
            if (str_contains($exception->getMessage(), 'same reference')) {
                Log::channel('monnify')->warning('Monnify account already exists, fetching existing', [
                    'user_id' => $user->id,
                ]);

                return $this->fetchExistingAccount($user, $wallet);
            }

            Log::channel('monnify')->error('Monnify reserved account creation failed', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'monnify_wallet_provisioning_failed',
                'entity_type' => 'wallet',
                'entity_id' => $wallet?->id,
                'old_values' => null,
                'new_values' => [
                    'error' => $exception->getMessage(),
                    'bvn_present' => filled($user->bvn),
                    'nin_present' => filled($user->nin),
                ],
                'ip_address' => null,
                'device_id' => null,
            ]);

            return $wallet;
        }
    }

    protected function fetchExistingAccount(User $user, ?Wallet $wallet): ?Wallet
    {
        $accountReference = 'PAYEASE-' . strtoupper($user->uuid);

        try {
            $details = $this->mmoClient->getReservedAccountDetails($accountReference);
            $accounts = $details['accounts'] ?? [];
            $primaryAccount = $accounts[0] ?? [];
            $accountNumber = $primaryAccount['accountNumber'] ?? '';
            $reservationRef = $details['reservationReference'] ?? '';

            if (empty($accountNumber)) {
                Log::channel('monnify')->error('No account number in Monnify details', [
                    'user_id' => $user->id,
                ]);
                return $wallet;
            }

            if (! $wallet) {
                throw new RuntimeException('No pending customer wallet found for user ' . $user->id . '. Tier 1 wallet must exist before fetching existing account.');
            }

            return DB::transaction(function () use ($wallet, $accountNumber, $reservationRef, $accountReference, $details): Wallet {
                $metadata = [
                    'reservation_reference' => $reservationRef,
                    'account_reference' => $accountReference,
                    'bank_name' => $details['accounts'][0]['bankName'] ?? '',
                ];

                $wallet->update([
                    'account_number' => $accountNumber,
                    'wallet_account_number' => $accountNumber,
                    'mmo_wallet_id' => $reservationRef,
                    'provider_reference' => $accountReference,
                    'status' => 'active',
                    'provider_metadata' => $metadata,
                ]);

                return $wallet->fresh();
            });
        } catch (RuntimeException $e) {
            Log::channel('monnify')->error('Failed to fetch existing Monnify account', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return $wallet;
        }
    }

    public function ensurePendingWallet(User $user): Wallet
    {
        return $user->wallets()->where('wallet_type', 'customer')->firstOrCreate(
            [
                'wallet_type' => 'customer',
            ],
            [
                'balance' => 0,
                'available_balance' => 0,
                'currency' => 'NGN',
                'status' => 'pending_kyc',
                'daily_limit' => 500000,
                'single_txn_limit' => 200000,
                'mmo_partner' => 'monnify',
            ]
        );
    }

    public function userHasRequiredIdentity(User $user): bool
    {
        return filled($user->bvn) || filled($user->nin);
    }

    protected function walletIsProvisioned(Wallet $wallet): bool
    {
        $status = strtolower((string) $wallet->status);

        if (in_array($status, ['pending_kyc', 'pending_provisioning'], true)) {
            return false;
        }

        return filled($wallet->account_number) && filled($wallet->mmo_wallet_id);
    }
}
