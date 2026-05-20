<?php

namespace App\Services;

use App\Exceptions\SSHException;
use App\Models\Server;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class ServerService
{
    public function __construct(
        private readonly SSHService    $sshService,
        private readonly MetricService $metricService,
    ) {}

    public function getAllForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = $user->servers()->with(['services', 'alerts' => fn ($q) => $q->unread()]);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('hostname', 'like', "%{$filters['search']}%")
                  ->orWhere('ip_address', 'like', "%{$filters['search']}%");
            });
        }

        return $query->latest()->paginate(15);
    }

    public function create(User $user, array $data): Server
    {
        return $user->servers()->create($data);
    }

    public function update(Server $server, array $data): Server
    {
        // Don't overwrite key/password if the user left the field blank.
        if (empty($data['ssh_private_key'])) {
            unset($data['ssh_private_key']);
        }
        if (empty($data['ssh_password'])) {
            unset($data['ssh_password']);
        }

        $server->update($data);

        return $server->fresh();
    }

    public function delete(Server $server): void
    {
        $server->delete();
    }

    // -------------------------------------------------------------------------
    // Connectivity

    /**
     * Fast TCP reachability check — updates server status, returns bool.
     */
    public function checkConnectivity(Server $server): bool
    {
        $online = $this->sshService->isReachable($server);

        if ($online) {
            $server->update(['status' => 'online', 'last_seen_at' => now()]);
        } else {
            $server->markOffline();
        }

        return $online;
    }

    /**
     * Full SSH auth test — returns result array for JSON responses.
     */
    public function testSSHConnection(Server $server): array
    {
        return $this->sshService->testConnection($server);
    }

    /**
     * SSH into server, collect metrics, store in DB. Called by polling jobs.
     *
     * @throws SSHException
     */
    public function pollMetrics(Server $server): void
    {
        $metrics = $this->sshService->collectMetrics($server);

        $this->metricService->record($server, $metrics);

        $server->update([
            'status'         => 'online',
            'last_seen_at'   => now(),
            'last_polled_at' => now(),
            'poll_failures'  => 0,
        ]);
    }

    // -------------------------------------------------------------------------

    public function getStatusSummary(User $user): array
    {
        $servers = $user->servers();

        return [
            'total'       => (clone $servers)->count(),
            'online'      => (clone $servers)->online()->count(),
            'offline'     => (clone $servers)->offline()->count(),
            'maintenance' => (clone $servers)->where('status', 'maintenance')->count(),
        ];
    }
}
