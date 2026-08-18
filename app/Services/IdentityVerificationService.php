<?php

namespace App\Services;

use App\Contracts\IdentityVerificationClientInterface;
use App\Jobs\SendSmsNotification;
use App\Models\KycDocument;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class IdentityVerificationService
{
    public const FACE_MATCH_THRESHOLD = 85.0;

    public function __construct(
        protected IdentityVerificationSettingsService $settingsService,
        protected MonnifyWalletProvisioning $monnifyProvisioning,
        protected WalletService $walletService,
        protected KycCompletionService $kycCompletion,
    ) {
    }

    public function verifyTier2Submission(User $user, string $nin, string $bvn, ?string $selfieImageBase64 = null, bool $consent = false): array
    {
        if (!$consent) {
            throw new RuntimeException('You must consent to identity verification before proceeding.');
        }

        $user->update(['identity_verification_consent_at' => now()]);

        $settings = $this->settingsService->getSettings();
        if (($settings['kyc_auto_verify'] ?? false) === false) {
            $this->applyDocumentStatus($user, 'pending');
            return [
                'auto_approved' => false,
                'status' => 'manual_review',
                'message' => 'Your documents have been submitted for manual review.',
            ];
        }

        $youverifyClient = $this->settingsService->makeYouverifyClient();
        $premblyClient = $this->settingsService->makePremblyClient();

        $ninResult = $this->safeVerify(fn () => $youverifyClient->verifyNin($nin, $user->full_name, true));
        $ninVerified = ($ninResult['verified'] ?? false);
        $ninProviderRef = $ninResult['provider_reference'] ?? '';

        $faceMatchResult = null;
        $bvnResult = null;
        $hasLiveness = $selfieImageBase64 !== null && $selfieImageBase64 !== '';

        if ($hasLiveness) {
            $faceMatchResult = $this->safeVerify(fn () => $premblyClient->verifyBvnFaceMatch($bvn, $selfieImageBase64, true));
        } else {
            $bvnResult = $this->safeVerify(fn () => $youverifyClient->verifyBvn($bvn, $user->full_name, true));
        }

        $faceMatchConfidence = $faceMatchResult['match_confidence'] ?? null;
        $faceMatchVerified = $faceMatchResult['verified'] ?? false;
        $bvnVerified = $bvnResult['verified'] ?? false;
        $bvnProviderRef = $bvnResult['provider_reference'] ?? '';

        $livenessDoc = KycDocument::where('user_id', $user->id)
            ->where('document_type', 'liveness_capture')
            ->latest()
            ->first();

        $ninDoc = KycDocument::where('user_id', $user->id)
            ->where('document_type', 'nin_slip')
            ->latest()
            ->first();

        $bvnDoc = KycDocument::where('user_id', $user->id)
            ->where('document_type', 'bvn_slip')
            ->latest()
            ->first();

        $ninVerificationFailed = !empty($ninResult['error']);
        $faceOrBvnFailed = $hasLiveness ? !empty($faceMatchResult['error']) : !empty($bvnResult['error']);

        if ($ninDoc) {
            $ninDoc->update([
                'verification_provider' => 'youverify',
                'verification_reference' => $ninProviderRef,
                'match_confidence' => $ninResult['match_confidence'] ?? null,
                'auto_verified' => $ninVerified,
                'verification_raw_response' => $ninResult['raw_response'] ?? [],
            ]);
        }

        if ($hasLiveness && $livenessDoc) {
            $livenessDoc->update([
                'verification_provider' => 'prembly',
                'verification_reference' => $faceMatchResult['provider_reference'] ?? '',
                'match_confidence' => $faceMatchConfidence,
                'auto_verified' => $faceMatchVerified,
                'verification_raw_response' => $faceMatchResult['raw_response'] ?? [],
            ]);
        }

        if (!$hasLiveness && $bvnDoc) {
            $bvnDoc->update([
                'verification_provider' => 'youverify',
                'verification_reference' => $bvnProviderRef,
                'match_confidence' => $bvnResult['match_confidence'] ?? null,
                'auto_verified' => $bvnVerified,
                'verification_raw_response' => $bvnResult['raw_response'] ?? [],
            ]);
        }

        if ($ninVerified) {
            $this->kycCompletion->recordNinVerification($user, $ninResult);
        }

        if (! $hasLiveness && $bvnVerified) {
            $this->kycCompletion->recordBvnVerification($user, $bvnResult);
        }

        // Liveness face match is treated as BVN verification for Tier 2 completion.
        if ($hasLiveness && $faceMatchVerified) {
            $this->kycCompletion->recordBvnVerification($user, [
                'verified' => true,
                'provider_reference' => $faceMatchResult['provider_reference'] ?? '',
                'match_confidence' => $faceMatchConfidence,
                'raw_response' => $faceMatchResult['raw_response'] ?? [],
            ]);
        }

        $autoApproved = $this->kycCompletion->tryCompleteTier2($user);

        if ($ninVerificationFailed || $faceOrBvnFailed) {
            $this->applyDocumentStatus($user, 'provider_error');
            return [
                'auto_approved' => false,
                'status' => 'provider_error',
                'message' => 'Verification provider error. Your submission will be reviewed manually.',
                'details' => [
                    'nin_verified' => $ninVerified,
                    'face_match_verified' => $faceMatchVerified ?? false,
                    'bvn_verified' => $bvnVerified ?? false,
                    'face_match_confidence' => $faceMatchConfidence,
                    'nin_error' => $ninResult['error'] ?? null,
                    'face_bvn_error' => $faceMatchResult['error'] ?? $bvnResult['error'] ?? null,
                ],
            ];
        }

        if ($autoApproved) {
            $this->applyDocumentStatus($user, 'auto_verified');
            $this->kycCompletion->dispatchTier2SuccessNotification($user);

            return [
                'auto_approved' => true,
                'status' => 'verified',
                'message' => 'Your identity has been verified successfully!',
                'details' => [
                    'nin_verified' => true,
                    'face_match_verified' => $faceMatchVerified ?? false,
                    'bvn_verified' => $bvnVerified ?? false,
                    'face_match_confidence' => $faceMatchConfidence,
                ],
            ];
        }

        $this->applyDocumentStatus($user, 'manual_review');

        $message = $hasLiveness
            ? "Your details are being reviewed. Face match confidence was {$faceMatchConfidence}% (threshold: " . self::FACE_MATCH_THRESHOLD . "%)."
            : 'Your details are being reviewed by our team.';

        return [
            'auto_approved' => false,
            'status' => 'manual_review',
            'message' => $message,
            'details' => [
                'nin_verified' => $ninVerified,
                'face_match_verified' => $faceMatchVerified ?? false,
                'bvn_verified' => $bvnVerified ?? false,
                'face_match_confidence' => $faceMatchConfidence,
            ],
        ];
    }

    protected function applyDocumentStatus(User $user, string $status): void
    {
        KycDocument::where('user_id', $user->id)
            ->whereIn('document_type', ['nin_slip', 'bvn_slip', 'liveness_capture'])
            ->update(['verification_status' => $status]);
    }

    protected function safeVerify(callable $fn): array
    {
        try {
            return $fn();
        } catch (\Throwable $e) {
            Log::error('Identity verification call failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'verified' => false,
                'match_confidence' => null,
                'provider_reference' => '',
                'raw_response' => [],
                'error' => $e->getMessage(),
            ];
        }
    }
}
