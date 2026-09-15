<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('copy_trader_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('strategy_name', 100);
            $table->text('bio')->nullable();
            $table->enum('risk_level', ['low', 'medium', 'high'])->default('medium');
            $table->boolean('is_public')->default(true);
            $table->boolean('is_accepting_copiers')->default(true);
            $table->timestamps();
        });

        Schema::create('copy_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('follower_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('allocation_limit', 15, 2);
            $table->decimal('used_amount', 15, 2)->default(0);
            $table->decimal('max_trade_amount', 15, 2);
            $table->decimal('copy_ratio_percent', 6, 2)->default(100);
            $table->enum('status', ['active', 'paused', 'stopped'])->default('active');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('stopped_at')->nullable();
            $table->timestamps();
            $table->unique(['follower_id', 'provider_id']);
            $table->index(['provider_id', 'status']);
            $table->index(['follower_id', 'status']);
        });

        Schema::create('copy_trade_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('copy_relationship_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_stock_transaction_id')->constrained('stock_transactions')->cascadeOnDelete();
            $table->foreignId('follower_stock_transaction_id')->nullable()->constrained('stock_transactions')->nullOnDelete();
            $table->enum('action', ['buy', 'sell']);
            $table->decimal('requested_amount', 15, 2)->default(0);
            $table->decimal('executed_amount', 15, 2)->default(0);
            $table->enum('status', ['completed', 'skipped', 'failed'])->default('completed');
            $table->string('failure_reason', 255)->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();
            $table->index(['copy_relationship_id', 'created_at']);
        });

        Schema::create('trading_bots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->enum('strategy', ['dca', 'price_below', 'price_above']);
            $table->enum('action', ['buy', 'sell'])->default('buy');
            $table->decimal('amount_per_trade', 15, 2)->nullable();
            $table->decimal('quantity_per_trade', 15, 6)->nullable();
            $table->decimal('trigger_price', 15, 2)->nullable();
            $table->unsignedInteger('interval_minutes')->default(60);
            $table->unsignedInteger('max_daily_trades')->default(3);
            $table->decimal('max_total_spend', 15, 2)->nullable();
            $table->decimal('spent_total', 15, 2)->default(0);
            $table->enum('status', ['active', 'paused'])->default('paused');
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'next_run_at']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('trading_bot_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trading_bot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('action', ['buy', 'sell']);
            $table->decimal('quantity', 15, 6)->default(0);
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('amount', 15, 2)->default(0);
            $table->enum('status', ['completed', 'skipped', 'failed'])->default('completed');
            $table->string('reason', 255)->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();
            $table->index(['trading_bot_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trading_bot_executions');
        Schema::dropIfExists('trading_bots');
        Schema::dropIfExists('copy_trade_executions');
        Schema::dropIfExists('copy_relationships');
        Schema::dropIfExists('copy_trader_profiles');
    }
};
