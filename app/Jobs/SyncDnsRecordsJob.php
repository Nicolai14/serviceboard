<?php

namespace App\Jobs;

use App\Models\CloudflareZone;
use App\Services\CloudflareService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncDnsRecordsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 60;
    public int $backoff = 15;

    public function __construct(public readonly int $zoneId) {}

    public function handle(CloudflareService $cloudflare): void
    {
        $zone = CloudflareZone::with('cloudflareToken')->find($this->zoneId);
        if (!$zone) return;

        try {
            $count = $cloudflare->syncDnsRecords($zone);
            Log::info("Cloudflare DNS: {$count} Record(s) synchronisiert für Zone {$zone->name}");
        } catch (\Throwable $e) {
            Log::error("Cloudflare DNS-Sync fehlgeschlagen für Zone {$zone->name}: " . $e->getMessage());
            $this->fail($e);
        }
    }
}
