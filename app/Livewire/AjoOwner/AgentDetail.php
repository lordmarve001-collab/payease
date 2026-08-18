<?php

namespace App\Livewire\AjoOwner;

use App\Models\Agent;
use App\Models\AjoContribution;
use App\Models\AjoGroup;
use App\Models\AuditLog;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AgentDetail extends Component
{
    public string $agentId = '';

    public function mount(string $id): void
    {
        $this->agentId = $id;
    }

    public function render()
    {
        /** @var Agent $agent */
        $agent = Agent::query()
            ->where('id', $this->agentId)
            ->where('ajo_owner_id', Auth::user()->ajoOwner?->id)
            ->with(['user', 'managingAjoGroups', 'assignedGroups', 'settlements'])
            ->withCount(['managingAjoGroups', 'assignedGroups'])
            ->first();

        abort_unless($agent, 404);

        $managingGroupIds = $agent->managingAjoGroups->pluck('id');
        $allGroupIds = $agent->assignedGroups->pluck('id')->merge($managingGroupIds)->unique();

        $totalCollected = 0;
        $thisMonthCollected = 0;
        $totalPayouts = 0;
        $recentTransactions = collect();

        if ($allGroupIds->isNotEmpty()) {
            $totalCollected = (float) AjoContribution::whereIn('group_id', $allGroupIds)
                ->where('agent_id', $agent->id)
                ->sum('amount');

            $thisMonthCollected = (float) AjoContribution::whereIn('group_id', $allGroupIds)
                ->where('agent_id', $agent->id)
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('amount');

            $totalPayouts = (float) \App\Models\AjoPayout::whereIn('group_id', $allGroupIds)
                ->where('agent_id', $agent->id)
                ->where('status', 'completed')
                ->sum('amount');

            $recentTransactions = Transaction::query()
                ->where(function ($q) use ($agent) {
                    $q->where('agent_id', $agent->id)
                      ->orWhere('initiated_by_agent_id', $agent->id);
                })
                ->latest()
                ->limit(10)
                ->get();
        }

        $recentAuditLogs = AuditLog::query()
            ->where('entity_type', 'agent')
            ->where('entity_id', $agent->id)
            ->latest()
            ->limit(10)
            ->get();

        return view('livewire.ajo-owner.agent-detail', [
            'agent' => $agent,
            'totalCollected' => $totalCollected,
            'thisMonthCollected' => $thisMonthCollected,
            'totalPayouts' => $totalPayouts,
            'recentTransactions' => $recentTransactions,
            'recentAuditLogs' => $recentAuditLogs,
        ])->layout('components.layouts.ajo-owner');
    }
}
