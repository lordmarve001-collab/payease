<?php

namespace App\Services;

use App\Jobs\SendSmsNotification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KycCompletionService
{
    public function __construct(
        protected MonnifyWalletProvisioning $monnifyProvisioning,
        protected WalletService $walletService,
    ) {
    }

    public function tier2RequirementsMet(User $user): bool
    {
        return filled($user->nin_verified_at)
            && filled($user->bvn_verified_at)
            && filled($user->next_of_kin_submitted_at);
    }

    public function tryCompleteTier2(User $user): bool
    {
        if ((int) $user->kyc_level >= 2) {
            return false;
        }

        if (! $this->tier2RequirementsMet($user)) {
            return false;
        }

        return DB::transaction(function () use ($user): bool {
            $freshUser = User::lockForUpdate()->find($user->id);

            if ((int) $freshUser->kyc_level >= 2) {
                return false;
            }

            if (! $this->tier2RequirementsMet($freshUser)) {
                return false;
            }

            $freshUser->update([
                'kyc_level' => 2,
                'kyc_verified_at' => now(),
            ]);

            $wallet = $freshUser->wallets()->where('wallet_type', 'customer')->first();
            if ($wallet) {
                $this->walletService->applyTierLimits($wallet, 2);
            }

            $this->monnifyProvisioning->provisionReservedAccount($freshUser);

            return true;
        });
    }

    public function dispatchTier2SuccessNotification(User $user): void
    {
        rescue(function () use ($user): void {
            SendSmsNotification::dispatch(
                $user->phone_number,
                "Your identity has been verified! Your account is now Tier 2. You can send up to ₦" . number_format(50000, 0) . " daily. -PayEase"
            );
        }, report: false);
    }

    public function recordNinVerification(User $user, array $result): void
    {
        if (! ($result['verified'] ?? false)) {
            return;
        }

        $user->update([
            'nin_verified_at' => now(),
        ]);

        $this->upsertVerificationDocument($user, 'nin', $result);
    }

    public function recordBvnVerification(User $user, array $result): void
    {
        if (! ($result['verified'] ?? false)) {
            return;
        }

        $user->update([
            'bvn_verified_at' => now(),
        ]);

        $this->upsertVerificationDocument($user, 'bvn', $result);
    }

    public function recordNextOfKinSubmission(User $user): void
    {
        $user->update([
            'next_of_kin_submitted_at' => now(),
        ]);
    }

    public function approveDocumentAsNinVerification(User $user, string $providerReference = ''): void
    {
        $user->update([
            'nin_verified_at' => now(),
        ]);

        $this->upsertVerificationDocument($user, 'nin', [
            'verified' => true,
            'provider_reference' => $providerReference,
            'match_confidence' => null,
            'raw_response' => [],
        ]);
    }

    public function approveDocumentAsBvnVerification(User $user, string $providerReference = ''): void
    {
        $user->update([
            'bvn_verified_at' => now(),
        ]);

        $this->upsertVerificationDocument($user, 'bvn', [
            'verified' => true,
            'provider_reference' => $providerReference,
            'match_confidence' => null,
            'raw_response' => [],
        ]);
    }

    protected function upsertVerificationDocument(User $user, string $documentType, array $result): void
    {
        try {
            \App\Models\KycDocument::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'document_type' => $documentType,
                ],
                [
                    'verification_status' => 'verified',
                    'verified_at' => now(),
                    'verification_provider' => 'youverify',
                    'verification_reference' => $result['provider_reference'] ?? '',
                    'match_confidence' => $result['match_confidence'] ?? null,
                    'auto_verified' => $result['verified'] ?? false,
                    'verification_raw_response' => $result['raw_response'] ?? [],
                ]
            );
        } catch (\Throwable $e) {
            Log::error('Failed to upsert verification document', [
                'user_id' => $user->id,
                'document_type' => $documentType,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
