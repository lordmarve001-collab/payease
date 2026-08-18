<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Transaction;

class TransactionObserver
{
    public function created(Transaction $transaction): void
    {
        AuditLog::create([
            'action' => 'transaction_created',
            'entity_type' => 'transaction',
            'entity_id' => $transaction->id,
            'new_values' => $transaction->only([
                'status', 'transaction_type', 'amount', 'reference',
                'from_wallet_id', 'to_wallet_id', 'channel',
            ]),
        ]);
    }

    public function updated(Transaction $transaction): void
    {
        if (!$transaction->isDirty('status')) {
            return;
        }

        AuditLog::create([
            'action' => 'transaction_status_changed',
            'entity_type' => 'transaction',
            'entity_id' => $transaction->id,
            'old_values' => ['status' => $transaction->getOriginal('status')],
            'new_values' => ['status' => $transaction->status],
        ]);
    }
}
