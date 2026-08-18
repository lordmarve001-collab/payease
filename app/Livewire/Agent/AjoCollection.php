<?php

namespace App\Livewire\Agent;

use App\Models\Agent;
use App\Models\AjoGroup;
use App\Models\AjoMember;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AjoService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use RuntimeException;

class AjoCollection extends Component
{
    public int $step = 1;
    public ?string $selectedGroupId = null;
    public ?string $selectedMemberId = null;
    public string $agentPin = '';
    public float $amount = 0;
    public float $commission = 0;
    public string $reference = '';
    public string $date = '';
    public string $resultState = '';
    public string $resultMessage = '';
    public array $progress = [
        'cycle_number' => 1,
        'paid_members' => 0,
        'total_members' => 0,
        'amount_collected' => 0,
        'target_amount' => 0,
        'percentage' => 0,
        'is_complete' => false,
    ];
    public bool $isLoading = false;

    public function selectGroup(string $groupId): void
    {
        if (!str()->isUuid($groupId)) {
            $this->dispatch('notify-error', message: 'Invalid group selection.');
            return;
        }

        $this->selectedGroupId = $groupId;
        $this->selectedMemberId = null;
        $this->agentPin = '';
        $this->resultState = '';
        $this->resultMessage = '';
        $this->step = 2;
        $this->syncPreview();
    }

    public function selectMember(string $memberId): void
    {
        if (!str()->isUuid($memberId)) {
            $this->dispatch('notify-error', message: 'Invalid member selection.');
            return;
        }

        $this->selectedMemberId = $memberId;
        $this->syncPreview();
        $this->step = 3;
    }

    public function goBack(): void
    {
        if ($this->step === 4) {
            $this->logAnother();
            return;
        }

        if ($this->step === 3) {
            $this->agentPin = '';
            $this->step = 2;
            return;
        }

        if ($this->step === 2) {
            $this->selectedGroupId = null;
            $this->selectedMemberId = null;
            $this->step = 1;
        }
    }

    public function confirmContribution(): void
    {
        if (!$this->verifyAgentPin('ajo_collection')) {
            return;
        }

        $this->isLoading = true;

        try {
            /** @var User $agentUser */
            $agentUser = Auth::user();
            $agent = $agentUser->agent;
            $group = $this->getSelectedGroup();
            $member = $this->getSelectedMember()?->user;

            if (!$agent || !$group || !$member) {
                throw new RuntimeException('Unable to prepare this Ajo contribution.');
            }

            /** @var AjoService $ajoService */
            $ajoService = app(AjoService::class);
            $contribution = $ajoService->logContribution($agent, $group, $member, (float) $this->amount);

            AuditLog::create([
                'user_id' => $agentUser->id,
                'action' => 'ajo_contribution',
                'entity_type' => 'ajo_contribution',
                'entity_id' => $contribution->id,
                'old_values' => null,
                'new_values' => [
                    'group_id' => $group->id,
                    'member_id' => $member->id,
                    'amount' => (float) $this->amount,
                    'reference' => $contribution->transaction?->reference,
                ],
                'ip_address' => request()->ip(),
                'device_id' => request()->userAgent(),
            ]);

            $this->reference = $contribution->transaction?->reference ?? '';
            $this->commission = (float) ($contribution->transaction?->commission ?? 0);
            $this->date = ($contribution->transaction?->completed_at ?? $contribution->created_at)->format('d M Y, h:i A');
            $this->progress = $ajoService->getCycleProgress($group->fresh());
            $this->resultState = 'success';
            $this->resultMessage = '';
            $this->step = 4;
            $this->dispatch('notify-success', message: 'Ajo contribution logged successfully.');
        } catch (RuntimeException $exception) {
            $this->resultState = 'failed';
            $this->resultMessage = $exception->getMessage();
            $this->step = 4;
            $this->dispatch('notify-error', message: $this->resultMessage);
        } finally {
            $this->isLoading = false;
        }
    }

    public function logAnother(): void
    {
        $this->selectedMemberId = null;
        $this->agentPin = '';
        $this->reference = '';
        $this->date = '';
        $this->resultState = '';
        $this->resultMessage = '';
        $this->step = 2;
        $this->syncPreview();
    }

