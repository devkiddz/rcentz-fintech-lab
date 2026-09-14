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
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 10)->unique()->notNull();
            $table->string('company_name')->notNull();
            $table->string('sector', 100)->nullable();
            $table->string('industry', 100)->nullable();
            $table->decimal('current_price', 10, 2)->notNull();
            $table->decimal('previous_close', 10, 2)->nullable();
            $table->decimal('change_amount', 10, 2)->nullable();
            $table->decimal('change_percentage', 5, 2)->nullable();
            $table->bigInteger('volume')->nullable();
            $table->decimal('market_cap', 20, 2)->nullable();
            $table->decimal('pe_ratio', 10, 2)->nullable();
            $table->decimal('dividend_yield', 5, 2)->nullable();
            $table->decimal('fifty_two_week_high', 10, 2)->nullable();
            $table->decimal('fifty_two_week_low', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->timestamp('last_updated')->nullable();
            $table->timestamps();
            
            $table->index(['symbol']);
            $table->index(['sector', 'industry']);
            $table->index(['is_active', 'is_featured']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
