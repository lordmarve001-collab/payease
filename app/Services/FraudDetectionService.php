<?php

namespace App\Services;

use App\Models\FraudAlert;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class FraudDetectionService
{
    protected const VELOCITY_WINDOW = 3600; // 1 hour
    protected const MAX_TRANSACTIONS_PER_HOUR = 10;
    protected const MAX_AMOUNT_PER_HOUR = 500000;
    protected const MAX_UNIQUE_RECIPIENTS_PER_DAY = 15;

    public function checkTransaction(Transaction $transaction, User $user): void
    {
        $this->checkVelocity($user, $transaction);
        $this->checkAnomalousAmount($user, $transaction);
        $this->checkUniqueRecipients($user, $transaction);
    }

    protected function checkVelocity(User $user, Transaction $transaction): void
    {
        $key = "fraud_tx_count_{$user->id}";
        $count = Cache::get($key, 0);

        if ($count >= self::MAX_TRANSACTIONS_PER_HOUR) {
            $this->createAlert(
                $user,
                'velocity',
                'high',
                $transaction,
                "User exceeded {$count} transactions in 1 hour (limit: " . self::MAX_TRANSACTIONS_PER_HOUR . ")",
                ['count' => $count, 'window' => self::VELOCITY_WINDOW]
            );
            return;
        }

        Cache::put($key, $count + 1, self::VELOCITY_WINDOW);

        $amountKey = "fraud_tx_amount_{$user->id}";
        $totalAmount = Cache::get($amountKey, 0) + (float) $transaction->amount;

        if ($totalAmount > self::MAX_AMOUNT_PER_HOUR) {
            $this->createAlert(
                $user,
                'anomalous_amount',
                'medium',
                $transaction,
                "User exceeded ₦" . number_format(self::MAX_AMOUNT_PER_HOUR) . " in transactions within 1 hour",
                ['total_amount' => $totalAmount, 'window' => self::VELOCITY_WINDOW]
            );
            return;
        }

        Cache::put($amountKey, $totalAmount, self::VELOCITY_WINDOW);
    }

    protected function checkAnomalousAmount(User $user, Transaction $transaction): void
    {
        $avgKey = "fraud_avg_amount_{$user->id}";
        $avgAmount = Cache::get($avgKey);

        if ($avgAmount === null) {
            $avgAmount = (float) $user->wallets()
                ->where('wallet_type', 'customer')
                ->first()
                ?->fromTransactions()
                ->where('status', 'completed')
                ->where('created_at', '>=', now()->subDays(30))
                ->avg('amount') ?? 0;

            Cache::put($avgKey, $avgAmount, 86400);
        }

        if ($avgAmount > 0 && (float) $transaction->amount > $avgAmount * 5) {
            $this->createAlert(
                $user,
                'anomalous_amount',
                'medium',
                $transaction,
                "Transaction amount ₦" . number_format($transaction->amount) . " is " . round((float) $transaction->amount / max($avgAmount, 1)) . "x the user's 30-day average",
                ['avg_amount' => $avgAmount, 'transaction_amount' => $transaction->amount]
            );
        }
    }

    protected function checkUniqueRecipients(User $user, Transaction $transaction): void
    {
        if (!$transaction->to_wallet_id) {
            return;
        }

        $recipientWalletId = $transaction->to_wallet_id;
        $key = "fraud_recipients_{$user->id}";
        $recipients = Cache::get($key, []);

        $recipients[] = $recipientWalletId;
        $uniqueRecipients = array_unique($recipients);

        Cache::put($key, $uniqueRecipients, 86400);

        if (count($uniqueRecipients) > self::MAX_UNIQUE_RECIPIENTS_PER_DAY) {
            $this->createAlert(
                $user,
                'velocity',
                'medium',
                $transaction,
                "User sent to " . count($uniqueRecipients) . " unique recipients in 24 hours",
                ['unique_recipients' => count($uniqueRecipients), 'limit' => self::MAX_UNIQUE_RECIPIENTS_PER_DAY]
            );
        }
    }

    protected function createAlert(
        User $user,
        string $type,
        string $severity,
        Transaction $transaction,
        string $description,
        array $context = [],
    ): FraudAlert {
        return FraudAlert::create([
            'user_id' => $user->id,
            'alert_type' => $type,
            'severity' => $severity,
            'entity_type' => 'transaction',
            'entity_id' => $transaction->id,
            'description' => $description,
            'context' => $context,
        ]);
    }
}
