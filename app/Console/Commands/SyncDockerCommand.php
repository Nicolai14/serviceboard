<?php

namespace App\Console\Commands;

use App\Exceptions\SSHException;
use App\Jobs\SyncAllDockerContainersJob;
use App\Models\Server;
use App\Services\DockerService;
use Illuminate\Console\Command;

class SyncDockerCommand extends Command
{
    protected $signature   = 'servers:docker {server? : Server ID (all if omitted)} {--queue : Dispatch as background jobs}';
    protected $description = 'Sync Docker container state from servers via SSH';

    public function handle(DockerService $dockerService): int
    {
        if ($this->option('queue')) {
            SyncAllDockerContainersJob::dispatch()->onQueue('monitoring');
            $this->info('Docker sync dispatched to queue.');
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
            $this->line("Syncing Docker on <fg=cyan>{$server->name}</> ({$server->hostname})…");

            try {
                $count = $dockerService->sync($server);
                $this->info("  {$count} container(s) synced.");

                $containers = $server->dockerContainers()->orderBy('name')->get();
                foreach ($containers as $c) {
                    $stateColor = $c->state === 'running' ? 'green' : 'red';
                    $cpu  = $c->cpu_percent  !== null ? number_format($c->cpu_percent, 2) . '%' : '—';
                    $mem  = $c->memory_usage_mb !== null ? number_format($c->memory_usage_mb) . 'MB' : '—';
                    $this->line(sprintf(
                        '  [<fg=%s>%s</>] %-30s  %-25s  CPU: %s  RAM: %s',
                        $stateColor, strtoupper($c->state),
                        $c->name, $c->image,
                        $cpu, $mem
                    ));
                }
            } catch (SSHException $e) {
                $this->error("  {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
