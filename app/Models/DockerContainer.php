<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DockerContainer extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'container_id',
        'name',
        'image',
        'state',
        'status_text',
        'cpu_percent',
        'memory_usage_mb',
        'memory_limit_mb',
        'memory_percent',
        'ports',
        'synced_at',
        'notify_on_down',
    ];

    protected $casts = [
        'ports'            => 'array',
        'cpu_percent'      => 'float',
        'memory_usage_mb'  => 'float',
        'memory_limit_mb'  => 'float',
        'memory_percent'   => 'float',
        'synced_at'        => 'datetime',
        'notify_on_down'   => 'boolean',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function isRunning(): bool
    {
        return $this->state === 'running';
    }

    public function getPortSummaryAttribute(): string
    {
        if (empty($this->ports)) {
            return '—';
        }

        return collect($this->ports)
            ->map(fn ($p) => "{$p['host']}→{$p['container']}/{$p['proto']}")
            ->implode(', ');
    }

    public function scopeRunning($query)
    {
        return $query->where('state', 'running');
    }

    public function scopeForServer($query, int $serverId)
    {
        return $query->where('server_id', $serverId);
    }
}
