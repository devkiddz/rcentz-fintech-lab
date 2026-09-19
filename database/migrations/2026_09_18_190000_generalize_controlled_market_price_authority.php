<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('controlled_market_instruments')) {
            return;
        }

        // M1 established market_instrument_id as the canonical parent link.
        // M2 allows non-Stock children (Forex now; Crypto later) to own an
        // Internal/Controlled price row without inventing a Stock surrogate.
        DB::statement('ALTER TABLE controlled_market_instruments MODIFY stock_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('controlled_market_instruments')) {
            return;
        }

        $nonStock = DB::table('controlled_market_instruments')
            ->whereNull('stock_id')
            ->count();

        if ($nonStock > 0) {
            throw new RuntimeException('Cannot restore controlled_market_instruments.stock_id NOT NULL while non-Stock controlled instruments exist.');
        }

        DB::statement('ALTER TABLE controlled_market_instruments MODIFY stock_id BIGINT UNSIGNED NOT NULL');
    }
};
