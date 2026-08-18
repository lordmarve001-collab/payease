<?php

namespace App\Livewire\AjoAgent;

use App\Models\Agent;
use App\Models\AjoGroup;
use App\Models\User;
use App\Services\AjoService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Groups extends Component
{
    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        /** @var User $user */
        $user = Auth::user();
        /** @var Agent|null $agent */
        $agent = $user->agent;

        $assignedGroups = collect();
        $ajoService = app(AjoService::class);

        if ($agent) {
            $query = AjoGroup::query()
                ->whereHas('agents', fn ($q) => $q->where('agent_id', $agent->id))
                ->orWhere('managing_agent_id', $agent->id)
                ->with(['managingAgent.user', 'members.user'])
                ->distinct();

            if ($this->search !== '') {
                $search = '%' . trim($this->search) . '%';
                $query->where('name', 'like', $search);
            }

            $groups = $query->latest()->get();

            $assignedGroups = $groups->map(function (AjoGroup $group) use ($ajoService, $agent) {
                $progress = $ajoService->getCycleProgress($group);
                $nextPayout = $ajoService->getNextPayout($group);
                $isPrimary = $group->managing_agent_id === $agent->id;

                return [
                    'group' => $group,
                    'progress' => $progress,
                    'next_payout' => $nextPayout,
                    'is_primary' => $isPrimary,
                ];
            });
        }

        return view('livewire.ajo-agent.groups', [
            'agent' => $agent,
            'assignedGroups' => $assignedGroups,
        ])->layout('components.layouts.ajo-agent');
    }
}
