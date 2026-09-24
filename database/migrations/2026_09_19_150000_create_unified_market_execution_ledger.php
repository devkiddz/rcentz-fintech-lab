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
        foreach (['users', 'market_instruments', 'wallet_transactions', 'stock_transactions', 'trade_positions', 'trade_position_events'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new \RuntimeException("Required table {$table} is unavailable.");
            }
        }

        if (! Schema::hasColumn('stock_transactions', 'market_instrument_id')) {
            throw new \RuntimeException('E2 must be migrated before the unified execution ledger.');
        }

        if (! Schema::hasTable('market_execution_transactions')) {
            Schema::create('market_execution_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('market_instrument_id')->constrained('market_instruments')->restrictOnDelete();
                $table->foreignId('wallet_transaction_id')->nullable()->constrained('wallet_transactions')->nullOnDelete();
                $table->foreignId('trade_position_id')->nullable()->constrained('trade_positions')->nullOnDelete();
                $table->string('native_type', 50)->nullable();
                $table->unsignedBigInteger('native_id')->nullable();
                $table->string('side', 12)->index();
                $table->string('execution_source', 60)->nullable()->index();
                $table->string('marketplace', 20)->default('live')->index();
                $table->decimal('quantity', 24, 8);
                $table->decimal('price', 24, 8);
                $table->decimal('gross_value', 24, 8);
                $table->decimal('fee', 20, 8)->default(0);
                $table->decimal('realized_profit_loss', 24, 8)->nullable();
                $table->string('status', 30)->default('completed')->index();
                $table->timestamp('executed_at')->nullable()->index();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->unique(['native_type', 'native_id'], 'market_execution_native_unique');
                $table->index(['user_id', 'marketplace', 'executed_at'], 'market_execution_user_market_time_idx');
                $table->index(['market_instrument_id', 'marketplace', 'executed_at'], 'market_execution_instrument_market_time_idx');
            });
        }

        if (! Schema::hasColumn('trade_positions', 'entry_market_execution_transaction_id')) {
            Schema::table('trade_positions', function (Blueprint $table) {
                $table->unsignedBigInteger('entry_market_execution_transaction_id')->nullable()->after('entry_transaction_id');
            });
        }
        if (! Schema::hasColumn('trade_positions', 'last_exit_market_execution_transaction_id')) {
            Schema::table('trade_positions', function (Blueprint $table) {
                $table->unsignedBigInteger('last_exit_market_execution_transaction_id')->nullable()->after('last_exit_transaction_id');
            });
        }
        if (! Schema::hasColumn('trade_position_events', 'market_execution_transaction_id')) {
            Schema::table('trade_position_events', function (Blueprint $table) {
                $table->unsignedBigInteger('market_execution_transaction_id')->nullable()->after('stock_transaction_id');
            });
        }

        if (DB::getDriverName() !== 'sqlite') {
            foreach ([
                ['trade_positions', 'entry_market_execution_transaction_id', 'trade_positions_entry_market_execution_fk'],
                ['trade_positions', 'last_exit_market_execution_transaction_id', 'trade_positions_exit_market_execution_fk'],
                ['trade_position_events', 'market_execution_transaction_id', 'trade_position_events_market_execution_fk'],
            ] as [$table, $column, $constraint]) {
                if (! $this->hasForeign($table, $column, 'market_execution_transactions')) {
                    DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$constraint} FOREIGN KEY ({$column}) REFERENCES market_execution_transactions(id) ON DELETE SET NULL");
                }
            }
        }

        DB::table('stock_transactions')
            ->whereNotNull('market_instrument_id')
            ->orderBy('id')
            ->get()
            ->each(function ($tx) {
                DB::table('market_execution_transactions')->updateOrInsert(
                    ['native_type' => 'stock_transaction', 'native_id' => $tx->id],
                    [
                        'user_id' => $tx->user_id,
                        'market_instrument_id' => $tx->market_instrument_id,
                        'wallet_transaction_id' => $tx->wallet_transaction_id,
                        'trade_position_id' => $tx->trade_position_id,
                        'side' => $tx->type,
                        'execution_source' => $tx->execution_source,
                        'marketplace' => $tx->marketplace ?: 'live',
                        'quantity' => $tx->quantity,
                        'price' => $tx->price_per_share,
                        'gross_value' => $tx->total_amount,
                        'fee' => $tx->fee ?? 0,
                        'realized_profit_loss' => null,
                        'status' => $tx->status,
                        'executed_at' => $tx->executed_at,
                        'metadata' => json_encode([
                            'stock_id' => $tx->stock_id,
                            'copy_strategy_id' => $tx->copy_strategy_id,
                            'compatibility_ledger' => 'stock_transactions',
                        ]),
                        'created_at' => $tx->created_at ?? now(),
                        'updated_at' => now(),
                    ]
                );
            });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("UPDATE trade_positions p JOIN market_execution_transactions m ON m.native_type = 'stock_transaction' AND m.native_id = p.entry_transaction_id SET p.entry_market_execution_transaction_id = m.id WHERE p.entry_transaction_id IS NOT NULL AND p.entry_market_execution_transaction_id IS NULL");
            DB::statement("UPDATE trade_positions p JOIN market_execution_transactions m ON m.native_type = 'stock_transaction' AND m.native_id = p.last_exit_transaction_id SET p.last_exit_market_execution_transaction_id = m.id WHERE p.last_exit_transaction_id IS NOT NULL AND p.last_exit_market_execution_transaction_id IS NULL");
            DB::statement("UPDATE trade_position_events e JOIN market_execution_transactions m ON m.native_type = 'stock_transaction' AND m.native_id = e.stock_transaction_id SET e.market_execution_transaction_id = m.id WHERE e.stock_transaction_id IS NOT NULL AND e.market_execution_transaction_id IS NULL");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('market_execution_transactions')) {
            $nonStock = DB::table('market_execution_transactions')
                ->where(function ($q) {
                    $q->whereNull('native_type')->orWhere('native_type', '!=', 'stock_transaction');
                })
                ->count();

            if ($nonStock > 0) {
                throw new \RuntimeException('Cannot roll back the unified execution ledger after non-Stock executions exist.');
            }
        }

        foreach ([
            ['trade_position_events', 'market_execution_transaction_id', 'trade_position_events_market_execution_fk'],
            ['trade_positions', 'last_exit_market_execution_transaction_id', 'trade_positions_exit_market_execution_fk'],
            ['trade_positions', 'entry_market_execution_transaction_id', 'trade_positions_entry_market_execution_fk'],
        ] as [$table, $column, $constraint]) {
            if (Schema::hasColumn($table, $column)) {
                if (DB::getDriverName() !== 'sqlite'
                    && $this->hasForeign($table, $column, 'market_execution_transactions')) {
                    DB::statement("ALTER TABLE {$table} DROP FOREIGN KEY {$constraint}");
                }
                Schema::table($table, function (Blueprint $blueprint) use ($column) {
                    $blueprint->dropColumn($column);
                });
            }
        }

        Schema::dropIfExists('market_execution_transactions');
    }
};
