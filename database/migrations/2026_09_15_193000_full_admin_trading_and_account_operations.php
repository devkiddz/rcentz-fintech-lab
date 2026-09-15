<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_transactions', 'initiated_by_user_id')) {
                $table->foreignId('initiated_by_user_id')
                    ->nullable()
                    ->after('execution_source')
                    ->constrained('users')
                    ->nullOnDelete()
                    ->index();
            }
        });

        if (! Schema::hasTable('admin_account_operations')) {
            Schema::create('admin_account_operations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('admin_id')->constrained('users')->restrictOnDelete();
                $table->string('operation_type', 60)->index();
                $table->string('direction', 12)->nullable();
                $table->decimal('amount', 18, 2)->nullable();
                $table->timestamp('effective_at')->nullable()->index();
                $table->string('reference', 120)->unique();
                $table->string('label', 180);
                $table->text('reason');
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_account_operations');

        Schema::table('stock_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('stock_transactions', 'initiated_by_user_id')) {
                $table->dropForeign(['initiated_by_user_id']);
                $table->dropColumn('initiated_by_user_id');
            }
        });
    }
};
