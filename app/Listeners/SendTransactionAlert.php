<?php

namespace App\Listeners;

use App\Events\TransactionCompleted;
use App\Jobs\SendSmsNotification;
use App\Mail\NotificationMessage;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class SendTransactionAlert
{
    public function handle(TransactionCompleted $event): void
    {
        $transaction = $event->transaction->fresh(['fromWallet.user', 'toWallet.user', 'agentUser']);

        if (!$transaction || strtolower((string) $transaction->status) !== 'completed') {
            return;
        }

        foreach ($this->buildAlerts($transaction) as $alert) {
            $user = $alert['user'] ?? null;
            if (!$user || ($alert['message'] ?? '') === '') {
                continue;
            }

            $this->dispatchToUser($user, $alert['message'], $transaction->transaction_type);
        }
    }

    protected function dispatchToUser(User $user, string $message, string $transactionType): void
    {
        $typePref = match ($transactionType) {
            'ajo_payout' => $user->notify_payout,
            'ajo_contribution' => $user->notify_contribution,
            default => true,
        };

        if ($typePref === false || $typePref === null) {
            return;
        }

        if ($user->notify_sms) {
            rescue(function () use ($user, $message): void {
                SendSmsNotification::dispatch($user->phone_number, $message);
            }, report: false);
        }

        if ($user->notify_email && $user->email) {
            rescue(function () use ($user, $message, $transactionType): void {
                Mail::to($user->email)->queue(
                    new NotificationMessage(
                        $user->full_name,
                        $message,
                        $this->subjectForType($transactionType),
                    ),
                );
            }, report: false);
        }
    }

    protected function subjectForType(string $type): string
    {
        return match ($type) {
            'transfer' => 'Transfer Notification - PayEase',
            'deposit' => 'Deposit Received - PayEase',
            'withdrawal' => 'Withdrawal Notification - PayEase',
            'bank_transfer_deposit' => 'Bank Transfer Deposit - PayEase',
            'bank_transfer_out' => 'Bank Transfer Update - PayEase',
            'ajo_contribution' => 'Ajo Contribution Recorded - PayEase',
            'ajo_payout' => 'Ajo Payout Received - PayEase',
            'reversal' => 'Transaction Reversed - PayEase',
            'airtime' => 'Airtime Purchase Successful - PayEase',
            'data' => 'Data Purchase Successful - PayEase',
            'cable' => 'Cable Subscription Successful - PayEase',
            'electricity' => 'Electricity Purchase Successful - PayEase',
            default => 'Transaction Notification - PayEase',
        };
    }

    /**
     * @return array<int, array{user:User|null,message:string}>
     */
    protected function buildAlerts(Transaction $transaction): array
    {
        return match (strtolower((string) $transaction->transaction_type)) {
            'transfer' => $this->buildTransferAlerts($transaction),
            'deposit' => $this->buildCashInAlerts($transaction),
            'withdrawal' => $this->buildCashOutAlerts($transaction),
            'bank_transfer_deposit' => $this->buildBankTransferDepositAlerts($transaction),
            'bank_transfer_out' => $this->buildBankTransferOutAlerts($transaction),
            'ajo_contribution' => $this->buildAjoContributionAlerts($transaction),
            'ajo_payout' => $this->buildAjoPayoutAlerts($transaction),
            'reversal' => $this->buildReversalAlerts($transaction),
            'airtime' => $this->buildBillAlerts($transaction, 'airtime'),
            'data' => $this->buildBillAlerts($transaction, 'data bundle'),
            'cable' => $this->buildBillAlerts($transaction, 'cable subscription'),
            'electricity' => $this->buildBillAlerts($transaction, 'electricity'),
            default => [],
        };
    }

    /**
     * @return array<int, array{user:User|null,message:string}>
     */
    protected function buildTransferAlerts(Transaction $transaction): array
    {
        $sender = $transaction->fromWallet?->user;
        $recipient = $transaction->toWallet?->user;

        if (!$sender || !$recipient) {
            return [];
        }

        return [
            [
                'user' => $sender,
                'message' => sprintf(
                    'You sent %s to %s. Ref: %s. New balance: %s. -PayEase',
                    $this->formatMoney($transaction->amount),
                    $recipient->full_name,
                    $transaction->reference,
                    $this->formatMoney($transaction->fromWallet->available_balance),
                ),
            ],
            [
                'user' => $recipient,
                'message' => sprintf(
                    'You received %s from %s. Ref: %s. New balance: %s. -PayEase',
                    $this->formatMoney($transaction->amount),
                    $sender->full_name,
                    $transaction->reference,
                    $this->formatMoney($transaction->toWallet->available_balance),
                ),
            ],
        ];
    }

    /**
     * @return array<int, array{user:User|null,message:string}>
     */
    protected function buildCashInAlerts(Transaction $transaction): array
    {
        $customer = $transaction->toWallet?->user;
        $agent = $transaction->agentUser;

        if (!$customer) {
            return [];
        }

        $alerts = [[
            'user' => $customer,
            'message' => sprintf(
                '%s deposited to your PayEase wallet via agent. New balance: %s. -PayEase',
                $this->formatMoney($transaction->amount),
                $this->formatMoney($transaction->toWallet->available_balance),
            ),
        ]];

        if ($agent) {
            $alerts[] = [
                'user' => $agent,
                'message' => sprintf(
                    'Cash In of %s for %s completed. Float: %s. -PayEase',
                    $this->formatMoney($transaction->amount),
                    $customer->full_name,
                    $this->formatMoney((float) ($transaction->metadata['agent_float_balance'] ?? 0)),
                ),
            ];
        }

        return $alerts;
    }

    /**
     * @return array<int, array{user:User|null,message:string}>
     */
    protected function buildCashOutAlerts(Transaction $transaction): array
    {
        $customer = $transaction->fromWallet?->user;
        $agent = $transaction->agentUser;

        if (!$customer) {
            return [];
        }

        $alerts = [[
            'user' => $customer,
            'message' => sprintf(
                '%s withdrawn from your PayEase wallet via agent. New balance: %s. -PayEase',
                $this->formatMoney($transaction->amount),
                $this->formatMoney($transaction->fromWallet->available_balance),
            ),
        ]];

        if ($agent) {
            $alerts[] = [
                'user' => $agent,
                'message' => sprintf(
                    'Cash Out of %s for %s completed. Float: %s. -PayEase',
                    $this->formatMoney($transaction->amount),
                    $customer->full_name,
                    $this->formatMoney((float) ($transaction->metadata['agent_float_balance'] ?? 0)),
                ),
            ];
        }

        return $alerts;
    }

    /**
     * @return array<int, array{user:User|null,message:string}>
     */
    protected function buildBankTransferDepositAlerts(Transaction $transaction): array
    {
        $customer = $transaction->toWallet?->user;

        if (!$customer) {
            return [];
        }

        return [[
            'user' => $customer,
            'message' => sprintf(
                '%s received in your PayEase wallet via bank transfer. New balance: %s. -PayEase',
                $this->formatMoney($transaction->amount),
                $this->formatMoney($transaction->toWallet->available_balance),
            ),
        ]];
    }

    /**
     * @return array<int, array{user:User|null,message:string}>
     */
    protected function buildAjoContributionAlerts(Transaction $transaction): array
    {
        $groupName = (string) ($transaction->metadata['group_name'] ?? 'your Ajo group');
        $cycleNumber = (int) ($transaction->metadata['cycle_number'] ?? 1);
        $member = User::find($transaction->metadata['member_user_id'] ?? null);

        if (!$member) {
            return [];
        }

        return [[
            'user' => $member,
            'message' => sprintf(
                'Your Ajo contribution of %s to "%s" was recorded. Cycle %d. -PayEase',
                $this->formatMoney($transaction->amount),
                $groupName,
                $cycleNumber,
            ),
        ]];
    }

    /**
     * @return array<int, array{user:User|null,message:string}>
     */
    protected function buildAjoPayoutAlerts(Transaction $transaction): array
    {
        $groupName = (string) ($transaction->metadata['group_name'] ?? 'your Ajo group');
        $recipient = User::find($transaction->metadata['member_user_id'] ?? $transaction->toWallet?->user?->id);

        if (!$recipient) {
            return [];
        }

        return [[
            'user' => $recipient,
            'message' => sprintf(
                'You received your Ajo payout of %s from "%s". -PayEase',
                $this->formatMoney($transaction->amount),
                $groupName,
            ),
        ]];
    }

    /**
     * @return array<int, array{user:User|null,message:string}>
     */
    protected function buildReversalAlerts(Transaction $transaction): array
    {
        $restoredUser = $transaction->toWallet?->user;
        $reference = (string) ($transaction->metadata['reversed_reference'] ?? $transaction->reference);

        if ($restoredUser) {
            return [[
                'user' => $restoredUser,
                'message' => sprintf(
                    'Your transaction Ref: %s was reversed. %s has been restored to your balance. -PayEase',
                    $reference,
                    $this->formatMoney($transaction->amount),
                ),
            ]];
        }

        $affectedUser = $transaction->fromWallet?->user;

        if (!$affectedUser) {
            return [];
        }

        return [[
            'user' => $affectedUser,
            'message' => sprintf(
                'Your transaction Ref: %s was reversed. %s was adjusted on your balance. -PayEase',
                $reference,
                $this->formatMoney($transaction->amount),
            ),
        ]];
    }

    /**
     * @return array<int, array{user:User|null,message:string}>
     */
    protected function buildBillAlerts(Transaction $transaction, string $label): array
    {
        $customer = $transaction->fromWallet?->user ?? $transaction->toWallet?->user;

        if (!$customer) {
            return [];
        }

        return [[
            'user' => $customer,
            'message' => sprintf(
                'Your %s purchase of %s via %s was successful. Ref: %s. -PayEase',
                $label,
                $this->formatMoney($transaction->amount),
                strtoupper((string) $transaction->channel),
                $transaction->reference,
            ),
        ]];
    }

    /**
     * @return array<int, array{user:User|null,message:string}>
     */
    protected function buildBankTransferOutAlerts(Transaction $transaction): array
    {
        $sender = $transaction->fromWallet?->user;

        if (!$sender) {
            return [];
        }

        $destinationName = (string) ($transaction->metadata['destination_account_name'] ?? 'bank account');
        $destinationAccount = (string) ($transaction->metadata['destination_account_number'] ?? '');

        $message = sprintf(
            'Your bank transfer of %s to %s%s has been submitted for processing. Ref: %s. -PayEase',
            $this->formatMoney($transaction->amount),
            $destinationName,
            $destinationAccount !== '' ? " ({$destinationAccount})" : '',
            $transaction->reference,
        );

        if ($transaction->status === 'pending_disbursement_otp') {
            $message = sprintf(
                'Your bank transfer of %s to %s%s is awaiting OTP validation. Ref: %s. -PayEase',
                $this->formatMoney($transaction->amount),
                $destinationName,
                $destinationAccount !== '' ? " ({$destinationAccount})" : '',
                $transaction->reference,
            );
        }

        return [[
            'user' => $sender,
            'message' => $message,
        ]];
    }

    protected function formatMoney(float|int|string|null $amount): string
    {
        return '₦' . number_format((float) $amount, 2);
    }
}
