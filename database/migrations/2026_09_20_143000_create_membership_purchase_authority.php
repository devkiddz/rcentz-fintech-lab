<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['users', 'wallets', 'wallet_transactions', 'membership_plans', 'memberships'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Required table {$table} is unavailable.");
            }
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE wallet_transactions MODIFY type ".
                "ENUM('deposit','withdrawal','investment','dividend','refund','membership') NOT NULL"
            );
        }

        if (! Schema::hasTable('membership_transactions')) {
            Schema::create('membership_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('membership_plan_id')->constrained('membership_plans')->restrictOnDelete();
                $table->foreignId('membership_id')->constrained('memberships')->cascadeOnDelete();
                $table->foreignId('wallet_transaction_id')->nullable()->constrained('wallet_transactions')->nullOnDelete();
                $table->string('type', 30)->default('purchase')->index();
                $table->decimal('amount', 15, 2)->default(0);
                $table->string('currency', 3)->default('USD');
                $table->string('status', 30)->default('completed')->index();
                $table->string('reference', 120)->unique();
                $table->string('idempotency_key', 120);
                $table->string('source', 60)->default('wallet_purchase');
                $table->json('metadata')->nullable();
                $table->timestamp('processed_at')->nullable()->index();
                $table->timestamps();

                $table->unique(['user_id', 'idempotency_key'], 'membership_tx_user_idempotency_unique');
                $table->index(['membership_plan_id', 'status', 'processed_at'], 'membership_tx_plan_status_time_idx');
                $table->index(['membership_id', 'status'], 'membership_tx_membership_status_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('membership_transactions') && DB::table('membership_transactions')->exists()) {
            throw new RuntimeException(
                'Cannot roll back Membership purchase authority while membership transaction history exists.'
            );
        }

        if (DB::table('wallet_transactions')->where('type', 'membership')->exists()) {
            throw new RuntimeException(
                'Cannot remove the membership wallet transaction type while membership ledger rows exist.'
            );
        }

        Schema::dropIfExists('membership_transactions');

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE wallet_transactions MODIFY type ".
                "ENUM('deposit','withdrawal','investment','dividend','refund') NOT NULL"
            );
        }
    }
};
