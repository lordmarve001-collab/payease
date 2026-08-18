<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;

class Disbursements extends Component
{
    use WithPagination;

    public ?string $selectedTransactionId = null;
    public bool $showOtpModal = false;
    public string $otpValue = '';

    public function viewTransaction(string $transactionId): void
    {
        $this->selectedTransactionId = $transactionId;
    }

    public function closeOtpModal(): void
    {
        $this->showOtpModal = false;
        $this->otpValue = '';
        $this->selectedTransactionId = null;
    }

    public function openOtpModal(string $transactionId): void
    {
        $this->selectedTransactionId = $transactionId;
        $this->showOtpModal = true;
        $this->otpValue = '';
    }

    public function submitOtp(): void
    {
        $this->validate([
            'otpValue' => ['required', 'string', 'min:4', 'max:8'],
        ], [
            'otpValue.required' => 'Enter the OTP received from Monnify.',
            'otpValue.min' => 'OTP must be at least 4 characters.',
        ]);

        $transactionService = app(TransactionService::class);

        try {
            $transactionService->completeDisbursementOtp(
                (string) $this->selectedTransactionId,
                $this->otpValue,
            );

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'disbursement_otp_completed',
                'entity_type' => 'transaction',
                'entity_id' => $this->selectedTransactionId,
                'old_values' => ['status' => 'pending_disbursement_otp'],
                'new_values' => ['status' => 'completed'],
                'ip_address' => request()->ip(),
                'device_id' => request()->userAgent(),
            ]);

            $this->closeOtpModal();
            $this->dispatch('notify-success', message: 'Disbursement OTP validated. Transfer completed.');
        } catch (RuntimeException $exception) {
            $this->addError('otpValue', $exception->getMessage());
        }
    }

    public function render()
    {
        $transactionService = app(TransactionService::class);
        $pendingDisbursements = $transactionService->getPendingDisbursements(15);

        $selectedTransaction = $this->selectedTransactionId
            ? Transaction::with(['fromWallet.user'])->find($this->selectedTransactionId)
            : null;

        return view('livewire.admin.disbursements', [
            'pendingDisbursements' => $pendingDisbursements,
            'selectedTransaction' => $selectedTransaction,
        ])->layout('components.layouts.admin');
    }
}
