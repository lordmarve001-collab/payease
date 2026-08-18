<?php

namespace App\Services;

use App\Models\AjoPayoutQueue;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AjoPayoutQueueService
{
    public function processQueueItem(string $queueId, User $admin): AjoPayoutQueue
    {
        return DB::transaction(function () use ($queueId, $admin): AjoPayoutQueue {
            $queueItem = AjoPayoutQueue::query()
                ->where('id', $queueId)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->firstOrFail();

            $recipientWallet = Wallet::query()
                ->where('user_id', $queueItem->member_user_id)
                ->where('wallet_type', 'customer')
                ->lockForUpdate()
                ->first();

            if (!$recipientWallet) {
                throw new RuntimeException('Recipient does not have a customer wallet.');
            }

            $recipientWallet->balance = round((float) $recipientWallet->balance + (float) $queueItem->amount, 2);
            $recipientWallet->available_balance = round((float) $recipientWallet->available_balance + (float) $queueItem->amount, 2);
            $recipientWallet->save();

            $queueItem->update([
                'status' => 'completed',
                'note' => 'Wallet credited by admin.',
                'processed_at' => now(),
                'processed_by' => $admin->id,
            ]);

            $queueItem->ajoPayout->transaction->update([
                'metadata' => array_merge($queueItem->ajoPayout->transaction->metadata ?? [], [
                    'wallet_credit_applied' => true,
                    'wallet_credit_processed_by' => $admin->id,
                    'wallet_credit_processed_at' => now()->toIso8601String(),
                ]),
            ]);

            return $queueItem->fresh();
        });
    }

    public function rejectQueueItem(string $queueId, User $admin, string $note = ''): AjoPayoutQueue
    {
        $queueItem = AjoPayoutQueue::query()
            ->where('id', $queueId)
            ->where('status', 'pending')
            ->firstOrFail();

        $queueItem->update([
            'status' => 'failed',
            'note' => $note ?: 'Rejected by admin.',
            'processed_at' => now(),
            'processed_by' => $admin->id,
        ]);

        return $queueItem->fresh();
    }
}
