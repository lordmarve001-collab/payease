<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KycDocument extends Model
{
    use HasFactory, HasUuids;

    public const DOCUMENT_TYPES = [
        'nin_slip',
        'bvn_slip',
        'liveness_capture',
        'proof_of_address',
        'address_indemnity_form',
        'nin',
        'bvn',
        'government_id',
        'utility_bill',
        'passport_photograph',
    ];

    protected $fillable = [
        'user_id',
        'document_type',
        'document_url',
        'verification_status',
        'verified_at',
        'rejection_reason',
        'submitted_by_agent_id',
        'verification_provider',
        'verification_reference',
        'match_confidence',
        'auto_verified',
        'verification_raw_response',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'auto_verified' => 'boolean',
        'match_confidence' => 'decimal:2',
        'verification_raw_response' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function submittedByAgent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'submitted_by_agent_id');
    }

    public function getViewableDocumentUrl(): ?string
    {
        $url = $this->document_url;

        if (!$url || str_starts_with($url, 'nin:') || str_starts_with($url, 'bvn:')) {
            return null;
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        if (str_starts_with($url, 'storage/')) {
            return asset($url);
        }

        return asset('storage/' . ltrim($url, '/'));
    }
}
