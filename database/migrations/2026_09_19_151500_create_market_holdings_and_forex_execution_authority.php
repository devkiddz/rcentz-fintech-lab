<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function hasIndex(string $table, string $index): bool
    {
        return (bool) DB::scalar(
            'SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
            [$table, $index]
        );
    }

    public function up(): void
    {
        foreach (['users', 'market_instruments', 'market_execution_transactions'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new \RuntimeException("Required table {$table} is unavailable.");
            }
        }

        if (! Schema::hasColumn('market_execution_transactions', 'idempotency_key')) {
            Schema::table('market_execution_transactions', function (Blueprint $table) {
                $table->string('idempotency_key', 120)->nullable()->after('native_id');
                $table->string('settlement_currency', 8)->nullable()->after('gross_value');
                $table->decimal('settlement_amount', 24, 8)->nullable()->after('settlement_currency');
            });
        }

        if (! $this->hasIndex('market_execution_transactions', 'market_execution_user_idempotency_unique')) {
            Schema::table('market_execution_transactions', function (Blueprint $table) {
                $table->unique(['user_id', 'idempotency_key'], 'market_execution_user_idempotency_unique');
            });
        }

        if (! Schema::hasTable('market_holdings')) {
            Schema::create('market_holdings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('market_instrument_id')->constrained('market_instruments')->restrictOnDelete();
                $table->string('marketplace', 20)->default('live');
                $table->string('settlement_currency', 8);
                $table->decimal('quantity', 28, 8);
                $table->decimal('average_entry_price', 28, 10);
                $table->decimal('total_invested', 28, 8);
                $table->decimal('current_value', 28, 8);
                $table->decimal('unrealized_gain_loss', 28, 8)->default(0);
                $table->decimal('unrealized_gain_loss_percentage', 16, 6)->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'market_instrument_id', 'marketplace'], 'market_holdings_user_instrument_market_unique');
                $table->index(['user_id', 'marketplace'], 'market_holdings_user_market_idx');
                $table->index(['market_instrument_id', 'marketplace'], 'market_holdings_instrument_market_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('market_holdings');

        if (Schema::hasTable('market_execution_transactions')) {
            if ($this->hasIndex('market_execution_transactions', 'market_execution_user_idempotency_unique')) {
                Schema::table('market_execution_transactions', function (Blueprint $table) {
                    $table->dropUnique('market_execution_user_idempotency_unique');
                });
            }

            Schema::table('market_execution_transactions', function (Blueprint $table) {
                foreach (['settlement_amount', 'settlement_currency', 'idempotency_key'] as $column) {
                    if (Schema::hasColumn('market_execution_transactions', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
