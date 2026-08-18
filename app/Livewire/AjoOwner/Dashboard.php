<?php

namespace App\Livewire\AjoOwner;

use App\Models\AjoContribution;
use App\Models\AjoGroup;
use App\Models\AjoMember;
use App\Models\AjoOwner;
use App\Models\User;
use App\Services\AjoService;
use App\Services\MonnifyWalletProvisioning;
use App\Services\MmoProviderSettingService;
use App\Services\MonnifyClient;
use App\Services\WalletService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class Dashboard extends Component
{
    public bool $isSyncing = false;

    public function syncBalance(): void
    {
        $this->isSyncing = true;

        try {
            /** @var User $user */
            $user = Auth::user();
            $walletService = app(WalletService::class);
            $wallet = $walletService->getCustomerWallet($user);

            if (!$wallet) {
                $this->dispatch('notify-error', message: 'No wallet found.');
                return;
            }

            // Check if wallet has a valid Monnify reserved account
            $hasValidAccount = filled($wallet->account_number)
                && $wallet->account_number !== ''
                && !str_starts_with((string) $wallet->mmo_wallet_id, 'PENDING')
                && !str_starts_with((string) $wallet->mmo_wallet_id, 'WALLET-');

            if (!$hasValidAccount) {
                // Wallet was never properly provisioned — attempt provisioning
                $provisioning = app(MonnifyWalletProvisioning::class);
                $wallet = $provisioning->provisionReservedAccount($user);

                if (!$wallet || !$wallet->account_number) {
                    $this->dispatch('notify-error', message: 'Your wallet account has not been provisioned yet. Please complete your KYC (NIN/BVN) to activate your account number.');
                    return;
                }
            }

            // Fetch live balance from Monnify
            $providerService = app(MmoProviderSettingService::class);
            $setting = $providerService->getProviderSetting('monnify');
            $credentials = is_array($setting->credentials) ? $setting->credentials : [];
            $client = new MonnifyClient($credentials, (string) $setting->environment);

            $monnifyBalance = $client->getBalance((string) $wallet->provider_reference);

            $wallet->update([
                'balance' => round($monnifyBalance, 2),
                'available_balance' => round($monnifyBalance, 2),
            ]);

            Log::channel('monnify')->info('Ajo owner balance synced', [
                'user_id' => $user->id,
                'monnify_balance' => $monnifyBalance,
            ]);

            $this->dispatch('notify-success', message: 'Balance synced — ₦' . number_format($monnifyBalance, 2));
        } catch (\Exception $e) {
            Log::channel('monnify')->error('Ajo owner balance sync failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('notify-error', message: 'Sync failed: ' . $e->getMessage());
        } finally {
            $this->isSyncing = false;
        }
    }

    public function render()
    {
        /** @var User $user */
        $user = Auth::user();
        /** @var AjoOwner $ajoOwner */
        $ajoOwner = $user->ajoOwner;

        $walletService = app(WalletService::class);
        $wallet = $walletService->getCustomerWallet($user);
        $accountDisplay = $wallet ? $walletService->getAccountDisplay($wallet) : null;
        $balance = $wallet ? (float) $wallet->available_balance : 0;
        $kycMessage = $walletService->getKycUpgradeMessage((int) $user->kyc_level);

        $currentTier = (int) $user->kyc_level;
        $tierConfig = config('tiers.tiers', []);
        $nextTier = $currentTier < 3 ? $currentTier + 1 : null;

        /** @var AjoService $ajoService */
        $ajoService = app(AjoService::class);

        $groups = AjoGroup::query()
            ->where('ajo_owner_id', $ajoOwner->id)
            ->with(['managingAgent.user', 'members.user', 'payouts'])
            ->get();
        $groupIds = $groups->pluck('id');
        $totalGroups = $groups->count();

        $totalMembers = $groupIds->isEmpty()
            ? 0
            : AjoMember::query()->whereIn('group_id', $groupIds)->count();

        $activeGroupIds = $groups->where('status', 'active')->pluck('id');
        $totalPoolValue = $activeGroupIds->isEmpty()
            ? 0
            : (float) AjoContribution::query()->whereIn('group_id', $activeGroupIds)->sum('amount');

        $thisMonthsCollections = $groupIds->isEmpty()
            ? 0
            : (float) AjoContribution::query()
                ->whereIn('group_id', $groupIds)
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('amount');

        $attentionGroups = $groups
            ->map(function (AjoGroup $group) use ($ajoService): array {
                $progress = $ajoService->getCycleProgress($group);
                $nextPayout = $ajoService->getNextPayout($group);

                return [
                    'group' => $group,
                    'progress' => $progress,
                    'next_payout' => $nextPayout,
                    'needs_attention' => (
                        (!$progress['is_complete'] && ($nextPayout['scheduled_date'] ?? null) && $nextPayout['scheduled_date']->lt(now()))
                        || ($nextPayout['is_due_within_48_hours'] ?? false)
                    ),
                ];
            })
            ->filter(fn (array $item): bool => $item['needs_attention'])
            ->sortBy(fn (array $item) => $item['next_payout']['scheduled_date'])
            ->values();

        return view('livewire.ajo-owner.dashboard', [
            'user' => $user,
            'ajoOwner' => $ajoOwner,
            'wallet' => $wallet,
            'accountDisplay' => $accountDisplay,
            'balance' => $balance,
            'kycMessage' => $kycMessage,
            'currentTier' => $currentTier,
            'tierConfig' => $tierConfig,
            'nextTier' => $nextTier,
            'groups' => $groups,
            'totalGroups' => $totalGroups,
            'totalMembers' => $totalMembers,
            'totalPoolValue' => $totalPoolValue,
            'thisMonthsCollections' => $thisMonthsCollections,
            'attentionGroups' => $attentionGroups,
        ])->layout('components.layouts.ajo-owner');
    }
}
