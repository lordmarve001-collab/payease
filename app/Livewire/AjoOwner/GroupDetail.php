<?php

namespace App\Livewire\AjoOwner;

use App\Helpers\PhoneNumberHelper;
use App\Models\AjoGroup;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AjoService;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use RuntimeException;

class GroupDetail extends Component
{
    public string $groupId;
    public string $memberPhone = '';
    public bool $showPayoutModal = false;
    public ?string $removingMemberId = null;

    protected function rules(): array
    {
        return [
            'groupId' => ['required', 'string', 'uuid'],
            'memberPhone' => ['nullable', 'string'],
        ];
    }

    public function mount(string $id): void
    {
        $this->groupId = $id;
        $this->validateOnly('groupId');
    }

    public function addMember(): void
    {
        /** @var User $user */
        $user = Auth::user();
        $group = $this->resolveGroup($user);
        /** @var TransactionService $transactionService */
        $transactionService = app(TransactionService::class);
        $member = $transactionService->lookupRecipientByPhone($this->memberPhone);

        if (!$member) {
            $this->addError('memberPhone', 'No registered PayEase user matches that phone number.');
            return;
        }

        try {
            /** @var AjoService $ajoService */
            $ajoService = app(AjoService::class);
            $ajoService->addMember($group, $member);

            $this->memberPhone = '';
            $this->resetErrorBag('memberPhone');
            $this->dispatch('notify-success', message: 'Member added to the group successfully.');
        } catch (RuntimeException $exception) {
            $this->addError('memberPhone', $exception->getMessage());
        }
    }

    public function confirmRemoveMember(string $memberId): void
    {
        $this->removingMemberId = $memberId;
    }

    public function cancelRemoveMember(): void
    {
        $this->removingMemberId = null;
    }

    public function removeMember(): void
    {
        if (!$this->removingMemberId) {
            return;
        }

        $user = Auth::user();
        $group = $this->resolveGroup($user);

        $member = $group->members()->where('id', $this->removingMemberId)->first();

        if (!$member) {
            $this->dispatch('notify-error', message: 'Member not found in this group.');
            $this->cancelRemoveMember();
            return;
        }

        try {
            /** @var AjoService $ajoService */
            $ajoService = app(AjoService::class);
            $member->update(['status' => 'defaulted']);
            $this->dispatch('notify-success', message: 'Member has been removed from the group.');
        } catch (\RuntimeException $exception) {
            $this->dispatch('notify-error', message: $exception->getMessage());
        }

        $this->cancelRemoveMember();
    }

    public function confirmPayout(): void
    {
        $this->showPayoutModal = true;
    }

    public function closePayoutModal(): void
    {
        $this->showPayoutModal = false;
    }

    public function processPayout(): void
    {
        /** @var User $user */
        $user = Auth::user();
        $group = $this->resolveGroup($user);
        /** @var AjoService $ajoService */
        $ajoService = app(AjoService::class);
        $nextPayout = $ajoService->getNextPayout($group);
        $recipient = $nextPayout['recipient'];

        if (!$recipient || !$group->managingAgent) {
            $this->dispatch('notify-error', message: 'This group has no payout recipient ready yet.');
            return;
        }

        try {
            $ajoService->processPayout($group, $recipient, $group->managingAgent, $nextPayout['cycle_number']);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'ajo_payout',
                'entity_type' => 'ajo_group',
                'entity_id' => $group->id,
                'old_values' => null,
                'new_values' => [
                    'recipient_id' => $recipient->id,
                    'cycle_number' => $nextPayout['cycle_number'],
                    'amount' => $nextPayout['amount'] ?? $group->contribution_amount * $group->members_count,
                ],
                'ip_address' => request()->ip(),
                'device_id' => request()->userAgent(),
            ]);

            $this->showPayoutModal = false;
            $this->dispatch('notify-success', message: 'Payout marked as paid successfully.');
        } catch (RuntimeException $exception) {
            $this->dispatch('notify-error', message: $exception->getMessage());
        }
    }

    public function render()
    {
        /** @var User $user */
        $user = Auth::user();
        $group = $this->resolveGroup($user);
        /** @var AjoService $ajoService */
        $ajoService = app(AjoService::class);

        $progress = $ajoService->getCycleProgress($group);
        $nextPayout = $ajoService->getNextPayout($group);
        $contributionsForCycle = $group->contributions()
            ->where('cycle_number', $progress['cycle_number'])
            ->pluck('id', 'user_id');
        $members = $group->members()
            ->with('user')
            ->orderByRaw('CASE WHEN position IS NULL THEN 999999 ELSE position END')
            ->get()
            ->map(function ($member) use ($contributionsForCycle) {
                $paymentStatus = $member->status === 'defaulted'
                    ? 'defaulted'
                    : ($contributionsForCycle->has($member->user_id) ? 'paid' : 'pending');

                return [
                    'member' => $member,
                    'payment_status' => $paymentStatus,
                ];
            });

        return view('livewire.ajo-owner.group-detail', [
            'group' => $group,
            'progress' => $progress,
            'members' => $members,
            'contributionHistory' => $ajoService->getContributionHistory($group),
            'nextPayout' => $nextPayout,
        ])->layout('components.layouts.ajo-owner');
    }

    protected function resolveGroup(User $user): AjoGroup
    {
        return AjoGroup::query()
            ->where('id', $this->groupId)
            ->where('ajo_owner_id', $user->ajoOwner?->id)
            ->with(['managingAgent.user', 'members.user', 'contributions', 'payouts'])
            ->firstOrFail();
    }
}
