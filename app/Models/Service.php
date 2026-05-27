<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'server_id',
        'name',
        'type',
        'port',
        'status',
        'check_url',
        'check_interval',
        'notes',
        'last_checked_at',
        'last_latency_ms',
        'notify_on_down',
    ];

    protected $casts = [
        'port' => 'integer',
        'check_interval' => 'integer',
        'last_checked_at' => 'datetime',
        'last_latency_ms' => 'integer',
        'notify_on_down' => 'boolean',
    ];

    /** @return BelongsTo<Server, $this> */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    public function scopeStopped($query)
    {
        return $query->where('status', 'stopped');
    }

    /**
     * A service can be health-checked once it has either an HTTP URL or a port.
     */
    public function scopeCheckable($query)
    {
        return $query->where(function ($q) {
            $q->whereNotNull('check_url')->orWhereNotNull('port');
        });
    }

    /**
     * Whether enough time has passed since the last check to run another one.
     */
    public function isCheckDue(): bool
    {
        return $this->last_checked_at === null
            || $this->last_checked_at->addSeconds($this->check_interval)->isPast();
    }
}
