<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('strategy_provider_applications')) {
            Schema::create('strategy_provider_applications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('display_name', 100);
                $table->text('experience')->nullable();
                $table->text('strategy_summary')->nullable();
                $table->enum('risk_level', ['low', 'medium', 'high'])->default('medium');
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->index();
                $table->text('admin_notes')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'status']);
            });
        }

        if (! Schema::hasColumn('copy_trader_profiles', 'approved_at')) {
            Schema::table('copy_trader_profiles', function (Blueprint $table) {
                $table->timestamp('approved_at')->nullable()->after('is_accepting_copiers');
                $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('copy_strategies')) {
            Schema::create('copy_strategies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('copy_trader_profile_id')->constrained()->cascadeOnDelete();
                $table->string('name', 120);
                $table->text('description')->nullable();
                $table->enum('risk_level', ['low', 'medium', 'high'])->default('medium');
                $table->decimal('minimum_allocation', 15, 2)->default(100);
                $table->decimal('recommended_allocation', 15, 2)->nullable();
                $table->boolean('is_public')->default(true);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index(['is_public', 'is_active']);
            });
        }

        if (! Schema::hasColumn('copy_relationships', 'copy_strategy_id')) {
            Schema::table('copy_relationships', function (Blueprint $table) {
                $table->foreignId('copy_strategy_id')->nullable()->after('provider_id')->constrained('copy_strategies')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('bot_products')) {
            Schema::create('bot_products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->foreignId('stock_id')->constrained()->cascadeOnDelete();
                $table->string('name', 120);
                $table->string('slug', 140)->unique();
                $table->text('description')->nullable();
                $table->enum('strategy', ['dca', 'price_below', 'price_above']);
                $table->enum('action', ['buy', 'sell'])->default('buy');
                $table->enum('risk_level', ['low', 'medium', 'high'])->default('medium');
                $table->decimal('price', 15, 2)->default(0);
                $table->enum('billing_period', ['one_time', 'monthly', 'quarterly', 'yearly'])->default('one_time');
                $table->decimal('minimum_balance', 15, 2)->default(0);
                $table->decimal('max_user_allocation', 15, 2)->nullable();
                $table->unsignedInteger('default_interval_minutes')->default(60);
                $table->unsignedInteger('default_max_daily_trades')->default(3);
                $table->decimal('default_trade_amount', 15, 2)->nullable();
                $table->decimal('default_trigger_price', 15, 2)->nullable();
                $table->boolean('allow_user_trade_amount')->default(true);
                $table->boolean('allow_user_trigger_price')->default(true);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index(['is_active', 'risk_level']);
            });
        }

        if (! Schema::hasTable('bot_subscriptions')) {
            Schema::create('bot_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('bot_product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('trading_bot_id')->nullable()->constrained()->nullOnDelete();
                $table->decimal('price_paid', 15, 2)->default(0);
                $table->enum('status', ['active', 'paused', 'cancelled', 'expired'])->default('paused')->index();
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'status']);
            });
        }

        if (! Schema::hasColumn('trading_bot_executions', 'bot_subscription_id')) {
            Schema::table('trading_bot_executions', function (Blueprint $table) {
                $table->foreignId('bot_subscription_id')->nullable()->after('trading_bot_id')->constrained()->nullOnDelete();
            });
        }

        // Preserve any v1 provider profiles by treating them as already-approved legacy providers.
        DB::table('copy_trader_profiles')->orderBy('id')->chunkById(100, function ($profiles) {
            foreach ($profiles as $profile) {
                $application = DB::table('strategy_provider_applications')
                    ->where('user_id', $profile->user_id)
                    ->where('status', 'approved')
                    ->first();

                if (! $application) {
                    DB::table('strategy_provider_applications')->insert([
                        'user_id' => $profile->user_id,
                        'display_name' => $profile->strategy_name,
                        'experience' => 'Existing provider migrated from Trading Intelligence v1.',
                        'strategy_summary' => $profile->bio,
                        'risk_level' => $profile->risk_level,
                        'status' => 'approved',
                        'reviewed_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                if (empty($profile->approved_at)) {
                    DB::table('copy_trader_profiles')->where('id', $profile->id)->update([
                        'approved_at' => now(),
                    ]);
                }

                $strategy = DB::table('copy_strategies')
                    ->where('copy_trader_profile_id', $profile->id)
                    ->first();

                if (! $strategy) {
                    $strategyId = DB::table('copy_strategies')->insertGetId([
                        'copy_trader_profile_id' => $profile->id,
                        'name' => $profile->strategy_name,
                        'description' => $profile->bio,
                        'risk_level' => $profile->risk_level,
                        'minimum_allocation' => 100,
                        'recommended_allocation' => null,
                        'is_public' => (bool) $profile->is_public,
                        'is_active' => (bool) $profile->is_accepting_copiers,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('copy_relationships')
                        ->where('provider_id', $profile->user_id)
                        ->whereNull('copy_strategy_id')
                        ->update(['copy_strategy_id' => $strategyId]);
                }
            }
        });

        // v1 user-created bots are kept for history but paused and excluded from v2 marketplace.
        DB::table('trading_bots')->where('status', 'active')->update(['status' => 'paused']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('trading_bot_executions', 'bot_subscription_id')) {
            Schema::table('trading_bot_executions', function (Blueprint $table) {
                $table->dropConstrainedForeignId('bot_subscription_id');
            });
        }

        Schema::dropIfExists('bot_subscriptions');
        Schema::dropIfExists('bot_products');

        if (Schema::hasColumn('copy_relationships', 'copy_strategy_id')) {
            Schema::table('copy_relationships', function (Blueprint $table) {
                $table->dropConstrainedForeignId('copy_strategy_id');
            });
        }

        Schema::dropIfExists('copy_strategies');

        if (Schema::hasColumn('copy_trader_profiles', 'approved_at')) {
            Schema::table('copy_trader_profiles', function (Blueprint $table) {
                $table->dropConstrainedForeignId('approved_by');
                $table->dropColumn('approved_at');
            });
        }

        Schema::dropIfExists('strategy_provider_applications');
    }
};
