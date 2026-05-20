<?php

namespace App\Console\Commands;

use App\Exceptions\SSHException;
use App\Jobs\PollAllServersJob;
use App\Models\Server;
use App\Services\ServerService;
use Illuminate\Console\Command;

class PollMetricsCommand extends Command
{
    protected $signature   = 'servers:metrics {server? : Server ID (all if omitted)} {--queue : Dispatch as background jobs}';
    protected $description = 'Poll CPU/RAM/Disk metrics from servers via SSH';

    public function handle(ServerService $serverService): int
    {
        if ($this->option('queue')) {
            PollAllServersJob::dispatch('metrics')->onQueue('monitoring');
            $this->info('Dispatched metric poll to queue.');
            return self::SUCCESS;
        }

        $servers = $this->argument('server')
            ? Server::whereNull('deleted_at')->where('id', $this->argument('server'))->get()
            : Server::whereNull('deleted_at')->withCredentials()->get();

        if ($servers->isEmpty()) {
            $this->warn('No servers with SSH credentials found.');
            return self::SUCCESS;
        }

        foreach ($servers as $server) {
            $this->line("Polling <fg=cyan>{$server->name}</> ({$server->hostname})…");

            try {
                $serverService->pollMetrics($server);
                $metric = $server->metrics()->latest('recorded_at')->first();

                $this->info(sprintf(
                    '  CPU: %s%%  RAM: %s%%  Disk: %s%%  Load: %s  Uptime: %s',
                    number_format($metric->cpu_usage, 1),
                    number_format($metric->memory_percent, 1),
                    number_format($metric->disk_percent, 1),
                    number_format($metric->load_average, 2),
                    $this->formatUptime($metric->uptime_seconds)
                ));
            } catch (SSHException $e) {
                $this->error("  {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }

    private function formatUptime(int $seconds): string
    {
        $days    = intdiv($seconds, 86400);
        $hours   = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        if ($days > 0) {
            return "{$days}d {$hours}h";
        }

        return $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";
    }
}
