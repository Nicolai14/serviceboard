<?php

namespace App\Policies;

use App\Models\Server;
use App\Models\User;

class ServerPolicy
{
    public function view(User $user, Server $server): bool
    {
        return $user->id === $server->user_id;
    }

    public function update(User $user, Server $server): bool
    {
        return $user->id === $server->user_id;
    }

    public function delete(User $user, Server $server): bool
    {
        return $user->id === $server->user_id;
    }
}
