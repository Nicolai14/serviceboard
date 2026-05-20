<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Server;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Pagination\LengthAwarePaginator;

class AlertService
{
    public function create(Server $server, User $user, string $type, string $severity, string $message, array $context = []): Alert
    {
        return Alert::create([
            'server_id' => $server->id,
            'user_id'   => $user->id,
            'type'      => $type,
            'severity'  => $severity,
            'message'   => $message,
            'context'   => $context,
        ]);
    }

    public function getForUser(User $user, array $filters = [], ?Workspace $workspace = null): LengthAwarePaginator
    {
        $query = Alert::where('user_id', $user->id)->with('server');

        if ($workspace) {
            $serverIds = $workspace->servers()->pluck('id');
            $query->whereIn('server_id', $serverIds);
        }

        if (!empty($filters['severity'])) {
            $query->bySeverity($filters['severity']);
        }

        if (isset($filters['unread']) && $filters['unread']) {
            $query->unread();
        }

        if (!empty($filters['server_id'])) {
            $query->where('server_id', $filters['server_id']);
        }

        return $query->latest()->paginate(20);
    }

    public function markAllAsRead(User $user, ?Workspace $workspace = null): int
    {
        $query = Alert::where('user_id', $user->id)->unread();

        if ($workspace) {
            $serverIds = $workspace->servers()->pluck('id');
            $query->whereIn('server_id', $serverIds);
        }

        return $query->update(['is_read' => true]);
    }

    public function markAsRead(Alert $alert): void
    {
        $alert->markAsRead();
    }

    public function resolve(Alert $alert): void
    {
        $alert->resolve();
    }

    public function getUnreadCount(User $user, ?Workspace $workspace = null): int
    {
        $query = Alert::where('user_id', $user->id)->unread();

        if ($workspace) {
            $serverIds = $workspace->servers()->pluck('id');
            $query->whereIn('server_id', $serverIds);
        }

        return $query->count();
    }

    public function getRecentForUser(User $user, int $limit = 5, ?Workspace $workspace = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = Alert::where('user_id', $user->id)->with('server')->latest()->limit($limit);

        if ($workspace) {
            $serverIds = $workspace->servers()->pluck('id');
            $query->whereIn('server_id', $serverIds);
        }

        return $query->get();
    }
}
