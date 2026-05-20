<?php

namespace App\Models;

use App\Enums\TeamRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    protected $fillable = ['name', 'slug', 'owner_id', 'settings'];

    protected $casts = [
        'settings' => 'array',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_members')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    public function teamMembers(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    public function servers(): HasMany
    {
        return $this->hasMany(Server::class);
    }

    public function getMemberRole(User $user): ?TeamRole
    {
        if ($user->id === $this->owner_id) {
            return TeamRole::Owner;
        }

        $member = $this->teamMembers()->where('user_id', $user->id)->first();

        return $member ? TeamRole::from($member->role) : null;
    }

    public function userCan(User $user, string $ability): bool
    {
        $role = $this->getMemberRole($user);

        if (! $role) {
            return false;
        }

        return match($ability) {
            'manage-members'    => $role->canManageMembers(),
            'manage-servers'    => $role->canManageServers(),
            'manage-cloudflare' => $role->canManageCloudflare(),
            'deploy'            => $role->canDeploy(),
            'view'              => $role->canView(),
            default             => false,
        };
    }
}
