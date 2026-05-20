<?php

namespace App\Jobs;

use App\Models\Server;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PollAllServersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly string $mode = 'status') {}

    public function handle(): void
    {
        if ($this->mode === 'metrics') {
            // Only poll servers that have SSH credentials configured.
            Server::withCredentials()->whereNull('deleted_at')->each(
                fn (Server $server) => PollServerMetricsJob::dispatch($server->id)->onQueue('monitoring')
            );

            return;
        }

        // TCP connectivity check for every server.
        Server::whereNull('deleted_at')->each(
            fn (Server $server) => CheckServerStatusJob::dispatch($server->id)->onQueue('monitoring')
        );
    }
}
