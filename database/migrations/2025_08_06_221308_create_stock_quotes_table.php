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
        Schema::create('stock_quotes', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 10)->notNull();
            $table->decimal('current_price', 10, 2)->notNull();
            $table->decimal('previous_close', 10, 2)->nullable();
            $table->decimal('change_amount', 10, 2)->nullable();
            $table->decimal('change_percentage', 5, 2)->nullable();
            $table->bigInteger('volume')->nullable();
            $table->decimal('high', 10, 2)->nullable();
            $table->decimal('low', 10, 2)->nullable();
            $table->decimal('open', 10, 2)->nullable();
            
            // Analyst recommendations
            $table->integer('strong_buy')->default(0);
            $table->integer('buy')->default(0);
            $table->integer('hold')->default(0);
            $table->integer('sell')->default(0);
            $table->integer('strong_sell')->default(0);
            $table->date('recommendation_date')->nullable();
            
            $table->timestamp('fetched_at')->notNull();
            $table->timestamps();
            
            $table->index(['symbol', 'fetched_at']);
            $table->index(['symbol']);
            $table->index(['fetched_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_quotes');
    }
};
