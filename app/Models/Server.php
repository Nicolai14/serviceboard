<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Server extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'workspace_id',
        'name',
        'hostname',
        'ip_address',
        'ssh_port',
        'ssh_user',
        'ssh_auth_method',
        'ssh_private_key',
        'ssh_password',
        'status',
        'os',
        'tags',
        'notes',
        'last_seen_at',
        'last_polled_at',
        'poll_failures',
    ];

    protected $casts = [
        'tags'           => 'array',
        'ssh_port'       => 'integer',
        'poll_failures'  => 'integer',
        'last_seen_at'   => 'datetime',
        'last_polled_at' => 'datetime',
        'ssh_private_key' => 'encrypted',
        'ssh_password'    => 'encrypted',
    ];

    protected $hidden = [
        'ssh_private_key',
        'ssh_password',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(Metric::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function dockerContainers(): HasMany
    {
        return $this->hasMany(DockerContainer::class);
    }

    public function isOnline(): bool
    {
        return $this->status === 'online';
    }

    public function hasSSHCredentials(): bool
    {
        return match ($this->ssh_auth_method) {
            'key'      => filled($this->ssh_private_key),
            'password' => filled($this->ssh_password),
            default    => false,
        };
    }

    public function markOnline(): void
    {
        $this->update(['status' => 'online', 'last_seen_at' => now(), 'poll_failures' => 0]);
    }

    public function markOffline(): void
    {
        $this->update(['status' => 'offline']);
    }

    public function incrementPollFailures(): void
    {
        $this->increment('poll_failures');

        if ($this->poll_failures >= 3) {
            $this->update(['status' => 'offline']);
        }
    }

    public function scopeOnline($query)
    {
        return $query->where('status', 'online');
    }

    public function scopeOffline($query)
    {
        return $query->where('status', 'offline');
    }

    public function scopeWithCredentials($query)
    {
        return $query->where(function ($q) {
            $q->where(function ($q2) {
                $q2->where('ssh_auth_method', 'key')->whereNotNull('ssh_private_key');
            })->orWhere(function ($q2) {
                $q2->where('ssh_auth_method', 'password')->whereNotNull('ssh_password');
            });
        });
    }
}
