<?php

namespace App\Jobs;

use App\Models\CloudflareToken;
use App\Services\CloudflareService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncCloudflareZonesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 60;
    public int $backoff = 30;

    public function __construct(public readonly int $tokenId) {}

    public function handle(CloudflareService $cloudflare): void
    {
        $token = CloudflareToken::find($this->tokenId);
        if (!$token) return;

        try {
            $count = $cloudflare->syncZones($token);
            $token->markActive();

            // Dispatch DNS sync for every zone
            $token->zones()->each(fn ($zone) =>
                SyncDnsRecordsJob::dispatch($zone->id)->onQueue('monitoring')
            );

            Log::info("Cloudflare: {$count} Zone(n) synchronisiert für Token #{$token->id} ({$token->name})");
        } catch (\Throwable $e) {
            $token->markError($e->getMessage());
            Log::error("Cloudflare Zone-Sync fehlgeschlagen #{$token->id}: " . $e->getMessage());
            $this->fail($e);
        }
    }

    public function failed(\Throwable $e): void
    {
        if ($token = CloudflareToken::find($this->tokenId)) {
            $token->markError($e->getMessage());
        }
    }
}
