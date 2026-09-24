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

    private function assertLegacyStockParents(): void
    {
        foreach (['bot_products', 'trading_bots'] as $table) {
            $missing = DB::table($table.' as b')
                ->leftJoin('stocks as s', 's.id', '=', 'b.stock_id')
                ->whereNotNull('b.stock_id')
                ->where(function ($q) {
                    $q->whereNull('s.id')->orWhereNull('s.market_instrument_id');
                })
                ->count();

            if ($missing > 0) {
                throw new RuntimeException("{$table} contains {$missing} legacy Stock row(s) without canonical MarketInstrument authority.");
            }
        }
    }

    public function up(): void
    {
        foreach ([
            'stocks',
            'market_instruments',
            'bot_products',
            'trading_bots',
            'trading_bot_executions',
            'broker_orders',
            'market_execution_transactions',
        ] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Required table {$table} is unavailable.");
            }
        }

        $this->assertLegacyStockParents();

        if (! Schema::hasColumn('bot_products', 'market_instrument_id')) {
            Schema::table('bot_products', function (Blueprint $table) {
                $table->unsignedBigInteger('market_instrument_id')->nullable()->after('stock_id');
            });
        }

        if (! Schema::hasColumn('trading_bots', 'market_instrument_id')) {
            Schema::table('trading_bots', function (Blueprint $table) {
                $table->unsignedBigInteger('market_instrument_id')->nullable()->after('stock_id');
            });
        }

        if (! Schema::hasColumn('trading_bot_executions', 'market_instrument_id')) {
            Schema::table('trading_bot_executions', function (Blueprint $table) {
                $table->unsignedBigInteger('market_instrument_id')->nullable()->after('bot_subscription_id');
            });
        }

        if (! Schema::hasColumn('trading_bot_executions', 'broker_order_id')) {
            Schema::table('trading_bot_executions', function (Blueprint $table) {
                $table->unsignedBigInteger('broker_order_id')->nullable()->after('market_instrument_id');
            });
        }

        if (! Schema::hasColumn('trading_bot_executions', 'market_execution_transaction_id')) {
            Schema::table('trading_bot_executions', function (Blueprint $table) {
                $table->unsignedBigInteger('market_execution_transaction_id')->nullable()->after('broker_order_id');
            });
        }

        if (DB::getDriverName() === 'sqlite') {
            foreach (DB::table('bot_products')->whereNull('market_instrument_id')->whereNotNull('stock_id')->get() as $row) {
                $parentId = DB::table('stocks')->where('id', $row->stock_id)->value('market_instrument_id');
                if ($parentId) DB::table('bot_products')->where('id', $row->id)->update(['market_instrument_id' => $parentId]);
            }
            foreach (DB::table('trading_bots')->whereNull('market_instrument_id')->whereNotNull('stock_id')->get() as $row) {
                $parentId = DB::table('stocks')->where('id', $row->stock_id)->value('market_instrument_id');
                if ($parentId) DB::table('trading_bots')->where('id', $row->id)->update(['market_instrument_id' => $parentId]);
            }
            foreach (DB::table('trading_bot_executions')->whereNull('market_instrument_id')->get() as $row) {
                $parentId = DB::table('trading_bots')->where('id', $row->trading_bot_id)->value('market_instrument_id');
                if ($parentId) DB::table('trading_bot_executions')->where('id', $row->id)->update(['market_instrument_id' => $parentId]);
            }
        } else {
            DB::statement('UPDATE bot_products b JOIN stocks s ON s.id = b.stock_id SET b.market_instrument_id = s.market_instrument_id WHERE b.market_instrument_id IS NULL AND b.stock_id IS NOT NULL');
            DB::statement('UPDATE trading_bots b JOIN stocks s ON s.id = b.stock_id SET b.market_instrument_id = s.market_instrument_id WHERE b.market_instrument_id IS NULL AND b.stock_id IS NOT NULL');
            DB::statement('UPDATE trading_bot_executions e JOIN trading_bots b ON b.id = e.trading_bot_id SET e.market_instrument_id = b.market_instrument_id WHERE e.market_instrument_id IS NULL AND b.market_instrument_id IS NOT NULL');
            DB::statement("UPDATE trading_bot_executions e JOIN market_execution_transactions m ON m.native_type = 'stock_transaction' AND m.native_id = e.stock_transaction_id SET e.market_execution_transaction_id = m.id WHERE e.stock_transaction_id IS NOT NULL AND e.market_execution_transaction_id IS NULL");
        }

        $missingProducts = DB::table('bot_products')->whereNull('market_instrument_id')->count();
        $missingBots = DB::table('trading_bots')->whereNull('market_instrument_id')->count();
        if ($missingProducts > 0 || $missingBots > 0) {
            throw new RuntimeException("Bot MarketInstrument backfill incomplete: {$missingProducts} product(s), {$missingBots} bot(s) remain unlinked.");
        }

        if (DB::getDriverName() !== 'sqlite') {
            foreach ([
                ['bot_products', 'bot_products_market_instrument_idx'],
                ['trading_bots', 'trading_bots_market_instrument_idx'],
                ['trading_bot_executions', 'trading_bot_executions_market_instrument_idx'],
                ['trading_bot_executions', 'trading_bot_executions_market_execution_idx'],
            ] as [$table, $index]) {
                if (! $this->hasIndex($table, $index)) {
                    $column = match ($index) {
                        'trading_bot_executions_market_execution_idx' => 'market_execution_transaction_id',
                        default => 'market_instrument_id',
                    };
                    DB::statement("ALTER TABLE {$table} ADD INDEX {$index} ({$column})");
                }
            }

            if (! $this->hasIndex('trading_bot_executions', 'trading_bot_executions_broker_order_unique')) {
                DB::statement('ALTER TABLE trading_bot_executions ADD UNIQUE INDEX trading_bot_executions_broker_order_unique (broker_order_id)');
            }

            foreach ([
                ['bot_products', 'market_instrument_id', 'market_instruments', 'bot_products_market_instrument_fk'],
                ['trading_bots', 'market_instrument_id', 'market_instruments', 'trading_bots_market_instrument_fk'],
                ['trading_bot_executions', 'market_instrument_id', 'market_instruments', 'trading_bot_executions_market_instrument_fk'],
                ['trading_bot_executions', 'broker_order_id', 'broker_orders', 'trading_bot_executions_broker_order_fk'],
                ['trading_bot_executions', 'market_execution_transaction_id', 'market_execution_transactions', 'trading_bot_executions_market_execution_fk'],
            ] as [$table, $column, $referenced, $constraint]) {
                if (! $this->hasForeign($table, $column, $referenced)) {
                    $delete = in_array($column, ['broker_order_id', 'market_execution_transaction_id'], true)
                        ? 'SET NULL'
                        : 'RESTRICT';
                    DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$constraint} FOREIGN KEY ({$column}) REFERENCES {$referenced}(id) ON DELETE {$delete}");
                }
            }

            // Stock IDs remain as compatibility rails but can no longer be the parent authority.
            DB::statement('ALTER TABLE bot_products MODIFY stock_id BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE trading_bots MODIFY stock_id BIGINT UNSIGNED NULL');

            // Cross-asset execution history requires FX/Crypto-safe precision.
            DB::statement('ALTER TABLE bot_products MODIFY default_trigger_price DECIMAL(24,8) NULL');
            DB::statement('ALTER TABLE trading_bots MODIFY quantity_per_trade DECIMAL(24,8) NULL');
            DB::statement('ALTER TABLE trading_bots MODIFY trigger_price DECIMAL(24,8) NULL');
            DB::statement('ALTER TABLE trading_bot_executions MODIFY quantity DECIMAL(24,8) NOT NULL DEFAULT 0');
            DB::statement('ALTER TABLE trading_bot_executions MODIFY price DECIMAL(24,8) NOT NULL DEFAULT 0');
            DB::statement('ALTER TABLE trading_bot_executions MODIFY amount DECIMAL(24,8) NOT NULL DEFAULT 0');
        }
    }

    public function down(): void
    {
        $nonStockProducts = DB::table('bot_products as b')
            ->join('market_instruments as m', 'm.id', '=', 'b.market_instrument_id')
            ->where(function ($q) {
                $q->whereNull('b.stock_id')->orWhere('m.asset_class', '!=', 'stock');
            })->count();
        $nonStockBots = DB::table('trading_bots as b')
            ->join('market_instruments as m', 'm.id', '=', 'b.market_instrument_id')
            ->where(function ($q) {
                $q->whereNull('b.stock_id')->orWhere('m.asset_class', '!=', 'stock');
            })->count();
        $brokerExecutions = DB::table('trading_bot_executions')->whereNotNull('broker_order_id')->count();

        if ($nonStockProducts > 0 || $nonStockBots > 0 || $brokerExecutions > 0) {
            throw new RuntimeException('Cannot roll back E7 MS1 after multi-asset bot data or BrokerOrder-routed bot executions exist.');
        }

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE bot_products MODIFY default_trigger_price DECIMAL(15,2) NULL');
            DB::statement('ALTER TABLE trading_bots MODIFY quantity_per_trade DECIMAL(15,6) NULL');
            DB::statement('ALTER TABLE trading_bots MODIFY trigger_price DECIMAL(15,2) NULL');
            DB::statement('ALTER TABLE trading_bot_executions MODIFY quantity DECIMAL(15,6) NOT NULL DEFAULT 0');
            DB::statement('ALTER TABLE trading_bot_executions MODIFY price DECIMAL(15,2) NOT NULL DEFAULT 0');
            DB::statement('ALTER TABLE trading_bot_executions MODIFY amount DECIMAL(15,2) NOT NULL DEFAULT 0');
            DB::statement('ALTER TABLE bot_products MODIFY stock_id BIGINT UNSIGNED NOT NULL');
            DB::statement('ALTER TABLE trading_bots MODIFY stock_id BIGINT UNSIGNED NOT NULL');
        }

        foreach ([
            ['trading_bot_executions', 'market_execution_transaction_id', 'market_execution_transactions', 'trading_bot_executions_market_execution_fk', 'trading_bot_executions_market_execution_idx'],
            ['trading_bot_executions', 'broker_order_id', 'broker_orders', 'trading_bot_executions_broker_order_fk', 'trading_bot_executions_broker_order_unique'],
            ['trading_bot_executions', 'market_instrument_id', 'market_instruments', 'trading_bot_executions_market_instrument_fk', 'trading_bot_executions_market_instrument_idx'],
            ['trading_bots', 'market_instrument_id', 'market_instruments', 'trading_bots_market_instrument_fk', 'trading_bots_market_instrument_idx'],
            ['bot_products', 'market_instrument_id', 'market_instruments', 'bot_products_market_instrument_fk', 'bot_products_market_instrument_idx'],
        ] as [$table, $column, $referenced, $constraint, $index]) {
            if (! Schema::hasColumn($table, $column)) {
                continue;
            }
            if (DB::getDriverName() !== 'sqlite' && $this->hasForeign($table, $column, $referenced)) {
                DB::statement("ALTER TABLE {$table} DROP FOREIGN KEY {$constraint}");
            }
            if (DB::getDriverName() !== 'sqlite' && $this->hasIndex($table, $index)) {
                DB::statement("ALTER TABLE {$table} DROP INDEX {$index}");
            }
            Schema::table($table, function (Blueprint $blueprint) use ($column) {
                $blueprint->dropColumn($column);
            });
        }
    }
};
