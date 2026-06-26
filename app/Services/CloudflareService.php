<?php

namespace App\Services;

use App\Enums\WorkspaceType;
use App\Models\CloudflareToken;
use App\Models\CloudflareZone;
use App\Models\DnsRecord;
use App\Models\Workspace;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CloudflareService
{
    private const BASE = 'https://api.cloudflare.com/client/v4';

    // -------------------------------------------------------------------------
    // HTTP client
    // -------------------------------------------------------------------------

    private function client(string $token)
    {
        return Http::withToken($token)
            ->acceptJson()
            ->timeout(15)
            ->baseUrl(self::BASE);
    }

    // -------------------------------------------------------------------------
    // Token verification
    // -------------------------------------------------------------------------

    /**
     * Verify a raw API token and return account details.
     * Returns ['valid' => bool, 'account_id' => string|null, 'account_name' => string|null, 'message' => string]
     */
    public function verifyToken(string $rawToken): array
    {
        try {
            $res = $this->client($rawToken)->get('/user/tokens/verify');
            $body = $res->json();

            if (! ($body['success'] ?? false)) {
                $msg = $body['errors'][0]['message'] ?? 'Token ungültig';
                return ['valid' => false, 'account_id' => null, 'account_name' => null, 'message' => $msg];
            }

            // Fetch account list to get account info
            $accRes  = $this->client($rawToken)->get('/accounts', ['per_page' => 1]);
            $accBody = $accRes->json();
            $account = $accBody['result'][0] ?? null;

            return [
                'valid'        => true,
                'account_id'   => $account['id'] ?? null,
                'account_name' => $account['name'] ?? null,
                'message'      => 'Token verifiziert',
            ];
        } catch (ConnectionException $e) {
            return ['valid' => false, 'account_id' => null, 'account_name' => null, 'message' => 'Verbindung zu Cloudflare fehlgeschlagen'];
        }
    }

    // -------------------------------------------------------------------------
    // Zone sync
    // -------------------------------------------------------------------------

    /**
     * Sync all zones for a token. Returns number of zones upserted.
     */
    public function syncZones(CloudflareToken $cfToken): int
    {
        $rawToken = $cfToken->api_token;
        $page     = 1;
        $synced   = 0;
        $seenIds  = [];

        // New zones default to the owner's personal workspace; existing zones
        // keep whatever workspace they were moved to.
        $personalWorkspaceId = Workspace::where('user_id', $cfToken->user_id)
            ->where('type', WorkspaceType::Personal)
            ->value('id');

        do {
            $res  = $this->client($rawToken)->get('/zones', ['per_page' => 50, 'page' => $page]);
            $body = $res->json();

            if (!($body['success'] ?? false)) {
                $msg = $body['errors'][0]['message'] ?? 'Unbekannter Cloudflare-Fehler';
                throw new RuntimeException("Zone-Sync fehlgeschlagen: {$msg}");
            }

            foreach ($body['result'] as $zone) {
                $model = CloudflareZone::updateOrCreate(
                    ['zone_id' => $zone['id']],
                    [
                        'cloudflare_token_id'    => $cfToken->id,
                        'user_id'                => $cfToken->user_id,
                        'name'                   => $zone['name'],
                        'status'                 => $zone['status'],
                        'paused'                 => $zone['paused'],
                        'plan_name'              => $zone['plan']['name'] ?? null,
                        'type'                   => $zone['type'],
                        'name_servers'           => $zone['name_servers'] ?? [],
                        'original_name_servers'  => $zone['original_name_servers'] ?? [],
                        'synced_at'              => now(),
                    ]
                );

                // Assign a workspace only when the zone is first discovered.
                if ($model->wasRecentlyCreated && $model->workspace_id === null && $personalWorkspaceId !== null) {
                    $model->update(['workspace_id' => $personalWorkspaceId]);
                }

                $seenIds[] = $zone['id'];
                $synced++;
            }

            $totalPages = $body['result_info']['total_pages'] ?? 1;
            $page++;
        } while ($page <= $totalPages);

        // Remove zones no longer in the account
        if (!empty($seenIds)) {
            CloudflareZone::where('cloudflare_token_id', $cfToken->id)
                ->whereNotIn('zone_id', $seenIds)
                ->delete();
        }

        return $synced;
    }

    // -------------------------------------------------------------------------
    // DNS record sync
    // -------------------------------------------------------------------------

    /**
     * Sync all DNS records for a single zone. Returns number of records upserted.
     */
    public function syncDnsRecords(CloudflareZone $zone): int
    {
        $rawToken = $zone->cloudflareToken->api_token;
        $page     = 1;
        $synced   = 0;
        $seenIds  = [];

        do {
            $res  = $this->client($rawToken)->get("/zones/{$zone->zone_id}/dns_records", [
                'per_page' => 100,
                'page'     => $page,
            ]);
            $body = $res->json();

            if (!($body['success'] ?? false)) {
                $msg = $body['errors'][0]['message'] ?? 'Unbekannter Cloudflare-Fehler';
                throw new RuntimeException("DNS-Sync fehlgeschlagen für {$zone->name}: {$msg}");
            }

            foreach ($body['result'] as $rec) {
                DnsRecord::updateOrCreate(
                    [
                        'cloudflare_zone_id' => $zone->id,
                        'cf_record_id'       => $rec['id'],
                    ],
                    [
                        'type'        => $rec['type'],
                        'name'        => $rec['name'],
                        'content'     => $rec['content'],
                        'proxied'     => $rec['proxied'] ?? false,
                        'proxiable'   => $rec['proxiable'] ?? false,
                        'ttl'         => $rec['ttl'] ?? 1,
                        'priority'    => $rec['priority'] ?? null,
                        'comment'     => $rec['comment'] ?? null,
                        'created_on'  => isset($rec['created_on']) ? \Carbon\Carbon::parse($rec['created_on']) : null,
                        'modified_on' => isset($rec['modified_on']) ? \Carbon\Carbon::parse($rec['modified_on']) : null,
                        'synced_at'   => now(),
                    ]
                );
                $seenIds[] = $rec['id'];
                $synced++;
            }

            $totalPages = $body['result_info']['total_pages'] ?? 1;
            $page++;
        } while ($page <= $totalPages);

        // Remove records that disappeared from Cloudflare
        if (!empty($seenIds)) {
            DnsRecord::where('cloudflare_zone_id', $zone->id)
                ->whereNotIn('cf_record_id', $seenIds)
                ->delete();
        }

        return $synced;
    }

    // -------------------------------------------------------------------------
    // Read helpers (for controller JSON endpoints)
    // -------------------------------------------------------------------------

    public function getZoneSummary(CloudflareZone $zone): array
    {
        $records = $zone->dnsRecords()->get();

        return [
            'total'    => $records->count(),
            'proxied'  => $records->where('proxied', true)->count(),
            'by_type'  => $records->groupBy('type')->map->count()->toArray(),
            'synced_at' => $zone->synced_at?->diffForHumans(),
        ];
    }
}
