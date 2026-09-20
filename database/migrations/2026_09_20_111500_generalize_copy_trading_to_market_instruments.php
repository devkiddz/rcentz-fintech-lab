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

    public function up(): void
    {
        foreach (['copy_trade_executions','copy_relationships','market_instruments','market_execution_transactions','broker_orders','stock_transactions'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Required table {$table} is unavailable.");
            }
        }

        if (! Schema::hasColumn('copy_trade_executions', 'market_instrument_id')) {
            Schema::table('copy_trade_executions', function (Blueprint $table) {
                $table->unsignedBigInteger('market_instrument_id')->nullable()->after('copy_relationship_id');
            });
        }
        if (! Schema::hasColumn('copy_trade_executions', 'provider_market_execution_transaction_id')) {
            Schema::table('copy_trade_executions', function (Blueprint $table) {
                $table->unsignedBigInteger('provider_market_execution_transaction_id')->nullable()->after('provider_stock_transaction_id');
            });
        }
        if (! Schema::hasColumn('copy_trade_executions', 'provider_broker_order_id')) {
            Schema::table('copy_trade_executions', function (Blueprint $table) {
                $table->unsignedBigInteger('provider_broker_order_id')->nullable()->after('provider_market_execution_transaction_id');
            });
        }
        if (! Schema::hasColumn('copy_trade_executions', 'follower_market_execution_transaction_id')) {
            Schema::table('copy_trade_executions', function (Blueprint $table) {
                $table->unsignedBigInteger('follower_market_execution_transaction_id')->nullable()->after('follower_stock_transaction_id');
            });
        }
        if (! Schema::hasColumn('copy_trade_executions', 'follower_broker_order_id')) {
            Schema::table('copy_trade_executions', function (Blueprint $table) {
                $table->unsignedBigInteger('follower_broker_order_id')->nullable()->after('follower_market_execution_transaction_id');
            });
        }

        DB::statement('ALTER TABLE copy_trade_executions MODIFY provider_stock_transaction_id BIGINT UNSIGNED NULL');

        DB::statement("UPDATE copy_trade_executions c JOIN stock_transactions s ON s.id = c.provider_stock_transaction_id SET c.market_instrument_id = s.market_instrument_id WHERE c.market_instrument_id IS NULL AND c.provider_stock_transaction_id IS NOT NULL");
        DB::statement("UPDATE copy_trade_executions c JOIN market_execution_transactions m ON m.native_type = 'stock_transaction' AND m.native_id = c.provider_stock_transaction_id SET c.provider_market_execution_transaction_id = m.id WHERE c.provider_market_execution_transaction_id IS NULL AND c.provider_stock_transaction_id IS NOT NULL");
        DB::statement("UPDATE copy_trade_executions c JOIN market_execution_transactions m ON m.native_type = 'stock_transaction' AND m.native_id = c.follower_stock_transaction_id SET c.follower_market_execution_transaction_id = m.id WHERE c.follower_market_execution_transaction_id IS NULL AND c.follower_stock_transaction_id IS NOT NULL");
        DB::statement("UPDATE copy_trade_executions c JOIN broker_orders b ON b.market_execution_transaction_id = c.provider_market_execution_transaction_id SET c.provider_broker_order_id = b.id WHERE c.provider_broker_order_id IS NULL");
        DB::statement("UPDATE copy_trade_executions c JOIN broker_orders b ON b.market_execution_transaction_id = c.follower_market_execution_transaction_id SET c.follower_broker_order_id = b.id WHERE c.follower_broker_order_id IS NULL");

        foreach ([
            ['copy_trade_executions', 'market_instrument_id', 'copy_trade_exec_market_instrument_fk', 'market_instruments'],
            ['copy_trade_executions', 'provider_market_execution_transaction_id', 'copy_trade_exec_provider_market_fk', 'market_execution_transactions'],
            ['copy_trade_executions', 'provider_broker_order_id', 'copy_trade_exec_provider_broker_fk', 'broker_orders'],
            ['copy_trade_executions', 'follower_market_execution_transaction_id', 'copy_trade_exec_follower_market_fk', 'market_execution_transactions'],
            ['copy_trade_executions', 'follower_broker_order_id', 'copy_trade_exec_follower_broker_fk', 'broker_orders'],
        ] as [$table, $column, $constraint, $referenced]) {
            if (! $this->hasForeign($table, $column, $referenced)) {
                DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$constraint} FOREIGN KEY ({$column}) REFERENCES {$referenced}(id) ON DELETE SET NULL");
            }
        }

        foreach ([
            ['copy_trade_executions', 'copy_trade_exec_market_idx', 'market_instrument_id'],
            ['copy_trade_executions', 'copy_trade_exec_provider_market_idx', 'provider_market_execution_transaction_id'],
            ['copy_trade_executions', 'copy_trade_exec_follower_market_idx', 'follower_market_execution_transaction_id'],
            ['copy_trade_executions', 'copy_trade_exec_follower_broker_idx', 'follower_broker_order_id'],
        ] as [$table, $index, $column]) {
            if (! $this->hasIndex($table, $index)) {
                DB::statement("ALTER TABLE {$table} ADD INDEX {$index} ({$column})");
            }
        }

        DB::statement("ALTER TABLE copy_relationships MODIFY status ENUM('active','paused','stopped','completed','settling','settlement_failed') NOT NULL DEFAULT 'active'");
    }

    public function down(): void
    {
        $nonStock = DB::table('copy_trade_executions')->whereNull('provider_stock_transaction_id')->whereNotNull('provider_market_execution_transaction_id')->count();
        if ($nonStock > 0) {
            throw new RuntimeException('Cannot roll back MS2A after non-Stock copy execution history exists.');
        }

        DB::table('copy_relationships')->whereIn('status', ['settling','settlement_failed'])->update(['status' => 'stopped']);
        DB::statement("ALTER TABLE copy_relationships MODIFY status ENUM('active','paused','stopped','completed') NOT NULL DEFAULT 'active'");

        foreach ([
            ['follower_broker_order_id', 'copy_trade_exec_follower_broker_fk', 'copy_trade_exec_follower_broker_idx'],
            ['follower_market_execution_transaction_id', 'copy_trade_exec_follower_market_fk', 'copy_trade_exec_follower_market_idx'],
            ['provider_broker_order_id', 'copy_trade_exec_provider_broker_fk', null],
            ['provider_market_execution_transaction_id', 'copy_trade_exec_provider_market_fk', 'copy_trade_exec_provider_market_idx'],
            ['market_instrument_id', 'copy_trade_exec_market_instrument_fk', 'copy_trade_exec_market_idx'],
        ] as [$column, $constraint, $index]) {
            if (Schema::hasColumn('copy_trade_executions', $column)) {
                if ($this->hasForeign('copy_trade_executions', $column, match($column) {
                    'market_instrument_id' => 'market_instruments',
                    'provider_broker_order_id', 'follower_broker_order_id' => 'broker_orders',
                    default => 'market_execution_transactions',
                })) {
                    DB::statement("ALTER TABLE copy_trade_executions DROP FOREIGN KEY {$constraint}");
                }
                if ($index && $this->hasIndex('copy_trade_executions', $index)) {
                    DB::statement("ALTER TABLE copy_trade_executions DROP INDEX {$index}");
                }
                Schema::table('copy_trade_executions', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }

        $missingProvider = DB::table('copy_trade_executions')->whereNull('provider_stock_transaction_id')->count();
        if ($missingProvider > 0) {
            throw new RuntimeException('Cannot restore required provider_stock_transaction_id while null rows exist.');
        }
        DB::statement('ALTER TABLE copy_trade_executions MODIFY provider_stock_transaction_id BIGINT UNSIGNED NOT NULL');
    }
};
