<?php

namespace App\Livewire\Customer;

use App\Contracts\MmoClientInterface;
use App\Helpers\PhoneNumberHelper;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\MonnifyClient;
use App\Services\MmoProviderSettingService;
use App\Services\TransactionService;
use App\Services\WalletService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use RuntimeException;

class SendMoney extends Component
{
    public int $step = 1;
    public string $recipientType = 'phone';
    public string $phone = '';
    public string $amount = '';
    public float $fee = 0;
    public float $total = 0;
    public string $recipientName = '';
    public string $reference = '';
    public float $newBalance = 0;
    public string $date = '';
    public bool $isLoading = false;
    public string $validationMessage = '';
    public bool $canProceed = false;
    public string $resultState = '';
    public string $resultMessage = '';
    public string $pin1 = '';
    public string $pin2 = '';
    public string $pin3 = '';
    public string $pin4 = '';
    public string $pin5 = '';
    public string $pin6 = '';
    public string $pinError = '';

    // Bank transfer fields
    public array $banks = [];
    public string $selectedBankCode = '';
    public string $accountNumber = '';
    public string $accountName = '';
    public bool $accountNameLoading = false;
    public string $accountNameError = '';

    protected function rules(): array
    {
        return [
            'phone' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:1'],
        ];
    }

    public function getPinLockSeconds(): int
    {
        $maxAttempts = (int) config('lockout.pin.max_attempts', 3);
        $lockoutDuration = (int) config('lockout.pin.lockout_duration', 86400);
        $lockKey = 'customer_pin_lock_send_' . auth()->id();

        $lockExpiresAt = Cache::get($lockKey);

        if (!$lockExpiresAt) {
            return 0;
        }

        return max(0, (int) ($lockExpiresAt - now()->timestamp));
    }

    public function normalizePinLockout(): void
    {
        $lockKey = 'customer_pin_lock_send_' . auth()->id();
        if (Cache::get($lockKey) && Cache::get($lockKey) <= now()->timestamp) {
            Cache::forget($lockKey);
            $this->pinError = '';
        }
    }

    public function mount(): void
    {
        if (request()->hasSession()) {
            if (app()->environment('local') && request()->query('mmo_fail')) {
                request()->session()->put('mock_mmo_force_fail', strtolower((string) request()->query('mmo_fail')));
            } else {
                request()->session()->forget('mock_mmo_force_fail');
            }
        }

        $this->syncPreview();
        $this->loadBanks();
    }

    protected function loadBanks(): void
    {
        $fallback = [
            ['code' => '035', 'name' => 'Access Bank'],
            ['code' => '033', 'name' => 'United Bank for Africa (UBA)'],
            ['code' => '011', 'name' => 'First Bank of Nigeria'],
            ['code' => '058', 'name' => 'Guaranty Trust Bank (GTBank)'],
            ['code' => '032', 'name' => 'Union Bank'],
            ['code' => '044', 'name' => 'Zenith Bank'],
            ['code' => '050', 'name' => 'Ecobank Nigeria'],
            ['code' => '070', 'name' => 'Fidelity Bank'],
            ['code' => '084', 'name' => 'First City Monument Bank (FCMB)'],
            ['code' => '076', 'name' => 'Polaris Bank'],
            ['code' => '221', 'name' => 'Stanbic IBTC Bank'],
            ['code' => '232', 'name' => 'Sterling Bank'],
            ['code' => '215', 'name' => 'Wema Bank'],
        ];

        try {
            $providerService = app(MmoProviderSettingService::class);
            $setting = $providerService->getProviderSetting('monnify');
            $credentials = is_array($setting->credentials) ? $setting->credentials : [];
            $client = new MonnifyClient($credentials, (string) $setting->environment);
            $result = $client->getBanks();

            $banksList = $result;

            if (!empty($result['banks']) && is_array($result['banks'])) {
                $banksList = $result['banks'];
            }

            if (is_array($banksList) && count($banksList) > 0) {
                $this->banks = collect($banksList)->map(fn ($b) => [
                    'code' => (string) ($b['bankCode'] ?? $b['code'] ?? ''),
                    'name' => $b['bankName'] ?? $b['name'] ?? '',
                ])->filter(fn ($b) => $b['code'] !== '' && $b['name'] !== '')->values()->all();
            } else {
                $this->banks = $fallback;
            }
        } catch (\Exception $e) {
            Log::channel('monnify')->warning('Customer send-money: failed to fetch banks, using fallback', [
                'error' => $e->getMessage(),
            ]);
            $this->banks = $fallback;
        }
    }

