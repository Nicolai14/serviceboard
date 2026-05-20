<?php

namespace App\Models;

use App\Enums\NotificationChannelType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationChannel extends Model
{
    protected $fillable = [
        'user_id', 'name', 'type', 'config', 'is_active',
        'last_tested_at', 'last_test_status',
    ];

    protected $casts = [
        'config'         => 'encrypted:array',
        'is_active'      => 'boolean',
        'last_tested_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getChannelTypeAttribute(): NotificationChannelType
    {
        return NotificationChannelType::from($this->type);
    }
}
