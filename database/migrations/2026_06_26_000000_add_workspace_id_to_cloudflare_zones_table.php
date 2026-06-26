<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cloudflare_zones', function (Blueprint $table) {
            $table->foreignId('workspace_id')
                ->nullable()
                ->after('user_id')
                ->constrained()
                ->nullOnDelete();
        });

        // Backfill: assign every existing zone to its owner's personal workspace.
        // Owners without a personal workspace yet are left null and get backfilled
        // on their next request by SetActiveWorkspace.
        $personalByUser = DB::table('workspaces')
            ->where('type', 'personal')
            ->pluck('id', 'user_id');

        foreach ($personalByUser as $userId => $workspaceId) {
            DB::table('cloudflare_zones')
                ->where('user_id', $userId)
                ->whereNull('workspace_id')
                ->update(['workspace_id' => $workspaceId]);
        }
    }

    public function down(): void
    {
        Schema::table('cloudflare_zones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('workspace_id');
        });
    }
};
