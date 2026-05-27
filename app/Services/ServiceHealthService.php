<?php

namespace App\Services;

use App\Models\Service;
use Illuminate\Support\Facades\Http;

class ServiceHealthService
{
    private const HTTP_TIMEOUT = 10;
    private const TCP_TIMEOUT  = 3;

    public function __construct(
        private readonly AlertService $alerts,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * Probe a single service, persist its status/latency, and raise (or resolve)
     * a service_down alert on a state transition. Returns the new status.
     */
    public function check(Service $service): string
    {
        $previous = $service->status;

        [$status, $latency] = $this->probe($service);

        $service->update([
            'status'          => $status,
            'last_latency_ms' => $latency,
            'last_checked_at' => now(),
        ]);

        if ($status === 'error' && $previous !== 'error') {
            $this->onDown($service);
        } elseif ($status === 'running' && $previous === 'error') {
            $this->onRecovered($service);
        }

        return $status;
    }

    /**
     * Run the actual reachability probe.
     *
     * @return array{0: string, 1: int|null} [status, latency_ms]
     */
    private function probe(Service $service): array
    {
        if ($service->check_url) {
            $start = microtime(true);

            try {
                $response = Http::timeout(self::HTTP_TIMEOUT)->get($service->check_url);
                $latency  = (int) round((microtime(true) - $start) * 1000);

                return [$response->successful() || $response->redirect() ? 'running' : 'error', $latency];
            } catch (\Throwable) {
                return ['error', null];
            }
        }

        if ($service->port) {
            $host  = $service->server->ip_address ?: $service->server->hostname;
            $start = microtime(true);

            $socket = @fsockopen($host, $service->port, $errno, $errstr, self::TCP_TIMEOUT);

            if ($socket !== false) {
                fclose($socket);

                return ['running', (int) round((microtime(true) - $start) * 1000)];
            }

            return ['error', null];
        }

        // Nothing configured to check against.
        return ['unknown', null];
    }

    private function onDown(Service $service): void
    {
        $server = $service->server;
        $target = $service->check_url ?: "Port {$service->port}";

        $this->alerts->create(
            $server,
            $server->user,
            'service_down',
            'warning',
            "Service \"{$service->name}\" auf {$server->name} antwortet nicht.",
            ['service_id' => $service->id, 'check' => $target],
        );

        if ($service->notify_on_down) {
            $this->notifications->dispatch(
                $server->user,
                "🔴 Service down: {$service->name}",
                "Server: *{$server->name}*\nService: `{$service->name}`\nCheck: {$target}",
            );
        }
    }

    private function onRecovered(Service $service): void
    {
        $service->server->alerts()
            ->where('type', 'service_down')
            ->where('context->service_id', $service->id)
            ->whereNull('resolved_at')
            ->update(['resolved_at' => now()]);
    }
}
