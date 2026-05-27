<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->boolean('alerts_enabled')->default(true)->after('poll_failures');
            $table->json('alert_thresholds')->nullable()->after('alerts_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn(['alerts_enabled', 'alert_thresholds']);
        });
    }
};
