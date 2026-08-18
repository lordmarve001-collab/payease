<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AdminNotification extends Model
{
    use HasUuids;

    protected $fillable = [
        'type',
        'title',
        'message',
        'action_url',
        'action_label',
        'severity',
        'is_read',
        'related_id',
        'related_type',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function markRead(): void
    {
        $this->update(['is_read' => true]);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }
}
