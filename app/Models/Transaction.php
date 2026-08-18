<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'reference',
        'transaction_type',
        'amount',
        'fee',
        'commission',
        'status',
        'from_wallet_id',
        'to_wallet_id',
        'agent_id',
        'recipient_phone',
        'description',
        'metadata',
        'mmo_partner',
        'mmo_transaction_id',
        'device_id',
        'latitude',
        'longitude',
        'completed_at',
        'channel',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'commission' => 'decimal:2',
        'metadata' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'completed_at' => 'datetime',
    ];

    public function fromWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'from_wallet_id');
    }

    public function toWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'to_wallet_id');
    }

    public function agentUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }
}
