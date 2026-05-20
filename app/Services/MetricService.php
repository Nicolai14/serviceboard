<?php

namespace App\Services;

use App\Models\Metric;
use App\Models\Server;
use Illuminate\Support\Collection;

class MetricService
{
    public function record(Server $server, array $data): Metric
    {
        return $server->metrics()->create([
            ...$data,
            'recorded_at' => now(),
        ]);
    }

    public function getLatest(Server $server): ?Metric
    {
        return $server->metrics()->latest('recorded_at')->first();
    }

    public function getHistory(Server $server, int $hours = 24): Collection
    {
        return $server->metrics()
            ->where('recorded_at', '>=', now()->subHours($hours))
            ->orderBy('recorded_at')
            ->get();
    }

    public function getAverages(Server $server, int $hours = 24): array
    {
        $metrics = $server->metrics()
            ->where('recorded_at', '>=', now()->subHours($hours))
            ->selectRaw('
                AVG(cpu_usage) as avg_cpu,
                AVG(memory_usage / NULLIF(memory_total, 0) * 100) as avg_memory_percent,
                AVG(disk_usage / NULLIF(disk_total, 0) * 100) as avg_disk_percent,
                MAX(cpu_usage) as max_cpu
            ')
            ->first();

        return [
            'avg_cpu'            => round($metrics->avg_cpu ?? 0, 1),
            'avg_memory_percent' => round($metrics->avg_memory_percent ?? 0, 1),
            'avg_disk_percent'   => round($metrics->avg_disk_percent ?? 0, 1),
            'max_cpu'            => round($metrics->max_cpu ?? 0, 1),
        ];
    }

    public function pruneOldMetrics(int $daysToKeep = 30): int
    {
        return Metric::where('recorded_at', '<', now()->subDays($daysToKeep))->delete();
    }

    public function getChartData(Server $server, int $hours = 24): array
    {
        $metrics = $this->getHistory($server, $hours);

        return [
            'labels' => $metrics->map(fn ($m) => $m->recorded_at->format('H:i'))->values()->toArray(),
            'cpu'    => $metrics->map(fn ($m) => round($m->cpu_usage, 1))->values()->toArray(),
            'memory' => $metrics->map(fn ($m) => round($m->memory_percent, 1))->values()->toArray(),
            'disk'   => $metrics->map(fn ($m) => round($m->disk_percent, 1))->values()->toArray(),
        ];
    }
}
