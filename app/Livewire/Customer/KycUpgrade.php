<?php

namespace App\Livewire\Customer;

use App\Helpers\PhoneNumberHelper;
use App\Models\KycDocument;
use App\Models\User;
use App\Services\IdentityVerificationService;
use App\Services\WalletService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class KycUpgrade extends Component
{
    use WithFileUploads;

    public string $nin = '';
    public string $bvn = '';
    public string $nextOfKinName = '';
    public string $nextOfKinRelationship = '';
    public string $nextOfKinPhone = '';
    public $ninDocument;
    public $bvnDocument;
    public bool $useLiveness = false;
    public $livenessCapture;
    public bool $consent = false;
    public string $statusMessage = '';
    public string $flowStep = 'form'; // form | submitting | verifying | verified | under_review | provider_error

    protected function rules(): array
    {
        return [
            'nin' => ['required', 'digits:11'],
            'bvn' => ['required', 'digits:11'],
            'nextOfKinName' => ['required', 'string', 'max:255'],
            'nextOfKinRelationship' => ['required', 'string', 'max:50'],
            'nextOfKinPhone' => ['required', 'string'],
            'ninDocument' => ['required', 'image', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'bvnDocument' => ['required', 'image', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'livenessCapture' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'consent' => ['accepted'],
        ];
    }

    protected $messages = [
        'consent.accepted' => 'You must consent to identity verification.',
    ];

    public ?string $blockedReason = null;

    public function mount(): void
    {
        $user = Auth::user();
        if (!$user) {
            $this->redirect(route('login'));
            return;
        }

        $level = (int) $user->kyc_level;

        if ($level < 1) {
            $this->redirect(route('customer.profile'), navigate: true);
            return;
        } elseif ($level >= 3) {
            $this->blockedReason = 'You have already completed identity verification. Your account is fully verified.';
        } elseif ($level >= 2) {
            $this->blockedReason = 'You have already completed identity verification.';
        }
    }

    public function submit(): void
    {
        $this->validate();

        try {
            $this->nextOfKinPhone = PhoneNumberHelper::normalize($this->nextOfKinPhone);
        } catch (\InvalidArgumentException) {
            $this->addError('nextOfKinPhone', 'Enter a valid Nigerian phone number for next of kin.');
            return;
        }

        /** @var User $user */
        $user = Auth::user();

        $this->flowStep = 'submitting';

        $ninPath = $this->ninDocument->store('kyc-documents', 'public');
        $bvnPath = $this->bvnDocument->store('kyc-documents', 'public');
        $livenessPath = null;
        $selfieBase64 = null;

        if ($this->useLiveness && $this->livenessCapture) {
            $livenessPath = $this->livenessCapture->store('kyc-documents', 'public');
            $selfieBase64 = base64_encode(file_get_contents($this->livenessCapture->getRealPath()));
        }

        $user->update([
            'nin' => $this->nin,
            'bvn' => $this->bvn,
            'next_of_kin_name' => $this->nextOfKinName,
            'next_of_kin_relationship' => $this->nextOfKinRelationship,
            'next_of_kin_phone' => $this->nextOfKinPhone,
            'next_of_kin_submitted_at' => now(),
        ]);

        KycDocument::create([
            'user_id' => $user->id,
            'document_type' => 'nin_slip',
            'document_url' => Storage::url($ninPath),
            'verification_status' => 'pending',
        ]);

        KycDocument::create([
            'user_id' => $user->id,
            'document_type' => 'bvn_slip',
            'document_url' => Storage::url($bvnPath),
            'verification_status' => 'pending',
        ]);

        if ($livenessPath) {
            KycDocument::create([
                'user_id' => $user->id,
                'document_type' => 'liveness_capture',
                'document_url' => Storage::url($livenessPath),
                'verification_status' => 'pending',
            ]);
        }

        $this->flowStep = 'verifying';

        rescue(fn () => app(\App\Services\AdminNotificationService::class)->create([
            'type' => 'kyc_submission',
            'title' => 'New KYC Submission',
            'message' => "{$user->full_name} ({$user->phone_number}) has submitted documents for identity verification.",
            'action_url' => '/admin/kyc-queue',
            'action_label' => 'Review KYC',
            'severity' => 'info',
            'related_id' => $user->id,
            'related_type' => User::class,
        ]), report: false);

        try {
            /** @var IdentityVerificationService $verificationService */
            $verificationService = app(IdentityVerificationService::class);
            $result = $verificationService->verifyTier2Submission(
                $user,
                $this->nin,
                $this->bvn,
                $selfieBase64,
                $this->consent,
            );

            $this->statusMessage = $result['message'];

            if ($result['auto_approved']) {
                $this->flowStep = 'verified';
                $this->dispatch('notify-success', message: 'Identity verified successfully!');
            } elseif ($result['status'] === 'provider_error') {
                $this->flowStep = 'provider_error';
                $this->dispatch('notify-info', message: $result['message']);
            } else {
                $this->flowStep = 'under_review';
                $this->dispatch('notify-info', message: $result['message']);
            }
        } catch (\Throwable $e) {
            $this->flowStep = 'under_review';
            $this->statusMessage = 'Your documents have been submitted for manual review.';
            $this->dispatch('notify-info', message: 'Verification provider unavailable. Your submission will be reviewed manually.');
        }
    }

    public function render()
    {
        /** @var User $user */
        $user = Auth::user();
        $walletService = app(WalletService::class);

        return view('livewire.customer.kyc-upgrade', [
            'user' => $user,
            'upgradeMessage' => $walletService->getKycUpgradeMessage((int) $user->kyc_level),
        ])->layout('components.layouts.customer');
    }
}
