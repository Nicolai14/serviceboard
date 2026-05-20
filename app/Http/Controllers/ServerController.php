<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreServerRequest;
use App\Http\Requests\UpdateServerRequest;
use App\Jobs\PollServerMetricsJob;
use App\Models\Server;
use App\Services\MetricService;
use App\Services\ServerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServerController extends Controller
{
    public function __construct(
        private readonly ServerService $serverService,
        private readonly MetricService $metricService,
    ) {}

    public function index(Request $request): View
    {
        $workspace = app('activeWorkspace');
        $servers   = $this->serverService->getAllForUser(
            $request->user(),
            $request->only(['status', 'search']),
            $workspace,
        );

        return view('servers.index', compact('servers'));
    }

    public function create(): View
    {
        return view('servers.create');
    }

    public function store(StoreServerRequest $request): RedirectResponse
    {
        $workspace = app('activeWorkspace');
        $this->serverService->create($request->user(), $request->validated(), $workspace);

        return redirect()->route('servers.index')->with('success', 'Server wurde erfolgreich hinzugefügt.');
    }

    public function show(Server $server): View
    {
        $this->authorize('view', $server);

        $latestMetric = $this->metricService->getLatest($server);
        $chartData    = $this->metricService->getChartData($server, 24);

        $server->load(['services', 'alerts' => fn ($q) => $q->latest()->limit(10)]);

        return view('servers.show', compact('server', 'latestMetric', 'chartData'));
    }

    public function edit(Server $server): View
    {
        $this->authorize('update', $server);

        return view('servers.edit', compact('server'));
    }

    public function update(UpdateServerRequest $request, Server $server): RedirectResponse
    {
        $this->authorize('update', $server);

        $this->serverService->update($server, $request->validated());

        return redirect()->route('servers.show', $server)->with('success', 'Server wurde aktualisiert.');
    }

    public function destroy(Server $server): RedirectResponse
    {
        $this->authorize('delete', $server);

        $this->serverService->delete($server);

        return redirect()->route('servers.index')->with('success', 'Server wurde gelöscht.');
    }

    // -------------------------------------------------------------------------
    // AJAX / JSON endpoints

    /**
     * GET /servers/{server}/status-json
     * Returns latest metric data for live widget updates.
     */
    public function statusJson(Server $server): JsonResponse
    {
        $this->authorize('view', $server);

        $metric = $this->metricService->getLatest($server);

        return response()->json([
            'status'         => $server->status,
            'last_seen_at'   => $server->last_seen_at?->diffForHumans(),
            'last_polled_at' => $server->last_polled_at?->diffForHumans(),
            'has_metric'     => (bool) $metric,
            'cpu'            => $metric ? round($metric->cpu_usage, 1) : null,
            'memory_percent' => $metric ? round($metric->memory_percent, 1) : null,
            'disk_percent'   => $metric ? round($metric->disk_percent, 1) : null,
            'load'           => $metric ? round($metric->load_average, 2) : null,
            'uptime_seconds' => $metric?->uptime_seconds,
        ]);
    }

    /**
     * POST /servers/{server}/check-online
     * Fast TCP ping — no SSH needed.
     */
    public function checkOnline(Server $server): JsonResponse
    {
        $this->authorize('view', $server);

        $online = $this->serverService->checkConnectivity($server);

        return response()->json([
            'online'  => $online,
            'status'  => $server->fresh()->status,
            'message' => $online
                ? "Host {$server->hostname}:{$server->ssh_port} ist erreichbar."
                : "Host {$server->hostname}:{$server->ssh_port} antwortet nicht.",
        ]);
    }

    /**
     * POST /servers/{server}/test-ssh
     * Full SSH handshake + auth test.
     */
    public function testSsh(Server $server): JsonResponse
    {
        $this->authorize('view', $server);

        $result = $this->serverService->testSSHConnection($server);

        return response()->json($result);
    }

    /**
     * POST /servers/{server}/poll-now
     * Dispatch an immediate SSH metrics poll job.
     */
    public function pollNow(Server $server): JsonResponse
    {
        $this->authorize('view', $server);

        if (!$server->hasSSHCredentials()) {
            return response()->json([
                'dispatched' => false,
                'message'    => 'Keine SSH-Zugangsdaten konfiguriert.',
            ], 422);
        }

        PollServerMetricsJob::dispatch($server->id)->onQueue('monitoring');

        return response()->json([
            'dispatched' => true,
            'message'    => 'Metriken werden im Hintergrund abgerufen…',
        ]);
    }
}
