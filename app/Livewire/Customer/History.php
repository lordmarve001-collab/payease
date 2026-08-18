<?php

namespace App\Livewire\Customer;

use App\Models\User;
use App\Services\TransactionService;
use App\Services\WalletService;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class History extends Component
{
    use WithPagination;

    public $filter = 'all'; // all, credit, debit, failed

    public function updatingFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        /** @var User $user */
        $user = Auth::user();
        $walletService = app(WalletService::class);
        $transactionService = app(TransactionService::class);

        $wallet = $walletService->getCustomerWallet($user);
        $paginator = $transactionService->getTransactionHistory(
            $user,
            $this->filter === 'all' ? null : $this->filter
        );
        $transactions = $paginator->getCollection()->groupBy(function ($transaction) {
            return $transaction->created_at->toDateString();
        });

        return view('livewire.customer.history', [
            'wallet' => $wallet,
            'transactions' => $transactions,
            'paginator' => $paginator,
        ])->layout('components.layouts.customer');
    }
}
