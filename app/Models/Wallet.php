<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'wallet_type',
        'balance',
        'available_balance',
        'currency',
        'status',
        'daily_limit',
        'single_txn_limit',
        'mmo_partner',
        'mmo_wallet_id',
        'account_number',
        'wallet_account_number',
        'provider_reference',
        'provider_metadata',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'available_balance' => 'decimal:2',
        'daily_limit' => 'decimal:2',
        'single_txn_limit' => 'decimal:2',
        'provider_metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fromTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'from_wallet_id');
    }

    public function toTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'to_wallet_id');
    }
}