    public function chooseAnotherGroup(): void
    {
        $this->selectedGroupId = null;
        $this->selectedMemberId = null;
        $this->agentPin = '';
        $this->reference = '';
        $this->date = '';
        $this->resultState = '';
        $this->resultMessage = '';
        $this->amount = 0;
        $this->commission = 0;
        $this->progress = [
            'cycle_number' => 1,
            'paid_members' => 0,
            'total_members' => 0,
            'amount_collected' => 0,
            'target_amount' => 0,
            'percentage' => 0,
            'is_complete' => false,
        ];
        $this->step = 1;
    }

    public function render()
    {
        /** @var User $user */
        $user = Auth::user();
        /** @var Agent $agent */
        $agent = $user->agent;
        /** @var AjoService $ajoService */
        $ajoService = app(AjoService::class);

        $groups = AjoGroup::query()
            ->where('managing_agent_id', $agent?->id)
            ->with(['members.user', 'payouts'])
            ->orderBy('name')
            ->get();

        $groupProgress = [];
        foreach ($groups as $group) {
            $groupProgress[$group->id] = $ajoService->getCycleProgress($group);
        }

        $selectedGroup = $this->getSelectedGroup();
        $members = $selectedGroup
            ? $ajoService->getMembersPendingForCurrentCycle($selectedGroup)
            : collect();

        return view('livewire.agent.ajo-collection', [
            'groups' => $groups,
            'groupProgress' => $groupProgress,
            'members' => $members,
            'selectedGroup' => $selectedGroup,
            'selectedMember' => $this->getSelectedMember(),
        ])->layout('components.layouts.agent');
    }

    protected function syncPreview(): void
    {
        $group = $this->getSelectedGroup();

        if (!$group) {
            $this->amount = 0;
            $this->commission = 0;
            return;
        }

        /** @var AjoService $ajoService */
        $ajoService = app(AjoService::class);
        $this->amount = (float) $group->contribution_amount;
        $this->commission = round($this->amount * 0.01, 2);
        $this->progress = $ajoService->getCycleProgress($group);
    }

    protected function getSelectedGroup(): ?AjoGroup
    {
        if (!$this->selectedGroupId) {
            return null;
        }

        /** @var User $user */
        $user = Auth::user();

        return AjoGroup::query()
            ->where('id', $this->selectedGroupId)
            ->where('managing_agent_id', $user->agent?->id)
            ->with(['members.user', 'payouts'])
            ->first();
    }

    protected function getSelectedMember(): ?AjoMember
    {
        $group = $this->getSelectedGroup();

        if (!$group || !$this->selectedMemberId) {
            return null;
        }

        return $group->members()
            ->with('user')
            ->where('id', $this->selectedMemberId)
            ->first();
    }

    protected function verifyAgentPin(string $action): bool
    {
        $validated = $this->validate([
            'agentPin' => ['required', 'digits:6'],
        ], [
            'agentPin.required' => 'Enter your agent PIN to continue.',
            'agentPin.digits' => 'Agent PIN must be 6 digits.',
        ]);

        /** @var User|null $agentUser */
        $agentUser = Auth::user();

        if (!$agentUser) {
            $this->addError('agentPin', 'Your session has expired. Please log in again.');
            return false;
        }

        $maxAttempts = (int) config('lockout.pin.max_attempts', 3);
        $lockoutDuration = (int) config('lockout.pin.lockout_duration', 86400);

        $attemptKey = 'agent_pin_attempts_' . $action . '_' . $agentUser->id;
        $lockKey = 'agent_pin_lock_' . $action . '_' . $agentUser->id;

        if (Cache::has($lockKey)) {
            $this->addError('agentPin', 'Too many incorrect PIN attempts. Transactions are locked.');
            return false;
        }

        if (!$agentUser->verifyTransferPin($validated['agentPin'])) {
            $attempts = Cache::increment($attemptKey, 1);
            Cache::put($attemptKey, $attempts, $lockoutDuration);

            if ($attempts >= $maxAttempts) {
                Cache::put($lockKey, true, $lockoutDuration);
                $this->addError('agentPin', 'Too many incorrect PIN attempts. Transactions are locked.');
            } else {
                $remaining = max(0, $maxAttempts - $attempts);
                $this->addError('agentPin', 'Incorrect agent PIN. ' . $remaining . ' attempt(s) remaining.');
            }

            return false;
        }

        Cache::forget($attemptKey);
        Cache::forget($lockKey);

        return true;
    }
}
