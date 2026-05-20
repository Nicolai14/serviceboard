<?php

namespace App\Http\Controllers;

use App\Jobs\SyncDockerContainersJob;
use App\Models\DockerContainer;
use App\Models\Server;
use App\Services\DockerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DockerController extends Controller
{
    public function __construct(private readonly DockerService $dockerService) {}

    /**
     * GET /docker
     * Global Docker overview — all containers across all user servers.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $serverIds = $user->servers()->pluck('id');

        $containers = DockerContainer::whereIn('server_id', $serverIds)
            ->with('server')
            ->orderByRaw("state = 'running' DESC")
            ->orderBy('server_id')
            ->orderBy('name')
            ->get();

        $stats = [
            'total'   => $containers->count(),
            'running' => $containers->where('state', 'running')->count(),
            'stopped' => $containers->whereNotIn('state', ['running'])->count(),
            'servers' => $user->servers()->count(),
        ];

        return view('docker.index', compact('containers', 'stats'));
    }

    /**
     * GET /servers/{server}/docker
     * Per-server Docker container list.
     */
    public function serverIndex(Server $server): View
    {
        $this->authorize('view', $server);

        $containers = $this->dockerService->getContainersForServer($server);

        return view('docker.server', compact('server', 'containers'));
    }

    /**
     * GET /servers/{server}/docker/status-json
     * JSON payload for Alpine.js live polling.
     */
    public function statusJson(Server $server): JsonResponse
    {
        $this->authorize('view', $server);

        $containers = $this->dockerService->getContainersForServer($server);

        return response()->json([
            'synced_at'  => $server->dockerContainers()->max('synced_at'),
            'total'      => $containers->count(),
            'running'    => $containers->where('state', 'running')->count(),
            'containers' => $containers->map(fn ($c) => [
                'id'               => $c->id,
                'container_id'     => $c->container_id,
                'name'             => $c->name,
                'image'            => $c->image,
                'state'            => $c->state,
                'status_text'      => $c->status_text,
                'cpu_percent'      => $c->cpu_percent !== null ? round($c->cpu_percent, 2) : null,
                'memory_usage_mb'  => $c->memory_usage_mb !== null ? round($c->memory_usage_mb) : null,
                'memory_limit_mb'  => $c->memory_limit_mb !== null ? round($c->memory_limit_mb) : null,
                'memory_percent'   => $c->memory_percent !== null ? round($c->memory_percent, 1) : null,
                'ports'            => $c->ports ?? [],
                'port_summary'     => $c->port_summary,
            ])->values(),
        ]);
    }

    /**
     * POST /servers/{server}/docker/sync
     * Dispatch an immediate sync job and return JSON.
     */
    public function syncNow(Server $server): JsonResponse
    {
        $this->authorize('view', $server);

        if (!$server->hasSSHCredentials()) {
            return response()->json([
                'dispatched' => false,
                'message'    => 'Keine SSH-Zugangsdaten konfiguriert.',
            ], 422);
        }

        SyncDockerContainersJob::dispatch($server->id)->onQueue('monitoring');

        return response()->json([
            'dispatched' => true,
            'message'    => 'Docker-Sync läuft im Hintergrund…',
        ]);
    }
}
