<?php

namespace App\Console\Commands;

use App\Jobs\SyncAllCloudflareJob;
use App\Jobs\SyncCloudflareZonesJob;
use App\Models\CloudflareToken;
use App\Services\CloudflareService;
use Illuminate\Console\Command;

class SyncCloudflareCommand extends Command
{
    protected $signature   = 'cloudflare:sync {token? : Token-ID (optional)} {--queue : Als Job dispatchen}';
    protected $description = 'Cloudflare Zonen und DNS-Records synchronisieren';

    public function handle(CloudflareService $cloudflare): int
    {
        $tokenId = $this->argument('token');

        if ($this->option('queue')) {
            if ($tokenId) {
                SyncCloudflareZonesJob::dispatch((int) $tokenId)->onQueue('monitoring');
                $this->info("Job für Token #{$tokenId} dispatched.");
            } else {
                SyncAllCloudflareJob::dispatch()->onQueue('monitoring');
                $this->info('Job für alle aktiven Tokens dispatched.');
            }
            return self::SUCCESS;
        }

        $tokens = $tokenId
            ? CloudflareToken::where('id', $tokenId)->where('status', 'active')->get()
            : CloudflareToken::where('status', 'active')->get();

        if ($tokens->isEmpty()) {
            $this->warn('Keine aktiven Cloudflare-Tokens gefunden.');
            return self::SUCCESS;
        }

        foreach ($tokens as $token) {
            $this->line('');
            $this->info("Token: <fg=cyan>{$token->name}</> (#{$token->id})");

            try {
                $zoneCount = $cloudflare->syncZones($token);
                $this->line("  <fg=green>✓</> {$zoneCount} Zone(n) synchronisiert");

                foreach ($token->zones as $zone) {
                    $dnsCount = $cloudflare->syncDnsRecords($zone);
                    $flag = $zone->paused ? ' <fg=yellow>[paused]</>' : '';
                    $this->line("    <fg=blue>→</> {$zone->name}{$flag}: {$dnsCount} DNS-Record(s)");
                }

                $token->markActive();
            } catch (\Throwable $e) {
                $token->markError($e->getMessage());
                $this->error("  Fehler: {$e->getMessage()}");
            }
        }

        $this->line('');
        $this->info('Cloudflare-Sync abgeschlossen.');
        return self::SUCCESS;
    }
}
