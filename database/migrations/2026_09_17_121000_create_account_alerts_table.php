<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 40)->default('direct');
            $table->string('priority', 20)->default('normal');
            $table->string('title', 160);
            $table->text('message');
            $table->string('action_url')->nullable();
            $table->string('action_label', 80)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'dismissed_at'], 'acct_alert_user_active_idx');
            $table->index(['user_id', 'expires_at'], 'acct_alert_user_exp_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_alerts');
    }
};
