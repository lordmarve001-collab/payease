<?php

namespace App\Providers;

use App\Events\AgentFloatBalanceDroppedLow;
use App\Events\TransactionCompleted;
use App\Listeners\SendLowFloatAlert;
use App\Listeners\SendTransactionAlert;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        TransactionCompleted::class => [
            SendTransactionAlert::class,
        ],
        AgentFloatBalanceDroppedLow::class => [
            SendLowFloatAlert::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
