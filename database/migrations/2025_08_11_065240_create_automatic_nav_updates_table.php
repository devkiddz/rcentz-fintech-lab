<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('automatic_nav_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investment_plan_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('update_type', ['increase', 'decrease']);
            $table->decimal('update_amount', 10, 4);
            $table->integer('update_interval_value');
            $table->enum('update_interval_unit', ['minutes', 'hours', 'days']);
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->timestamp('last_executed_at')->nullable();
            $table->timestamp('next_execution_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('total_executions')->default(0);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            // Indexes for performance
            $table->index(['is_active', 'next_execution_at']);
            $table->index(['investment_plan_id', 'is_active']);
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automatic_nav_updates');
    }
};
