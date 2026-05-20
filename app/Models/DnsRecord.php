<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DnsRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'cloudflare_zone_id',
        'cf_record_id',
        'type',
        'name',
        'content',
        'proxied',
        'proxiable',
        'ttl',
        'priority',
        'comment',
        'created_on',
        'modified_on',
        'synced_at',
    ];

    protected $casts = [
        'proxied'     => 'boolean',
        'proxiable'   => 'boolean',
        'created_on'  => 'datetime',
        'modified_on' => 'datetime',
        'synced_at'   => 'datetime',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(CloudflareZone::class, 'cloudflare_zone_id');
    }

    public function getTtlLabelAttribute(): string
    {
        return $this->ttl === 1 ? 'Auto' : $this->ttl . 's';
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', strtoupper($type));
    }

    public function scopeProxied($query)
    {
        return $query->where('proxied', true);
    }
}
