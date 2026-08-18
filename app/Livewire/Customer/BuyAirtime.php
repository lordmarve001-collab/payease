<?php

namespace App\Livewire\Customer;

use App\Models\User;
use App\Services\BillPaymentService;
use App\Services\WalletService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class BuyAirtime extends Component
{
    public string $step = 'select';
    public string $phoneNumber = '';
    public string $selectedNetwork = '';
    public string $amount = '';
    public float $fee = 0;
    public float $total = 0;
    public string $validationMessage = '';
    public bool $isProcessing = false;
    public string $resultState = '';
    public string $resultMessage = '';

    public string $pin1 = '';
    public string $pin2 = '';
    public string $pin3 = '';
    public string $pin4 = '';
    public string $pin5 = '';
    public string $pin6 = '';
    public string $pinError = '';

    protected BillPaymentService $billPaymentService;
    protected WalletService $walletService;

    public function boot(BillPaymentService $billPaymentService, WalletService $walletService): void
    {
        $this->billPaymentService = $billPaymentService;
        $this->walletService = $walletService;
    }

    public function selectNetwork(string $network): void
    {
        $this->selectedNetwork = $network;
        $this->step = 'amount';
        $this->validationMessage = '';
    }

    public function updatedAmount(): void
    {
        $this->validateAmount();
    }

    public function setAmount(string $val): void
    {
        $this->amount = $val;
        $this->validateAmount();
    }

    protected function validateAmount(): void
    {
        $this->validationMessage = '';
        $this->fee = 0;
        $this->total = 0;

        if ($this->amount === '' || (float) $this->amount <= 0) {
            return;
        }

        $amount = (float) $this->amount;

        if ($amount < 50) {
            $this->validationMessage = 'Minimum airtime is ?50.';
            return;
        }

        $this->total = $amount;
    }

    public function goToConfirm(): void
    {
        if (!$this->validateDetails()) {
            return;
        }

        $this->step = 'confirm';
    }

    public function goToPin(): void
    {
        $this->pinError = '';
        $this->resetPinInputs();

        if (!$this->validateDetails()) {
            return;
        }

        $this->step = 'pin';
        $this->dispatch('focus-airtime-pin');
    }

    protected function validateDetails(): bool
    {
        $this->validateAmount();

        if ($this->validationMessage !== '') {
            return false;
        }

        $phone = trim($this->phoneNumber);

        if ($phone === '') {
            $this->validationMessage = 'Enter your phone number.';
            return false;
        }

        if (strlen($phone) < 10) {
            $this->validationMessage = 'Enter a valid phone number.';
            return false;
        }

        return true;
    }

    public function updated($property): void
    {
        if (in_array($property, ['pin1', 'pin2', 'pin3', 'pin4', 'pin5', 'pin6'], true)) {
            $this->$property = preg_replace('/\D/', '', (string) $this->$property) ?? '';
            $this->pinError = '';
        }
    }

    public function confirmPin(): void
    {
        $this->pinError = '';

        $pin = $this->pin1 . $this->pin2 . $this->pin3 . $this->pin4 . $this->pin5 . $this->pin6;

        if (strlen($pin) !== 6) {
            $this->pinError = 'Enter your 6-digit transfer PIN.';
            $this->dispatch('focus-airtime-pin');
            return;
        }

        /** @var User $user */
        $user = Auth::user();

        if (!$user->verifyTransferPin($pin)) {
            $this->pinError = 'Incorrect PIN.';
            $this->resetPinInputs();
            $this->dispatch('focus-airtime-pin');
            return;
        }

        $this->purchase();
    }

    public function purchase(): void
    {
        $this->validationMessage = '';

        if ($this->isProcessing) {
            return;
        }

        $this->validateAmount();

        if ($this->validationMessage !== '') {
            $this->step = 'amount';
            return;
        }

        $this->isProcessing = true;

        try {
            $result = $this->billPaymentService->purchaseAirtime(
                trim($this->phoneNumber),
                $this->selectedNetwork,
                (float) $this->amount,
                'web'
            );
        } catch (\Throwable $e) {
            $this->isProcessing = false;
            $this->resetPinInputs();
            $this->resultState = 'failed';
            $this->resultMessage = $e->getMessage();
            $this->step = 'result';
            return;
        }

        $this->isProcessing = false;
        $this->resetPinInputs();

        if (($result['status'] ?? '') === 'success') {
            $this->resultState = 'success';
            $this->resultMessage = 'Airtime purchase was successful.';
        } else {
            $this->resultState = 'failed';
            $this->resultMessage = $result['error'] ?? 'Airtime purchase failed.';
        }

        $this->step = 'result';
    }

    public function goBack(): void
    {
        if ($this->step === 'amount') {
            $this->step = 'select';
        } elseif ($this->step === 'confirm') {
            $this->step = 'amount';
        } elseif ($this->step === 'pin') {
            $this->step = 'amount';
            $this->resetPinInputs();
        } elseif ($this->step === 'result') {
            $this->reset();
        }
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

    public function render()
    {
        $user = Auth::user();
        $wallet = $this->walletService->getCustomerWallet($user);

        return view('livewire.customer.buy-airtime', [
            'wallet' => $wallet,
            'networks' => ['MTN', 'Airtel', 'Glo', '9mobile'],
        ])->layout('components.layouts.customer');
    }
}
