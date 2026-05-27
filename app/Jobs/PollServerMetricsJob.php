<?php

namespace App\Jobs;

use App\Exceptions\SSHException;
use App\Models\Metric;
use App\Models\Server;
use App\Models\User;
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

        $owner = $server->user;

        try {
            $serverService->pollMetrics($server);

            // Trigger a high-CPU/RAM alert if thresholds are breached.
            $latest = $server->metrics()->latest('recorded_at')->first();
            if ($latest) {
                $this->checkThresholds($server, $owner, $latest, $alertService);
            }
        } catch (SSHException $e) {
            $server->incrementPollFailures();

            if ($server->poll_failures >= 3) {
                $alertService->create(
                    $server,
                    $owner,
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

    private function checkThresholds(Server $server, User $owner, Metric $metric, AlertService $alertService): void
    {
        if (! $server->alerts_enabled) {
            return;
        }

        $t = $server->thresholds();

        $this->evaluate($alertService, $server, $owner, 'high_cpu', 'CPU-Auslastung',
            (float) $metric->cpu_usage, $t['cpu_warning'], $t['cpu_critical'], ['cpu' => $metric->cpu_usage]);

        $this->evaluate($alertService, $server, $owner, 'high_memory', 'RAM-Auslastung',
            (float) $metric->memory_percent, $t['memory_warning'], $t['memory_critical'], ['memory_percent' => $metric->memory_percent]);

        $this->evaluate($alertService, $server, $owner, 'high_disk', 'Disk-Auslastung',
            (float) $metric->disk_percent, $t['disk_warning'], $t['disk_critical'], ['disk_percent' => $metric->disk_percent]);
    }

    /**
     * Raise a critical/warning alert when a metric reaches its configured level.
     *
     * @param array<string, mixed> $context
     */
    private function evaluate(
        AlertService $alertService,
        Server $server,
        User $owner,
        string $type,
        string $label,
        float $value,
        int $warning,
        int $critical,
        array $context,
    ): void {
        if ($value >= $critical) {
            $alertService->create($server, $owner, $type, 'critical', "Kritische {$label}: " . round($value, 1) . '%', $context);
        } elseif ($value >= $warning) {
            $alertService->create($server, $owner, $type, 'warning', "Hohe {$label}: " . round($value, 1) . '%', $context);
        }
    }
}
