<?php

namespace App\Livewire\AjoAgent;

use App\Models\Agent;
use App\Models\AjoGroup;
use App\Models\AjoMember;
use App\Models\User;
use App\Services\AjoService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Collect extends Component
{
    public string $step = 'select_group';
    public ?string $selectedGroupId = null;
    public ?string $selectedMemberId = null;
    public string $amount = '';
    public string $search = '';
    public int $page = 1;

    public bool $confirming = false;
    public bool $processing = false;

    public string $resultState = '';
    public string $resultMessage = '';
    public float $resultAmount = 0;
    public string $resultMember = '';
    public string $resultGroup = '';
    public int $resultCycle = 0;

    public function updatingSearch(): void
    {
        $this->page = 1;
    }

    public function selectGroup(string $groupId): void
    {
        $this->selectedGroupId = $groupId;
        $this->selectedMemberId = null;
        $this->search = '';
        $this->page = 1;
        $this->step = 'select_member';
    }

    public function goToPage(int $page): void
    {
        $this->page = $page;
    }

    public function collectMember(string $memberId): void
    {
        $this->selectedMemberId = $memberId;

        $member = AjoMember::with('user', 'group')->find($memberId);

        if ($member && $member->group) {
            if ($member->group->model_type === 'rotational') {
                $this->amount = (string) $member->group->contribution_amount;
            } else {
                $this->amount = '';
            }
        }

        $this->step = 'confirm';
        $this->confirming = false;
    }

    public function goBack(): void
    {
        match ($this->step) {
            'select_member' => $this->step = 'select_group',
            'confirm' => $this->step = 'select_member',
            default => null,
        };
        $this->confirming = false;
        $this->resetErrorBag();
    }

    public function submitContribution(): void
    {
        $this->confirming = true;
    }

    public function confirmContribution(): void
    {
        $this->validate([
            'amount' => ['required', 'numeric', 'min:1'],
        ], [
            'amount.required' => 'Enter a contribution amount.',
            'amount.numeric' => 'Amount must be a valid number.',
            'amount.min' => 'Amount must be at least ₦1.',
        ]);

        $this->processing = true;

        try {
            /** @var User $user */
            $user = Auth::user();
            /** @var Agent $agent */
            $agent = $user->agent;

            $group = AjoGroup::findOrFail($this->selectedGroupId);
            $member = AjoMember::with('user')->findOrFail($this->selectedMemberId);

            $ajoService = app(AjoService::class);
            $contribution = $ajoService->logContribution(
                $agent,
                $group,
                $member->user,
                (float) $this->amount
            );

            $this->resultState = 'success';
            $this->resultMessage = 'Contribution logged successfully!';
            $this->resultAmount = (float) $this->amount;
            $this->resultMember = $member->user->full_name ?? 'Unknown';
            $this->resultGroup = $group->name;
            $this->resultCycle = $contribution->cycle_number;
            $this->step = 'result';
        } catch (\Throwable $e) {
            $this->resultState = 'error';
            $this->resultMessage = $e->getMessage();
            $this->step = 'result';
        } finally {
            $this->processing = false;
            $this->confirming = false;
        }
    }

    public function resetForm(): void
    {
        $this->step = 'select_group';
        $this->selectedGroupId = null;
        $this->selectedMemberId = null;
        $this->amount = '';
        $this->search = '';
        $this->page = 1;
        $this->confirming = false;
        $this->resultState = '';
        $this->resultMessage = '';
        $this->resultAmount = 0;
        $this->resultMember = '';
        $this->resultGroup = '';
        $this->resultCycle = 0;
        $this->resetErrorBag();
    }

    public function render()
    {
        /** @var User $user */
        $user = Auth::user();
        /** @var Agent|null $agent */
        $agent = $user->agent;

        $assignedGroups = collect();
        $members = collect();
        $selectedGroup = null;
        $selectedMember = null;
        $cycleNumber = 0;
        $paidUserIds = collect();

        if ($agent) {
            $assignedGroups = AjoGroup::query()
                ->where('status', 'active')
                ->where(function ($q) use ($agent) {
                    $q->whereHas('agents', fn ($q2) => $q2->where('agent_id', $agent->id))
                      ->orWhere('managing_agent_id', $agent->id);
                })
                ->with('ajoOwner')
                ->get()
                ->unique('id');

            if ($this->selectedGroupId) {
                $selectedGroup = AjoGroup::find($this->selectedGroupId);

                if ($selectedGroup) {
                    $ajoService = app(AjoService::class);
                    $cycleNumber = $ajoService->getCurrentCycleNumber($selectedGroup);

                    $paidUserIds = \App\Models\AjoContribution::query()
                        ->where('group_id', $selectedGroup->id)
                        ->where('cycle_number', $cycleNumber)
                        ->pluck('user_id');

                    $allMembers = AjoMember::query()
                        ->with('user')
                        ->where('group_id', $selectedGroup->id)
                        ->where('status', 'active')
                        ->orderBy('position')
                        ->get();

                    if ($this->search !== '') {
                        $search = strtolower(trim($this->search));
                        $allMembers = $allMembers->filter(fn ($m) =>
                            str_contains(strtolower($m->user?->full_name ?? ''), $search) ||
                            str_contains($m->user?->phone_number ?? '', $search)
                        )->values();
                    }

                    $perPage = 10;
                    $total = $allMembers->count();
                    $items = $allMembers->slice(($this->page - 1) * $perPage, $perPage)->values();

                    $members = [
                        'items' => $items,
                        'total' => $total,
                        'perPage' => $perPage,
                        'currentPage' => $this->page,
                        'lastPage' => (int) ceil($total / $perPage),
                    ];
                }
            }

            if ($this->selectedMemberId) {
                $selectedMember = AjoMember::with('user', 'group')->find($this->selectedMemberId);
            }
        }

        return view('livewire.ajo-agent.collect', [
            'agent' => $agent,
            'assignedGroups' => $assignedGroups,
            'members' => $members,
            'selectedGroup' => $selectedGroup,
            'selectedMember' => $selectedMember,
            'cycleNumber' => $cycleNumber,
            'paidUserIds' => $paidUserIds,
        ])->layout('components.layouts.ajo-agent');
    }
}
