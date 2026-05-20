<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->string('ssh_user', 100)->default('root')->after('ssh_port');
            $table->enum('ssh_auth_method', ['key', 'password'])->default('key')->after('ssh_user');
            $table->text('ssh_private_key')->nullable()->after('ssh_auth_method');
            $table->string('ssh_password')->nullable()->after('ssh_private_key');
            $table->timestamp('last_seen_at')->nullable()->after('ssh_password');
            $table->timestamp('last_polled_at')->nullable()->after('last_seen_at');
            $table->unsignedSmallInteger('poll_failures')->default(0)->after('last_polled_at');
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn([
                'ssh_user', 'ssh_auth_method', 'ssh_private_key',
                'ssh_password', 'last_seen_at', 'last_polled_at', 'poll_failures',
            ]);
        });
    }
};
