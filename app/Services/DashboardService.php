<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\CloudflareZone;
use App\Models\DockerContainer;
use App\Models\DnsRecord;
use App\Models\Metric;
use App\Models\User;
use App\Models\Workspace;

class DashboardService
{
    public function __construct(
        private readonly ServerService $serverService,
        private readonly AlertService $alertService,
    ) {}

    public function getSummary(User $user, ?Workspace $workspace = null): array
    {
        $serverStats  = $this->serverService->getStatusSummary($user, $workspace);
        $unreadAlerts = $this->alertService->getUnreadCount($user, $workspace);
        $recentAlerts = $this->alertService->getRecentForUser($user, 6, $workspace);

        $serversQuery = $workspace
            ? $workspace->servers()
            : $user->servers();

        $servers = $serversQuery->with([
            'metrics' => fn ($q) => $q->latest('recorded_at')->limit(1),
        ])->latest()->get();

        // Docker stats scoped to workspace servers
        $serverIds = $servers->pluck('id');
        $allContainers = DockerContainer::whereIn('server_id', $serverIds)->get(['server_id', 'state']);
        $dockerStats = [
            'total'   => $allContainers->count(),
            'running' => $allContainers->where('state', 'running')->count(),
            'stopped' => $allContainers->where('state', '!=', 'running')->count(),
        ];

        // Cloudflare stats
        $zoneIds = CloudflareZone::where('user_id', $user->id)->pluck('id');
        $cloudflareStats = [
            'zones'     => CloudflareZone::where('user_id', $user->id)->count(),
            'active'    => CloudflareZone::where('user_id', $user->id)->where('status', 'active')->where('paused', false)->count(),
            'dns_total' => DnsRecord::whereIn('cloudflare_zone_id', $zoneIds)->count(),
        ];

        // Top containers by CPU for dashboard widget
        $topContainers = DockerContainer::whereIn('server_id', $serverIds)
            ->with('server:id,name')
            ->whereNotNull('cpu_percent')
            ->orderByDesc('cpu_percent')
            ->limit(5)
            ->get();

        // Aggregated resource stats — latest metric per server
        $resourceStats = $this->getResourceStats($serverIds);

        // Activity feed — last 8 events (alerts + status changes)
        $activityFeed = $this->getActivityFeed($user, $serverIds);

        return [
            'server_stats'     => $serverStats,
            'docker_stats'     => $dockerStats,
            'cloudflare_stats' => $cloudflareStats,
            'unread_alerts'    => $unreadAlerts,
            'recent_alerts'    => $recentAlerts,
            'servers'          => $servers,
            'top_containers'   => $topContainers,
            'resource_stats'   => $resourceStats,
            'activity_feed'    => $activityFeed,
        ];
    }

    private function getResourceStats($serverIds): array
    {
        if ($serverIds->isEmpty()) {
            return ['cpu_avg' => null, 'ram_used_gb' => null, 'ram_total_gb' => null, 'ram_pct' => null];
        }

        $latestMetrics = Metric::whereIn('server_id', $serverIds)
            ->whereIn('id', function ($q) use ($serverIds) {
                $q->selectRaw('MAX(id)')->from('metrics')
                  ->whereIn('server_id', $serverIds)
                  ->groupBy('server_id');
            })
            ->where('recorded_at', '>=', now()->subMinutes(10))
            ->get();

        if ($latestMetrics->isEmpty()) {
            return ['cpu_avg' => null, 'ram_used_gb' => null, 'ram_total_gb' => null, 'ram_pct' => null];
        }

        $ramUsed  = $latestMetrics->sum('memory_usage');
        $ramTotal = $latestMetrics->sum('memory_total');

        return [
            'cpu_avg'      => round($latestMetrics->avg('cpu_usage'), 1),
            'ram_used_gb'  => round($ramUsed / 1024, 1),
            'ram_total_gb' => round($ramTotal / 1024, 1),
            'ram_pct'      => $ramTotal > 0 ? round(($ramUsed / $ramTotal) * 100, 0) : 0,
            'server_count' => $latestMetrics->count(),
        ];
    }

    private function getActivityFeed(User $user, $serverIds): array
    {
        if ($serverIds->isEmpty()) {
            return [];
        }

        return Alert::whereIn('server_id', $serverIds)
            ->with('server:id,name')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get()
            ->map(fn ($a) => [
                'type'       => $a->type,
                'severity'   => $a->severity,
                'message'    => $a->message,
                'server'     => $a->server->name ?? '—',
                'created_at' => $a->created_at,
                'time_ago'   => $a->created_at->diffForHumans(),
                'resolved'   => !is_null($a->resolved_at),
            ])
            ->toArray();
    }
}
