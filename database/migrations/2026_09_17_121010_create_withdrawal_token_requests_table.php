<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawal_token_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 18, 2);
            $table->text('note')->nullable();
            $table->string('status', 30)->default('pending');
            $table->string('token_hash')->nullable();
            $table->string('token_last_four', 4)->nullable();
            $table->timestamp('token_generated_at')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamp('token_verified_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->foreignId('generated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('account_alert_id')->nullable()->constrained('account_alerts')->nullOnDelete();
            $table->foreignId('wallet_transaction_id')->nullable()->constrained('wallet_transactions')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'status'], 'wdr_token_user_status_idx');
            $table->index(['status', 'created_at'], 'wdr_token_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawal_token_requests');
    }
};
