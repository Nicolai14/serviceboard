<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('docker_containers', function (Blueprint $table) {
            $table->boolean('notify_on_down')->default(false)->after('synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('docker_containers', function (Blueprint $table) {
            $table->dropColumn('notify_on_down');
        });
    }
};
