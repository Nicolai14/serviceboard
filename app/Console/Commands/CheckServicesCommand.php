<?php

namespace App\Console\Commands;

use App\Jobs\CheckAllServicesJob;
use App\Models\Service;
use App\Services\ServiceHealthService;
use Illuminate\Console\Command;

class CheckServicesCommand extends Command
{
    protected $signature   = 'services:check {--queue : Dispatch as background jobs}';
    protected $description = 'Run health checks (HTTP / TCP) against configured services';

    public function handle(ServiceHealthService $health): int
    {
        if ($this->option('queue')) {
            CheckAllServicesJob::dispatch()->onQueue('monitoring');
            $this->info('Dispatched service health checks to queue.');

            return self::SUCCESS;
        }

        $services = Service::query()->checkable()->get();

        if ($services->isEmpty()) {
            $this->warn('No checkable services found.');

            return self::SUCCESS;
        }

        foreach ($services as $service) {
            $status = $health->check($service);
            $color  = $status === 'running' ? 'green' : ($status === 'error' ? 'red' : 'yellow');

            $this->line(sprintf(
                '  [<fg=%s>%s</>] %-25s %s%s',
                $color,
                strtoupper($status),
                $service->name,
                $service->check_url ?: "port {$service->port}",
                $service->last_latency_ms !== null ? "  ({$service->last_latency_ms}ms)" : '',
            ));
        }

        return self::SUCCESS;
    }
}
