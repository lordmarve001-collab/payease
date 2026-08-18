<?php

namespace App\Services;

use App\Helpers\PhoneNumberHelper;
use App\Jobs\SendSmsNotification;
use App\Mail\WelcomeMail;
use App\Models\Agent;
use App\Models\KycDocument;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class AgentCustomerService
{
    public function registerCustomerViaAgent(Agent $agent, string $phone, string $fullName, ?string $email = null): User
    {
        $normalizedPhone = PhoneNumberHelper::normalize($phone);

        $existing = User::where('phone_number', $normalizedPhone)->first();
        if ($existing) {
            throw new RuntimeException('A user with this phone number already exists.');
        }

        $plainPassword = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user = User::create([
            'phone_number' => $normalizedPhone,
            'email' => $email ?: null,
            'full_name' => $fullName,
            'status' => 'active',
            'kyc_level' => 1,
            'kyc_verified_at' => now(),
            'login_password' => Hash::make($plainPassword),
            'must_change_password' => true,
            'registered_by_agent_id' => $agent->id,
        ]);

        $user->assignRole('customer');

        app(WalletService::class)->createTierWallet($user, 1);

        if ($email) {
            rescue(function () use ($user, $plainPassword): void {
                Mail::to($user->email)->send(new WelcomeMail($user, $plainPassword));
            }, report: false);
        }

        rescue(function () use ($user, $fullName, $plainPassword): void {
            SendSmsNotification::dispatch(
                $user->phone_number,
                "Welcome to PayEase, {$fullName}! Default password: {$plainPassword}. Please change it after login. -PayEase"
            );
        }, report: false);

        return $user;
    }

    public function submitKycViaAgent(Agent $agent, User $customer, int $targetTier, array $data, array $documents): void
    {
        $currentLevel = (int) $customer->kyc_level;

        if ($targetTier <= $currentLevel) {
            throw new RuntimeException("Customer is already at Tier {$currentLevel}. Cannot submit Tier {$targetTier} upgrade.");
        }

        $tier2Completed = false;

        if ($targetTier === 2) {
            $tier2Completed = $this->submitTier2($agent, $customer, $data, $documents);
        } elseif ($targetTier === 3) {
            if ($currentLevel < 2) {
                throw new RuntimeException('Customer must complete Tier 2 before upgrading to Tier 3.');
            }
            $this->submitTier3($agent, $customer, $data, $documents);
        } else {
            throw new RuntimeException('Invalid target tier. Only Tier 2 and Tier 3 upgrades are supported.');
        }

        $message = $tier2Completed
            ? "Your Tier 2 upgrade has been completed by an agent. Your account is now Tier 2. -PayEase"
            : "Your Tier {$targetTier} upgrade has been submitted by an agent and is under review. -PayEase";

        SendSmsNotification::dispatch(
            $customer->phone_number,
            $message,
        );
    }

    protected function submitTier2(Agent $agent, User $customer, array $data, array $documents): bool
    {
        $kycCompletion = app(KycCompletionService::class);

        $nin = (string) ($data['nin'] ?? '');
        $bvn = (string) ($data['bvn'] ?? '');
        $nextOfKinName = (string) ($data['next_of_kin_name'] ?? '');
        $nextOfKinRelationship = (string) ($data['next_of_kin_relationship'] ?? '');
        $nextOfKinPhone = (string) ($data['next_of_kin_phone'] ?? '');

        $hasNin = $nin !== '' || filled($customer->nin_verified_at);
        $hasBvn = $bvn !== '' || filled($customer->bvn_verified_at);
        $hasNextOfKin = filled($customer->next_of_kin_submitted_at)
            || ($nextOfKinName !== '' && $nextOfKinRelationship !== '' && $nextOfKinPhone !== '');

        if (! $hasNin || ! $hasBvn || ! $hasNextOfKin) {
            throw new RuntimeException('NIN, BVN, and Next of Kin details are required for Tier 2 upgrade.');
        }

        $updateData = [];

        if ($nin !== '' && blank($customer->nin_verified_at)) {
            $updateData['nin'] = $nin;
        }

        if ($bvn !== '' && blank($customer->bvn_verified_at)) {
            $updateData['bvn'] = $bvn;
        }

        if (blank($customer->next_of_kin_submitted_at)) {
            if ($nextOfKinName === '' || $nextOfKinRelationship === '' || $nextOfKinPhone === '') {
                throw new RuntimeException('Next of Kin details are required for Tier 2 upgrade.');
            }

            try {
                $nextOfKinPhone = PhoneNumberHelper::normalize($nextOfKinPhone);
            } catch (InvalidArgumentException) {
                throw new RuntimeException('Invalid next of kin phone number.');
            }

            $updateData['next_of_kin_name'] = $nextOfKinName;
            $updateData['next_of_kin_relationship'] = $nextOfKinRelationship;
            $updateData['next_of_kin_phone'] = $nextOfKinPhone;
            $updateData['next_of_kin_submitted_at'] = now();
        }

        if (! empty($updateData)) {
            $customer->update($updateData);
        }

        // Agent-submitted NIN and BVN are treated as verified immediately (agent accountability via PIN).
        if ($nin !== '' && blank($customer->nin_verified_at)) {
            $kycCompletion->approveDocumentAsNinVerification($customer);
        }

        if ($bvn !== '' && blank($customer->bvn_verified_at)) {
            $kycCompletion->approveDocumentAsBvnVerification($customer);
        }

        foreach (['nin_slip', 'bvn_slip', 'liveness_capture'] as $docType) {
            if (isset($documents[$docType]) && $documents[$docType]) {
                $path = $documents[$docType]->store('kyc-documents', 'public');
                KycDocument::updateOrCreate(
                    [
                        'user_id' => $customer->id,
                        'document_type' => $docType,
                    ],
                    [
                        'document_url' => Storage::url($path),
                        'verification_status' => 'verified',
                        'verified_at' => now(),
                        'submitted_by_agent_id' => $agent->id,
                    ]
                );
            }
        }

        return $kycCompletion->tryCompleteTier2($customer);
    }

    protected function submitTier3(Agent $agent, User $customer, array $data, array $documents): void
    {
        $addressDoc = $documents['proof_of_address'] ?? null;
        $indemnityDoc = $documents['address_indemnity_form'] ?? null;

        if (!$addressDoc && !$indemnityDoc) {
            throw new RuntimeException('A proof of address document or indemnity form is required for Tier 3 upgrade.');
        }

        foreach (['proof_of_address' => $addressDoc, 'address_indemnity_form' => $indemnityDoc] as $docType => $file) {
            if ($file) {
                $path = $file->store('kyc-documents', 'public');
                KycDocument::create([
                    'user_id' => $customer->id,
                    'document_type' => $docType,
                    'document_url' => Storage::url($path),
                    'verification_status' => 'pending',
                    'submitted_by_agent_id' => $agent->id,
                ]);
            }
        }
    }
}
