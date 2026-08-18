<?php

namespace App\Livewire\AjoAgent;

use App\Models\Agent;
use App\Models\AjoContribution;
use App\Models\AjoGroup;
use App\Models\User;
use App\Services\AjoService;
use App\Services\WalletService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        /** @var User $user */
        $user = Auth::user();
        /** @var Agent|null $agent */
        $agent = $user->agent;

        $walletService = app(WalletService::class);
        $wallet = $agent ? $walletService->getAgentWallet($user) : null;

        if (!$agent) {
            return view('livewire.ajo-agent.dashboard', [
                'user' => $user,
                'agent' => null,
                'wallet' => null,
                'assignedGroups' => collect(),
                'stats' => ['total_groups' => 0, 'active_groups' => 0, 'total_members' => 0, 'total_collected' => 0, 'float_balance' => 0, 'wallet_balance' => 0],
            ])->layout('components.layouts.ajo-agent');
        }

        $ajoService = app(AjoService::class);

        $assignedGroups = AjoGroup::query()
            ->whereHas('agents', function ($query) use ($agent): void {
                $query->where('agent_id', $agent->id);
            })
            ->with(['managingAgent.user', 'members.user', 'payouts'])
            ->get();

        $activeGroups = $assignedGroups->where('status', 'active');
        $pendingGroups = $assignedGroups->where('status', 'pending');

        $groupSummaries = $assignedGroups->map(function (AjoGroup $group) use ($ajoService, $agent) {
            $progress = $ajoService->getCycleProgress($group);
            $nextPayout = $ajoService->getNextPayout($group);
            $pendingMembers = $ajoService->getMembersPendingForCurrentCycle($group);
            $isPrimaryAgent = $group->managing_agent_id === $agent->id;

            return [
                'group' => $group,
                'progress' => $progress,
                'next_payout' => $nextPayout,
                'pending_members' => $pendingMembers,
                'is_primary' => $isPrimaryAgent,
            ];
        });

        $totalMembers = $assignedGroups->sum('members_count');
        $totalCollected = (float) AjoContribution::query()
            ->whereIn('group_id', $assignedGroups->pluck('id'))
            ->where('logged_by_agent_id', $agent->id)
            ->sum('amount');

        $stats = [
            'total_groups' => $assignedGroups->count(),
            'active_groups' => $activeGroups->count(),
            'pending_groups' => $pendingGroups->count(),
            'total_members' => $totalMembers,
            'total_collected' => $totalCollected,
            'float_balance' => (float) $agent->float_balance,
            'wallet_balance' => (float) ($wallet->available_balance ?? 0),
        ];

        return view('livewire.ajo-agent.dashboard', [
            'user' => $user,
            'agent' => $agent,
            'wallet' => $wallet,
            'assignedGroups' => $groupSummaries,
            'stats' => $stats,
        ])->layout('components.layouts.ajo-agent');
    }
}
