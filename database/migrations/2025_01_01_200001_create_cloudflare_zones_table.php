<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cloudflare_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cloudflare_token_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('zone_id', 64)->unique();        // Cloudflare zone UUID
            $table->string('name');                          // domain.com
            $table->string('status', 30)->default('active'); // active|pending|initializing|moved|deleted|deactivated
            $table->boolean('paused')->default(false);
            $table->string('plan_name')->nullable();
            $table->string('type', 20)->default('full');     // full|partial|secondary
            $table->json('name_servers')->nullable();
            $table->json('original_name_servers')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cloudflare_zones');
    }
};
