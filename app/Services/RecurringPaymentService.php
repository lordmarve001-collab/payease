<?php

namespace App\Services;

use App\Models\RecurringPayment;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecurringPaymentService
{
    public function __construct(
        protected BillPaymentService $billPaymentService,
        protected TransactionService $transactionService,
        protected WalletService $walletService,
    ) {}

    public function create(
        User $user,
        string $paymentType,
        float $amount,
        string $frequency,
        array $paymentDetails,
        ?string $transferPin = null,
        ?int $maxExecutions = null,
    ): RecurringPayment {
        $pinHash = $transferPin
            ? \Illuminate\Support\Facades\Hash::make($transferPin, ['rounds' => 12])
            : null;

        return RecurringPayment::create([
            'user_id' => $user->id,
            'payment_type' => $paymentType,
            'amount' => $amount,
            'frequency' => $frequency,
            'payment_details' => $paymentDetails,
            'transfer_pin_hash' => $pinHash,
            'next_execution_at' => $this->getNextExecutionDate($frequency),
            'max_executions' => $maxExecutions,
        ]);
    }

    public function processDue(): int
    {
        $duePayments = RecurringPayment::where('status', 'active')
            ->where('next_execution_at', '<=', now())
            ->get();

        $processed = 0;

        foreach ($duePayments as $payment) {
            try {
                $this->executePayment($payment);
                $processed++;
            } catch (\Throwable $e) {
                Log::error('Recurring payment failed', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);

                $payment->update(['status' => 'paused']);
            }
        }

        return $processed;
    }

    protected function executePayment(RecurringPayment $payment): void
    {
        $user = $payment->user;
        $wallet = $this->walletService->getCustomerWallet($user);

        if (!$wallet || (float) $wallet->available_balance < (float) $payment->amount) {
            throw new \RuntimeException('Insufficient balance for recurring payment.');
        }

        match ($payment->payment_type) {
            'airtime' => $this->executeAirtime($payment, $user),
            'data' => $this->executeData($payment, $user),
            'cable' => $this->executeCable($payment, $user),
            'electricity' => $this->executeElectricity($payment, $user),
            default => throw new \RuntimeException('Unknown payment type: ' . $payment->payment_type),
        };

        $payment->recordExecution();
    }

    protected function executeAirtime(RecurringPayment $payment, User $user): void
    {
        $details = $payment->payment_details;

        $result = $this->billPaymentService->purchaseAirtime(
            $details['phone'] ?? $user->phone_number,
            $details['network'],
            (float) $payment->amount,
            'recurring',
            $user,
        );

        if (($result['status'] ?? '') !== 'success') {
            throw new \RuntimeException($result['error'] ?? 'Airtime purchase failed');
        }
    }

    protected function executeData(RecurringPayment $payment, User $user): void
    {
        $details = $payment->payment_details;

        $result = $this->billPaymentService->purchaseData(
            $details['phone'] ?? $user->phone_number,
            $details['network'],
            $details['bundle_code'],
            (float) $payment->amount,
            'recurring',
            $user,
        );

        if (($result['status'] ?? '') !== 'success') {
            throw new \RuntimeException($result['error'] ?? 'Data purchase failed');
        }
    }

    protected function executeCable(RecurringPayment $payment, User $user): void
    {
        $details = $payment->payment_details;

        $result = $this->billPaymentService->purchaseCable(
            $details['smartcard'],
            $details['package_code'],
            $details['provider'],
            (float) $payment->amount,
            'recurring',
            $user,
        );

        if (($result['status'] ?? '') !== 'success') {
            throw new \RuntimeException($result['error'] ?? 'Cable subscription failed');
        }
    }

    protected function executeElectricity(RecurringPayment $payment, User $user): void
    {
        $details = $payment->payment_details;

        $result = $this->billPaymentService->purchaseElectricity(
            $details['meter_number'],
            $details['disco'],
            (float) $payment->amount,
            'recurring',
            $details['meter_type'] ?? 'prepaid',
            $user,
        );

        if (($result['status'] ?? '') !== 'success') {
            throw new \RuntimeException($result['error'] ?? 'Electricity purchase failed');
        }
    }

    protected function getNextExecutionDate(string $frequency): \Carbon\Carbon
    {
        return match ($frequency) {
            'daily' => now()->addDay(),
            'weekly' => now()->addWeek(),
            'monthly' => now()->addMonth(),
            default: now()->addMonth(),
        };
    }
}
