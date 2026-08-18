<?php

namespace App\Livewire\Customer;

use App\Models\User;
use App\Services\WalletService;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class TransactionLimits extends Component
{
    public array $limits = [];
    public ?array $walletLimits = null;

    public function boot(WalletService $walletService)
    {
        $user = Auth::user();
        $tiers = config('tiers.tiers', []);
        $currentLevel = (int) $user->kyc_level;
        $this->limits = $tiers;

        $wallet = $walletService->getCustomerWallet($user);
        if ($wallet) {
            $this->walletLimits = [
                'daily_limit' => (float) $wallet->daily_limit,
                'single_txn_limit' => (float) $wallet->single_txn_limit,
            ];
        }
    }

    public function render()
    {
        return view('livewire.customer.transaction-limits', [
            'currentLevel' => (int) Auth::user()->kyc_level,
        ])->layout('components.layouts.customer');
    }
}
