<?php

namespace App\Livewire\AjoOwner;

use App\Models\User;
use App\Models\Transaction;
use App\Services\MonnifyClient;
use App\Services\MonnifyWalletProvisioning;
use App\Services\MmoProviderSettingService;
use App\Services\WalletService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;

class AddFund extends Component
{
    public ?array $accountDisplay = null;
    public ?float $monnifyBalance = null;
    public bool $isSyncing = false;
    public string $cardAmount = '';
    public bool $isInitiatingPayment = false;

    public function updatedCardAmount($value): void
    {
        $this->cardAmount = preg_replace('/[^0-9]/', '', (string) $value) ?? '';
    }

    public function boot(WalletService $walletService): void
    {
        $user = Auth::user();
        $wallet = $walletService->getCustomerWallet($user);
        $this->accountDisplay = $wallet ? $walletService->getAccountDisplay($wallet) : null;

        if ($this->isWalletProvisioned($wallet)) {
            try {
                $providerService = app(MmoProviderSettingService::class);
                $setting = $providerService->getProviderSetting('monnify');
                $credentials = is_array($setting->credentials) ? $setting->credentials : [];
                $client = new MonnifyClient($credentials, (string) $setting->environment);
                $this->monnifyBalance = $client->getBalance((string) $wallet->provider_reference);
            } catch (\Exception $e) {
                Log::channel('monnify')->warning('Could not fetch Monnify balance on add-fund page', [
                    'user_id' => Auth::id(),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

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

            if (!$this->isWalletProvisioned($wallet)) {
                $provisioning = app(MonnifyWalletProvisioning::class);
                $wallet = $provisioning->provisionReservedAccount($user);

                if (!$wallet || !$wallet->account_number) {
                    $this->dispatch('notify-error', message: 'Your wallet account is not active yet. Complete your KYC verification (NIN/BVN) to activate your account number.');
                    return;
                }
            }

            $providerService = app(MmoProviderSettingService::class);
            $setting = $providerService->getProviderSetting('monnify');
            $credentials = is_array($setting->credentials) ? $setting->credentials : [];
            $client = new MonnifyClient($credentials, (string) $setting->environment);

            $this->monnifyBalance = $client->getBalance((string) $wallet->provider_reference);

            $wallet->update([
                'balance' => round($this->monnifyBalance, 2),
                'available_balance' => round($this->monnifyBalance, 2),
            ]);

            $this->accountDisplay = $walletService->getAccountDisplay($wallet);
            $this->dispatch('notify-success', message: 'Balance synced — ₦' . number_format($this->monnifyBalance, 2));
        } catch (\Exception $e) {
            Log::channel('monnify')->error('Ajo owner add-fund sync failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('notify-error', message: 'Sync failed: ' . $e->getMessage());
        } finally {
            $this->isSyncing = false;
        }
    }

    public function payWithCard(): void
    {
        $this->validate([
            'cardAmount' => ['required', 'numeric', 'min:100', 'max:500000'],
        ]);

        $this->isInitiatingPayment = true;

        try {
            /** @var User $user */
            $user = Auth::user();

            $providerService = app(MmoProviderSettingService::class);
            $setting = $providerService->getProviderSetting('monnify');
            $credentials = is_array($setting->credentials) ? $setting->credentials : [];
            $client = new MonnifyClient($credentials, (string) $setting->environment);

            $walletService = app(WalletService::class);
            $wallet = $walletService->getCustomerWallet($user);

            if (!$wallet) {
                $this->dispatch('notify-error', message: 'No wallet found.');
                return;
            }

            do {
                $paymentReference = 'FundWallet-' . Str::upper(Str::random(16));
            } while (Transaction::where('reference', $paymentReference)->exists());

            $redirectUrl = route('payment.callback');

            $result = $client->initiateTransaction(
                amount: (float) $this->cardAmount,
                customerName: $user->full_name,
                customerEmail: $user->email ?? $user->phone_number . '@payease.local',
                paymentReference: $paymentReference,
                paymentDescription: 'Fund PayEase Wallet — ₦' . number_format((float) $this->cardAmount, 2),
                redirectUrl: $redirectUrl,
                metadata: [
                    'user_id' => $user->id,
                    'purpose' => 'wallet_funding',
                ],
            );

            $checkoutUrl = $result['checkoutUrl'] ?? $result['redirectUrl'] ?? null;

            if (!$checkoutUrl) {
                $this->dispatch('notify-error', message: 'Could not initialize payment. Please try again.');
                return;
            }

            Transaction::create([
                'reference' => $paymentReference,
                'transaction_type' => 'wallet_funding',
                'amount' => (float) $this->cardAmount,
                'status' => 'pending',
                'to_wallet_id' => $wallet->id,
                'description' => 'Card funding via Monnify',
                'mmo_partner' => 'monnify',
                'channel' => 'web',
                'metadata' => [
                    'user_id' => $user->id,
                    'initiated_via' => 'card_payment',
                    'monnify_response' => $result,
                ],
            ]);

            \App\Models\AuditLog::create([
                'user_id' => $user->id,
                'action' => 'card_payment_initiated',
                'entity_type' => 'transaction',
                'entity_id' => $paymentReference,
                'new_values' => [
                    'amount' => (float) $this->cardAmount,
                    'reference' => $paymentReference,
                ],
                'ip_address' => request()->ip(),
                'device_id' => request()->userAgent(),
            ]);

            $this->dispatch('redirect-to-monnify', url: $checkoutUrl);
        } catch (\Exception $e) {
            Log::channel('monnify')->error('Card payment initiation failed', [
                'user_id' => Auth::id(),
                'amount' => $this->cardAmount,
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('notify-error', message: 'Payment initiation failed: ' . $e->getMessage());
        } finally {
            $this->isInitiatingPayment = false;
        }
    }

    public function render()
    {
        $user = Auth::user();
        $walletService = app(WalletService::class);
        $wallet = $walletService->getCustomerWallet($user);

        return view('livewire.ajo-owner.add-fund', [
            'wallet' => $wallet,
            'accountDisplay' => $this->accountDisplay,
            'monnifyBalance' => $this->monnifyBalance,
        ])->layout('components.layouts.ajo-owner');
    }

    protected function isWalletProvisioned($wallet): bool
    {
        if (!$wallet) return false;

        $mmoId = (string) ($wallet->provider_reference ?? '');

        return filled($wallet->account_number)
            && $mmoId !== ''
            && !str_starts_with($mmoId, 'PENDING')
            && !str_starts_with($mmoId, 'WALLET-');
    }
}
