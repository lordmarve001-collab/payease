<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AjoPayoutQueue extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'ajo_payout_queue';

    protected $fillable = [
        'ajo_payout_id',
        'group_id',
        'member_user_id',
        'agent_id',
        'amount',
        'cycle_number',
        'status',
        'note',
        'processed_at',
        'processed_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'cycle_number' => 'integer',
        'processed_at' => 'datetime',
    ];

    public function ajoPayout(): BelongsTo
    {
        return $this->belongsTo(AjoPayout::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(AjoGroup::class);
    }

    public function memberUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'member_user_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
