<?php

namespace App\Livewire\Customer;

use App\Models\Transaction;
use App\Models\User;
use App\Services\MonnifyClient;
use App\Services\MmoProviderSettingService;
use App\Services\WalletService;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AddMoney extends Component
{
    public ?array $accountDisplay = null;
    public ?float $monnifyBalance = null;
    public string $cardAmount = '';
    public string $cardError = '';
    public string $cardSuccess = '';
    public bool $isInitiatingPayment = false;
    public bool $isSyncing = false;

    protected $rules = [
        'cardAmount' => 'required|numeric|min:100|max:1000000',
    ];

    public function boot(WalletService $walletService)
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
                Log::channel('monnify')->warning('Could not fetch Monnify balance on customer add-money page', [
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
            $user = Auth::user();
            $walletService = app(WalletService::class);
            $wallet = $walletService->getCustomerWallet($user);

            if (!$wallet) {
                $this->dispatch('notify-error', message: 'No wallet found.');
                return;
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
            Log::channel('monnify')->error('Customer add-money sync failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('notify-error', message: 'Sync failed: ' . $e->getMessage());
        } finally {
            $this->isSyncing = false;
        }
    }

    public function initiateCardPayment()
    {
        $this->validate();

        $this->cardError = '';
        $this->cardSuccess = '';
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
                $this->cardError = 'No wallet found.';
                $this->isInitiatingPayment = false;
                return null;
            }

            do {
                $paymentReference = 'CardFund-' . Str::upper(Str::random(16));
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
                $this->cardError = 'Could not initialize payment. Please try again.';
                $this->isInitiatingPayment = false;
                return null;
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

            $this->isInitiatingPayment = false;

            return [
                'reference' => $paymentReference,
                'checkout_url' => $checkoutUrl,
            ];
        } catch (\Exception $e) {
            Log::channel('monnify')->error('Customer card payment initiation failed', [
                'user_id' => Auth::id(),
                'amount' => $this->cardAmount,
                'error' => $e->getMessage(),
            ]);
            $this->cardError = 'Payment initiation failed: ' . $e->getMessage();
            $this->isInitiatingPayment = false;
            return null;
        }
    }

    public function verifyCardPayment(string $reference)
    {
        $transaction = Transaction::where('reference', $reference)
            ->where('transaction_type', 'wallet_funding')
            ->where('status', 'completed')
            ->first();

        if (!$transaction) {
            return;
        }

        $this->cardSuccess = '₦' . number_format($transaction->amount, 2) . ' added successfully via card payment!';
        $this->cardAmount = '';
        $this->dispatch('notify-success', message: $this->cardSuccess);
    }

    public function render()
    {
        $user = Auth::user();
        /** @var WalletService $walletService */
        $walletService = app(WalletService::class);
        $wallet = $walletService->getCustomerWallet($user);

        $paymentRef = request()->query('paymentReference');
        if ($paymentRef) {
            $this->verifyCardPayment($paymentRef);
        }

        return view('livewire.customer.add-money', [
            'wallet' => $wallet,
            'accountDisplay' => $this->accountDisplay,
            'monnifyBalance' => $this->monnifyBalance,
        ])->layout('components.layouts.customer');
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
