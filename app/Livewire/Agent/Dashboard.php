<?php

namespace App\Livewire\Agent;

use App\Models\Agent;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use App\Services\WalletService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        /** @var User $user */
        $user = Auth::user();
        /** @var Agent $agent */
        $agent = $user->agent;
        /** @var TransactionService $transactionService */
        $transactionService = app(TransactionService::class);
        /** @var WalletService $walletService */
        $walletService = app(WalletService::class);

        $wallet = $walletService->getAgentWallet($user);
        $recentTransactions = $transactionService->getAgentRecentTransactions($agent);
        $todaySummary = $transactionService->getAgentEarnings($agent, 'today');
        $isLowFloat = $agent->max_float > 0
            ? (((float) $agent->float_balance / (float) $agent->max_float) < 0.2)
            : false;

        $registeredCustomersCount = \App\Models\User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'customer'))
            ->where('registered_by_agent_id', $agent->id)
            ->count();

        $todayTransactionCount = Transaction::query()
            ->where('agent_id', $agent->id)
            ->whereDate('created_at', today())
            ->count();

        return view('livewire.agent.dashboard', [
            'user' => $user,
            'agent' => $agent,
            'wallet' => $wallet,
            'recentTransactions' => $recentTransactions,
            'todayEarnings' => $todaySummary['period_total'] ?? 0,
            'isLowFloat' => $isLowFloat,
            'registeredCustomersCount' => $registeredCustomersCount,
            'todayTransactionCount' => $todayTransactionCount,
        ])->layout('components.layouts.agent');
    }
}
