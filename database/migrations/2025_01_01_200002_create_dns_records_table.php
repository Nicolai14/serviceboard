<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dns_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cloudflare_zone_id')->constrained()->cascadeOnDelete();
            $table->string('cf_record_id', 64);     // Cloudflare record UUID
            $table->string('type', 10);              // A, AAAA, CNAME, MX, TXT, NS, SRV, CAA ...
            $table->string('name');                  // subdomain.domain.com or @
            $table->text('content');                 // IP, target hostname, TXT value ...
            $table->boolean('proxied')->default(false);
            $table->boolean('proxiable')->default(true);
            $table->unsignedInteger('ttl')->default(1);  // 1 = auto in Cloudflare
            $table->unsignedSmallInteger('priority')->nullable(); // MX, SRV
            $table->string('comment')->nullable();
            $table->timestamp('created_on')->nullable();
            $table->timestamp('modified_on')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['cloudflare_zone_id', 'cf_record_id']);
            $table->index(['cloudflare_zone_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dns_records');
    }
};
