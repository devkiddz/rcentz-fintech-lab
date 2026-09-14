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
        Schema::create('investment_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name')->notNull();
            $table->text('description')->nullable();
            $table->enum('type', ['mutual_fund', 'etf', 'retirement', 'tesla_focused', 'esg', 'index_fund'])->notNull();
            $table->string('category', 100)->notNull();
            $table->enum('risk_level', ['low', 'medium', 'high', 'very_high'])->notNull();
            $table->decimal('minimum_investment', 10, 2)->notNull();
            $table->decimal('management_fee', 5, 4)->notNull();
            $table->decimal('expense_ratio', 5, 4)->notNull();
            $table->decimal('nav', 10, 4)->notNull();
            $table->json('nav_history')->nullable();
            $table->timestamp('last_nav_update')->nullable();
            $table->decimal('previous_nav', 10, 4)->nullable();
            $table->decimal('nav_change', 10, 4)->nullable();
            $table->decimal('nav_change_percentage', 5, 2)->nullable();
            $table->decimal('total_assets', 20, 2)->nullable();
            $table->date('inception_date')->nullable();
            $table->decimal('dividend_yield', 5, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->string('image')->nullable();
            $table->string('prospectus_url', 500)->nullable();
            $table->string('fact_sheet_url', 500)->nullable();
            $table->timestamp('last_updated')->nullable();
            $table->timestamps();
            
            $table->index(['type', 'category']);
            $table->index(['risk_level']);
            $table->index(['is_active', 'is_featured']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investment_plans');
    }
};
