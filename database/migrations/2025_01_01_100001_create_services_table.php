<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['web', 'database', 'cache', 'queue', 'proxy', 'mail', 'custom'])->default('custom');
            $table->unsignedSmallInteger('port')->nullable();
            $table->enum('status', ['running', 'stopped', 'error', 'unknown'])->default('unknown');
            $table->string('check_url')->nullable();
            $table->unsignedSmallInteger('check_interval')->default(60);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['server_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
