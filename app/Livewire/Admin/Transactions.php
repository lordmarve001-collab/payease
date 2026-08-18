<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;

class Transactions extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';
    public string $typeFilter = 'all';
    public string $dateFilter = 'all';
    public ?string $selectedTransactionId = null;
    public bool $showTransactionModal = false;
    public bool $showReverseModal = false;
    public string $reversalReason = '';

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

    public function openReverseModal(string $transactionId): void
    {
        $this->selectedTransactionId = $transactionId;
        $this->showTransactionModal = false;
        $this->showReverseModal = true;
        $this->reversalReason = '';
    }

    public function closeReverseModal(): void
    {
        $this->showReverseModal = false;
        $this->reversalReason = '';
    }

    public function reverseSelectedTransaction(): void
    {
        $this->validate([
            'reversalReason' => ['required', 'string', 'min:5'],
        ], [
            'reversalReason.required' => 'A reversal reason is required.',
            'reversalReason.min' => 'Please provide a clearer reversal reason.',
        ]);

        $transaction = Transaction::with(['fromWallet.user', 'toWallet.user', 'agentUser'])->findOrFail((string) $this->selectedTransactionId);
        /** @var TransactionService $transactionService */
        $transactionService = app(TransactionService::class);

        try {
            $reversal = $transactionService->reverseTransaction($transaction, Auth::user(), $this->reversalReason);

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'transaction_reversed',
                'entity_type' => 'transaction',
                'entity_id' => $transaction->id,
                'old_values' => ['status' => $transaction->status],
                'new_values' => [
                    'status' => 'reversed',
                    'reason' => $this->reversalReason,
                    'reversal_transaction_id' => $reversal->id,
                    'reversal_reference' => $reversal->reference,
                ],
                'ip_address' => request()->ip(),
                'device_id' => request()->userAgent(),
            ]);

            $this->dispatch('notify-success', message: 'Transaction reversed successfully.');
            $this->closeReverseModal();
            $this->selectedTransactionId = $transaction->id;
            $this->showTransactionModal = true;
        } catch (RuntimeException $exception) {
            $this->addError('reversalReason', $exception->getMessage());
        }
    }

    public function render()
    {
        $transactions = Transaction::query()
            ->with(['fromWallet.user', 'toWallet.user', 'agentUser'])
            ->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->typeFilter !== 'all', fn ($query) => $query->where('transaction_type', $this->typeFilter))
            ->when($this->dateFilter === 'today', fn ($query) => $query->whereDate('created_at', today()))
            ->when($this->dateFilter === '24h', fn ($query) => $query->where('created_at', '>=', now()->subDay()))
            ->when($this->search, function ($q) {
                $q->where(function ($query): void {
                    $query->where('reference', 'like', "%{$this->search}%")
                        ->orWhere('recipient_phone', 'like', "%{$this->search}%")
                        ->orWhereHas('fromWallet.user', function ($q2) {
                            $q2->where('full_name', 'like', "%{$this->search}%");
                        })
                        ->orWhereHas('toWallet.user', function ($q3) {
                            $q3->where('full_name', 'like', "%{$this->search}%");
                        })
                        ->orWhereHas('agentUser', function ($q4) {
                            $q4->where('full_name', 'like', "%{$this->search}%");
                        });
                });
            })
            ->latest()
            ->paginate(10);

        return view('livewire.admin.transactions', [
            'transactions' => $transactions,
            'selectedTransaction' => $this->selectedTransactionId
                ? Transaction::with(['fromWallet.user', 'toWallet.user', 'agentUser'])->find($this->selectedTransactionId)
                : null,
        ])->layout('components.layouts.admin');
    }
}
