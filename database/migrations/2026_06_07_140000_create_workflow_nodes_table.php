<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();

            // Building-block type (app, server, docker, software, domain, …).
            $table->string('type', 30);
            $table->string('label')->nullable();

            // Position on the canvas (pixels, relative to the canvas origin).
            $table->integer('position_x')->default(0);
            $table->integer('position_y')->default(0);

            // Free-form extra fields per block (url, notes, …).
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index('workspace_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_nodes');
    }
};
