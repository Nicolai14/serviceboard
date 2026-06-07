<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_edges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();

            $table->foreignId('from_node_id')->constrained('workflow_nodes')->cascadeOnDelete();
            $table->foreignId('to_node_id')->constrained('workflow_nodes')->cascadeOnDelete();

            // Optional label describing the connection (e.g. "HTTPS", "SMTP").
            $table->string('label')->nullable();

            $table->timestamps();

            $table->index('workspace_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_edges');
    }
};
