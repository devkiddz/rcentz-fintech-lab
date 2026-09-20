<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['users', 'wallets', 'wallet_transactions', 'financial_activities'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Required table {$table} is unavailable.");
            }
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE wallet_transactions MODIFY type ".
                "ENUM('deposit','withdrawal','investment','dividend','refund','membership','reward') NOT NULL"
            );
        }

        if (! Schema::hasTable('reward_campaigns')) {
            Schema::create('reward_campaigns', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug', 120)->unique();
                $table->string('campaign_type', 40)->index();
                $table->text('description')->nullable();
                $table->string('reward_kind', 30)->index();
                $table->decimal('cash_amount', 15, 2)->nullable();
                $table->string('currency', 3)->default('USD');
                $table->string('non_cash_label')->nullable();
                $table->string('eligibility_key', 120)->nullable();
                $table->string('status', 30)->default('active')->index();
                $table->timestamp('starts_at')->nullable()->index();
                $table->timestamp('ends_at')->nullable()->index();
                $table->unsignedInteger('max_grants')->nullable();
                $table->unsignedInteger('per_user_limit')->default(1);
                $table->boolean('is_visible')->default(true)->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('reward_grants')) {
            Schema::create('reward_grants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reward_campaign_id')->constrained('reward_campaigns')->restrictOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('wallet_transaction_id')->nullable()->constrained('wallet_transactions')->nullOnDelete();
                $table->foreignId('financial_activity_id')->nullable()->constrained('financial_activities')->nullOnDelete();
                $table->foreignId('granted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('source_type', 60)->index();
                $table->string('source_reference', 120);
                $table->string('reward_kind', 30)->index();
                $table->decimal('amount', 15, 2)->default(0);
                $table->string('currency', 3)->default('USD');
                $table->json('non_cash_payload')->nullable();
                $table->string('status', 30)->default('fulfilled')->index();
                $table->string('reference', 120)->unique();
                $table->string('idempotency_key', 120);
                $table->json('metadata')->nullable();
                $table->timestamp('granted_at')->index();
                $table->timestamps();

                $table->unique(['user_id', 'idempotency_key'], 'reward_grant_user_idempotency_unique');
                $table->index(['reward_campaign_id', 'status', 'granted_at'], 'reward_grant_campaign_status_time_idx');
                $table->index(['user_id', 'granted_at'], 'reward_grant_user_time_idx');
            });
        }

        if (! Schema::hasTable('reward_audit_logs')) {
            Schema::create('reward_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reward_campaign_id')->nullable()->constrained('reward_campaigns')->nullOnDelete();
                $table->foreignId('reward_grant_id')->nullable()->constrained('reward_grants')->nullOnDelete();
                $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 100)->index();
                $table->string('reference', 120)->nullable()->index();
                $table->text('reason')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('occurred_at')->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('reward_grants') && DB::table('reward_grants')->exists()) {
            throw new RuntimeException('Cannot roll back Reward authority while reward grant history exists.');
        }

        if (DB::table('wallet_transactions')->where('type', 'reward')->exists()) {
            throw new RuntimeException('Cannot remove the reward wallet transaction type while reward ledger rows exist.');
        }

        Schema::dropIfExists('reward_audit_logs');
        Schema::dropIfExists('reward_grants');
        Schema::dropIfExists('reward_campaigns');

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE wallet_transactions MODIFY type ".
                "ENUM('deposit','withdrawal','investment','dividend','refund','membership') NOT NULL"
            );
        }
    }
};
