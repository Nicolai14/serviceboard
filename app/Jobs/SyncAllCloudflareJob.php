<?php

namespace App\Jobs;

use App\Models\CloudflareToken;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncAllCloudflareJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 30;

    public function handle(): void
    {
        CloudflareToken::where('status', 'active')
            ->each(fn ($token) =>
                SyncCloudflareZonesJob::dispatch($token->id)->onQueue('monitoring')
            );
    }
}
