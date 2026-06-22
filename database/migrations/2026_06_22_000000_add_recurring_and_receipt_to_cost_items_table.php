<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cost_items', function (Blueprint $table) {
            $table->boolean('is_recurring')->default(true)->after('notes');
            $table->string('receipt_path')->nullable()->after('is_recurring');
        });
    }

    public function down(): void
    {
        Schema::table('cost_items', function (Blueprint $table) {
            $table->dropColumn(['is_recurring', 'receipt_path']);
        });
    }
};
