<?php

namespace App\Http\Controllers;

use App\Jobs\SyncCloudflareZonesJob;
use App\Jobs\SyncDnsRecordsJob;
use App\Models\CloudflareToken;
use App\Models\CloudflareZone;
use App\Models\DnsRecord;
use App\Services\CloudflareService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CloudflareController extends Controller
{
    public function __construct(private readonly CloudflareService $cloudflare) {}

    // -------------------------------------------------------------------------
    // Overview
    // -------------------------------------------------------------------------

    /**
     * GET /cloudflare/dns
     * Aggregate view of all DNS records across all user zones, with filters.
     */
    public function dnsIndex(Request $request): View
    {
        $workspace = app('activeWorkspace');
        $zoneIds   = CloudflareZone::where('workspace_id', $workspace->id)->pluck('id');

        $query = DnsRecord::whereIn('cloudflare_zone_id', $zoneIds)->with('zone:id,name');

        if ($zoneId = $request->get('zone_id')) {
            $query->where('cloudflare_zone_id', $zoneId);
        }
        if ($type = $request->get('type')) {
            $query->where('type', strtoupper($type));
        }
        if ($request->get('proxied') !== null && $request->get('proxied') !== '') {
            $query->where('proxied', $request->boolean('proxied'));
        }
        if ($search = $request->get('search')) {
            $query->where(fn ($q) =>
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('content', 'like', '%' . $search . '%')
            );
        }

        $records = $query->orderByRaw("CASE type WHEN 'A' THEN 1 WHEN 'AAAA' THEN 2 WHEN 'CNAME' THEN 3 WHEN 'MX' THEN 4 WHEN 'TXT' THEN 5 WHEN 'NS' THEN 6 WHEN 'SRV' THEN 7 WHEN 'CAA' THEN 8 ELSE 9 END")
            ->orderBy('name')
            ->paginate(50)
            ->withQueryString();

        $zones = CloudflareZone::where('workspace_id', $workspace->id)->orderBy('name')->get(['id', 'name']);
        $types = DnsRecord::whereIn('cloudflare_zone_id', $zoneIds)
            ->distinct()->orderBy('type')->pluck('type');

        $totalRecords = DnsRecord::whereIn('cloudflare_zone_id', $zoneIds)->count();

        return view('cloudflare.dns', compact('records', 'zones', 'types', 'totalRecords'));
    }

    public function index(Request $request): View
    {
        $user      = $request->user();
        $workspace = app('activeWorkspace');

        $tokens = CloudflareToken::where('user_id', $user->id)
            ->withCount('zones')
            ->orderBy('created_at')
            ->get();

        $zones = CloudflareZone::where('workspace_id', $workspace->id)
            ->withCount('dnsRecords')
            ->orderBy('name')
            ->get();

        $stats = [
            'tokens'     => $tokens->count(),
            'zones'      => $zones->count(),
            'active'     => $zones->where('status', 'active')->where('paused', false)->count(),
            'dns_total'  => $zones->sum('dns_records_count'),
        ];

        $workspaces = $user->workspaces()->orderBy('type')->get();

        return view('cloudflare.index', compact('tokens', 'zones', 'stats', 'workspaces'));
    }

    // -------------------------------------------------------------------------
    // Token management
    // -------------------------------------------------------------------------

    public function storeToken(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'api_token' => 'required|string|min:10',
        ]);

        // Verify token before saving
        $verify = $this->cloudflare->verifyToken($data['api_token']);
        if (!$verify['valid']) {
            return back()
                ->withInput()
                ->with('error', 'Token ungültig: ' . $verify['message']);
        }

        $token = CloudflareToken::create([
            'user_id'      => $request->user()->id,
            'name'         => $data['name'],
            'api_token'    => $data['api_token'],
            'account_id'   => $verify['account_id'],
            'account_name' => $verify['account_name'],
            'status'       => 'active',
            'last_verified_at' => now(),
        ]);

        // Trigger immediate zone + DNS sync
        SyncCloudflareZonesJob::dispatch($token->id)->onQueue('monitoring');

        return redirect()->route('cloudflare.index')
            ->with('success', 'Token "' . $token->name . '" gespeichert. Zonen werden synchronisiert...');
    }

    public function destroyToken(Request $request, CloudflareToken $token): RedirectResponse
    {
        abort_unless($token->user_id === $request->user()->id, 403);

        $name = $token->name;
        $token->delete(); // cascades to zones + dns_records

        return redirect()->route('cloudflare.index')
            ->with('success', 'Token "' . $name . '" und alle zugehoerigen Daten wurden geloescht.');
    }

    public function syncToken(Request $request, CloudflareToken $token): JsonResponse
    {
        abort_unless($token->user_id === $request->user()->id, 403);

        SyncCloudflareZonesJob::dispatch($token->id)->onQueue('monitoring');

        return response()->json([
            'dispatched' => true,
            'message'    => 'Zone-Sync läuft im Hintergrund…',
        ]);
    }

    // -------------------------------------------------------------------------
    // Zone / DNS detail
    // -------------------------------------------------------------------------

    public function zoneShow(Request $request, CloudflareZone $zone): View
    {
        abort_unless($zone->user_id === $request->user()->id, 403);

        $records = $zone->dnsRecords()
            ->orderByRaw("CASE type WHEN 'A' THEN 1 WHEN 'AAAA' THEN 2 WHEN 'CNAME' THEN 3 WHEN 'MX' THEN 4 WHEN 'TXT' THEN 5 WHEN 'NS' THEN 6 WHEN 'SRV' THEN 7 WHEN 'CAA' THEN 8 ELSE 9 END ASC")
            ->orderBy('name')
            ->get();

        $summary = $this->cloudflare->getZoneSummary($zone);

        return view('cloudflare.zone', compact('zone', 'records', 'summary'));
    }

    public function zoneStatusJson(Request $request, CloudflareZone $zone): JsonResponse
    {
        abort_unless($zone->user_id === $request->user()->id, 403);

        $records = $zone->dnsRecords()
            ->orderByRaw("CASE type WHEN 'A' THEN 1 WHEN 'AAAA' THEN 2 WHEN 'CNAME' THEN 3 WHEN 'MX' THEN 4 WHEN 'TXT' THEN 5 WHEN 'NS' THEN 6 WHEN 'SRV' THEN 7 WHEN 'CAA' THEN 8 ELSE 9 END ASC")
            ->orderBy('name')
            ->get(['id', 'cf_record_id', 'type', 'name', 'content', 'proxied', 'proxiable', 'ttl', 'priority', 'comment', 'modified_on', 'synced_at']);

        return response()->json([
            'synced_at' => $zone->synced_at?->diffForHumans(),
            'total'     => $records->count(),
            'records'   => $records->map(fn ($r) => [
                'id'          => $r->id,
                'type'        => $r->type,
                'name'        => $r->name,
                'content'     => $r->content,
                'proxied'     => $r->proxied,
                'proxiable'   => $r->proxiable,
                'ttl_label'   => $r->ttl === 1 ? 'Auto' : $r->ttl . 's',
                'priority'    => $r->priority,
                'comment'     => $r->comment,
                'modified_on' => $r->modified_on?->diffForHumans(),
            ])->values(),
        ]);
    }

    public function syncDns(Request $request, CloudflareZone $zone): JsonResponse
    {
        abort_unless($zone->user_id === $request->user()->id, 403);

        SyncDnsRecordsJob::dispatch($zone->id)->onQueue('monitoring');

        return response()->json([
            'dispatched' => true,
            'message'    => 'DNS-Sync läuft im Hintergrund…',
        ]);
    }

    /**
     * POST /cloudflare/zones/{zone}/workspace
     * Move a domain into another of the user's workspaces.
     */
    public function moveZone(Request $request, CloudflareZone $zone): RedirectResponse
    {
        abort_unless($zone->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'workspace_id' => 'required|integer',
        ]);

        // Only allow moving into a workspace the user owns.
        $target = $request->user()->workspaces()->find($data['workspace_id']);
        abort_unless($target !== null, 403);

        $zone->update(['workspace_id' => $target->id]);

        return back()->with('success', sprintf('Domain "%s" nach %s verschoben.', $zone->name, $target->name));
    }
}
