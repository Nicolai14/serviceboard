<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->timestamp('last_checked_at')->nullable()->after('notes');
            $table->unsignedInteger('last_latency_ms')->nullable()->after('last_checked_at');
            $table->boolean('notify_on_down')->default(false)->after('last_latency_ms');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['last_checked_at', 'last_latency_ms', 'notify_on_down']);
        });
    }
};
