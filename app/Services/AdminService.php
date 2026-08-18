<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\KycDocument;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminService
{
    public function getOverviewKpis(): array
    {
        $currentUsers = User::count();
        $previousUsers = User::where('created_at', '<', now()->subDays(7))->count();

        $currentDailyTransactions = Transaction::whereDate('created_at', today())->count();
        $previousDailyTransactions = Transaction::whereDate('created_at', today()->subDay())->count();

        $currentRevenue = (float) Transaction::whereDate('created_at', today())
            ->where('status', 'completed')
            ->sum('fee');
        $previousRevenue = (float) Transaction::whereDate('created_at', today()->subDay())
            ->where('status', 'completed')
            ->sum('fee');

        $currentActiveAgents = Agent::where('status', 'active')->count();
        $previousActiveAgents = Agent::where('status', 'active')
            ->where('created_at', '<', now()->subDays(7))
            ->count();

        return [
            'total_users' => $this->formatKpi($currentUsers, $previousUsers, false),
            'daily_transactions' => $this->formatKpi($currentDailyTransactions, $previousDailyTransactions, false),
            'revenue' => $this->formatKpi($currentRevenue, $previousRevenue, true),
            'active_agents' => $this->formatKpi($currentActiveAgents, $previousActiveAgents, false),
        ];
    }

    public function getTransactionVolumeChart(): array
    {
        $start = today()->subDays(6);
        $counts = Transaction::query()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as aggregate')
            ->whereDate('created_at', '>=', $start)
            ->groupBy('day')
            ->pluck('aggregate', 'day');

        $labels = [];
        $data = [];

        foreach (range(0, 6) as $offset) {
            $date = $start->copy()->addDays($offset);
            $key = $date->toDateString();
            $labels[] = $date->format('D');
            $data[] = (int) ($counts[$key] ?? 0);
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    public function getAgentPerformanceChart(int $limit = 5): array
    {
        $rows = Transaction::query()
            ->selectRaw('agent_id, COUNT(*) as aggregate')
            ->whereNotNull('agent_id')
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->groupBy('agent_id')
            ->orderByDesc('aggregate')
            ->limit($limit)
            ->get();

        $labels = [];
        $data = [];

        foreach ($rows as $row) {
            $agentUser = User::find($row->agent_id);
            $labels[] = $agentUser?->full_name ?? 'Unknown Agent';
            $data[] = (int) $row->aggregate;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    public function getRecentAlerts(): array
    {
        $pendingAgents = Agent::where('status', 'pending')->count();
        $lowFloatAgents = Agent::query()
            ->whereColumn('float_balance', '<', \DB::raw('max_float * 0.2'))
            ->count();
        $recentFailedTransactions = Transaction::query()
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subDay())
            ->count();

        $alerts = collect();

        // High-volume agent registration fraud monitoring
        $highVolumeAgents = Agent::query()
            ->select('agents.id', 'agents.user_id')
            ->with('user')
            ->get()
            ->filter(function (Agent $agent): bool {
                return User::where('registered_by_agent_id', $agent->id)
                    ->whereDate('created_at', today())
                    ->count() > 20;
            });

        foreach ($highVolumeAgents as $agent) {
            $count = User::where('registered_by_agent_id', $agent->id)
                ->whereDate('created_at', today())
                ->count();
            $alerts->push([
                'severity' => 'warning',
                'title' => "Agent {$agent->user?->full_name} registered {$count} customers today",
                'message' => 'This agent has exceeded the 20-customer daily threshold. Review recent registrations for potential fraud.',
            ]);
        }

        if ($pendingAgents > 0) {
            $alerts->push([
                'severity' => 'info',
                'title' => $pendingAgents . ' agent approval' . ($pendingAgents === 1 ? '' : 's') . ' pending',
                'message' => 'Pending agents are waiting for approval before Cash In and Cash Out unlock.',
            ]);
        }

        if ($lowFloatAgents > 0) {
            $alerts->push([
                'severity' => 'warning',
                'title' => $lowFloatAgents . ' agent' . ($lowFloatAgents === 1 ? '' : 's') . ' below float threshold',
                'message' => 'These agents are below 20% of max float and may need a float top-up soon.',
            ]);
        }

        if ($recentFailedTransactions > 0) {
            $alerts->push([
                'severity' => $recentFailedTransactions > 5 ? 'critical' : 'warning',
                'title' => $recentFailedTransactions . ' failed transaction' . ($recentFailedTransactions === 1 ? '' : 's') . ' in the last 24 hours',
                'message' => $recentFailedTransactions > 5
                    ? 'Failure volume has crossed the critical threshold and needs investigation.'
                    : 'A small number of recent failed transactions were detected.',
            ]);
        }

        if ($alerts->isEmpty()) {
            $alerts->push([
                'severity' => 'info',
                'title' => 'No critical admin alerts right now',
                'message' => 'Approval queue, float levels, and failure volume are within normal bounds.',
            ]);
        }

        return $alerts->values()->all();
    }

    public function getPendingKycCount(): array
    {
        $grouped = KycDocument::query()
            ->selectRaw('verification_status, COUNT(*) as aggregate')
            ->groupBy('verification_status')
            ->pluck('aggregate', 'verification_status');

        return [
            'pending' => (int) ($grouped['pending'] ?? 0),
            'verified' => (int) ($grouped['verified'] ?? 0),
            'rejected' => (int) ($grouped['rejected'] ?? 0),
        ];
    }

    protected function formatKpi(float|int $current, float|int $previous, bool $currency): array
    {
        $direction = $current >= $previous ? 'up' : 'down';
        $formattedValue = $currency
            ? '₦' . number_format((float) $current, 2)
            : number_format((float) $current, (float) $current === floor((float) $current) ? 0 : 2);

        return [
            'value' => $formattedValue,
            'raw' => $current,
            'trend' => $this->formatTrend($current, $previous),
            'direction' => $direction,
        ];
    }

    protected function formatTrend(float|int $current, float|int $previous): string
    {
        if ((float) $previous === 0.0) {
            if ((float) $current === 0.0) {
                return '0%';
            }

            return '100%';
        }

        $percent = round((((float) $current - (float) $previous) / (float) $previous) * 100, 1);

        return abs($percent) . '%';
    }
}
