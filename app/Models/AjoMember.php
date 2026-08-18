<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AjoMember extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'group_id',
        'user_id',
        'position',
        'status',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(AjoGroup::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
