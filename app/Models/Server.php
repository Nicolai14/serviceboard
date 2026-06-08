<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'alerts_enabled',
        'alert_thresholds',
    ];

    protected $casts = [
        'tags'           => 'array',
        'ssh_port'       => 'integer',
        'poll_failures'  => 'integer',
        'last_seen_at'   => 'datetime',
        'last_polled_at' => 'datetime',
        'ssh_private_key' => 'encrypted',
        'ssh_password'    => 'encrypted',
        'alerts_enabled'  => 'boolean',
        'alert_thresholds' => 'array',
    ];

    /**
     * Default alert thresholds (percent) used when a server has no overrides.
     *
     * @var array<string, int>
     */
    public const DEFAULT_THRESHOLDS = [
        'cpu_warning'     => 80,
        'cpu_critical'    => 90,
        'memory_warning'  => 80,
        'memory_critical' => 90,
        'disk_warning'    => 80,
        'disk_critical'   => 90,
    ];

    /**
     * Effective alert thresholds: stored overrides merged over the defaults.
     *
     * @return array<string, int>
     */
    public function thresholds(): array
    {
        return array_merge(self::DEFAULT_THRESHOLDS, $this->alert_thresholds ?? []);
    }

    protected $hidden = [
        'ssh_private_key',
        'ssh_password',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Team, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return HasMany<Deployment, $this> */
    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class);
    }

    /** @return HasMany<Service, $this> */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /** @return HasMany<Metric, $this> */
    public function metrics(): HasMany
    {
        return $this->hasMany(Metric::class);
    }

    /** @return HasOne<Metric, $this> */
    public function latestMetric(): HasOne
    {
        return $this->hasOne(Metric::class)->latestOfMany('recorded_at');
    }

    /** @return HasMany<Alert, $this> */
    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    /** @return HasMany<DockerContainer, $this> */
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
