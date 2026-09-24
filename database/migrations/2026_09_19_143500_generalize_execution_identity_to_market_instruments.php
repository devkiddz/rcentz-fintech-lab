<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function hasForeign(string $table, string $column, string $referencedTable): bool
    {
        return (bool) DB::scalar(
            "SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME = ?",
            [$table, $column, $referencedTable]
        );
    }

    private function hasIndex(string $table, string $index): bool
    {
        return (bool) DB::scalar(
            "SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?",
            [$table, $index]
        );
    }

    private function assertLegacyRowsHaveParents(): void
    {
        foreach (['stock_holdings', 'stock_transactions', 'trade_positions'] as $table) {
            $missing = DB::table($table.' as legacy')
                ->leftJoin('stocks as stocks', 'stocks.id', '=', 'legacy.stock_id')
                ->where(function ($query) {
                    $query->whereNull('stocks.id')->orWhereNull('stocks.market_instrument_id');
                })
                ->count();

            if ($missing > 0) {
                throw new RuntimeException("{$table} contains {$missing} row(s) without canonical MarketInstrument parent identity.");
            }
        }
    }

    public function up(): void
    {
        foreach (['market_instruments', 'stocks', 'stock_holdings', 'stock_transactions', 'trade_positions'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Required table {$table} is unavailable.");
            }
        }

        $this->assertLegacyRowsHaveParents();

        if (! Schema::hasColumn('stock_holdings', 'market_instrument_id')) {
            Schema::table('stock_holdings', function (Blueprint $table) {
                $table->unsignedBigInteger('market_instrument_id')->nullable()->after('stock_id');
            });
        }

        if (! Schema::hasColumn('stock_transactions', 'market_instrument_id')) {
            Schema::table('stock_transactions', function (Blueprint $table) {
                $table->unsignedBigInteger('market_instrument_id')->nullable()->after('stock_id');
            });
        }

        if (! Schema::hasColumn('trade_positions', 'market_instrument_id')) {
            Schema::table('trade_positions', function (Blueprint $table) {
                $table->unsignedBigInteger('market_instrument_id')->nullable()->after('stock_id');
            });
        }

        if (DB::getDriverName() === 'sqlite') {
            foreach (['stock_holdings', 'stock_transactions', 'trade_positions'] as $table) {
                foreach (DB::table($table)->whereNull('market_instrument_id')->get() as $row) {
                    $parentId = DB::table('stocks')->where('id', $row->stock_id)->value('market_instrument_id');
                    if ($parentId) DB::table($table)->where('id', $row->id)->update(['market_instrument_id' => $parentId]);
                }
            }
        } else {
            DB::statement('UPDATE stock_holdings h JOIN stocks s ON s.id = h.stock_id SET h.market_instrument_id = s.market_instrument_id WHERE h.market_instrument_id IS NULL');
            DB::statement('UPDATE stock_transactions t JOIN stocks s ON s.id = t.stock_id SET t.market_instrument_id = s.market_instrument_id WHERE t.market_instrument_id IS NULL');
            DB::statement('UPDATE trade_positions p JOIN stocks s ON s.id = p.stock_id SET p.market_instrument_id = s.market_instrument_id WHERE p.market_instrument_id IS NULL');
        }

        if (DB::getDriverName() !== 'sqlite') {
            foreach ([
                ['stock_holdings', 'stock_holdings_market_instrument_idx'],
                ['stock_transactions', 'stock_transactions_market_instrument_idx'],
                ['trade_positions', 'trade_positions_market_instrument_idx'],
            ] as [$table, $index]) {
                if (! $this->hasIndex($table, $index)) {
                    DB::statement("ALTER TABLE {$table} ADD INDEX {$index} (market_instrument_id)");
                }
            }

            if (! $this->hasIndex('trade_positions', 'trade_positions_market_status_idx')) {
                DB::statement('ALTER TABLE trade_positions ADD INDEX trade_positions_market_status_idx (market_instrument_id, status)');
            }

            foreach ([
                ['stock_holdings', 'stock_holdings_market_instrument_fk'],
                ['stock_transactions', 'stock_transactions_market_instrument_fk'],
                ['trade_positions', 'trade_positions_market_instrument_fk'],
            ] as [$table, $constraint]) {
                if (! $this->hasForeign($table, 'market_instrument_id', 'market_instruments')) {
                    DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$constraint} FOREIGN KEY (market_instrument_id) REFERENCES market_instruments(id) ON DELETE RESTRICT");
                }
            }
        }

        // TradePosition is the cross-asset position authority. Stock remains a
        // compatibility child reference for existing equity execution only.
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('trade_positions', function (Blueprint $table) {
                $table->unsignedBigInteger('stock_id')->nullable()->change();
            });
        } else {
            DB::statement('ALTER TABLE trade_positions MODIFY stock_id BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('trade_positions', 'stock_id')) {
            $nonStock = DB::table('trade_positions')->whereNull('stock_id')->count();
            if ($nonStock > 0) {
                throw new RuntimeException('Cannot roll back E2 while non-Stock TradePosition rows exist.');
            }
            if (DB::getDriverName() === 'sqlite') {
                Schema::table('trade_positions', function (Blueprint $table) {
                    $table->unsignedBigInteger('stock_id')->nullable(false)->change();
                });
            } else {
                DB::statement('ALTER TABLE trade_positions MODIFY stock_id BIGINT UNSIGNED NOT NULL');
            }
        }

        foreach ([
            ['trade_positions', 'trade_positions_market_instrument_fk', 'trade_positions_market_status_idx', 'trade_positions_market_instrument_idx'],
            ['stock_transactions', 'stock_transactions_market_instrument_fk', null, 'stock_transactions_market_instrument_idx'],
            ['stock_holdings', 'stock_holdings_market_instrument_fk', null, 'stock_holdings_market_instrument_idx'],
        ] as [$table, $constraint, $compoundIndex, $index]) {
            if (Schema::hasColumn($table, 'market_instrument_id')) {
                if (DB::getDriverName() !== 'sqlite'
                    && $this->hasForeign($table, 'market_instrument_id', 'market_instruments')) {
                    DB::statement("ALTER TABLE {$table} DROP FOREIGN KEY {$constraint}");
                }
                if (DB::getDriverName() !== 'sqlite'
                    && $compoundIndex && $this->hasIndex($table, $compoundIndex)) {
                    DB::statement("ALTER TABLE {$table} DROP INDEX {$compoundIndex}");
                }
                if (DB::getDriverName() !== 'sqlite'
                    && $this->hasIndex($table, $index)) {
                    DB::statement("ALTER TABLE {$table} DROP INDEX {$index}");
                }
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropColumn('market_instrument_id');
                });
            }
        }
    }
};
