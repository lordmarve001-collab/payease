<?php

namespace App\Livewire\Agent;

use App\Models\Agent;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Transactions extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';
    public string $typeFilter = 'all';
    public string $dateFilter = 'all';
    public ?string $selectedTransactionId = null;
    public bool $showTransactionModal = false;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDateFilter(): void
    {
        $this->resetPage();
    }

    public function viewTransaction(string $transactionId): void
    {
        $this->selectedTransactionId = $transactionId;
        $this->showTransactionModal = true;
    }

    public function closeTransactionModal(): void
    {
        $this->showTransactionModal = false;
    }

    public function render()
    {
        /** @var User $user */
        $user = Auth::user();
        /** @var Agent $agent */
        $agent = $user->agent;

        $transactions = Transaction::query()
            ->with(['fromWallet.user', 'toWallet.user', 'agentUser'])
            ->where('agent_id', $agent->user_id)
            ->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->typeFilter !== 'all', fn ($query) => $query->where('transaction_type', $this->typeFilter))
            ->when($this->dateFilter === 'today', fn ($query) => $query->whereDate('created_at', today()))
            ->when($this->dateFilter === '24h', fn ($query) => $query->where('created_at', '>=', now()->subDay()))
            ->when($this->dateFilter === 'week', fn ($query) => $query->where('created_at', '>=', now()->subWeek()))
            ->when($this->dateFilter === 'month', fn ($query) => $query->where('created_at', '>=', now()->subMonth()))
            ->when($this->search, function ($q) {
                $q->where(function ($query): void {
                    $query->where('reference', 'like', "%{$this->search}%")
                        ->orWhere('recipient_phone', 'like', "%{$this->search}%")
                        ->orWhereHas('fromWallet.user', function ($q2) {
                            $q2->where('full_name', 'like', "%{$this->search}%");
                        })
                        ->orWhereHas('toWallet.user', function ($q3) {
                            $q3->where('full_name', 'like', "%{$this->search}%");
                        });
                });
            })
            ->latest()
            ->paginate(10);

        return view('livewire.agent.transactions', [
            'transactions' => $transactions,
            'selectedTransaction' => $this->selectedTransactionId
                ? Transaction::with(['fromWallet.user', 'toWallet.user', 'agentUser'])->find($this->selectedTransactionId)
                : null,
        ])->layout('components.layouts.agent');
    }
}
