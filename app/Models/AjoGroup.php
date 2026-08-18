<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AjoGroup extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'ajo_owner_id',
        'name',
        'model_type',
        'description',
        'contribution_amount',
        'owner_fee_percentage',
        'collection_period_days',
        'collection_end_date',
        'min_contribution',
        'max_contribution',
        'target_pool_amount',
        'frequency',
        'members_count',
        'payout_order',
        'managing_agent_id',
        'status',
        'start_date',
    ];

    protected $casts = [
        'contribution_amount' => 'decimal:2',
        'owner_fee_percentage' => 'decimal:2',
        'min_contribution' => 'decimal:2',
        'max_contribution' => 'decimal:2',
        'target_pool_amount' => 'decimal:2',
        'start_date' => 'date',
        'collection_end_date' => 'date',
    ];

    public function isRotational(): bool
    {
        return $this->model_type === 'rotational';
    }

    public function isSavingsPool(): bool
    {
        return $this->model_type === 'savings_pool';
    }

    public function isContinuousPool(): bool
    {
        return $this->model_type === 'continuous_pool';
    }

    public function ajoOwner(): BelongsTo
    {
        return $this->belongsTo(AjoOwner::class);
    }

    public function managingAgent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'managing_agent_id');
    }

    public function agents(): BelongsToMany
    {
        return $this->belongsToMany(Agent::class, 'ajo_group_agents', 'ajo_group_id', 'agent_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function members(): HasMany
    {
        return $this->hasMany(AjoMember::class, 'group_id');
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(AjoContribution::class, 'group_id');
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(AjoPayout::class, 'group_id');
    }
}
