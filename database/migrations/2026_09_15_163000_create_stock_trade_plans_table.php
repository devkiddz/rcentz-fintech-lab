<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_trade_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_transaction_id')->constrained('stock_transactions')->cascadeOnDelete();
            $table->foreignId('executed_transaction_id')->nullable()->constrained('stock_transactions')->nullOnDelete();

            $table->string('source_type', 12); // buy | sell
            $table->string('planned_action', 20); // sell | buy_back
            $table->string('mode', 20)->default('reminder'); // reminder | automatic
            $table->unsignedInteger('duration_minutes');
            $table->decimal('quantity', 18, 6);
            $table->timestamp('due_at');
            $table->string('status', 20)->default('active'); // active | due | completed | cancelled | failed
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status', 'due_at']);
            $table->index(['status', 'due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_trade_plans');
    }
};
