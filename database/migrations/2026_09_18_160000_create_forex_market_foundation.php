<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forex_pairs', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 16)->unique();
            $table->string('display_symbol', 16);
            $table->string('name');
            $table->string('base_currency', 3);
            $table->string('quote_currency', 3);
            $table->decimal('pip_size', 16, 8)->default(0.00010000);
            $table->unsignedTinyInteger('price_precision')->default(5);
            $table->decimal('current_rate', 24, 8)->nullable();
            $table->decimal('previous_close', 24, 8)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('external_feed_enabled')->default(true);
            $table->json('preferred_sessions')->nullable();
            $table->timestamp('last_updated')->nullable();
            $table->timestamps();

            $table->index(['base_currency', 'quote_currency'], 'forex_pairs_currency_idx');
            $table->index(['is_active', 'is_featured'], 'forex_pairs_active_featured_idx');
        });

        Schema::create('forex_candles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('forex_pair_id')->constrained('forex_pairs')->cascadeOnDelete();
            $table->string('interval', 12)->default('1d');
            $table->timestamp('timestamp');
            $table->decimal('open', 24, 8);
            $table->decimal('high', 24, 8);
            $table->decimal('low', 24, 8);
            $table->decimal('close', 24, 8);
            $table->decimal('volume', 24, 4)->nullable();
            $table->string('source', 40)->default('alpha_vantage');
            $table->timestamps();

            $table->unique(['forex_pair_id', 'interval', 'timestamp'], 'forex_candles_pair_interval_time_unique');
            $table->index(['forex_pair_id', 'timestamp'], 'forex_candles_pair_time_idx');
        });

        Schema::create('market_instruments', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 24);
            $table->string('display_symbol', 24);
            $table->string('name');
            $table->string('asset_class', 24);
            $table->string('market', 40)->nullable();
            $table->string('base_asset', 16)->nullable();
            $table->string('quote_asset', 16)->nullable();
            $table->unsignedTinyInteger('price_precision')->default(4);
            $table->decimal('pip_size', 16, 8)->nullable();
            $table->foreignId('stock_id')->nullable()->constrained('stocks')->nullOnDelete();
            $table->foreignId('forex_pair_id')->nullable()->constrained('forex_pairs')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['asset_class', 'symbol'], 'market_instruments_class_symbol_unique');
            $table->unique('stock_id', 'market_instruments_stock_unique');
            $table->unique('forex_pair_id', 'market_instruments_forex_unique');
            $table->index(['asset_class', 'is_active'], 'market_instruments_class_active_idx');
        });

        if (Schema::hasTable('stocks')) {
            $now = now();
            DB::table('stocks')
                ->orderBy('id')
                ->get(['id', 'symbol', 'company_name', 'is_active', 'is_featured'])
                ->each(function ($stock) use ($now) {
                    DB::table('market_instruments')->insertOrIgnore([
                        'symbol' => strtoupper((string) $stock->symbol),
                        'display_symbol' => strtoupper((string) $stock->symbol),
                        'name' => (string) $stock->company_name,
                        'asset_class' => 'stock',
                        'market' => 'us_equity',
                        'base_asset' => strtoupper((string) $stock->symbol),
                        'quote_asset' => 'USD',
                        'price_precision' => 2,
                        'pip_size' => null,
                        'stock_id' => $stock->id,
                        'forex_pair_id' => null,
                        'is_active' => (bool) $stock->is_active,
                        'is_featured' => (bool) $stock->is_featured,
                        'metadata' => json_encode(['source' => 'stocks']),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('market_instruments');
        Schema::dropIfExists('forex_candles');
        Schema::dropIfExists('forex_pairs');
    }
};
