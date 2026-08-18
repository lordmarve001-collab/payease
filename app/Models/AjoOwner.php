<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AjoOwner extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'business_name',
        'business_description',
        'business_address',
        'lga',
        'state',
        'has_experience',
        'planned_groups',
        'members_per_group',
        'agent_assignment_preference',
        'reference_contact_name',
        'reference_contact_phone',
        'bank_name',
        'account_name',
        'account_number',
        'status',
        'rejection_reason',
        'approved_at',
        'approved_by',
    ];

    protected $casts = [
        'has_experience' => 'boolean',
        'planned_groups' => 'integer',
        'members_per_group' => 'integer',
        'approved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class);
    }

    public function ajoGroups(): HasMany
    {
        return $this->hasMany(AjoGroup::class);
    }
}
