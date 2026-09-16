<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('stocks', 'external_feed_enabled')) {
            Schema::table('stocks', function (Blueprint $table) {
                $table->boolean('external_feed_enabled')
                    ->default(true)
                    ->after('is_featured')
                    ->index();
            });
        }

        // Backfill only clearly synthetic/internal instruments: a controlled
        // instrument with no persisted external quote history.
        $controlledStockIds = DB::table('controlled_market_instruments')
            ->pluck('stock_id');

        foreach ($controlledStockIds as $stockId) {
            $stock = DB::table('stocks')->where('id', $stockId)->first();
            if (! $stock) {
                continue;
            }

            $hasExternalQuote = DB::table('stock_quotes')
                ->where('symbol', $stock->symbol)
                ->exists();

            if (! $hasExternalQuote) {
                DB::table('stocks')
                    ->where('id', $stockId)
                    ->update(['external_feed_enabled' => false]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('stocks', 'external_feed_enabled')) {
            Schema::table('stocks', function (Blueprint $table) {
                $table->dropColumn('external_feed_enabled');
            });
        }
    }
};
