<?php

namespace App\Jobs;

use App\Exceptions\SSHException;
use App\Models\Metric;
use App\Models\Server;
use App\Models\User;
use App\Services\AlertService;
use App\Services\NotificationService;
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

    public function handle(ServerService $serverService, AlertService $alertService, NotificationService $notifications): void
    {
        $server = Server::find($this->serverId);

        if (!$server || $server->status === 'maintenance') {
            return;
        }

        $owner = $server->user;

        try {
            $serverService->pollMetrics($server);

            $latest = $server->metrics()->latest('recorded_at')->first();
            if ($latest) {
                $this->checkThresholds($server, $owner, $latest, $alertService, $notifications);
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

    private function checkThresholds(
        Server $server,
        User $owner,
        Metric $metric,
        AlertService $alertService,
        NotificationService $notifications,
    ): void {
        if (! $server->alerts_enabled) {
            return;
        }

        $t = $server->thresholds();

        $this->evaluate($alertService, $notifications, $server, $owner, 'high_cpu', 'CPU-Auslastung',
            (float) $metric->cpu_usage, $t['cpu_warning'], $t['cpu_critical'], ['cpu' => $metric->cpu_usage]);

        $this->evaluate($alertService, $notifications, $server, $owner, 'high_memory', 'RAM-Auslastung',
            (float) $metric->memory_percent, $t['memory_warning'], $t['memory_critical'], ['memory_percent' => $metric->memory_percent]);

        $this->evaluate($alertService, $notifications, $server, $owner, 'high_disk', 'Disk-Auslastung',
            (float) $metric->disk_percent, $t['disk_warning'], $t['disk_critical'], ['disk_percent' => $metric->disk_percent]);
    }

    /**
     * Raise a critical/warning alert when a metric reaches its configured level.
     *
     * @param array<string, mixed> $context
     */
    private function evaluate(
        AlertService $alertService,
        NotificationService $notifications,
        Server $server,
        User $owner,
        string $type,
        string $label,
        float $value,
        int $warning,
        int $critical,
        array $context,
    ): void {
        $severity = match (true) {
            $value >= $critical => 'critical',
            $value >= $warning  => 'warning',
            default             => null,
        };

        if ($severity === null) {
            return;
        }

        $hasOpenAlert = $server->alerts()
            ->where('type', $type)
            ->whereNull('resolved_at')
            ->exists();

        if ($hasOpenAlert) {
            return;
        }

        $rounded = round($value, 1);
        $message = $severity === 'critical'
            ? "Kritische {$label}: {$rounded}%"
            : "Hohe {$label}: {$rounded}%";

        $alertService->create($server, $owner, $type, $severity, $message, $context);

        $icon = $severity === 'critical' ? '🔴' : '🟠';
        $notifications->dispatch(
            $owner,
            "{$icon} {$message} auf {$server->name}",
            "Server: *{$server->name}*\nMetrik: {$label}\nWert: *{$rounded}%*\nSchwellwert: " . ($severity === 'critical' ? "{$critical}%" : "{$warning}%"),
        );
    }
}
