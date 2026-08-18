<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactSubmission extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name', 'email', 'phone', 'subject', 'message',
        'status', 'ip_address', 'notes',
    ];

    protected $casts = [];

    public function scopeUnread($query)
    {
        return $query->where('status', 'unread');
    }
}
