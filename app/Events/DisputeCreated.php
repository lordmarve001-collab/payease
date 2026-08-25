<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DisputeCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public \App\Models\Dispute $dispute,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.disputes'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'dispute.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->dispute->id,
            'category' => $this->dispute->category,
            'subject' => $this->dispute->subject,
            'status' => $this->dispute->status,
            'user_id' => $this->dispute->user_id,
            'created_at' => $this->dispute->created_at->toIso8601String(),
        ];
    }
}
