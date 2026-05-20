<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('docker_containers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();

            // Identity
            $table->string('container_id', 64);
            $table->string('name');
            $table->string('image');

            // State from docker ps
            $table->string('state', 30)->default('unknown');    // running|exited|paused|restarting|dead|created
            $table->string('status_text')->nullable();           // "Up 2 hours", "Exited (0) 1 hour ago"

            // Metrics from docker stats (null when container is not running)
            $table->float('cpu_percent')->nullable();
            $table->float('memory_usage_mb')->nullable();
            $table->float('memory_limit_mb')->nullable();
            $table->float('memory_percent')->nullable();

            // Network / port bindings
            $table->json('ports')->nullable();                   // [{"host":"80","container":"80","proto":"tcp"}, …]

            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['server_id', 'container_id']);
            $table->index(['server_id', 'state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('docker_containers');
    }
};