    public function updatedPhone(): void
    {
        $this->syncPreview();
    }

    public function updatedAmount($value): void
    {
        $this->amount = preg_replace('/[^0-9]/', '', (string) $value) ?? '';
        $this->syncPreview();
    }

    public function setAmount($value): void
    {
        $this->amount = (string) $value;
        $this->syncPreview();
    }

    public function updatedSelectedBankCode(): void
    {
        $this->accountName = '';
        $this->accountNameError = '';
        $this->syncPreview();

        if (strlen($this->accountNumber) >= 10) {
            $this->lookupAccountName();
        }
    }

    public function updatedAccountNumber(): void
    {
        $this->accountNumber = preg_replace('/\D/', '', (string) $this->accountNumber) ?? '';

        if (strlen($this->accountNumber) >= 10) {
            $this->lookupAccountName();
        } else {
            $this->accountName = '';
            $this->accountNameError = '';
        }

        $this->syncPreview();
    }

    protected function lookupAccountName(): void
    {
        if ($this->selectedBankCode === '' || strlen($this->accountNumber) < 10) {
            return;
        }

        $this->accountNameLoading = true;
        $this->accountNameError = '';

        try {
            $providerService = app(MmoProviderSettingService::class);
            $setting = $providerService->getProviderSetting('monnify');
            $credentials = is_array($setting->credentials) ? $setting->credentials : [];
            $client = new MonnifyClient($credentials, (string) $setting->environment);

            $this->accountName = $client->resolveBankAccountName($this->selectedBankCode, $this->accountNumber);
            $this->accountNameError = '';
        } catch (\Exception $e) {
            $this->accountName = '';
            $this->accountNameError = 'Could not verify account name. Please check the details.';
            Log::channel('monnify')->warning('Customer send-money: account name lookup failed', [
                'bank_code' => $this->selectedBankCode,
                'account_number' => $this->accountNumber,
                'error' => $e->getMessage(),
            ]);
        } finally {
            $this->accountNameLoading = false;
        }
    }

    public function continueToConfirm(): void
    {
        $this->syncPreview();

        if (!$this->canProceed) {
            $this->dispatch('notify-error', message: $this->validationMessage !== '' ? $this->validationMessage : 'Please fix the transfer details and try again.');
            return;
        }

        // For bank transfers, validate account details
        if ($this->recipientType === 'bank') {
            if (strlen($this->accountNumber) !== 10) {
                $this->dispatch('notify-error', message: 'Please enter a valid 10-digit account number.');
                return;
            }
            if (!$this->selectedBankCode) {
                $this->dispatch('notify-error', message: 'Please select a bank.');
                return;
            }
            if (!$this->accountName) {
                $this->dispatch('notify-error', message: 'Please verify the account name before proceeding.');
                return;
            }
        }

        $this->step = 2;
    }

    public function continueToPinStep(): void
    {
        $this->syncPreview();

        if (!$this->canProceed) {
            $this->dispatch('notify-error', message: $this->validationMessage !== '' ? $this->validationMessage : 'Unable to continue with this transfer.');
            return;
        }

        $this->pinError = '';
        $this->step = 25;
        $this->dispatch('focus-transfer-pin');
    }

    public function goBack(): void
    {
        if ($this->step === 25 || $this->step === 3) {
            $this->resetPinInputs();
            $this->pinError = '';
            $this->step = 2;
            return;
        }

        $this->step = 1;
    }

