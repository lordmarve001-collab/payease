<?php

namespace App\Livewire\Agent;

use App\Helpers\PhoneNumberHelper;
use App\Models\Agent;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\TransactionService;
use App\Services\WalletService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use RuntimeException;

class CashOut extends Component
{
    public int $step = 1;
    public string $phone = '';
    public string $customerName = '';
    public float $customerBalance = 0;
    public int $kycTier = 0;
    public ?string $customerId = null;
    public string $amount = '';
    public float $commission = 0;
    public string $reference = '';
    public float $newCustomerBalance = 0;
    public float $newFloatBalance = 0;
    public string $date = '';
    public bool $isLoading = false;
    public string $validationMessage = '';
    public string $validationCode = '';
    public bool $canProceed = false;
    public string $agentPin = '';
    public string $resultState = '';
    public string $resultMessage = '';

    protected function rules(): array
    {
        return [
            'phone' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:1'],
        ];
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
    }
    public function lookupCustomer(): void
    {
        $this->isLoading = true;

        try {
            /** @var TransactionService $transactionService */
            $transactionService = app(TransactionService::class);
            /** @var WalletService $walletService */
            $walletService = app(WalletService::class);

            $customer = $transactionService->lookupRecipientByPhone($this->phone);
            $wallet = $customer ? $walletService->getCustomerWallet($customer) : null;

            if (!$customer || !$wallet) {
                $this->addError('phone', 'Customer not found.');
                return;
            }

            $this->customerId = $customer->id;
            $this->customerName = $customer->full_name;
            $this->customerBalance = (float) $wallet->available_balance;
            $this->kycTier = (int) $customer->kyc_level;
            $this->step = 2;
            $this->syncPreview();
        } finally {
            $this->isLoading = false;
        }
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

    public function continueToPin(): void
    {
        $this->syncPreview();

        if (!$this->canProceed) {
            $this->dispatch('notify-error', message: $this->validationMessage !== '' ? $this->validationMessage : 'Please fix the withdrawal details and try again.');
            return;
        }

        $this->step = 3;
    }

    public function goBack(): void
    {
        if ($this->step === 3) {
            $this->step = 2;
            $this->agentPin = '';
            return;
        }

        $this->resetFlow();
    }

    public function confirmWithdrawal(): void
    {
        $this->syncPreview();

        if (!$this->canProceed) {
            $this->dispatch('notify-error', message: $this->validationMessage !== '' ? $this->validationMessage : 'Unable to continue with this withdrawal.');
            return;
        }

        if (!$this->verifyAgentPin('cash_out')) {
            return;
        }

        $this->isLoading = true;

        try {
            /** @var User $agentUser */
            $agentUser = Auth::user();
            /** @var Agent $agent */
            $agent = $agentUser->agent;
            $customer = $this->getCustomer();

            if (!$agent || !$customer) {
                throw new RuntimeException('Unable to prepare this cash-out transaction.');
            }

            /** @var TransactionService $transactionService */
            $transactionService = app(TransactionService::class);
            $transaction = $transactionService->processCashOut($agent, $customer, (float) $this->amount);

            AuditLog::create([
                'user_id' => $agentUser->id,
                'action' => 'agent_cash_out',
                'entity_type' => 'transaction',
                'entity_id' => $transaction->id,
                'old_values' => null,
                'new_values' => [
                    'customer_id' => $customer->id,
                    'customer_phone' => $this->phone,
                    'amount' => (float) $this->amount,
                    'reference' => $transaction->reference,
                ],
                'ip_address' => request()->ip(),
                'device_id' => request()->userAgent(),
            ]);

            $this->reference = $transaction->reference;
            $this->date = ($transaction->completed_at ?? $transaction->created_at)->format('d M Y, h:i A');
            $this->newCustomerBalance = (float) ($transaction->fromWallet?->available_balance ?? 0);
            $this->newFloatBalance = (float) $agentUser->fresh()->agent?->float_balance;
            $this->resultState = 'success';
            $this->resultMessage = '';
            $this->step = 4;
            $this->dispatch('notify-success', message: 'Cash out completed successfully.');
        } catch (RuntimeException $exception) {
            $this->date = now()->format('d M Y, h:i A');
            $this->resultState = 'failed';
            $this->resultMessage = $exception->getMessage();
            $this->step = 4;
            $this->dispatch('notify-error', message: $this->resultMessage);
        } finally {
            $this->isLoading = false;
        }
    }

    public function tryAgain(): void
    {
        $this->resultState = '';
        $this->resultMessage = '';
        $this->agentPin = '';
        $this->step = 3;
        $this->syncPreview();
    }

    public function render()
    {
        return view('livewire.agent.cash-out')->layout('components.layouts.agent');
    }

    protected function getCustomer(): ?User
    {
        if (!$this->customerId) {
            return null;
        }

        return User::find($this->customerId);
    }

    protected function syncPreview(): void
    {
        $customer = $this->getCustomer();

        /** @var User|null $agentUser */
        $agentUser = Auth::user();
        $agent = $agentUser?->agent;

        if (!$customer || !$agent) {
            $this->commission = 0;
            $this->validationMessage = '';
            $this->validationCode = '';
            $this->canProceed = false;
            return;
        }

        /** @var TransactionService $transactionService */
        $transactionService = app(TransactionService::class);
        $preview = $transactionService->getAgentCashOutPreview($agent, $customer, (float) $this->amount);

        $this->commission = (float) $preview['commission'];
        $this->validationCode = (string) ($preview['error_code'] ?? '');
        $this->canProceed = (bool) $preview['can_proceed'];
        $this->validationMessage = $this->shouldDisplayMessage((string) ($preview['message'] ?? '')) ? (string) $preview['message'] : '';
        $this->customerBalance = (float) ($preview['wallet']?->available_balance ?? $this->customerBalance);
    }

    protected function shouldDisplayMessage(string $message): bool
    {
        if ($message === '') {
            return false;
        }

        if ($message === 'Enter an amount to continue.' && $this->amount === '') {
            return false;
        }

        return true;
    }

    protected function verifyAgentPin(string $action): bool
    {
        $validated = $this->validate([
            'agentPin' => ['required', 'digits:6'],
        ], [
            'agentPin.required' => 'Enter your agent PIN to continue.',
            'agentPin.digits' => 'Agent PIN must be 6 digits.',
        ]);

        /** @var User|null $agentUser */
        $agentUser = Auth::user();

        if (!$agentUser) {
            $this->addError('agentPin', 'Your session has expired. Please log in again.');
            return false;
        }

        $maxAttempts = (int) config('lockout.pin.max_attempts', 3);
        $lockoutDuration = (int) config('lockout.pin.lockout_duration', 86400);

        $attemptKey = 'agent_pin_attempts_' . $action . '_' . $agentUser->id;
        $lockKey = 'agent_pin_lock_' . $action . '_' . $agentUser->id;

        if (Cache::has($lockKey)) {
            $this->addError('agentPin', 'Too many incorrect PIN attempts. Transactions are locked.');
            return false;
        }

        if (!$agentUser->verifyTransferPin($validated['agentPin'])) {
            $attempts = Cache::increment($attemptKey, 1);
            Cache::put($attemptKey, $attempts, $lockoutDuration);

            if ($attempts >= $maxAttempts) {
                Cache::put($lockKey, true, $lockoutDuration);
                $this->addError('agentPin', 'Too many incorrect PIN attempts. Transactions are locked.');
            } else {
                $remaining = max(0, $maxAttempts - $attempts);
                $this->addError('agentPin', 'Incorrect agent PIN. ' . $remaining . ' attempt(s) remaining.');
            }

            return false;
        }

        Cache::forget($attemptKey);
        Cache::forget($lockKey);

        return true;
    }

    protected function resetFlow(): void
    {
        $this->reset([
            'step',
            'phone',
            'customerName',
            'customerBalance',
            'kycTier',
            'customerId',
            'amount',
            'commission',
            'reference',
            'newCustomerBalance',
            'newFloatBalance',
            'date',
            'validationMessage',
            'validationCode',
            'canProceed',
            'agentPin',
            'resultState',
            'resultMessage',
        ]);

        $this->step = 1;
    }
}
