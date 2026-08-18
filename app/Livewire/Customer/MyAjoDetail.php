<?php

namespace App\Livewire\Customer;

use App\Models\AjoMember;
use App\Models\AjoContribution;
use App\Models\AjoPayout;
use App\Services\AjoService;
use App\Services\WalletService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use RuntimeException;

class MyAjoDetail extends Component
{
    public string $groupId;
    public string $contributionMessage = '';

    protected AjoService $ajoService;
    protected WalletService $walletService;

    public function boot(AjoService $ajoService, WalletService $walletService): void
    {
        $this->ajoService = $ajoService;
        $this->walletService = $walletService;
    }

    public function mount(string $id): void
    {
        $this->groupId = $id;
    }

    public function payContribution()
    {
        $this->contributionMessage = '';
        $user = Auth::user();
        $membership = AjoMember::where('user_id', $user->id)
            ->where('group_id', $this->groupId)
            ->firstOrFail();

        $group = $membership->group;
        $amount = (float) $group->contribution_amount;

        $wallet = $this->walletService->getCustomerWallet($user);
        if (!$wallet || (float) $wallet->available_balance < $amount) {
            $this->contributionMessage = 'Insufficient wallet balance. Please add money first.';
            return;
        }

        // Check if already paid
        $cycleNumber = $this->ajoService->getCurrentCycleNumber($group);
        $alreadyPaid = AjoContribution::where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->where('cycle_number', $cycleNumber)
            ->exists();

        if ($alreadyPaid) {
            $this->contributionMessage = 'You have already paid for this cycle.';
            return;
        }

        try {
            $contribution = $this->recordSelfContribution($user, $group, $amount, $cycleNumber);

            // Debit the user's wallet
            $wallet->balance = round((float) $wallet->balance - $amount, 2);
            $wallet->available_balance = round((float) $wallet->available_balance - $amount, 2);
            $wallet->save();

            $this->contributionMessage = 'Contribution of ₦' . number_format($amount, 0) . ' paid successfully!';
            $this->dispatch('notify-success', message: $this->contributionMessage);
        } catch (RuntimeException $e) {
            $this->contributionMessage = $e->getMessage();
            $this->dispatch('notify-error', message: $this->contributionMessage);
        }
    }

    protected function recordSelfContribution($user, $group, float $amount, int $cycleNumber): AjoContribution
    {
        return AjoContribution::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'logged_by_agent_id' => null,
            'amount' => $amount,
            'cycle_number' => $cycleNumber,
            'status' => 'completed',
            'transaction_id' => null,
        ]);
    }

    public function render()
    {
        $user = Auth::user();
        $wallet = $this->walletService->getCustomerWallet($user);

        $membership = AjoMember::where('user_id', $user->id)
            ->where('group_id', $this->groupId)
            ->whereHas('group', function ($q) {
                $q->whereIn('status', ['active', 'pending']);
            })
            ->with('group.ajoOwner.user', 'group.members.user', 'group.managingAgent.user')
            ->firstOrFail();

        $group = $membership->group;
        $progress = $this->ajoService->getCycleProgress($group);
        $nextPayout = $this->ajoService->getNextPayout($group);
        $contributions = $this->ajoService->getContributionHistory($group);

        $myContributions = AjoContribution::where('user_id', $user->id)
            ->where('group_id', $group->id)
            ->orderByDesc('cycle_number')
            ->with('transaction')
            ->get();

        $myPayouts = AjoPayout::where('user_id', $user->id)
            ->where('group_id', $group->id)
            ->orderByDesc('cycle_number')
            ->with('transaction')
            ->get();

        // Current cycle contributions for member list
        $currentCycleNumber = $progress['cycle_number'];
        $currentContributions = AjoContribution::where('group_id', $group->id)
            ->where('cycle_number', $currentCycleNumber)
            ->get()
            ->keyBy('user_id');

        return view('livewire.customer.my-ajo-detail', [
            'wallet' => $wallet,
            'membership' => $membership,
            'group' => $group,
            'progress' => $progress,
            'nextPayout' => $nextPayout,
            'myContributions' => $myContributions,
            'myPayouts' => $myPayouts,
            'currentContributions' => $currentContributions,
        ])->layout('components.layouts.customer');
    }
}
