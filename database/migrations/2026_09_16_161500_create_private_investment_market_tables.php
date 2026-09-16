<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('private_investment_instruments', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('symbol', 24)->unique();
            $table->string('name');
            $table->string('category', 40);
            $table->text('description')->nullable();
            $table->string('risk_level', 20)->default('medium');
            $table->string('status', 20)->default('active');
            $table->string('currency', 8)->default('USD');
            $table->decimal('opening_price', 20, 6);
            $table->decimal('current_price', 20, 6);
            $table->decimal('previous_price', 20, 6);
            $table->decimal('unit_supply', 24, 6)->default(0);
            $table->decimal('available_units', 24, 6)->default(0);
            $table->decimal('minimum_investment', 20, 2)->default(0);
            $table->decimal('maximum_investment', 20, 2)->nullable();
            $table->decimal('management_fee_percent', 8, 4)->default(0);
            $table->unsignedInteger('lock_period_days')->default(0);
            $table->date('maturity_date')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->timestamp('last_valued_at')->nullable();
            $table->timestamps();

            $table->index(['category', 'status']);
        });

        Schema::create('private_investment_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instrument_id')->constrained('private_investment_instruments')->cascadeOnDelete();
            $table->string('asset_type', 50);
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('acquisition_value', 20, 2)->default(0);
            $table->decimal('current_valuation', 20, 2)->default(0);
            $table->decimal('ownership_percentage', 8, 4)->default(100);
            $table->string('status', 20)->default('active');
            $table->date('acquired_at')->nullable();
            $table->timestamp('effective_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['instrument_id', 'status']);
        });

        Schema::create('private_investment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instrument_id')->constrained('private_investment_instruments')->cascadeOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained('private_investment_assets')->nullOnDelete();
            $table->string('event_type', 60);
            $table->string('direction', 20)->default('neutral');
            $table->string('adjustment_type', 20)->nullable();
            $table->decimal('adjustment_value', 20, 6)->nullable();
            $table->decimal('previous_price', 20, 6)->nullable();
            $table->decimal('new_price', 20, 6)->nullable();
            $table->text('reason');
            $table->string('approval_state', 20)->default('approved');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamp('effective_at');
            $table->timestamps();

            $table->index(['instrument_id', 'effective_at']);
            $table->index(['event_type', 'approval_state']);
        });

        Schema::create('private_investment_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instrument_id')->constrained('private_investment_instruments')->cascadeOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('private_investment_events')->nullOnDelete();
            $table->string('timeframe', 12)->default('1d');
            $table->decimal('open', 20, 6);
            $table->decimal('high', 20, 6);
            $table->decimal('low', 20, 6);
            $table->decimal('close', 20, 6);
            $table->decimal('change_amount', 20, 6)->default(0);
            $table->decimal('change_percent', 14, 6)->default(0);
            $table->string('source', 40)->default('valuation_engine');
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->unique(['instrument_id', 'timeframe', 'recorded_at'], 'private_inv_price_unique');
            $table->index(['instrument_id', 'recorded_at']);
        });

        Schema::create('private_investment_holdings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('instrument_id')->constrained('private_investment_instruments')->cascadeOnDelete();
            $table->decimal('units', 24, 6);
            $table->decimal('average_entry_price', 20, 6);
            $table->decimal('cost_basis', 20, 2);
            $table->decimal('current_value', 20, 2);
            $table->decimal('unrealized_profit_loss', 20, 2)->default(0);
            $table->decimal('unrealized_return_percent', 14, 6)->default(0);
            $table->decimal('realized_profit_loss', 20, 2)->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'instrument_id'], 'private_inv_user_instrument_unique');
        });

        Schema::create('private_investment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('instrument_id')->constrained('private_investment_instruments')->cascadeOnDelete();
            $table->foreignId('holding_id')->nullable()->constrained('private_investment_holdings')->nullOnDelete();
            $table->string('type', 30);
            $table->decimal('units', 24, 6)->default(0);
            $table->decimal('price_per_unit', 20, 6)->default(0);
            $table->decimal('gross_amount', 20, 2)->default(0);
            $table->decimal('fee', 20, 2)->default(0);
            $table->decimal('net_amount', 20, 2)->default(0);
            $table->string('status', 20)->default('completed');
            $table->string('reference', 80)->unique();
            $table->json('metadata')->nullable();
            $table->timestamp('executed_at');
            $table->timestamps();

            $table->index(['user_id', 'executed_at']);
            $table->index(['instrument_id', 'executed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('private_investment_transactions');
        Schema::dropIfExists('private_investment_holdings');
        Schema::dropIfExists('private_investment_prices');
        Schema::dropIfExists('private_investment_events');
        Schema::dropIfExists('private_investment_assets');
        Schema::dropIfExists('private_investment_instruments');
    }
};
