<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vip_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 15, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('billing_interval', 32)->default('monthly');
            $table->unsignedInteger('duration_days')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('vip_entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vip_plan_id')->constrained('vip_plans')->cascadeOnDelete();
            $table->string('key', 120);
            $table->string('label');
            $table->text('description')->nullable();
            $table->json('value')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['vip_plan_id', 'key']);
            $table->index(['vip_plan_id', 'enabled']);
        });

        Schema::create('vip_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vip_plan_id')->constrained('vip_plans')->restrictOnDelete();
            $table->string('status', 32)->default('pending');
            $table->decimal('price_paid', 15, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('source', 64)->nullable();
            $table->string('reference', 120)->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->foreignId('activated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['vip_plan_id', 'status']);
            $table->index('ends_at');
            $table->index('reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vip_memberships');
        Schema::dropIfExists('vip_entitlements');
        Schema::dropIfExists('vip_plans');
    }
};
