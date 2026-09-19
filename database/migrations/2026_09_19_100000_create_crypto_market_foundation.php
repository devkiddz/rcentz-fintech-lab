<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('crypto_pairs')) {
            Schema::create('crypto_pairs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('market_instrument_id')->unique()->constrained('market_instruments')->cascadeOnDelete();
                $table->string('symbol', 20)->unique();
                $table->string('display_symbol', 24);
                $table->string('name', 120);
                $table->string('base_asset', 20);
                $table->string('quote_asset', 20)->default('USD');
                $table->decimal('current_rate', 30, 12)->nullable();
                $table->decimal('previous_close', 30, 12)->nullable();
                $table->unsignedTinyInteger('price_precision')->default(2);
                $table->decimal('minimum_tick', 30, 12)->default(0.01);
                $table->boolean('is_active')->default(true)->index();
                $table->boolean('is_featured')->default(false)->index();
                $table->boolean('external_feed_enabled')->default(true);
                $table->timestamp('last_updated')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['base_asset', 'quote_asset']);
            });
        }

        if (! Schema::hasTable('crypto_candles')) {
            Schema::create('crypto_candles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('crypto_pair_id')->constrained('crypto_pairs')->cascadeOnDelete();
                $table->string('interval', 12)->default('1d');
                $table->timestamp('timestamp');
                $table->decimal('open', 30, 12);
                $table->decimal('high', 30, 12);
                $table->decimal('low', 30, 12);
                $table->decimal('close', 30, 12);
                $table->decimal('volume', 36, 8)->nullable();
                $table->string('source', 40)->default('alpha_vantage');
                $table->timestamps();
                $table->unique(['crypto_pair_id', 'interval', 'timestamp'], 'crypto_candles_pair_interval_time_unique');
                $table->index(['crypto_pair_id', 'timestamp'], 'crypto_candles_pair_time_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('crypto_candles');
        Schema::dropIfExists('crypto_pairs');
    }
};
