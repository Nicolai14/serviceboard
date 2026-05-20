<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CloudflareZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'cloudflare_token_id',
        'user_id',
        'zone_id',
        'name',
        'status',
        'paused',
        'plan_name',
        'type',
        'name_servers',
        'original_name_servers',
        'synced_at',
    ];

    protected $casts = [
        'paused'                 => 'boolean',
        'name_servers'           => 'array',
        'original_name_servers'  => 'array',
        'synced_at'              => 'datetime',
    ];

    public function cloudflareToken(): BelongsTo
    {
        return $this->belongsTo(CloudflareToken::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dnsRecords(): HasMany
    {
        return $this->hasMany(DnsRecord::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && !$this->paused;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where('paused', false);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
