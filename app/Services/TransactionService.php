<?php

namespace App\Services;

use App\Contracts\MmoClientInterface;
use App\Events\AgentFloatBalanceDroppedLow;
use App\Events\TransactionCompleted;
use App\Helpers\PhoneNumberHelper;
use App\Http\Middleware\EnsureKycTier;
use App\Models\Agent;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class TransactionService
{
    public function __construct(
        protected MmoClientInterface $mmoClient,
        protected WalletService $walletService,
        protected PlatformLiquidityService $liquidityService,
    ) {
    }

    public function lookupRecipientByPhone(string $recipientPhone): ?User
    {
        $normalized = null;
        try {
            $normalized = PhoneNumberHelper::normalize($recipientPhone);
        } catch (\InvalidArgumentException) {
        }

        if ($normalized !== null) {
            $user = User::where('phone_number', $normalized)->first();
            if ($user) {
                return $user;
            }
        }

        return User::where('phone_number', trim($recipientPhone))->first();
    }

    public function calculateTransferFee(float $amount): float
    {
        $fee = $amount * 0.005;

        if ($fee < 10) {
            $fee = 10;
        }

        if ($fee > 100) {
            $fee = 100;
        }

        return round($fee, 2);
    }

    public function calculateAgentCommission(Agent $agent, float $amount): float
    {
        return round($amount * ((float) $agent->commission_rate / 100), 2);
    }

    public function getTransferPreview(User $sender, string $recipientPhone, float $amount): array
    {
        $recipientPhone = trim($recipientPhone);
        $senderWallet = $this->walletService->getCustomerWallet($sender);
        $recipient = $this->lookupRecipientByPhone($recipientPhone);
        $fee = $amount > 0 ? $this->calculateTransferFee($amount) : 0;
        $total = $amount > 0 ? round($amount + $fee, 2) : 0;
        $message = null;

        if (!$senderWallet) {
            $message = 'Your wallet is not available right now.';
        } elseif ($recipientPhone === '') {
            $message = 'Enter a recipient phone number.';
        } elseif ($amount <= 0) {
            $message = 'Enter an amount to continue.';
        } elseif ($amount < 50) {
            $message = 'Minimum transfer amount is ₦50.00.';
        } elseif (!$recipient) {
            $message = 'Recipient not found.';
        } elseif ($recipient->is($sender)) {
            $message = 'You cannot send money to yourself.';
        } else {
            try {
                EnsureKycTier::ensureTransferAllowed($sender, $amount);
            } catch (RuntimeException $exception) {
                $message = $exception->getMessage();
            }

            if ($message === null) {
                $limitViolation = $this->walletService->getLimitViolation($senderWallet, $amount);

                if ($limitViolation !== null) {
                    $message = $limitViolation;
                } elseif ($total > (float) $senderWallet->available_balance) {
                    $message = 'Insufficient balance for this transfer.';
                }
            }
        }

        return [
            'recipient' => $recipient,
            'recipient_name' => $recipient?->full_name ?? '',
            'fee' => $fee,
            'total' => $total,
            'message' => $message,
            'can_proceed' => $message === null,
            'wallet' => $senderWallet,
        ];
    }

    public function initiateTransfer(User $sender, string $recipientPhone, float $amount): Transaction
    {
        $preview = $this->getTransferPreview($sender, $recipientPhone, $amount);

        if (!$preview['can_proceed']) {
            throw new RuntimeException((string) $preview['message']);
        }

        /** @var Wallet $senderWallet */
        $senderWallet = $preview['wallet'];
        /** @var User $recipient */
        $recipient = $preview['recipient'];
        $recipientWallet = $this->walletService->getCustomerWallet($recipient);

        if (!$recipientWallet) {
            throw new RuntimeException('Recipient wallet is not available.');
        }

        $fee = (float) $preview['fee'];
        $total = (float) $preview['total'];
        $reference = 'PAY' . Str::upper(Str::random(10));

        $transaction = Transaction::create([
            'reference' => $reference,
            'transaction_type' => 'transfer',
            'amount' => $amount,
            'fee' => $fee,
            'status' => 'pending',
            'from_wallet_id' => $senderWallet->id,
            'to_wallet_id' => $recipientWallet->id,
            'recipient_phone' => $recipientPhone,
            'description' => 'Transfer to ' . $recipient->full_name,
            'mmo_partner' => $senderWallet->mmo_partner,
            'metadata' => [
                'recipient_user_id' => $recipient->id,
            ],
        ]);

        try {
            DB::transaction(function () use ($transaction, $senderWallet, $recipientWallet, $total, $amount): void {
                $lockedSenderWallet = Wallet::whereKey($senderWallet->id)->lockForUpdate()->firstOrFail();
                $lockedRecipientWallet = Wallet::whereKey($recipientWallet->id)->lockForUpdate()->firstOrFail();

                $limitViolation = $this->walletService->getLimitViolation($lockedSenderWallet, $amount);
                if ($limitViolation !== null) {
                    throw new RuntimeException($limitViolation);
                }

                if ($total > (float) $lockedSenderWallet->available_balance) {
                    throw new RuntimeException('Insufficient balance for this transfer.');
                }

                $debitResponse = $this->buildInternalMovementResponse('debit', $lockedSenderWallet->mmo_wallet_id, $total, $transaction->reference);
                $creditResponse = $this->buildInternalMovementResponse('credit', $lockedRecipientWallet->mmo_wallet_id, $amount, $transaction->reference);

                $lockedSenderWallet->balance = (float) $lockedSenderWallet->balance - $total;
                $lockedSenderWallet->available_balance = (float) $lockedSenderWallet->available_balance - $total;
                $lockedSenderWallet->save();

                $lockedRecipientWallet->balance = (float) $lockedRecipientWallet->balance + $amount;
                $lockedRecipientWallet->available_balance = (float) $lockedRecipientWallet->available_balance + $amount;
                $lockedRecipientWallet->save();

                $transaction->update([
                    'status' => 'completed',
                    'mmo_transaction_id' => $creditResponse['mmo_transaction_id'] ?? $debitResponse['mmo_transaction_id'] ?? null,
                    'completed_at' => now(),
                    'metadata' => array_merge($transaction->metadata ?? [], [
                        'debit_response' => $debitResponse,
                        'credit_response' => $creditResponse,
                    ]),
                ]);
            });
        } catch (Throwable $throwable) {
            $transaction->update([
                'status' => 'failed',
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'error' => $throwable->getMessage(),
                ]),
            ]);

            throw new RuntimeException($throwable->getMessage(), previous: $throwable);
        }

        $completedTransaction = $transaction->fresh(['fromWallet.user', 'toWallet.user']);
        event(new TransactionCompleted($completedTransaction));

        return $completedTransaction;
    }

    public function getAgentCashInPreview(Agent $agent, User $customer, float $amount): array
    {
        $customerWallet = $this->walletService->getCustomerWallet($customer);
        $commission = $amount > 0 ? $this->calculateAgentCommission($agent, $amount) : 0;
        $message = null;

        try {
            $this->ensureAgentCanTransact($agent);
        } catch (RuntimeException $exception) {
            $message = $exception->getMessage();
        }

        if ($message === null && !$customerWallet) {
            $message = 'Customer wallet is not available right now.';
        }

        if ($message === null && $amount <= 0) {
            $message = 'Enter an amount to continue.';
        }

        if ($message === null && $amount < 50) {
            $message = 'Minimum transaction amount is ₦50.00.';
        }

        if ($message === null && $customerWallet && $amount > (float) $customerWallet->single_txn_limit) {
            $message = 'Amount exceeds this customer\'s single transaction limit.';
        }

        if ($message === null) {
            try {
                EnsureKycTier::ensureTransferAllowed($customer, $amount);
            } catch (RuntimeException $exception) {
                $message = $exception->getMessage();
            }
        }

        return [
            'wallet' => $customerWallet,
            'commission' => $commission,
            'message' => $message,
            'can_proceed' => $message === null,
            'new_customer_balance' => $customerWallet ? round((float) $customerWallet->available_balance + $amount, 2) : 0,
            'new_float_balance' => round((float) $agent->float_balance + $amount, 2),
        ];
    }

    public function processCashIn(Agent $agent, User $customer, float $amount): Transaction
    {
        $preview = $this->getAgentCashInPreview($agent, $customer, $amount);

        if (!$preview['can_proceed']) {
            throw new RuntimeException((string) $preview['message']);
        }

        /** @var Wallet $customerWallet */
        $customerWallet = $preview['wallet'];
        $commission = (float) $preview['commission'];
        $reference = 'DEP' . Str::upper(Str::random(10));

        $transaction = Transaction::create([
            'reference' => $reference,
            'transaction_type' => 'deposit',
            'amount' => $amount,
            'commission' => $commission,
            'status' => 'pending',
            'to_wallet_id' => $customerWallet->id,
            'agent_id' => $agent->user_id,
            'recipient_phone' => $customer->phone_number,
            'description' => 'Cash deposit by agent ' . $agent->user->full_name,
            'mmo_partner' => $customerWallet->mmo_partner,
            'metadata' => [
                'customer_user_id' => $customer->id,
                'agent_model_id' => $agent->id,
            ],
        ]);

        try {
            DB::transaction(function () use ($transaction, $agent, $customerWallet, $commission, $amount): void {
                $lockedAgent = Agent::whereKey($agent->id)->lockForUpdate()->firstOrFail();
                $this->ensureAgentCanTransact($lockedAgent);

                $lockedCustomerWallet = Wallet::whereKey($customerWallet->id)->lockForUpdate()->firstOrFail();
                $creditResponse = $this->buildInternalMovementResponse('credit', $lockedCustomerWallet->mmo_wallet_id, $amount, $transaction->reference);

                $lockedCustomerWallet->balance = round((float) $lockedCustomerWallet->balance + $amount, 2);
                $lockedCustomerWallet->available_balance = round((float) $lockedCustomerWallet->available_balance + $amount, 2);
                $lockedCustomerWallet->save();

                $lockedAgent->float_balance = round((float) $lockedAgent->float_balance + $amount, 2);
                $lockedAgent->total_earnings = round((float) $lockedAgent->total_earnings + $commission, 2);
                $lockedAgent->save();

                $transaction->update([
                    'status' => 'completed',
                    'mmo_transaction_id' => $creditResponse['mmo_transaction_id'] ?? null,
                    'completed_at' => now(),
                    'metadata' => array_merge($transaction->metadata ?? [], [
                        'credit_response' => $creditResponse,
                        'agent_float_balance' => (float) $lockedAgent->float_balance,
                    ]),
                ]);
            });
        } catch (Throwable $throwable) {
            $transaction->update([
                'status' => 'failed',
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'error' => $throwable->getMessage(),
                ]),
            ]);

            throw new RuntimeException($throwable->getMessage(), previous: $throwable);
        }

        $completedTransaction = $transaction->fresh(['agentUser', 'toWallet.user']);
        event(new TransactionCompleted($completedTransaction));

        return $completedTransaction;
    }

    public function getAgentCashOutPreview(Agent $agent, User $customer, float $amount): array
    {
        $customerWallet = $this->walletService->getCustomerWallet($customer);
        $commission = $amount > 0 ? $this->calculateAgentCommission($agent, $amount) : 0;
        $message = null;
        $error_code = null;

        try {
            $this->ensureAgentCanTransact($agent);
        } catch (RuntimeException $exception) {
            $message = $exception->getMessage();
            $error_code = 'agent_status';
        }

        if ($message === null && !$customerWallet) {
            $message = 'Customer wallet is not available right now.';
            $error_code = 'wallet_unavailable';
        }

        if ($message === null && $amount <= 0) {
            $message = 'Enter an amount to continue.';
            $error_code = 'amount_required';
        }

        if ($message === null && $amount < 50) {
            $message = 'Minimum transaction amount is ₦50.00.';
            $error_code = 'amount_minimum';
        }

        if ($message === null && (float) $agent->float_balance < $amount) {
            $message = 'Insufficient float balance. Request a float top-up.';
            $error_code = 'insufficient_float';
        }

        if ($message === null && $customerWallet) {
            $limitViolation = $this->walletService->getLimitViolation($customerWallet, $amount);

            if ($limitViolation !== null) {
                $message = $limitViolation;
                $error_code = 'customer_limit';
            } elseif ((float) $customerWallet->available_balance < $amount) {
                $message = 'Customer has insufficient balance for this withdrawal.';
                $error_code = 'insufficient_customer_balance';
            }
        }

        return [
            'wallet' => $customerWallet,
            'commission' => $commission,
            'message' => $message,
            'error_code' => $error_code,
            'can_proceed' => $message === null,
            'new_customer_balance' => $customerWallet ? round((float) $customerWallet->available_balance - $amount, 2) : 0,
            'new_float_balance' => round((float) $agent->float_balance - $amount, 2),
        ];
    }

    public function processCashOut(Agent $agent, User $customer, float $amount): Transaction
    {
        $preview = $this->getAgentCashOutPreview($agent, $customer, $amount);
        $wasLowFloat = $this->isBelowLowFloatThreshold($agent);

        if (!$preview['can_proceed']) {
            throw new RuntimeException((string) $preview['message']);
        }

        /** @var Wallet $customerWallet */
        $customerWallet = $preview['wallet'];
        $commission = (float) $preview['commission'];
        $reference = 'WDL' . Str::upper(Str::random(10));

        $transaction = Transaction::create([
            'reference' => $reference,
            'transaction_type' => 'withdrawal',
            'amount' => $amount,
            'commission' => $commission,
            'status' => 'pending',
            'from_wallet_id' => $customerWallet->id,
            'agent_id' => $agent->user_id,
            'recipient_phone' => $customer->phone_number,
            'description' => 'Cash withdrawal by agent ' . $agent->user->full_name,
            'mmo_partner' => $customerWallet->mmo_partner,
            'metadata' => [
                'customer_user_id' => $customer->id,
                'agent_model_id' => $agent->id,
            ],
        ]);

        try {
            DB::transaction(function () use ($transaction, $agent, $customerWallet, $commission, $amount): void {
                $lockedAgent = Agent::whereKey($agent->id)->lockForUpdate()->firstOrFail();
                $this->ensureAgentCanTransact($lockedAgent);

                if ((float) $lockedAgent->float_balance < $amount) {
                    throw new RuntimeException('Insufficient float balance. Request a float top-up.');
                }

                $lockedCustomerWallet = Wallet::whereKey($customerWallet->id)->lockForUpdate()->firstOrFail();
                $limitViolation = $this->walletService->getLimitViolation($lockedCustomerWallet, $amount);

                if ($limitViolation !== null) {
                    throw new RuntimeException($limitViolation);
                }

                if ((float) $lockedCustomerWallet->available_balance < $amount) {
                    throw new RuntimeException('Customer has insufficient balance for this withdrawal.');
                }

                $debitResponse = $this->buildInternalMovementResponse('debit', $lockedCustomerWallet->mmo_wallet_id, $amount, $transaction->reference);

                $lockedCustomerWallet->balance = round((float) $lockedCustomerWallet->balance - $amount, 2);
                $lockedCustomerWallet->available_balance = round((float) $lockedCustomerWallet->available_balance - $amount, 2);
                $lockedCustomerWallet->save();

                $lockedAgent->float_balance = round((float) $lockedAgent->float_balance - $amount, 2);
                $lockedAgent->total_earnings = round((float) $lockedAgent->total_earnings + $commission, 2);
                $lockedAgent->save();

                $transaction->update([
                    'status' => 'completed',
                    'mmo_transaction_id' => $debitResponse['mmo_transaction_id'] ?? null,
                    'completed_at' => now(),
                    'metadata' => array_merge($transaction->metadata ?? [], [
                        'debit_response' => $debitResponse,
                        'agent_float_balance' => (float) $lockedAgent->float_balance,
                    ]),
                ]);
            });
        } catch (Throwable $throwable) {
            $transaction->update([
                'status' => 'failed',
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'error' => $throwable->getMessage(),
                ]),
            ]);

            throw new RuntimeException($throwable->getMessage(), previous: $throwable);
        }

        $completedTransaction = $transaction->fresh(['agentUser', 'fromWallet.user']);
        event(new TransactionCompleted($completedTransaction));
        $this->dispatchLowFloatAlertIfCrossed($agent->fresh(), $wasLowFloat);

        return $completedTransaction;
    }

    public function reverseTransaction(Transaction $transaction, User $admin, string $reason = ''): Transaction
    {
        $reason = trim($reason);
        $agentBefore = $transaction->agent_id
            ? Agent::query()->where('user_id', $transaction->agent_id)->first()
            : null;
        $wasLowFloat = $agentBefore ? $this->isBelowLowFloatThreshold($agentBefore) : false;

        if ($reason === '') {
            throw new RuntimeException('A reversal reason is required.');
        }

        $reference = 'REV' . Str::upper(Str::random(10));

        $reversalTransaction = DB::transaction(function () use ($transaction, $admin, $reason, $reference): Transaction {
            $lockedTransaction = Transaction::query()
                ->with(['fromWallet.user', 'toWallet.user', 'agentUser.agent'])
                ->lockForUpdate()
                ->findOrFail($transaction->id);

            $this->assertTransactionCanBeReversed($lockedTransaction);

            $reversalData = $this->buildReversalData($lockedTransaction);

            $reversalTransaction = Transaction::create([
                'reference' => $reference,
                'transaction_type' => 'reversal',
                'amount' => (float) $lockedTransaction->amount,
                'fee' => (float) $lockedTransaction->fee,
                'commission' => (float) $lockedTransaction->commission,
                'status' => 'pending',
                'from_wallet_id' => $reversalData['from_wallet_id'],
                'to_wallet_id' => $reversalData['to_wallet_id'],
                'agent_id' => $lockedTransaction->agent_id,
                'recipient_phone' => $lockedTransaction->recipient_phone,
                'description' => 'Reversal for ' . $lockedTransaction->reference,
                'mmo_partner' => $lockedTransaction->mmo_partner,
                'metadata' => [
                    'reversed_transaction_id' => $lockedTransaction->id,
                    'reversed_reference' => $lockedTransaction->reference,
                    'reversal_reason' => $reason,
                    'reversed_by_admin_id' => $admin->id,
                ],
            ]);

            try {
                $this->applyReversalEffects($lockedTransaction, $reversalTransaction);

                $reversalTransaction->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

                $lockedTransaction->update([
                    'status' => 'reversed',
                    'metadata' => array_merge($lockedTransaction->metadata ?? [], [
                        'reversed_at' => now()->toIso8601String(),
                        'reversal_reason' => $reason,
                        'reversed_by_admin_id' => $admin->id,
                        'reversal_transaction_id' => $reversalTransaction->id,
                        'reversal_reference' => $reversalTransaction->reference,
                    ]),
                ]);
            } catch (Throwable $throwable) {
                $reversalTransaction->update([
                    'status' => 'failed',
                    'metadata' => array_merge($reversalTransaction->metadata ?? [], [
                        'error' => $throwable->getMessage(),
                    ]),
                ]);

                throw $throwable;
            }

            return $reversalTransaction->fresh(['fromWallet.user', 'toWallet.user', 'agentUser']);
        });

        event(new TransactionCompleted($reversalTransaction));
        if ($agentBefore) {
            $this->dispatchLowFloatAlertIfCrossed($agentBefore->fresh(), $wasLowFloat);
        }

        return $reversalTransaction;
    }

    protected function dispatchLowFloatAlertIfCrossed(?Agent $agent, bool $wasLowFloat): void
    {
        if (!$agent) {
            return;
        }

        if (!$wasLowFloat && $this->isBelowLowFloatThreshold($agent)) {
            event(new AgentFloatBalanceDroppedLow($agent));
        }
    }

    protected function isBelowLowFloatThreshold(Agent $agent): bool
    {
        return (float) $agent->float_balance < ((float) $agent->max_float * 0.2);
    }

    public function getRecentTransactions(User $user, int $limit = 5): Collection
    {
        $wallet = $this->walletService->getCustomerWallet($user);

        if (!$wallet) {
            return new Collection();
        }

        return Transaction::query()
            ->with(['fromWallet.user', 'toWallet.user'])
            ->where(function ($query) use ($wallet): void {
                $query->where('from_wallet_id', $wallet->id)
                    ->orWhere('to_wallet_id', $wallet->id);
            })
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function getAgentRecentTransactions(Agent $agent, int $limit = 5): Collection
    {
        return Transaction::query()
            ->with(['fromWallet.user', 'toWallet.user', 'agentUser'])
            ->where('agent_id', $agent->user_id)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function getTransactionHistory(User $user, ?string $typeFilter = null, int $perPage = 15): LengthAwarePaginator
    {
        $wallet = $this->walletService->getCustomerWallet($user);

        if (!$wallet) {
            return Transaction::query()->whereRaw('1 = 0')->paginate($perPage);
        }

        $query = Transaction::query()
            ->with(['fromWallet.user', 'toWallet.user'])
            ->where(function ($builder) use ($wallet): void {
                $builder->where('from_wallet_id', $wallet->id)
                    ->orWhere('to_wallet_id', $wallet->id);
            });

        if ($typeFilter === 'credit') {
            $query->where('to_wallet_id', $wallet->id)->where('status', 'completed');
        } elseif ($typeFilter === 'debit') {
            $query->where('from_wallet_id', $wallet->id)->where('status', 'completed');
        } elseif ($typeFilter === 'failed') {
            $query->where('status', 'failed');
        }

        return $query->latest()->paginate($perPage);
    }

    public function getAgentEarnings(Agent $agent, ?string $period = null): array
    {
        $periodTransactions = Transaction::query()
            ->where('agent_id', $agent->user_id)
            ->where('status', 'completed');
        $this->applyAgentPeriod($periodTransactions, $period);

        $periodItems = $periodTransactions->latest()->get();
        $periodEarned = round((float) $periodItems->sum(fn (Transaction $transaction) => (float) $transaction->commission), 2);

        $lifetimeEarned = round((float) Transaction::query()
            ->where('agent_id', $agent->user_id)
            ->where('status', 'completed')
            ->sum('commission'), 2);

        $withdrawn = round((float) Transaction::query()
            ->where('agent_id', $agent->user_id)
            ->where('transaction_type', 'commission')
            ->where('status', 'completed')
            ->sum('amount'), 2);

        $breakdown = [
            'deposit' => round((float) $periodItems
                ->whereIn('transaction_type', ['deposit', 'cash_in'])
                ->sum(fn (Transaction $transaction) => (float) $transaction->commission), 2),
            'withdrawal' => round((float) $periodItems
                ->whereIn('transaction_type', ['withdrawal', 'cash_out'])
                ->sum(fn (Transaction $transaction) => (float) $transaction->commission), 2),
        ];

        return [
            'period_total' => $periodEarned,
            'lifetime_total' => $lifetimeEarned,
            'available_to_withdraw' => max(0, round($lifetimeEarned - $withdrawn, 2)),
            'withdrawn_total' => $withdrawn,
            'breakdown' => $breakdown,
            'transactions' => $periodItems,
        ];
    }

    public function withdrawAgentEarnings(Agent $agent): Transaction
    {
        $this->ensureAgentCanTransact($agent);
        $summary = $this->getAgentEarnings($agent);
        $available = (float) $summary['available_to_withdraw'];

        if ($available <= 0) {
            throw new RuntimeException('No earnings are available to withdraw right now.');
        }

        return Transaction::create([
            'reference' => 'COM' . Str::upper(Str::random(10)),
            'transaction_type' => 'commission',
            'amount' => $available,
            'status' => 'completed',
            'agent_id' => $agent->user_id,
            'description' => 'Agent commission withdrawal',
            'metadata' => [
                'agent_model_id' => $agent->id,
                'withdrawal_type' => 'internal_bookkeeping',
            ],
            'completed_at' => now(),
        ])->fresh('agentUser');
    }

    protected function ensureAgentCanTransact(Agent $agent): void
    {
        $status = strtolower((string) $agent->status);

        if ($status === 'active') {
            return;
        }

        throw new RuntimeException(match ($status) {
            'pending' => 'Your agent account is pending approval.',
            'suspended' => 'Your agent account is suspended. Contact support.',
            default => 'Your agent account cannot process transactions right now.',
        });
    }

    protected function assertTransactionCanBeReversed(Transaction $transaction): void
    {
        if ($transaction->status === 'failed') {
            throw new RuntimeException('Failed transactions cannot be reversed.');
        }

        if ($transaction->status === 'reversed') {
            throw new RuntimeException('This transaction has already been reversed.');
        }

        if ($transaction->transaction_type === 'reversal') {
            throw new RuntimeException('Reversal transactions cannot be reversed again.');
        }

        if ($transaction->status !== 'completed') {
            throw new RuntimeException('Only completed transactions can be reversed.');
        }

        $effectiveTime = $transaction->completed_at ?? $transaction->created_at;

        if ($effectiveTime->lt(now()->subDay())) {
            throw new RuntimeException('Only transactions from the last 24 hours can be reversed.');
        }
    }

    protected function buildReversalData(Transaction $transaction): array
    {
        return match ($transaction->transaction_type) {
            'transfer' => [
                'from_wallet_id' => $transaction->to_wallet_id,
                'to_wallet_id' => $transaction->from_wallet_id,
            ],
            'deposit' => [
                'from_wallet_id' => $transaction->to_wallet_id,
                'to_wallet_id' => null,
            ],
            'withdrawal' => [
                'from_wallet_id' => null,
                'to_wallet_id' => $transaction->from_wallet_id,
            ],
            default => throw new RuntimeException('This transaction type is not reversible in this phase.'),
        };
    }

    protected function applyReversalEffects(Transaction $original, Transaction $reversal): void
    {
        match ($original->transaction_type) {
            'transfer' => $this->reverseTransfer($original, $reversal),
            'deposit' => $this->reverseDeposit($original, $reversal),
            'withdrawal' => $this->reverseWithdrawal($original, $reversal),
            default => throw new RuntimeException('This transaction type is not reversible in this phase.'),
        };
    }

    protected function reverseTransfer(Transaction $original, Transaction $reversal): void
    {
        $senderWallet = Wallet::whereKey($original->from_wallet_id)->lockForUpdate()->firstOrFail();
        $recipientWallet = Wallet::whereKey($original->to_wallet_id)->lockForUpdate()->firstOrFail();
        $refundTotal = round((float) $original->amount + (float) $original->fee, 2);

        if ((float) $recipientWallet->available_balance < (float) $original->amount) {
            throw new RuntimeException('Recipient balance is too low to reverse this transfer.');
        }

        $debitResponse = $this->buildInternalMovementResponse('debit', $recipientWallet->mmo_wallet_id, (float) $original->amount, $reversal->reference);
        $creditResponse = $this->buildInternalMovementResponse('credit', $senderWallet->mmo_wallet_id, $refundTotal, $reversal->reference);

        $recipientWallet->balance = round((float) $recipientWallet->balance - (float) $original->amount, 2);
        $recipientWallet->available_balance = round((float) $recipientWallet->available_balance - (float) $original->amount, 2);
        $recipientWallet->save();

        $senderWallet->balance = round((float) $senderWallet->balance + $refundTotal, 2);
        $senderWallet->available_balance = round((float) $senderWallet->available_balance + $refundTotal, 2);
        $senderWallet->save();

        $reversal->update([
            'mmo_transaction_id' => $creditResponse['mmo_transaction_id'] ?? $debitResponse['mmo_transaction_id'] ?? null,
            'metadata' => array_merge($reversal->metadata ?? [], [
                'debit_response' => $debitResponse,
                'credit_response' => $creditResponse,
                'wallet_adjustments' => [
                    'credited_wallet_id' => $senderWallet->id,
                    'credited_amount' => $refundTotal,
                    'debited_wallet_id' => $recipientWallet->id,
                    'debited_amount' => (float) $original->amount,
                ],
            ]),
        ]);
    }

    protected function reverseDeposit(Transaction $original, Transaction $reversal): void
    {
        $customerWallet = Wallet::whereKey($original->to_wallet_id)->lockForUpdate()->firstOrFail();

        if ((float) $customerWallet->available_balance < (float) $original->amount) {
            throw new RuntimeException('Customer balance is too low to reverse this cash-in transaction.');
        }

        $agent = $this->lockAgentForReversal($original);
        $debitResponse = $this->buildInternalMovementResponse('debit', $customerWallet->mmo_wallet_id, (float) $original->amount, $reversal->reference);

        $customerWallet->balance = round((float) $customerWallet->balance - (float) $original->amount, 2);
        $customerWallet->available_balance = round((float) $customerWallet->available_balance - (float) $original->amount, 2);
        $customerWallet->save();

        if ($agent) {
            $agent->float_balance = round((float) $agent->float_balance - (float) $original->amount, 2);
            $agent->total_earnings = round((float) $agent->total_earnings - (float) $original->commission, 2);
            $agent->save();
        }

        $reversal->update([
            'mmo_transaction_id' => $debitResponse['mmo_transaction_id'] ?? null,
            'metadata' => array_merge($reversal->metadata ?? [], [
                'debit_response' => $debitResponse,
                'wallet_adjustments' => [
                    'debited_wallet_id' => $customerWallet->id,
                    'debited_amount' => (float) $original->amount,
                ],
                'agent_adjustments' => $agent ? [
                    'agent_id' => $agent->id,
                    'float_delta' => -(float) $original->amount,
                    'earnings_delta' => -(float) $original->commission,
                ] : null,
            ]),
        ]);
    }

    protected function reverseWithdrawal(Transaction $original, Transaction $reversal): void
    {
        $customerWallet = Wallet::whereKey($original->from_wallet_id)->lockForUpdate()->firstOrFail();
        $agent = $this->lockAgentForReversal($original);
        $creditResponse = $this->buildInternalMovementResponse('credit', $customerWallet->mmo_wallet_id, (float) $original->amount, $reversal->reference);

        $customerWallet->balance = round((float) $customerWallet->balance + (float) $original->amount, 2);
        $customerWallet->available_balance = round((float) $customerWallet->available_balance + (float) $original->amount, 2);
        $customerWallet->save();

        if ($agent) {
            $agent->float_balance = round((float) $agent->float_balance + (float) $original->amount, 2);
            $agent->total_earnings = round((float) $agent->total_earnings - (float) $original->commission, 2);
            $agent->save();
        }

        $reversal->update([
            'mmo_transaction_id' => $creditResponse['mmo_transaction_id'] ?? null,
            'metadata' => array_merge($reversal->metadata ?? [], [
                'credit_response' => $creditResponse,
                'wallet_adjustments' => [
                    'credited_wallet_id' => $customerWallet->id,
                    'credited_amount' => (float) $original->amount,
                ],
                'agent_adjustments' => $agent ? [
                    'agent_id' => $agent->id,
                    'float_delta' => (float) $original->amount,
                    'earnings_delta' => -(float) $original->commission,
                ] : null,
            ]),
        ]);
    }

    protected function lockAgentForReversal(Transaction $transaction): ?Agent
    {
        if (!$transaction->agent_id) {
            return null;
        }

        return Agent::query()
            ->where('user_id', $transaction->agent_id)
            ->lockForUpdate()
            ->first();
    }

    protected function buildInternalMovementResponse(string $operation, ?string $walletId, float $amount, string $reference): array
    {
        if ($this->mmoClient instanceof MockMmoClient) {
            return $operation === 'debit'
                ? $this->mmoClient->debit((string) $walletId, $amount, $reference)
                : $this->mmoClient->credit((string) $walletId, $amount, $reference);
        }

        return [
            'status' => 'success',
            'reference' => $reference,
            'amount' => $amount,
            'operation' => $operation,
            'wallet_id' => $walletId,
            'mmo_transaction_id' => 'LEDGER-' . strtoupper(substr(md5($operation . $reference . $walletId), 0, 12)),
        ];
    }

    protected function applyAgentPeriod(Builder $query, ?string $period): void
    {
        if ($period === 'today') {
            $query->whereDate('created_at', today());
            return;
        }

        if ($period === 'week') {
            $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
            return;
        }

        if ($period === 'month') {
            $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
        }
    }

    public function initiateBankTransferDisbursement(
        User $sender,
        string $destinationBankCode,
        string $destinationAccountNumber,
        string $destinationAccountName,
        float $amount,
        string $description = '',
    ): Transaction {
        $senderWallet = $this->walletService->getCustomerWallet($sender);

        if (!$senderWallet) {
            throw new RuntimeException('Your wallet is not available for disbursements.');
        }

        $fee = $this->calculateTransferFee($amount);
        $total = round($amount + $fee, 2);

        if ($total > (float) $senderWallet->available_balance) {
            throw new RuntimeException('Insufficient balance for this transfer.');
        }

        $limitViolation = $this->walletService->getLimitViolation($senderWallet, $amount);
        if ($limitViolation !== null) {
            throw new RuntimeException($limitViolation);
        }

        EnsureKycTier::ensureTransferAllowed($sender, $amount);

        $this->liquidityService->assertSufficientLiquidity($amount);

        $reference = 'DISB' . Str::upper(Str::random(10));

        $transaction = DB::transaction(function () use (
            $sender,
            $senderWallet,
            $destinationBankCode,
            $destinationAccountNumber,
            $destinationAccountName,
            $amount,
            $fee,
            $total,
            $reference,
            $description,
        ): Transaction {
            $lockedWallet = Wallet::whereKey($senderWallet->id)->lockForUpdate()->firstOrFail();

            if ($total > (float) $lockedWallet->available_balance) {
                throw new RuntimeException('Insufficient balance for this transfer.');
            }

            $lockedWallet->balance = round((float) $lockedWallet->balance - $total, 2);
            $lockedWallet->available_balance = round((float) $lockedWallet->available_balance - $total, 2);
            $lockedWallet->save();

            $transaction = Transaction::create([
                'reference' => $reference,
                'transaction_type' => 'bank_transfer_out',
                'amount' => $amount,
                'fee' => $fee,
                'status' => 'pending_disbursement_otp',
                'from_wallet_id' => $lockedWallet->id,
                'recipient_phone' => $sender->phone_number,
                'description' => $description !== '' ? $description : "Bank transfer to {$destinationAccountName}",
                'mmo_partner' => 'monnify',
                'metadata' => [
                    'destination_bank_code' => $destinationBankCode,
                    'destination_account_number' => $destinationAccountNumber,
                    'destination_account_name' => $destinationAccountName,
                    'sender_user_id' => $sender->id,
                ],
            ]);

            return $transaction->fresh(['fromWallet.user']);
        });

        try {
            $disbursementResult = $this->mmoClient->initiateDisbursement(
                $destinationBankCode,
                $destinationAccountNumber,
                $destinationAccountName,
                $amount,
                $reference,
                $description,
            );

            $transaction->update([
                'mmo_transaction_id' => $disbursementResult['transaction_reference'] ?? $reference,
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'disbursement_initiated' => true,
                    'external_reference' => $disbursementResult['external_reference'] ?? '',
                    'requires_otp' => $disbursementResult['requires_otp'] ?? false,
                    'otp_reference' => $disbursementResult['otp_reference'] ?? '',
                ]),
            ]);

            if (!($disbursementResult['requires_otp'] ?? false)) {
                $transaction->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

                event(new TransactionCompleted($transaction->fresh(['fromWallet.user'])));
            }
        } catch (Throwable $throwable) {
            $transaction->update([
                'status' => 'failed',
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'error' => $throwable->getMessage(),
                ]),
            ]);

            DB::transaction(function () use ($transaction, $senderWallet): void {
                $lockedWallet = Wallet::whereKey($senderWallet->id)->lockForUpdate()->firstOrFail();
                $total = round((float) $transaction->amount + (float) $transaction->fee, 2);
                $lockedWallet->balance = round((float) $lockedWallet->balance + $total, 2);
                $lockedWallet->available_balance = round((float) $lockedWallet->available_balance + $total, 2);
                $lockedWallet->save();
            });

            throw new RuntimeException($throwable->getMessage(), previous: $throwable);
        }

        return $transaction->fresh(['fromWallet.user']);
    }

    public function completeDisbursementOtp(string $transactionId, string $otp): Transaction
    {
        $transaction = Transaction::query()->findOrFail($transactionId);

        if ($transaction->status !== 'pending_disbursement_otp') {
            throw new RuntimeException('This transaction is not pending OTP validation.');
        }

        $otpReference = (string) ($transaction->metadata['otp_reference'] ?? $transaction->mmo_transaction_id ?? '');

        if ($otpReference === '') {
            throw new RuntimeException('No OTP reference found for this transaction.');
        }

        $result = $this->mmoClient->completeDisbursementOtp($otpReference, $otp);

        $transaction->update([
            'status' => 'completed',
            'completed_at' => now(),
            'metadata' => array_merge($transaction->metadata ?? [], [
                'otp_validated' => true,
                'disbursement_completed' => true,
                'final_status' => $result['status'] ?? 'completed',
                'external_reference_completed' => $result['external_reference'] ?? '',
            ]),
        ]);

        event(new TransactionCompleted($transaction->fresh(['fromWallet.user'])));

        return $transaction;
    }

    public function getPendingDisbursements(int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Transaction::query()
            ->where('status', 'pending_disbursement_otp')
            ->where('transaction_type', 'bank_transfer_out')
            ->with(['fromWallet.user'])
            ->latest()
            ->paginate($perPage);
    }
}
