<?php

namespace App\Services;

use App\Models\CloudflareZone;
use App\Models\CostItem;
use App\Models\Server;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Collection;

class CostService
{
    /**
     * Ensure every server (of the workspace) and every Cloudflare domain
     * (of the user) has a cost row in this workspace, and prune rows whose
     * linked resource no longer exists. Manual items are left untouched.
     */
    public function sync(User $user, Workspace $workspace): void
    {
        $serverIds = $workspace->servers()->pluck('id');
        $zoneIds   = CloudflareZone::where('user_id', $user->id)->pluck('id');

        $this->ensureItems($user, $workspace, Server::class, $serverIds);
        $this->ensureItems($user, $workspace, CloudflareZone::class, $zoneIds);

        // Prune linked items whose resource was removed.
        CostItem::where('workspace_id', $workspace->id)
            ->where('costable_type', Server::class)
            ->whereNotIn('costable_id', $serverIds)
            ->delete();

        CostItem::where('workspace_id', $workspace->id)
            ->where('costable_type', CloudflareZone::class)
            ->whereNotIn('costable_id', $zoneIds)
            ->delete();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int>  $ids
     */
    private function ensureItems(User $user, Workspace $workspace, string $type, Collection $ids): void
    {
        if ($ids->isEmpty()) {
            return;
        }

        $existing = CostItem::where('workspace_id', $workspace->id)
            ->where('costable_type', $type)
            ->pluck('costable_id');

        $missing = $ids->diff($existing);

        foreach ($missing as $id) {
            CostItem::create([
                'workspace_id'  => $workspace->id,
                'user_id'       => $user->id,
                'costable_type' => $type,
                'costable_id'   => $id,
            ]);
        }
    }

    /**
     * Load all cost items of a workspace, grouped by category, with totals.
     *
     * @return array{
     *     groups: array<string, \Illuminate\Support\Collection<int, CostItem>>,
     *     totals: array<string, float>,
     *     grand_total: float,
     *     priced_count: int,
     *     unpriced_count: int
     * }
     */
    public function overview(Workspace $workspace): array
    {
        $items = CostItem::where('workspace_id', $workspace->id)
            ->with('costable')
            ->get()
            ->sortBy(fn (CostItem $i) => $i->displayName(), SORT_FLAG_CASE | SORT_NATURAL)
            ->values();

        $groups = [
            'server' => collect(),
            'domain' => collect(),
            'manual' => collect(),
        ];

        foreach ($items as $item) {
            $groups[$item->category()]->push($item);
        }

        $totals = [
            'server' => (float) $groups['server']->sum('monthly_price'),
            'domain' => (float) $groups['domain']->sum('monthly_price'),
            'manual' => (float) $groups['manual']->sum('monthly_price'),
        ];

        return [
            'groups'         => $groups,
            'totals'         => $totals,
            'grand_total'    => array_sum($totals),
            'priced_count'   => $items->whereNotNull('monthly_price')->count(),
            'unpriced_count' => $items->whereNull('monthly_price')->count(),
        ];
    }
}
