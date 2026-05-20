<?php

namespace App\Services;

use App\Models\CloudflareZone;
use App\Models\DockerContainer;
use App\Models\DnsRecord;
use App\Models\User;

class DashboardService
{
    public function __construct(
        private readonly ServerService $serverService,
        private readonly AlertService $alertService,
    ) {}

    public function getSummary(User $user): array
    {
        $serverStats  = $this->serverService->getStatusSummary($user);
        $unreadAlerts = $this->alertService->getUnreadCount($user);
        $recentAlerts = $this->alertService->getRecentForUser($user, 6);

        $servers = $user->servers()->with([
            'metrics' => fn ($q) => $q->latest('recorded_at')->limit(1),
        ])->latest()->get();

        // Docker stats across all user servers
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

        return [
            'server_stats'     => $serverStats,
            'docker_stats'     => $dockerStats,
            'cloudflare_stats' => $cloudflareStats,
            'unread_alerts'    => $unreadAlerts,
            'recent_alerts'    => $recentAlerts,
            'servers'          => $servers,
            'top_containers'   => $topContainers,
        ];
    }
}
