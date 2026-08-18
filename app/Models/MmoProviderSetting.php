<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MmoProviderSetting extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'provider',
        'is_active',
        'environment',
        'credentials',
        'last_tested_at',
        'last_test_status',
        'last_test_message',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'credentials' => 'encrypted:array',
        'last_tested_at' => 'datetime',
    ];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
