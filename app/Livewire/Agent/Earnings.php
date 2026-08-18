<?php

namespace App\Livewire\Agent;

use App\Models\Agent;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use RuntimeException;

class Earnings extends Component
{
    public string $filter = 'month';

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
    }

    public function withdrawEarnings(): void
    {
        /** @var User $user */
        $user = Auth::user();
        /** @var Agent $agent */
        $agent = $user->agent;
        /** @var TransactionService $transactionService */
        $transactionService = app(TransactionService::class);

        try {
            $transactionService->withdrawAgentEarnings($agent);
            $this->dispatch('notify-success', message: 'Earnings withdrawn successfully.');
        } catch (RuntimeException $exception) {
            $this->dispatch('notify-error', message: $exception->getMessage());
        }
    }

    public function render()
    {
        /** @var User $user */
        $user = Auth::user();
        /** @var Agent $agent */
        $agent = $user->agent;
        /** @var TransactionService $transactionService */
        $transactionService = app(TransactionService::class);

        $summary = $transactionService->getAgentEarnings($agent, $this->filter);

        return view('livewire.agent.earnings', [
            'agent' => $agent,
            'transactions' => $summary['transactions'],
            'availableEarnings' => $summary['available_to_withdraw'],
            'periodTotal' => $summary['period_total'],
            'earningsBreakdown' => $summary['breakdown'],
        ])->layout('components.layouts.agent');
    }
}
