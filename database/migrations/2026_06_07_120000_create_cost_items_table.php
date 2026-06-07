<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cost_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Polymorphic link to the costed resource (Server / CloudflareZone).
            // Null for free / manual line items.
            $table->nullableMorphs('costable');

            // Label is only used for manual items; linked items derive it from
            // the related model.
            $table->string('label')->nullable();
            $table->decimal('monthly_price', 10, 2)->nullable();
            $table->char('currency', 3)->default('EUR');
            $table->text('notes')->nullable();
            $table->timestamps();

            // One auto-item per resource per workspace. MySQL allows multiple
            // NULL tuples, so manual items (null costable) are unaffected.
            $table->unique(['workspace_id', 'costable_type', 'costable_id'], 'cost_items_workspace_costable_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cost_items');
    }
};
