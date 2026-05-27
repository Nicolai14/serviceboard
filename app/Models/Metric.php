<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Metric extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'server_id',
        'cpu_usage',
        'memory_usage',
        'memory_total',
        'disk_usage',
        'disk_total',
        'load_average',
        'uptime_seconds',
        'recorded_at',
    ];

    protected $casts = [
        'cpu_usage' => 'float',
        'memory_usage' => 'float',
        'memory_total' => 'float',
        'disk_usage' => 'float',
        'disk_total' => 'float',
        'load_average' => 'float',
        'uptime_seconds' => 'integer',
        'recorded_at' => 'datetime',
    ];

    /** @return BelongsTo<Server, $this> */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function getCpuPercentAttribute(): string
    {
        return number_format($this->cpu_usage, 1) . '%';
    }

    public function getMemoryPercentAttribute(): float
    {
        if ($this->memory_total === 0.0) {
            return 0;
        }

        return round(($this->memory_usage / $this->memory_total) * 100, 1);
    }

    public function getDiskPercentAttribute(): float
    {
        if ($this->disk_total === 0.0) {
            return 0;
        }

        return round(($this->disk_usage / $this->disk_total) * 100, 1);
    }
}
