<?php

namespace App\Jobs;

use App\Exceptions\SSHException;
use App\Models\Server;
use App\Services\AlertService;
use App\Services\DockerService;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SyncDockerContainersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 30;
    public int $backoff = 20;

    public function __construct(public readonly int $serverId) {}

    public function handle(DockerService $dockerService, AlertService $alertService, NotificationService $notificationService): void
    {
        $server = Server::find($this->serverId);

        if (!$server || !$server->hasSSHCredentials() || $server->status === 'maintenance') {
            return;
        }

        try {
            $count = $dockerService->sync($server);

            $this->checkForStoppedContainers($server, $alertService, $notificationService);

            logger()->info("DockerSync: {$count} containers synced for server {$server->name}");
        } catch (SSHException $e) {
            logger()->warning("DockerSync failed for server {$server->name}: {$e->getMessage()}");
        }
    }

    public function failed(Throwable $e): void
    {
        logger()->error("SyncDockerContainersJob failed for server #{$this->serverId}: {$e->getMessage()}");
    }

    private function checkForStoppedContainers(Server $server, AlertService $alertService, NotificationService $notificationService): void
    {
        $owner = $server->user;

        $stopped = $server->dockerContainers()
            ->where('state', '!=', 'running')
            ->where('state', '!=', 'created')
            ->where('synced_at', '>=', now()->subMinutes(5))
            ->get();

        foreach ($stopped as $container) {
            $existingAlert = $server->alerts()
                ->where('type', 'container_down')
                ->where('context->container_id', $container->container_id)
                ->whereNull('resolved_at')
                ->exists();

            if (!$existingAlert) {
                $alertService->create(
                    $server,
                    $owner,
                    'container_down',
                    'warning',
                    "Container \"{$container->name}\" ist nicht mehr aktiv ({$container->state})",
                    ['container_id' => $container->container_id, 'image' => $container->image]
                );

                if ($container->notify_on_down) {
                    $notificationService->dispatch(
                        $owner,
                        "🚨 Container down: {$container->name}",
                        "Server: *{$server->name}*\nContainer: `{$container->name}`\nStatus: {$container->state}\nImage: {$container->image}"
                    );
                }
            }
        }
    }
}
