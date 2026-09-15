<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_transactions', function (Blueprint $table) {
            $table->foreignId('copy_strategy_id')
                ->nullable()
                ->after('stock_id')
                ->constrained('copy_strategies')
                ->nullOnDelete();

            $table->string('execution_source', 60)
                ->nullable()
                ->after('copy_strategy_id')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('stock_transactions', function (Blueprint $table) {
            $table->dropForeign(['copy_strategy_id']);
            $table->dropColumn(['copy_strategy_id', 'execution_source']);
        });
    }
};
