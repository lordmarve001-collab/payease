<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AjoContribution extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'group_id',
        'user_id',
        'logged_by_agent_id',
        'amount',
        'cycle_number',
        'status',
        'transaction_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(AjoGroup::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function loggedByAgent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'logged_by_agent_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
