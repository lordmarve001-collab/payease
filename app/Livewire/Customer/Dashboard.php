<?php

namespace App\Livewire\Customer;

use App\Models\User;
use App\Services\TransactionService;
use App\Services\WalletService;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Dashboard extends Component
{
    public function render()
    {
        /** @var User $user */
        $user = Auth::user();
        $walletService = app(WalletService::class);
        $transactionService = app(TransactionService::class);

        $wallet = $walletService->getCustomerWallet($user);
        $recentTransactions = $transactionService->getRecentTransactions($user);
        $balance = $walletService->getBalance($user);
        $accountDisplay = $wallet ? $walletService->getAccountDisplay($wallet) : null;
        $kycUpgradeMessage = $walletService->getKycUpgradeMessage((int) $user->kyc_level);

        $currentTier = (int) $user->kyc_level;
        $nextTier = $currentTier < 3 ? $currentTier + 1 : null;
        $tierConfig = config('tiers.tiers', []);

        return view('livewire.customer.dashboard', [
            'user' => $user,
            'wallet' => $wallet,
            'balance' => $balance,
            'accountDisplay' => $accountDisplay,
            'recentTransactions' => $recentTransactions,
            'kycUpgradeMessage' => $kycUpgradeMessage,
            'currentTier' => $currentTier,
            'nextTier' => $nextTier,
            'tierConfig' => $tierConfig,
        ])->layout('components.layouts.customer');
    }
}
