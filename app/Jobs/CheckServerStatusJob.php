<?php

namespace App\Jobs;

use App\Models\Server;
use App\Services\ServerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckServerStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 10;

    public function __construct(public readonly int $serverId) {}

    public function handle(ServerService $serverService): void
    {
        $server = Server::find($this->serverId);

        if (!$server) {
            return;
        }

        $serverService->checkConnectivity($server);
    }
}
