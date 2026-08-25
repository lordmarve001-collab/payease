<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Referral extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'referrer_id',
        'referred_id',
        'status',
        'reward_amount',
        'reward_status',
        'qualified_at',
        'rewarded_at',
    ];

    protected $casts = [
        'reward_amount' => 'decimal:2',
        'qualified_at' => 'datetime',
        'rewarded_at' => 'datetime',
    ];

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referred(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_id');
    }

    public function qualify(float $amount = 100.00): void
    {
        $this->update([
            'status' => 'qualified',
            'reward_amount' => $amount,
            'qualified_at' => now(),
        ]);
    }

    public function reward(): void
    {
        $this->update([
            'status' => 'rewarded',
            'reward_status' => 'paid',
            'rewarded_at' => now(),
        ]);
    }
}
