<?php

namespace App\Services\Telegram;

use App\Models\Metric;
use App\Models\Server;
use Illuminate\Support\Collection;

class UsageChartBuilder
{
    private const QUICKCHART_BASE = 'https://quickchart.io/chart';
    private const MAX_URL_LENGTH  = 1900;

    /**
     * Build a QuickChart URL showing CPU/RAM/Disk per server as a grouped bar chart.
     *
     * @param  Collection<int, Server>  $servers
     * @return array{url: string|null, rows: array<int, array{name: string, cpu: float|null, ram: float|null, disk: float|null, recorded_at: ?string}>}
     */
    public function buildSnapshot(Collection $servers): array
    {
        $rows = [];

        foreach ($servers as $server) {
            $metric = Metric::where('server_id', $server->id)
                ->latest('recorded_at')
                ->first();

            $rows[] = [
                'name'        => $server->name,
                'cpu'         => $metric?->cpu_usage,
                'ram'         => $metric ? $metric->memory_percent : null,
                'disk'        => $metric ? $metric->disk_percent : null,
                'recorded_at' => $metric?->recorded_at?->toIso8601String(),
            ];
        }

        $hasAny = collect($rows)->contains(fn ($r) => $r['cpu'] !== null || $r['ram'] !== null || $r['disk'] !== null);

        if (! $hasAny) {
            return ['url' => null, 'rows' => $rows];
        }

        $config = [
            'type' => 'bar',
            'data' => [
                'labels'   => array_map(fn ($r) => $r['name'], $rows),
                'datasets' => [
                    ['label' => 'CPU %',  'data' => array_map(fn ($r) => $this->round($r['cpu']),  $rows), 'backgroundColor' => 'rgba(54, 162, 235, 0.7)'],
                    ['label' => 'RAM %',  'data' => array_map(fn ($r) => $this->round($r['ram']),  $rows), 'backgroundColor' => 'rgba(255, 159, 64, 0.7)'],
                    ['label' => 'Disk %', 'data' => array_map(fn ($r) => $this->round($r['disk']), $rows), 'backgroundColor' => 'rgba(75, 192, 192, 0.7)'],
                ],
            ],
            'options' => [
                'title'  => ['display' => true, 'text' => 'Auslastung pro Server'],
                'scales' => [
                    'yAxes' => [[
                        'ticks' => ['beginAtZero' => true, 'max' => 100],
                    ]],
                ],
            ],
        ];

        $url = self::QUICKCHART_BASE
            . '?w=700&h=400&bkg=white&c='
            . rawurlencode((string) json_encode($config));

        if (strlen($url) > self::MAX_URL_LENGTH) {
            return ['url' => null, 'rows' => $rows];
        }

        return ['url' => $url, 'rows' => $rows];
    }

    private function round(?float $value): ?float
    {
        return $value === null ? null : round($value, 1);
    }
}
