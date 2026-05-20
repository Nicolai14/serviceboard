<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->float('cpu_usage')->default(0);
            $table->float('memory_usage')->default(0);
            $table->float('memory_total')->default(0);
            $table->float('disk_usage')->default(0);
            $table->float('disk_total')->default(0);
            $table->float('load_average')->default(0);
            $table->unsignedBigInteger('uptime_seconds')->default(0);
            $table->timestamp('recorded_at')->useCurrent();

            $table->index(['server_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metrics');
    }
};
