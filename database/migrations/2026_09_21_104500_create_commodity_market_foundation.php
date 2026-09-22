<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commodity_instruments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('market_instrument_id')->unique()->constrained('market_instruments')->cascadeOnDelete();
            $table->string('symbol', 24)->unique();
            $table->string('display_symbol', 24);
            $table->string('name', 120);
            $table->string('commodity_code', 24);
            $table->string('quote_asset', 16)->default('USD');
            $table->string('provider_symbol', 32);
            $table->string('provider_family', 40);
            $table->string('unit', 40)->nullable();
            $table->decimal('current_price', 30, 12)->nullable();
            $table->decimal('previous_close', 30, 12)->nullable();
            $table->unsignedTinyInteger('price_precision')->default(2);
            $table->decimal('minimum_tick', 30, 12)->default(0.01);
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('external_feed_enabled')->default(true);
            $table->timestamp('last_updated')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['commodity_code', 'quote_asset']);
        });

        Schema::create('commodity_price_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commodity_instrument_id')->constrained('commodity_instruments')->cascadeOnDelete();
            $table->string('interval', 12)->default('1d');
            $table->timestamp('timestamp');
            $table->decimal('price', 30, 12);
            $table->string('source', 40)->default('alpha_vantage');
            $table->timestamps();
            $table->unique(['commodity_instrument_id', 'interval', 'timestamp'], 'commodity_points_instrument_interval_time_unique');
            $table->index(['commodity_instrument_id', 'timestamp'], 'commodity_points_instrument_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commodity_price_points');
        Schema::dropIfExists('commodity_instruments');
    }
};
