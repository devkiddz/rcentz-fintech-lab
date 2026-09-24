<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // M1 is intentionally a compatibility bridge. The existing reverse
        // market_instruments.stock_id / forex_pair_id columns remain until the
        // runtime has fully moved to parent-first authority.
        if (! Schema::hasColumn('stocks', 'market_instrument_id')) {
            Schema::table('stocks', function (Blueprint $table) {
                $table->unsignedBigInteger('market_instrument_id')->nullable()->after('id');
                $table->unique('market_instrument_id', 'stocks_market_instrument_unique');
            });
        }

        if (! Schema::hasColumn('forex_pairs', 'market_instrument_id')) {
            Schema::table('forex_pairs', function (Blueprint $table) {
                $table->unsignedBigInteger('market_instrument_id')->nullable()->after('id');
                $table->unique('market_instrument_id', 'forex_pairs_market_instrument_unique');
            });
        }

        if (! Schema::hasColumn('controlled_market_instruments', 'market_instrument_id')) {
            Schema::table('controlled_market_instruments', function (Blueprint $table) {
                $table->unsignedBigInteger('market_instrument_id')->nullable()->after('id');
                $table->unique('market_instrument_id', 'controlled_market_instruments_market_unique');
            });
        }

        // Existing rows receive the canonical parent ID. This is the one-time
        // data bridge required to turn MarketInstrument into the parent.
        if (DB::getDriverName() === 'sqlite') {
            foreach (DB::table('stocks')->whereNull('market_instrument_id')->get() as $row) {
                $parentId = DB::table('market_instruments')->where('stock_id', $row->id)->value('id');
                if ($parentId) DB::table('stocks')->where('id', $row->id)->update(['market_instrument_id' => $parentId]);
            }
            foreach (DB::table('forex_pairs')->whereNull('market_instrument_id')->get() as $row) {
                $parentId = DB::table('market_instruments')->where('forex_pair_id', $row->id)->value('id');
                if ($parentId) DB::table('forex_pairs')->where('id', $row->id)->update(['market_instrument_id' => $parentId]);
            }
            foreach (DB::table('controlled_market_instruments')->whereNull('market_instrument_id')->get() as $row) {
                $parentId = DB::table('market_instruments')->where('stock_id', $row->stock_id)->value('id');
                if ($parentId) DB::table('controlled_market_instruments')->where('id', $row->id)->update(['market_instrument_id' => $parentId]);
            }
        } else {
            DB::statement("\n                UPDATE stocks s\n                INNER JOIN market_instruments mi ON mi.stock_id = s.id\n                SET s.market_instrument_id = mi.id\n                WHERE s.market_instrument_id IS NULL\n            ");

            DB::statement("\n                UPDATE forex_pairs fp\n                INNER JOIN market_instruments mi ON mi.forex_pair_id = fp.id\n                SET fp.market_instrument_id = mi.id\n                WHERE fp.market_instrument_id IS NULL\n            ");

            DB::statement("\n                UPDATE controlled_market_instruments cmi\n                INNER JOIN market_instruments mi ON mi.stock_id = cmi.stock_id\n                SET cmi.market_instrument_id = mi.id\n                WHERE cmi.market_instrument_id IS NULL\n            ");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('controlled_market_instruments', 'market_instrument_id')) {
            Schema::table('controlled_market_instruments', function (Blueprint $table) {
                $table->dropUnique('controlled_market_instruments_market_unique');
                $table->dropColumn('market_instrument_id');
            });
        }

        if (Schema::hasColumn('forex_pairs', 'market_instrument_id')) {
            Schema::table('forex_pairs', function (Blueprint $table) {
                $table->dropUnique('forex_pairs_market_instrument_unique');
                $table->dropColumn('market_instrument_id');
            });
        }

        if (Schema::hasColumn('stocks', 'market_instrument_id')) {
            Schema::table('stocks', function (Blueprint $table) {
                $table->dropUnique('stocks_market_instrument_unique');
                $table->dropColumn('market_instrument_id');
            });
        }
    }
};
