<?php

namespace App\Http\Controllers;

use App\Enums\DeploymentType;
use App\Jobs\RunDeploymentJob;
use App\Models\Deployment;
use App\Models\Server;
use App\Services\DeploymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DeploymentController extends Controller
{
    public function __construct(private readonly DeploymentService $deployments) {}

    public function index(Server $server): View
    {
        $this->authorize('view', $server);

        $deployments = $server->deployments()->latest()->paginate(20);

        return view('deployments.index', compact('server', 'deployments'));
    }

    public function create(Server $server): View
    {
        $this->authorize('view', $server);

        return view('deployments.create', [
            'server' => $server,
            'types'  => DeploymentType::cases(),
        ]);
    }

    public function store(Request $request, Server $server): RedirectResponse
    {
        $this->authorize('update', $server);

        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:100'],
            'type'         => ['required', Rule::in(array_map(fn ($t) => $t->value, DeploymentType::cases()))],
            'directory'    => ['nullable', 'string', 'max:255'],
            'repository'   => ['nullable', 'string', 'max:255'],
            'branch'       => ['nullable', 'string', 'max:100'],
            'script'       => ['nullable', 'string', 'max:10000'],
            'compose_file' => ['nullable', 'string', 'max:255'],
            'pull_images'  => ['nullable', 'boolean'],
        ]);

        $deployment = $this->deployments->create($server, $request->user(), [
            'name'   => $validated['name'],
            'type'   => $validated['type'],
            'config' => $this->buildConfig($request, $validated['type']),
        ]);

        RunDeploymentJob::dispatch($deployment->id)->onQueue('monitoring');

        return redirect()
            ->route('servers.deployments.show', [$server, $deployment])
            ->with('success', 'Deployment gestartet.');
    }

    public function show(Server $server, Deployment $deployment): View
    {
        $this->authorize('view', $server);
        abort_unless($deployment->server_id === $server->id, 404);

        return view('deployments.show', compact('server', 'deployment'));
    }

    public function statusJson(Server $server, Deployment $deployment): JsonResponse
    {
        $this->authorize('view', $server);
        abort_unless($deployment->server_id === $server->id, 404);

        $status = $deployment->getDeploymentStatusAttribute();

        return response()->json([
            'status'   => $status->value,
            'label'    => $status->label(),
            'color'    => $status->color(),
            'active'   => $status->isActive(),
            'log'      => $deployment->log ?? '',
            'duration' => $deployment->duration,
        ]);
    }

    public function retry(Server $server, Deployment $deployment): RedirectResponse
    {
        $this->authorize('update', $server);
        abort_unless($deployment->server_id === $server->id, 404);

        $clone = $this->deployments->create($server, $server->user, [
            'name'   => $deployment->name,
            'type'   => $deployment->type,
            'config' => $deployment->config,
        ]);

        RunDeploymentJob::dispatch($clone->id)->onQueue('monitoring');

        return redirect()
            ->route('servers.deployments.show', [$server, $clone])
            ->with('success', 'Deployment erneut gestartet.');
    }

    public function destroy(Server $server, Deployment $deployment): RedirectResponse
    {
        $this->authorize('update', $server);
        abort_unless($deployment->server_id === $server->id, 404);

        $deployment->delete();

        return redirect()
            ->route('servers.deployments.index', $server)
            ->with('success', 'Deployment-Eintrag entfernt.');
    }

    /**
     * @return array<string, mixed>
     */
    private function buildConfig(Request $request, string $type): array
    {
        return match ($type) {
            DeploymentType::Git->value => array_filter([
                'repository' => $request->string('repository')->trim()->value(),
                'branch'     => $request->string('branch')->trim()->value() ?: 'main',
                'directory'  => $request->string('directory')->trim()->value(),
            ], fn ($v) => $v !== ''),

            DeploymentType::Script->value => array_filter([
                'script'    => (string) $request->input('script', ''),
                'directory' => $request->string('directory')->trim()->value(),
            ], fn ($v) => $v !== ''),

            DeploymentType::DockerCompose->value => array_filter([
                'directory'    => $request->string('directory')->trim()->value(),
                'compose_file' => $request->string('compose_file')->trim()->value() ?: 'docker-compose.yml',
                'pull_images'  => $request->boolean('pull_images'),
            ], fn ($v) => $v !== '' && $v !== false),

            default => [],
        };
    }
}
