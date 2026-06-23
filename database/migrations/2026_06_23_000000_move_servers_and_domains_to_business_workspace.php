<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $user = DB::table('users')->where('email', 'nicolai.proch@schauinsland-reisen.de')->first();

        if (! $user) {
            return;
        }

        $personal = DB::table('workspaces')
            ->where('user_id', $user->id)
            ->where('type', 'personal')
            ->first();

        $business = DB::table('workspaces')
            ->where('user_id', $user->id)
            ->where('type', 'business')
            ->first();

        if (! $personal || ! $business) {
            return;
        }

        // Move all non-offline servers from personal → business
        $servers = DB::table('servers')
            ->where('workspace_id', $personal->id)
            ->where('status', '!=', 'offline')
            ->whereNull('deleted_at')
            ->get();

        foreach ($servers as $server) {
            DB::table('servers')
                ->where('id', $server->id)
                ->update(['workspace_id' => $business->id]);

            $personalItem = DB::table('cost_items')
                ->where('workspace_id', $personal->id)
                ->where('costable_type', 'App\\Models\\Server')
                ->where('costable_id', $server->id)
                ->first();

            if (! $personalItem) {
                continue;
            }

            $businessItem = DB::table('cost_items')
                ->where('workspace_id', $business->id)
                ->where('costable_type', 'App\\Models\\Server')
                ->where('costable_id', $server->id)
                ->first();

            if ($businessItem) {
                // Copy price/notes from personal if business row is empty
                if ($personalItem->monthly_price !== null && $businessItem->monthly_price === null) {
                    DB::table('cost_items')
                        ->where('id', $businessItem->id)
                        ->update([
                            'monthly_price' => $personalItem->monthly_price,
                            'notes'         => $personalItem->notes,
                        ]);
                }
                DB::table('cost_items')->where('id', $personalItem->id)->delete();
            } else {
                DB::table('cost_items')
                    ->where('id', $personalItem->id)
                    ->update(['workspace_id' => $business->id]);
            }
        }

        // Move domain cost items from personal → business
        $domainItems = DB::table('cost_items')
            ->where('workspace_id', $personal->id)
            ->where('costable_type', 'App\\Models\\CloudflareZone')
            ->get();

        foreach ($domainItems as $item) {
            $existing = DB::table('cost_items')
                ->where('workspace_id', $business->id)
                ->where('costable_type', 'App\\Models\\CloudflareZone')
                ->where('costable_id', $item->costable_id)
                ->first();

            if ($existing) {
                if ($item->monthly_price !== null && $existing->monthly_price === null) {
                    DB::table('cost_items')
                        ->where('id', $existing->id)
                        ->update([
                            'monthly_price' => $item->monthly_price,
                            'notes'         => $item->notes,
                        ]);
                }
                DB::table('cost_items')->where('id', $item->id)->delete();
            } else {
                DB::table('cost_items')
                    ->where('id', $item->id)
                    ->update(['workspace_id' => $business->id]);
            }
        }
    }

    public function down(): void
    {
        // Data migration — not reversible
    }
};
