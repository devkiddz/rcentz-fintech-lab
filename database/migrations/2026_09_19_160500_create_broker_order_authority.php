<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['users', 'market_instruments', 'market_execution_transactions'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new \RuntimeException("Required table {$table} is unavailable.");
            }
        }

        if (! Schema::hasTable('broker_orders')) {
            Schema::create('broker_orders', function (Blueprint $table) {
                $table->id();
                $table->uuid('public_id')->unique();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('market_instrument_id')->constrained('market_instruments')->restrictOnDelete();
                $table->foreignId('market_execution_transaction_id')->nullable()->constrained('market_execution_transactions')->nullOnDelete();
                $table->string('marketplace', 20)->default('live')->index();
                $table->string('side', 12)->index();
                $table->string('order_type', 20)->default('market')->index();
                $table->decimal('quantity', 30, 10);
                $table->string('quantity_mode', 30)->default('units');
                $table->decimal('filled_quantity', 30, 10)->default(0);
                $table->decimal('average_fill_price', 30, 12)->nullable();
                $table->decimal('gross_value', 30, 8)->nullable();
                $table->decimal('fee', 20, 8)->default(0);
                $table->string('settlement_currency', 12)->nullable();
                $table->decimal('settlement_amount', 30, 8)->nullable();
                $table->string('status', 30)->default('accepted')->index();
                $table->string('time_in_force', 12)->default('IOC');
                $table->string('idempotency_key', 120);
                $table->string('execution_source', 60)->default('broker_order');
                $table->json('risk_controls')->nullable();
                $table->string('failure_code', 120)->nullable();
                $table->text('failure_message')->nullable();
                $table->timestamp('accepted_at')->nullable()->index();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('filled_at')->nullable()->index();
                $table->timestamp('failed_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'idempotency_key'], 'broker_orders_user_idempotency_unique');
                $table->index(['user_id', 'status', 'created_at'], 'broker_orders_user_status_time_idx');
                $table->index(['market_instrument_id', 'status', 'created_at'], 'broker_orders_instrument_status_time_idx');
            });
        }

        if (! Schema::hasTable('broker_order_events')) {
            Schema::create('broker_order_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('broker_order_id')->constrained('broker_orders')->cascadeOnDelete();
                $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('actor_type', 30)->default('system');
                $table->string('event_type', 50)->index();
                $table->string('from_status', 30)->nullable();
                $table->string('to_status', 30)->nullable()->index();
                $table->text('note')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['broker_order_id', 'created_at'], 'broker_order_events_order_time_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('broker_order_events');
        Schema::dropIfExists('broker_orders');
    }
};