    public function updated($property): void
    {
        if (in_array($property, ['pin1', 'pin2', 'pin3', 'pin4', 'pin5', 'pin6'], true)) {
            $this->$property = preg_replace('/\D/', '', (string) $this->$property) ?? '';
            $this->pinError = '';
        }
    }

    public function confirmTransferPin(): void
    {
        $this->syncPreview();
        $this->normalizePinLockout();

        if (!$this->canProceed) {
            $this->dispatch('notify-error', message: $this->validationMessage !== '' ? $this->validationMessage : 'Unable to continue with this transfer.');
            return;
        }

        if ($this->getPinLockSeconds() > 0) {
            $this->pinError = 'Send Money is locked for ' . $this->getPinLockSeconds() . ' seconds.';
            return;
        }

        if (strlen($this->getTransferPin()) !== 6) {
            $this->pinError = 'Enter your 6-digit transfer PIN.';
            $this->dispatch('focus-transfer-pin');
            return;
        }

        /** @var User $user */
        $user = Auth::user();

        $maxAttempts = (int) config('lockout.pin.max_attempts', 3);
        $lockoutDuration = (int) config('lockout.pin.lockout_duration', 86400);
        $attemptKey = 'customer_pin_attempts_send_' . $user->id;
        $lockKey = 'customer_pin_lock_send_' . $user->id;

        if (Cache::get($lockKey) && Cache::get($lockKey) > now()->timestamp) {
            $this->pinError = 'Send Money is locked. Please try again later.';
            $this->dispatch('focus-transfer-pin');
            return;
        }

        if (!$user->verifyTransferPin($this->getTransferPin())) {
            $attempts = Cache::increment($attemptKey, 1);
            Cache::put($attemptKey, $attempts, $lockoutDuration);
            $this->resetPinInputs();

            if ($attempts >= $maxAttempts) {
                Cache::put($lockKey, now()->addSeconds($lockoutDuration)->timestamp, $lockoutDuration);
                $this->pinError = 'Send Money is locked.';
            } else {
                $remaining = max(0, $maxAttempts - $attempts);
                $this->pinError = 'Incorrect PIN. ' . $remaining . ' attempt(s) remaining.';
            }

            $this->dispatch('focus-transfer-pin');
            return;
        }

        Cache::forget($attemptKey);
        Cache::forget($lockKey);
        $this->pinError = '';

        $this->completeTransfer($user);
    }

    public function tryAgain(): void
    {
        $this->resultState = '';
        $this->resultMessage = '';
        $this->resetPinInputs();
        $this->pinError = '';
        $this->step = 2;
        $this->syncPreview();
    }

    public function render()
    {
        /** @var User $user */
        $user = Auth::user();
        /** @var WalletService $walletService */
        $walletService = app(WalletService::class);

        $wallet = $walletService->getCustomerWallet($user);
        $this->syncPreview();
        $this->normalizePinLockout();

        return view('livewire.customer.send-money', [
            'wallet' => $wallet,
            'canContinue' => $this->recipientType === 'phone' ? $this->canProceed : ($this->canProceed && $this->accountName !== ''),
            'canAdvanceToPin' => $this->canProceed,
            'pinLockSeconds' => $this->getPinLockSeconds(),
            'pinLength' => strlen($this->getTransferPin()),
            'banks' => $this->banks,
        ])->layout('components.layouts.customer');
    }

    protected function completeTransfer(User $user): void
    {
        $this->isLoading = true;
        $this->resultState = '';
        $this->resultMessage = '';

        try {
            /** @var TransactionService $transactionService */
            $transactionService = app(TransactionService::class);
            /** @var WalletService $walletService */
            $walletService = app(WalletService::class);

            if ($this->recipientType === 'phone') {
                $transaction = $transactionService->initiateTransfer($user, $this->phone, (float) $this->amount);
            } else {
                // Bank transfer - use the bank transfer method
                $transaction = $this->initiateBankTransfer($user, $transactionService);
            }

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'send_money',
                'entity_type' => 'transaction',
                'entity_id' => $transaction->id,
                'old_values' => null,
                'new_values' => [
                    'recipient' => $this->recipientType === 'phone' ? $this->phone : $this->accountNumber,
                    'amount' => (float) $this->amount,
                    'reference' => $transaction->reference,
                    'type' => $this->recipientType,
                ],
                'ip_address' => request()->ip(),
                'device_id' => request()->userAgent(),
            ]);

