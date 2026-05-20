<?php

namespace App\Models;

use App\Enums\WorkspaceType;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function servers(): HasMany
    {
        return $this->hasMany(Server::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function ownedTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'owner_id');
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_members')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    public function notificationChannels(): HasMany
    {
        return $this->hasMany(NotificationChannel::class);
    }

    public function workspaces(): HasMany
    {
        return $this->hasMany(Workspace::class);
    }

    public function ensureWorkspacesExist(): void
    {
        if (!$this->workspaces()->where('type', WorkspaceType::Personal)->exists()) {
            $this->workspaces()->create([
                'name'  => 'Privat',
                'type'  => WorkspaceType::Personal,
                'color' => '#3b82f6',
            ]);
        }

        if (!$this->workspaces()->where('type', WorkspaceType::Business)->exists()) {
            $this->workspaces()->create([
                'name'  => 'Geschäftlich',
                'type'  => WorkspaceType::Business,
                'color' => '#f59e0b',
            ]);
        }
    }
}
