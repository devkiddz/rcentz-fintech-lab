<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('signals', function (Blueprint $table) {
            $table->foreignId('market_instrument_id')
                ->nullable()
                ->after('stock_id')
                ->constrained('market_instruments')
                ->nullOnDelete();
            $table->index(['market_instrument_id', 'marketplace', 'status'], 'signals_market_instrument_status_idx');
        });

        Schema::table('signal_analysis_runs', function (Blueprint $table) {
            $table->foreignId('market_instrument_id')
                ->nullable()
                ->after('stock_id')
                ->constrained('market_instruments')
                ->nullOnDelete();
            $table->index(['market_instrument_id', 'marketplace', 'analyzed_at'], 'signal_analysis_market_instrument_time_idx');
        });

        // Existing Signal schema made stock_id mandatory. FX2 preserves the FK
        // but allows NULL for non-stock instruments. These are MySQL-specific
        // compatibility DDL/data bridges; SQLite test databases do not need them.
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE signals MODIFY stock_id BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE signal_analysis_runs MODIFY stock_id BIGINT UNSIGNED NULL');

            DB::statement("\n                UPDATE signals s\n                INNER JOIN market_instruments mi ON mi.stock_id = s.stock_id AND mi.asset_class = 'stock'\n                SET s.market_instrument_id = mi.id\n                WHERE s.market_instrument_id IS NULL\n            ");

            DB::statement("\n                UPDATE signal_analysis_runs r\n                INNER JOIN market_instruments mi ON mi.stock_id = r.stock_id AND mi.asset_class = 'stock'\n                SET r.market_instrument_id = mi.id\n                WHERE r.market_instrument_id IS NULL\n            ");
        }
    }

    public function down(): void
    {
        if (DB::table('signals')->whereNull('stock_id')->exists()
            || DB::table('signal_analysis_runs')->whereNull('stock_id')->exists()) {
            throw new RuntimeException('FX2 rollback is blocked while non-stock Signal records exist.');
        }

        Schema::table('signal_analysis_runs', function (Blueprint $table) {
            $table->dropForeign(['market_instrument_id']);
            $table->dropIndex('signal_analysis_market_instrument_time_idx');
            $table->dropColumn('market_instrument_id');
        });

        Schema::table('signals', function (Blueprint $table) {
            $table->dropForeign(['market_instrument_id']);
            $table->dropIndex('signals_market_instrument_status_idx');
            $table->dropColumn('market_instrument_id');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE signals MODIFY stock_id BIGINT UNSIGNED NOT NULL');
            DB::statement('ALTER TABLE signal_analysis_runs MODIFY stock_id BIGINT UNSIGNED NOT NULL');
        }
    }
};
