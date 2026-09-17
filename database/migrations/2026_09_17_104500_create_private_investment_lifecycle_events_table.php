<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('private_investment_lifecycle_events')) {
            Schema::create('private_investment_lifecycle_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('instrument_id')->constrained('private_investment_instruments')->cascadeOnDelete();
                $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('type', 40);
                $table->string('calculation_mode', 40);
                $table->decimal('value', 18, 6);
                $table->decimal('total_amount', 18, 2)->default(0);
                $table->unsignedInteger('affected_holdings')->default(0);
                $table->text('reason');
                $table->json('metadata')->nullable();
                $table->timestamp('effective_at');
                $table->timestamps();
            });
        }

        // Explicit short names avoid MySQL's 64-character identifier limit.
        Schema::table('private_investment_lifecycle_events', function (Blueprint $table) {
            $table->index(['instrument_id', 'effective_at'], 'pinv_life_inst_eff_idx');
            $table->index(['type', 'effective_at'], 'pinv_life_type_eff_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('private_investment_lifecycle_events');
    }
};
