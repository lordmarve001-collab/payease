<?php

namespace App\Services;

use App\Jobs\SendSmsNotification;
use App\Models\AjoOwner;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AjoOwnerApplicationService
{
    public function submitApplication(User $user, array $data): AjoOwner
    {
        if ((int) $user->kyc_level < 2) {
            throw new RuntimeException('You must complete Tier 2 KYC verification before applying to become an Ajo Owner.');
        }

        $existing = AjoOwner::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'active'])
            ->first();

        if ($existing) {
            if ($existing->status === 'pending') {
                throw new RuntimeException('You already have a pending Ajo Owner application.');
            }
            throw new RuntimeException('You are already an active Ajo Owner.');
        }

        $existingRejected = AjoOwner::where('user_id', $user->id)->where('status', 'rejected')->first();
        if ($existingRejected) {
            return DB::transaction(function () use ($existingRejected, $user, $data) {
                $existingRejected->update([
                    'business_name' => $data['business_name'],
                    'business_description' => $data['business_description'] ?? null,
                    'business_address' => $data['business_address'] ?? null,
                    'lga' => $data['lga'] ?? null,
                    'state' => $data['state'] ?? null,
                    'has_experience' => !empty($data['has_experience']),
                    'planned_groups' => (int) ($data['planned_groups'] ?? 1),
                    'members_per_group' => (int) ($data['members_per_group'] ?? 0),
                    'agent_assignment_preference' => $data['agent_assignment_preference'] ?? null,
                    'reference_contact_name' => $data['reference_contact_name'] ?? null,
                    'reference_contact_phone' => $data['reference_contact_phone'] ?? null,
                    'status' => 'pending',
                    'rejection_reason' => null,
                    'approved_at' => null,
                    'approved_by' => null,
                ]);

                rescue(function () use ($user): void {
                    SendSmsNotification::dispatch(
                        $user->phone_number,
                        'Your PayEase Ajo Owner application has been received and is under review. -PayEase'
                    );
                }, report: false);

                return $existingRejected->fresh();
            });
        }

        return DB::transaction(function () use ($user, $data) {
            $ajoOwner = AjoOwner::create([
                'user_id' => $user->id,
                'business_name' => $data['business_name'],
                'business_description' => $data['business_description'] ?? null,
                'business_address' => $data['business_address'] ?? null,
                'lga' => $data['lga'] ?? null,
                'state' => $data['state'] ?? null,
                'has_experience' => !empty($data['has_experience']),
                'planned_groups' => (int) ($data['planned_groups'] ?? 1),
                'members_per_group' => (int) ($data['members_per_group'] ?? 0),
                'agent_assignment_preference' => $data['agent_assignment_preference'] ?? null,
                'reference_contact_name' => $data['reference_contact_name'] ?? null,
                'reference_contact_phone' => $data['reference_contact_phone'] ?? null,
                'status' => 'pending',
            ]);

            rescue(function () use ($user): void {
                SendSmsNotification::dispatch(
                    $user->phone_number,
                    'Your PayEase Ajo Owner application has been received and is under review. -PayEase'
                );
            }, report: false);

            return $ajoOwner;
        });
    }

    public function approve(AjoOwner $ajoOwner, User $admin): void
    {
        DB::transaction(function () use ($ajoOwner, $admin) {
            $ajoOwner->update([
                'status' => 'active',
                'approved_at' => now(),
                'approved_by' => $admin->id,
            ]);

            $user = $ajoOwner->user;
            if ($user && !$user->hasRole('ajo_owner')) {
                $user->assignRole('ajo_owner');
            }
        });

        rescue(function () use ($ajoOwner): void {
            $user = $ajoOwner->user;
            if ($user) {
                SendSmsNotification::dispatch(
                    $user->phone_number,
                    'Congratulations! Your PayEase Ajo Owner application has been approved. Log in to create your first Ajo group. -PayEase'
                );
            }
        }, report: false);
    }

    public function reject(AjoOwner $ajoOwner, User $admin, string $reason): void
    {
        $ajoOwner->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);

        rescue(function () use ($ajoOwner, $reason): void {
            $user = $ajoOwner->user;
            if ($user) {
                SendSmsNotification::dispatch(
                    $user->phone_number,
                    "Your PayEase Ajo Owner application was not approved: {$reason} -PayEase"
                );
            }
        }, report: false);
    }
}
