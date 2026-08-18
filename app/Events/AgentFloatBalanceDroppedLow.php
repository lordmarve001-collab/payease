<?php

namespace App\Events;

use App\Models\Agent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AgentFloatBalanceDroppedLow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Agent $agent,
    ) {
    }
}
