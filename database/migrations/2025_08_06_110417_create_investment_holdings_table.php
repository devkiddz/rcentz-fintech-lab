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
        Schema::create('investment_holdings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('investment_plan_id')->constrained()->onDelete('cascade');
            $table->decimal('units', 15, 6)->notNull();
            $table->decimal('average_cost', 10, 4)->notNull();
            $table->decimal('total_invested', 15, 2)->notNull();
            $table->decimal('current_value', 15, 2)->notNull();
            $table->decimal('unrealized_gain_loss', 15, 2)->notNull();
            $table->decimal('unrealized_gain_loss_percentage', 5, 2)->notNull();
            $table->timestamps();
            
            $table->unique(['user_id', 'investment_plan_id']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investment_holdings');
    }
};
