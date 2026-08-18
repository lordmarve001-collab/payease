<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agent extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'ajo_owner_id',
        'business_name',
        'business_address',
        'gps_latitude',
        'gps_longitude',
        'lga',
        'state',
        'float_balance',
        'max_float',
        'commission_rate',
        'total_earnings',
        'status',
        'id_document_url',
        'shop_photo_url',
        'approved_at',
        'last_settlement_at',
        'settlement_frequency_days',
    ];

    protected $casts = [
        'float_balance' => 'decimal:2',
        'max_float' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'total_earnings' => 'decimal:2',
        'gps_latitude' => 'decimal:8',
        'gps_longitude' => 'decimal:8',
        'approved_at' => 'datetime',
        'last_settlement_at' => 'datetime',
        'settlement_frequency_days' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ajoOwner(): BelongsTo
    {
        return $this->belongsTo(AjoOwner::class);
    }

    public function managingAjoGroups(): HasMany
    {
        return $this->hasMany(AjoGroup::class, 'managing_agent_id');
    }

    public function assignedGroups(): BelongsToMany
    {
        return $this->belongsToMany(AjoGroup::class, 'ajo_group_agents', 'agent_id', 'ajo_group_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function floatTopUpRequests(): HasMany
    {
        return $this->hasMany(FloatTopUpRequest::class);
    }

    public function pendingTopUpRequest(): HasMany
    {
        return $this->hasMany(FloatTopUpRequest::class)->where('status', 'pending');
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(AgentSettlement::class);
    }
}
