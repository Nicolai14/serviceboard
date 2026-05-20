<?php

namespace App\Models;

use App\Enums\DeploymentStatus;
use App\Enums\DeploymentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deployment extends Model
{
    protected $fillable = [
        'server_id', 'user_id', 'name', 'type', 'status',
        'trigger', 'config', 'log', 'started_at', 'finished_at',
    ];

    protected $casts = [
        'config'      => 'array',
        'started_at'  => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getDeploymentStatusAttribute(): DeploymentStatus
    {
        return DeploymentStatus::from($this->status);
    }

    public function getDeploymentTypeAttribute(): DeploymentType
    {
        return DeploymentType::from($this->type);
    }

    public function getDurationAttribute(): ?int
    {
        if (! $this->started_at || ! $this->finished_at) {
            return null;
        }

        return (int) $this->started_at->diffInSeconds($this->finished_at);
    }

    public function isActive(): bool
    {
        return DeploymentStatus::from($this->status)->isActive();
    }

    public function appendLog(string $line): void
    {
        $this->log = ($this->log ?? '') . $line . "\n";
        $this->save();
    }
}
