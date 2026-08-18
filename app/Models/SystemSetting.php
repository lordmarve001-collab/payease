<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'key',
        'payload',
    ];

    protected $casts = [
        'payload' => 'encrypted:array',
    ];
}
