<?php

namespace App\Console\Commands;

use App\Enums\WorkspaceType;
use App\Models\CloudflareZone;
use App\Models\CostItem;
use App\Models\Server;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MoveToBusinessCommand extends Command
{
    protected $signature = 'workspace:move-to-business
        {user : User id or email}
        {--include-offline : Also move servers with status=offline}
        {--dry-run : Show what would change without writing}';

    protected $description = 'Move all servers and domains of a user from their personal into their business workspace';

    public function handle(): int
    {
        $ident = $this->argument('user');
        $user  = is_numeric($ident)
            ? User::find((int) $ident)
            : User::where('email', $ident)->first();

        if (! $user) {
            $this->error(sprintf('User "%s" nicht gefunden.', $ident));
            return self::FAILURE;
        }

        $user->ensureWorkspacesExist();

        $personal = $user->workspaces()->where('type', WorkspaceType::Personal)->first();
        $business = $user->workspaces()->where('type', WorkspaceType::Business)->first();

        if (! $personal || ! $business) {
            $this->error('Privat- oder Geschäftlich-Workspace fehlt.');
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        $serverQuery = Server::where('workspace_id', $personal->id);
        if (! $this->option('include-offline')) {
            $serverQuery->where('status', '!=', 'offline');
        }
        $servers = $serverQuery->get();

        $zones = CloudflareZone::where('workspace_id', $personal->id)->get();

        $this->info("User: {$user->email}");
        $this->line("  Server zu verschieben:  {$servers->count()}" . ($this->option('include-offline') ? '' : ' (offline ausgenommen)'));
        $this->line("  Domains zu verschieben: {$zones->count()}");

        if ($dryRun) {
            foreach ($servers as $s) {
                $this->line("  <fg=yellow>[dry]</> Server  {$s->name} ({$s->status})");
            }
            foreach ($zones as $z) {
                $this->line("  <fg=yellow>[dry]</> Domain  {$z->name}");
            }
            $this->warn('Dry-run — nichts geschrieben.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($servers, $zones, $personal, $business) {
            foreach ($servers as $server) {
                $server->update(['workspace_id' => $business->id]);
                $this->migrateCostItem(Server::class, $server->id, $personal->id, $business->id);
            }

            foreach ($zones as $zone) {
                $zone->update(['workspace_id' => $business->id]);
                $this->migrateCostItem(CloudflareZone::class, $zone->id, $personal->id, $business->id);
            }
        });

        $this->info("Fertig: {$servers->count()} Server + {$zones->count()} Domains nach Geschäftlich verschoben.");

        return self::SUCCESS;
    }

    /**
     * Carry a resource's cost row from the personal into the business workspace,
     * preserving price/notes and avoiding a duplicate row.
     */
    private function migrateCostItem(string $type, int $id, int $personalId, int $businessId): void
    {
        $personalItem = CostItem::where('workspace_id', $personalId)
            ->where('costable_type', $type)
            ->where('costable_id', $id)
            ->first();

        if (! $personalItem) {
            return;
        }

        $businessItem = CostItem::where('workspace_id', $businessId)
            ->where('costable_type', $type)
            ->where('costable_id', $id)
            ->first();

        if (! $businessItem) {
            $personalItem->update(['workspace_id' => $businessId]);
            return;
        }

        // Business row already exists — fill its price/notes from personal if empty, then drop personal.
        if ($personalItem->monthly_price !== null && $businessItem->monthly_price === null) {
            $businessItem->update([
                'monthly_price' => $personalItem->monthly_price,
                'notes'         => $personalItem->notes,
            ]);
        }

        $personalItem->delete();
    }
}
