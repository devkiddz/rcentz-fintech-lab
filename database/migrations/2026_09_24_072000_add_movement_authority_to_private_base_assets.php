<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('private_market_references', function (Blueprint $table) {
            $table->string('movement_mode', 16)
                ->default('manual')
                ->after('status');
            $table->string('movement_behavior', 16)
                ->default('smart')
                ->after('movement_mode');
            $table->decimal('movement_strength', 8, 4)
                ->default(1)
                ->after('movement_behavior');
            $table->decimal(
                'movement_volatility_percent',
                12,
                6
            )
                ->default(0.05)
                ->after('movement_strength');
            $table->unsignedSmallInteger('movement_tick_seconds')
                ->default(30)
                ->after('movement_volatility_percent');
            $table->decimal('movement_anchor_price', 24, 8)
                ->nullable()
                ->after('movement_tick_seconds');
            $table->timestamp('movement_last_moved_at')
                ->nullable()
                ->after('movement_anchor_price');

            $table->index(
                ['status', 'movement_mode'],
                'priv_ref_movement_idx'
            );
        });

        DB::table('private_market_references')->update([
            'movement_anchor_price' => DB::raw('current_price'),
            'movement_last_moved_at' => DB::raw('last_valued_at'),
        ]);
    }

    public function down(): void
    {
        Schema::table('private_market_references', function (Blueprint $table) {
            $table->dropIndex('priv_ref_movement_idx');
            $table->dropColumn([
                'movement_mode',
                'movement_behavior',
                'movement_strength',
                'movement_volatility_percent',
                'movement_tick_seconds',
                'movement_anchor_price',
                'movement_last_moved_at',
            ]);
        });
    }
};
