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
            "SELECT COUNT(*)
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME = ?",
            [$table, $column, $referencedTable]
        );
    }

    private function hasIndex(string $table, string $index): bool
    {
        return (bool) DB::scalar(
            "SELECT COUNT(*)
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND INDEX_NAME = ?",
            [$table, $index]
        );
    }

    public function up(): void
    {
        // This migration is deliberately recovery-safe because the first V5.0
        // attempt may already have created these tables before MySQL rejected
        // the stock_transactions FK name.
        if (! Schema::hasTable('trade_positions')) {
            Schema::create('trade_positions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('stock_id')->constrained()->cascadeOnDelete();
                $table->foreignId('entry_transaction_id')->nullable()->constrained('stock_transactions')->nullOnDelete();
                $table->foreignId('last_exit_transaction_id')->nullable()->constrained('stock_transactions')->nullOnDelete();
                $table->foreignId('source_position_id')->nullable()->constrained('trade_positions')->nullOnDelete();

                $table->string('context_type', 60)->index();
                $table->unsignedBigInteger('context_id')->nullable()->index();
                $table->string('direction', 12)->default('long')->index();

                $table->decimal('initial_quantity', 20, 8);
                $table->decimal('open_quantity', 20, 8);
                $table->decimal('entry_price', 20, 8);
                $table->decimal('average_exit_price', 20, 8)->nullable();

                $table->decimal('stop_loss_price', 20, 8)->nullable();
                $table->decimal('take_profit_price', 20, 8)->nullable();
                $table->decimal('stop_loss_percent', 10, 4)->nullable();
                $table->decimal('take_profit_percent', 10, 4)->nullable();

                $table->unsignedInteger('duration_minutes')->nullable();
                $table->timestamp('opened_at')->index();
                $table->timestamp('expires_at')->nullable()->index();
                $table->timestamp('closed_at')->nullable()->index();

                $table->string('status', 30)->default('open')->index();
                $table->string('exit_reason', 60)->nullable();
                $table->decimal('realized_profit_loss', 20, 8)->default(0);
                $table->decimal('realized_return_percent', 12, 6)->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['user_id','stock_id','status']);
                $table->index(['context_type','context_id','status']);
            });
        }

        if (! Schema::hasTable('trade_position_events')) {
            Schema::create('trade_position_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('trade_position_id')->constrained('trade_positions')->cascadeOnDelete();
                $table->foreignId('stock_transaction_id')->nullable()->constrained('stock_transactions')->nullOnDelete();
                $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('actor_type', 30)->default('system');
                $table->string('event_type', 60)->index();
                $table->decimal('quantity', 20, 8)->nullable();
                $table->decimal('price', 20, 8)->nullable();
                $table->decimal('profit_loss', 20, 8)->nullable();
                $table->text('note')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('stock_transactions', 'trade_position_id')) {
            Schema::table('stock_transactions', function (Blueprint $table) {
                $table->unsignedBigInteger('trade_position_id')->nullable()->after('initiated_by_user_id');
            });
        }

        if (DB::getDriverName() !== 'sqlite'
            && ! $this->hasIndex('stock_transactions', 'stock_transactions_trade_position_idx')) {
            DB::statement(
                'ALTER TABLE stock_transactions
                 ADD INDEX stock_transactions_trade_position_idx (trade_position_id)'
            );
        }

        if (DB::getDriverName() !== 'sqlite'
            && ! $this->hasForeign('stock_transactions', 'trade_position_id', 'trade_positions')) {
            DB::statement(
                'ALTER TABLE stock_transactions
                 ADD CONSTRAINT stock_transactions_trade_position_fk
                 FOREIGN KEY (trade_position_id) REFERENCES trade_positions(id)
                 ON DELETE SET NULL'
            );
        }

        if (! Schema::hasColumn('trading_bots', 'stop_loss_percent')) {
            Schema::table('trading_bots', function (Blueprint $table) {
                $table->decimal('stop_loss_percent', 10, 4)->nullable()->after('trigger_price');
            });
        }

        if (! Schema::hasColumn('trading_bots', 'take_profit_percent')) {
            Schema::table('trading_bots', function (Blueprint $table) {
                $table->decimal('take_profit_percent', 10, 4)->nullable()->after('stop_loss_percent');
            });
        }

        if (! Schema::hasColumn('trading_bots', 'position_duration_minutes')) {
            Schema::table('trading_bots', function (Blueprint $table) {
                $table->unsignedInteger('position_duration_minutes')->nullable()->after('take_profit_percent');
            });
        }

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("
                ALTER TABLE copy_relationships
                MODIFY status ENUM('active','paused','stopped','settling','settlement_failed','completed')
                NOT NULL DEFAULT 'active'
            ");
        }
    }

    public function down(): void
    {
        DB::table('copy_relationships')
            ->whereIn('status', ['settling','settlement_failed'])
            ->update(['status' => 'stopped']);

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("
                ALTER TABLE copy_relationships
                MODIFY status ENUM('active','paused','stopped','completed')
                NOT NULL DEFAULT 'active'
            ");
        }

        Schema::table('trading_bots', function (Blueprint $table) {
            foreach (['position_duration_minutes','take_profit_percent','stop_loss_percent'] as $column) {
                if (Schema::hasColumn('trading_bots', $column)) $table->dropColumn($column);
            }
        });

        if (Schema::hasColumn('stock_transactions', 'trade_position_id')) {
            if (DB::getDriverName() !== 'sqlite'
                && $this->hasForeign('stock_transactions', 'trade_position_id', 'trade_positions')) {
                DB::statement('ALTER TABLE stock_transactions DROP FOREIGN KEY stock_transactions_trade_position_fk');
            }
            if (DB::getDriverName() !== 'sqlite'
                && $this->hasIndex('stock_transactions', 'stock_transactions_trade_position_idx')) {
                DB::statement('ALTER TABLE stock_transactions DROP INDEX stock_transactions_trade_position_idx');
            }
            Schema::table('stock_transactions', function (Blueprint $table) {
                $table->dropColumn('trade_position_id');
            });
        }

        Schema::dropIfExists('trade_position_events');
        Schema::dropIfExists('trade_positions');
    }
};
