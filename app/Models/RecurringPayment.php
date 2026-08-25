<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringPayment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'payment_type',
        'amount',
        'frequency',
        'payment_details',
        'status',
        'transfer_pin_hash',
        'next_execution_at',
        'last_executed_at',
        'executions_count',
        'max_executions',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_details' => 'array',
        'next_execution_at' => 'datetime',
        'last_executed_at' => 'datetime',
    ];

    protected $hidden = [
        'transfer_pin_hash',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isDue(): bool
    {
        return $this->status === 'active'
            && $this->next_execution_at
            && $this->next_execution_at->isPast();
    }

    public function shouldContinue(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->max_executions !== null && $this->executions_count >= $this->max_executions) {
            return false;
        }

        return true;
    }

    public function getNextExecutionDate(): \Carbon\Carbon
    {
        return match ($this->frequency) {
            'daily' => now()->addDay(),
            'weekly' => now()->addWeek(),
            'monthly' => now()->addMonth(),
            default: now()->addMonth(),
        };
    }

    public function recordExecution(): void
    {
        $this->update([
            'executions_count' => $this->executions_count + 1,
            'last_executed_at' => now(),
            'next_execution_at' => $this->shouldContinue() ? $this->getNextExecutionDate() : null,
            'status' => $this->shouldContinue() ? $this->status : 'completed',
        ]);
    }
}
