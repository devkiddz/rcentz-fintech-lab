<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_products', function (Blueprint $table) {
            $table->boolean('use_manual_performance')->default(false)->after('is_active');
            $table->decimal('manual_profit_loss', 15, 2)->nullable()->after('use_manual_performance');
            $table->decimal('manual_return_percent', 8, 2)->nullable()->after('manual_profit_loss');
            $table->string('manual_performance_note', 255)->nullable()->after('manual_return_percent');
            $table->foreignId('manual_performance_updated_by')->nullable()->after('manual_performance_note')->constrained('users')->nullOnDelete();
            $table->timestamp('manual_performance_updated_at')->nullable()->after('manual_performance_updated_by');
        });

        Schema::table('copy_strategies', function (Blueprint $table) {
            $table->boolean('use_manual_performance')->default(false)->after('is_active');
            $table->decimal('manual_profit_loss', 15, 2)->nullable()->after('use_manual_performance');
            $table->decimal('manual_return_percent', 8, 2)->nullable()->after('manual_profit_loss');
            $table->string('manual_performance_note', 255)->nullable()->after('manual_return_percent');
            $table->foreignId('manual_performance_updated_by')->nullable()->after('manual_performance_note')->constrained('users')->nullOnDelete();
            $table->timestamp('manual_performance_updated_at')->nullable()->after('manual_performance_updated_by');
        });
    }

    public function down(): void
    {
        Schema::table('bot_products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('manual_performance_updated_by');
            $table->dropColumn([
                'use_manual_performance',
                'manual_profit_loss',
                'manual_return_percent',
                'manual_performance_note',
                'manual_performance_updated_at',
            ]);
        });

        Schema::table('copy_strategies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('manual_performance_updated_by');
            $table->dropColumn([
                'use_manual_performance',
                'manual_profit_loss',
                'manual_return_percent',
                'manual_performance_note',
                'manual_performance_updated_at',
            ]);
        });
    }
};
