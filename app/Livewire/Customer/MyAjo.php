<?php

namespace App\Livewire\Customer;

use App\Models\AjoMember;
use App\Services\AjoService;
use App\Services\WalletService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class MyAjo extends Component
{
    protected AjoService $ajoService;
    protected WalletService $walletService;

    public function boot(AjoService $ajoService, WalletService $walletService): void
    {
        $this->ajoService = $ajoService;
        $this->walletService = $walletService;
    }

    public function render()
    {
        $user = Auth::user();
        $wallet = $this->walletService->getCustomerWallet($user);

        $memberships = AjoMember::where('user_id', $user->id)
            ->whereHas('group', function ($q) {
                $q->whereIn('status', ['active', 'pending']);
            })
            ->with('group.ajoOwner.user')
            ->get();

        $groups = $memberships->map(function (AjoMember $membership) {
            $group = $membership->group;
            return [
                'membership' => $membership,
                'group' => $group,
                'owner' => $group->ajoOwner,
                'progress' => $this->ajoService->getCycleProgress($group),
                'nextPayout' => $this->ajoService->getNextPayout($group),
            ];
        });

        return view('livewire.customer.my-ajo', [
            'wallet' => $wallet,
            'groups' => $groups,
        ])->layout('components.layouts.customer');
    }
}