            $this->reference = $transaction->reference;
            $this->date = ($transaction->completed_at ?? $transaction->created_at)->format('d M Y, h:i A');
            $this->newBalance = $walletService->getBalance($user->fresh());
            $this->resetPinInputs();
            $this->step = 3;

            if ($transaction->status === 'pending_disbursement_otp') {
                $this->resultState = 'pending';
                $this->resultMessage = 'An OTP has been sent to your registered email. Please authorize the transfer to complete it.';
                $this->dispatch('notify-info', message: $this->resultMessage);
            } else {
                $this->resultState = 'success';
                $this->dispatch('notify-success', message: 'Transfer completed successfully.');
            }
        } catch (RuntimeException $exception) {
            $this->date = now()->format('d M Y, h:i A');
            $this->resultState = 'failed';
            $this->resultMessage = $exception->getMessage();
            $this->step = 3;
            $this->dispatch('notify-error', message: $this->resultMessage);
        } finally {
            $this->isLoading = false;
        }
    }

    protected function initiateBankTransfer(User $user, TransactionService $transactionService)
    {
        $bank = collect($this->banks)->firstWhere('code', $this->selectedBankCode);
        $bankName = $bank['name'] ?? 'Unknown Bank';

        return $transactionService->initiateBankTransferDisbursement(
            $user,
            $this->selectedBankCode,
            $this->accountNumber,
            $this->accountName,
            (float) $this->amount,
            "Transfer to {$this->accountName} ({$bankName})"
        );
    }

    protected function syncPreview(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user) {
            $this->recipientName = '';
            $this->fee = 0;
            $this->total = 0;
            $this->validationMessage = '';
            $this->canProceed = false;
            return;
        }

        if ($this->recipientType === 'bank') {
            if ($this->amount > 0 && strlen($this->accountNumber) >= 10 && $this->selectedBankCode && $this->accountName) {
                $this->recipientName = $this->accountName;
                $bank = collect($this->banks)->firstWhere('code', $this->selectedBankCode);
                $this->fee = 0;
                $this->total = round((float) $this->amount, 2);
                $this->canProceed = true;
                $this->validationMessage = '';
            } else {
                $this->recipientName = '';
                $this->fee = 0;
                $this->total = 0;
                $this->canProceed = false;
            }
            return;
        }

        /** @var TransactionService $transactionService */
        $transactionService = app(TransactionService::class);
        $preview = $transactionService->getTransferPreview($user, $this->phone, (float) $this->amount);

        $this->recipientName = (string) $preview['recipient_name'];
        $this->fee = (float) $preview['fee'];
        $this->total = (float) $preview['total'];
        $this->canProceed = (bool) $preview['can_proceed'];
        $this->validationMessage = $this->shouldDisplayMessage((string) ($preview['message'] ?? '')) ? (string) $preview['message'] : '';
    }

    protected function shouldDisplayMessage(string $message): bool
    {
        if ($message === '') {
            return false;
        }

        if ($message === 'Enter a recipient phone number.') {
            return false;
        }

        if ($message === 'Enter an amount to continue.' && $this->amount === '') {
            return false;
        }

        if ($message === 'Recipient not found.' && strlen($this->phone) < 10) {
            return false;
        }

        return true;
    }

    protected function getTransferPin(): string
    {
        return $this->pin1 . $this->pin2 . $this->pin3 . $this->pin4 . $this->pin5 . $this->pin6;
    }

    protected function resetPinInputs(): void
    {
        $this->pin1 = '';
        $this->pin2 = '';
        $this->pin3 = '';
        $this->pin4 = '';
        $this->pin5 = '';
        $this->pin6 = '';
    }
}
