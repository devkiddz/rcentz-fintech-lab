<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_environments', function (Blueprint $table) {
            $table->id();
            $table->string('active_marketplace', 20)->default('live');
            $table->string('controlled_drive_mode', 20)->default('range');
            $table->decimal('controlled_drive_strength', 8, 4)->default(1);
            $table->unsignedInteger('controlled_tick_seconds')->default(60);
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('controlled_market_instruments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_id')->unique()->constrained('stocks')->cascadeOnDelete();
            $table->string('symbol', 20)->unique();
            $table->string('label', 120);
            $table->string('asset_class', 40)->default('equity');
            $table->decimal('current_price', 24, 8);
            $table->decimal('previous_price', 24, 8);
            $table->decimal('opening_price', 24, 8);
            $table->decimal('high', 24, 8);
            $table->decimal('low', 24, 8);
            $table->unsignedTinyInteger('decimal_precision')->default(2);
            $table->decimal('minimum_tick', 24, 8)->default(0.01);
            $table->decimal('minimum_price', 24, 8)->default(0.01);
            $table->decimal('volatility_percent', 10, 6)->default(0.18);
            $table->decimal('individual_bias', 8, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_moved_at')->nullable();
            $table->timestamps();
            $table->index(['is_active', 'symbol']);
        });

        Schema::create('controlled_market_ticks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('controlled_market_instrument_id')
                ->constrained('controlled_market_instruments')
                ->cascadeOnDelete();
            $table->string('drive_mode', 20);
            $table->decimal('open', 24, 8);
            $table->decimal('high', 24, 8);
            $table->decimal('low', 24, 8);
            $table->decimal('close', 24, 8);
            $table->decimal('change_amount', 24, 8)->default(0);
            $table->decimal('change_percent', 12, 6)->default(0);
            $table->timestamp('ticked_at');
            $table->index(['controlled_market_instrument_id', 'ticked_at'], 'controlled_ticks_instrument_time_idx');
        });

        Schema::table('stock_transactions', function (Blueprint $table) {
            $table->string('marketplace', 20)->default('live')->after('execution_source')->index();
        });

        Schema::table('trade_positions', function (Blueprint $table) {
            $table->string('marketplace', 20)->default('live')->after('context_id')->index();
        });

        DB::table('market_environments')->insert([
            'id' => 1,
            'active_marketplace' => 'live',
            'controlled_drive_mode' => 'range',
            'controlled_drive_strength' => 1,
            'controlled_tick_seconds' => 60,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $stocks = DB::table('stocks')->where('is_active', true)->orderBy('id')->get();

        foreach ($stocks as $stock) {
            $price = max(0.000001, (float) $stock->current_price);

            if ($price >= 1) {
                $precision = 2;
                $tick = 0.01;
            } elseif ($price >= 0.01) {
                $precision = 4;
                $tick = 0.0001;
            } else {
                $precision = 6;
                $tick = 0.000001;
            }

            $volatility = match (true) {
                $price >= 1000 => 0.08,
                $price >= 100 => 0.12,
                $price >= 10 => 0.18,
                $price >= 1 => 0.25,
                default => 0.40,
            };

            $id = DB::table('controlled_market_instruments')->insertGetId([
                'stock_id' => $stock->id,
                'symbol' => strtoupper($stock->symbol),
                'label' => $stock->company_name ?: $stock->symbol,
                'asset_class' => 'equity',
                'current_price' => $price,
                'previous_price' => $price,
                'opening_price' => $price,
                'high' => $price,
                'low' => $price,
                'decimal_precision' => $precision,
                'minimum_tick' => $tick,
                'minimum_price' => $tick,
                'volatility_percent' => $volatility,
                'individual_bias' => 0,
                'is_active' => true,
                'last_moved_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('controlled_market_ticks')->insert([
                'controlled_market_instrument_id' => $id,
                'drive_mode' => 'seed',
                'open' => $price,
                'high' => $price,
                'low' => $price,
                'close' => $price,
                'change_amount' => 0,
                'change_percent' => 0,
                'ticked_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('trade_positions', function (Blueprint $table) {
            $table->dropIndex(['marketplace']);
            $table->dropColumn('marketplace');
        });

        Schema::table('stock_transactions', function (Blueprint $table) {
            $table->dropIndex(['marketplace']);
            $table->dropColumn('marketplace');
        });

        Schema::dropIfExists('controlled_market_ticks');
        Schema::dropIfExists('controlled_market_instruments');
        Schema::dropIfExists('market_environments');
    }
};
