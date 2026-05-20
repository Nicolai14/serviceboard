<?php

namespace App\Jobs;

use App\Exceptions\SSHException;
use App\Models\Server;
use App\Services\AlertService;
use App\Services\ServerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class PollServerMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 30;
    public int $backoff = 30;

    public function __construct(public readonly int $serverId) {}

    public function handle(ServerService $serverService, AlertService $alertService): void
    {
        $server = Server::find($this->serverId);

        if (!$server || $server->status === 'maintenance') {
            return;
        }

        try {
            $serverService->pollMetrics($server);

            // Trigger a high-CPU/RAM alert if thresholds are breached.
            $latest = $server->metrics()->latest('recorded_at')->first();
            if ($latest) {
                $this->checkThresholds($server, $latest, $alertService);
            }
        } catch (SSHException $e) {
            $server->incrementPollFailures();

            if ($server->poll_failures >= 3) {
                $alertService->create(
                    $server,
                    $server->user,
                    'ssh_failure',
                    'warning',
                    "SSH-Verbindung fehlgeschlagen: {$e->getMessage()}"
                );
            }
        }
    }

    public function failed(Throwable $e): void
    {
        $server = Server::find($this->serverId);
        $server?->incrementPollFailures();
    }

    private function checkThresholds(Server $server, $metric, AlertService $alertService): void
    {
        if ($metric->cpu_usage > 90) {
            $alertService->create(
                $server,
                $server->user,
                'high_cpu',
                'critical',
                "Kritische CPU-Auslastung: {$metric->cpu_usage}%",
                ['cpu' => $metric->cpu_usage]
            );
        } elseif ($metric->cpu_usage > 75) {
            $alertService->create(
                $server,
                $server->user,
                'high_cpu',
                'warning',
                "Hohe CPU-Auslastung: {$metric->cpu_usage}%",
                ['cpu' => $metric->cpu_usage]
            );
        }

        if ($metric->memory_percent > 90) {
            $alertService->create(
                $server,
                $server->user,
                'high_memory',
                'critical',
                "Kritische RAM-Auslastung: {$metric->memory_percent}%",
                ['memory_percent' => $metric->memory_percent]
            );
        }

        if ($metric->disk_percent > 85) {
            $alertService->create(
                $server,
                $server->user,
                'high_disk',
                'warning',
                "Hohe Disk-Auslastung: {$metric->disk_percent}%",
                ['disk_percent' => $metric->disk_percent]
            );
        }
    }
}
