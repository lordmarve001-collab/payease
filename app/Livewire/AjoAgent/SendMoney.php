<?php

namespace App\Livewire\AjoAgent;

use App\Helpers\PhoneNumberHelper;
use App\Models\Agent;
use App\Models\Transaction;
use App\Models\User;
use App\Services\MmoProviderSettingService;
use App\Services\TransactionService;
use App\Services\WalletService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class SendMoney extends Component
{
    public int $step = 1;
    public string $recipientType = 'phone';
    public string $phone = '';
    public string $amount = '';
    public string $description = '';
    public string $recipientName = '';
    public bool $recipientFound = false;
    public string $validationMessage = '';

    public string $selectedBankCode = '';
    public string $accountNumber = '';
    public string $accountName = '';
    public bool $accountNameLoading = false;
    public string $accountNameError = '';
    public array $banks = [];

    public string $pin = '';
    public bool $processing = false;
    public string $resultState = '';
    public string $resultMessage = '';
    public float $resultAmount = 0;
    public float $resultNewBalance = 0;
    public string $resultReference = '';

    public float $fee = 0;
    public float $total = 0;

    public function mount(): void
    {
        try {
            $client = app(MmoProviderSettingService::class)->resolveActiveClient();
            $this->banks = $client->getBanks();
        } catch (\Throwable) {
            $this->banks = [];
        }
    }

    public function updatedPhone(): void
    {
        $this->recipientFound = false;
        $this->recipientName = '';
        $this->validationMessage = '';

        if (strlen($this->phone) >= 10) {
            $this->lookupRecipient();
        }
    }

    public function updatedAccountNumber(): void
    {
        $this->accountName = '';
        $this->accountNameError = '';

        if (strlen($this->accountNumber) === 10 && $this->selectedBankCode !== '') {
            $this->lookupBankAccount();
        }
    }

    public function updatedSelectedBankCode(): void
    {
        $this->accountName = '';
        $this->accountNameError = '';

        if (strlen($this->accountNumber) === 10 && $this->selectedBankCode !== '') {
            $this->lookupBankAccount();
        }
    }

    public function lookupRecipient(): void
    {
        $txService = app(TransactionService::class);
        $recipient = $txService->lookupRecipientByPhone($this->phone);

        if ($recipient) {
            $this->recipientFound = true;
            $this->recipientName = $recipient->full_name;
            $this->validationMessage = '';
        } else {
            $this->recipientFound = false;
            $this->recipientName = '';
            $this->validationMessage = 'Recipient not found on PayEase.';
        }
    }

    public function lookupBankAccount(): void
    {
        $this->accountNameLoading = true;
        $this->accountNameError = '';
        $this->accountName = '';

        try {
            $client = app(MmoProviderSettingService::class)->resolveActiveClient();
            $name = $client->resolveBankAccountName($this->selectedBankCode, $this->accountNumber);
            $this->accountName = $name;
            $this->recipientFound = true;
        } catch (\Throwable $e) {
            $this->accountNameError = $e->getMessage();
            $this->recipientFound = false;
        } finally {
            $this->accountNameLoading = false;
        }
    }

    public function updatedAmount(): void
    {
        $this->calculateFee();
    }

    public function calculateFee(): void
    {
        $amt = (float) $this->amount;
        if ($amt <= 0) {
            $this->fee = 0;
            $this->total = 0;
            return;
        }
        $txService = app(TransactionService::class);
        $this->fee = $txService->calculateTransferFee($amt);
        $this->total = $amt + $this->fee;
    }

    public function proceedToReview(): void
    {
        $this->calculateFee();
        $this->step = 2;
    }

    public function submitTransfer(): void
    {
        $this->validate([
            'pin' => ['required', 'digits:6'],
        ]);

        /** @var User $user */
        $user = Auth::user();
        if (!$user->verifyTransferPin($this->pin)) {
            $this->addError('pin', 'Incorrect PIN.');
            return;
        }

        $this->processing = true;

        try {
            /** @var Agent $agent */
            $agent = $user->agent;
            $txService = app(TransactionService::class);

            if ($this->recipientType === 'phone') {
                $normalized = PhoneNumberHelper::normalize($this->phone);
                $recipient = User::where('phone_number', $normalized)->firstOrFail();

                $result = $txService->initiateInternalTransfer(
                    fromUser: $user,
                    toUser: $recipient,
                    amount: (float) $this->amount,
                    description: $this->description ?: 'Agent transfer via PayEase',
                );
            } else {
                $result = $txService->initiateBankTransferDisbursement(
                    senderUser: $user,
                    bankCode: $this->selectedBankCode,
                    accountNumber: $this->accountNumber,
                    accountName: $this->accountName,
                    amount: (float) $this->amount,
                    description: $this->description ?: 'Agent bank transfer via PayEase',
                );
            }

            $this->resultState = 'success';
            $this->resultMessage = 'Transfer completed successfully!';
            $this->resultAmount = (float) $this->amount;
            $this->resultNewBalance = (float) $agent->fresh()->float_balance;
            $this->resultReference = $result['reference'] ?? $result->reference ?? 'N/A';
            $this->step = 3;
        } catch (\Throwable $e) {
            $this->resultState = 'error';
            $this->resultMessage = $e->getMessage();
            $this->step = 3;
        } finally {
            $this->processing = false;
        }
    }

    public function resetForm(): void
    {
        $this->reset();
        $this->step = 1;
        $this->banks = [];
        $this->mount();
    }

    public function render()
    {
        /** @var User $user */
        $user = Auth::user();
        /** @var Agent|null $agent */
        $agent = $user->agent;

        $walletService = app(WalletService::class);
        $wallet = $agent ? $walletService->getAgentWallet($user) : null;

        return view('livewire.ajo-agent.send-money', [
            'agent' => $agent,
            'wallet' => $wallet,
        ])->layout('components.layouts.ajo-agent');
    }
}
