<?php

namespace App\Console\Commands;

use App\Jobs\PollAllServersJob;
use Illuminate\Console\Command;

class CheckServersCommand extends Command
{
    protected $signature   = 'servers:check {--queue : Dispatch as background jobs}';
    protected $description = 'TCP ping all servers to check online/offline status';

    public function handle(): int
    {
        $this->info('Checking server connectivity…');

        if ($this->option('queue')) {
            PollAllServersJob::dispatch('status')->onQueue('monitoring');
            $this->info('Dispatched to queue.');
            return self::SUCCESS;
        }

        // Synchronous — useful for local testing
        app(\App\Models\Server::class)::whereNull('deleted_at')->each(function ($server) {
            $online = app(\App\Services\ServerService::class)->checkConnectivity($server);
            $this->line(sprintf(
                '  [%s] %s (%s)',
                $online ? '<fg=green>ONLINE</>' : '<fg=red>OFFLINE</>',
                $server->name,
                $server->hostname
            ));
        });

        return self::SUCCESS;
    }
}
