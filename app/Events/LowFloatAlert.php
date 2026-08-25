<?php

namespace App\Events;

use App\Models\Transaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LowFloatAlert implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Transaction $transaction,
        public float $remainingFloat,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.float-alerts'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'admin.low-float';
    }

    public function broadcastWith(): array
    {
        return [
            'agent_id' => $this->transaction->agent_id,
            'remaining_float' => $this->remainingFloat,
            'transaction_id' => $this->transaction->id,
            'amount' => (float) $this->transaction->amount,
            'created_at' => $this->transaction->created_at->toIso8601String(),
        ];
    }
}
