<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_holdings', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'stock_id']);
            $table->string('marketplace', 20)->default('live')->after('stock_id')->index();
            $table->unique(
                ['user_id', 'stock_id', 'marketplace'],
                'stock_holdings_user_stock_market_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('stock_holdings', function (Blueprint $table) {
            $table->dropUnique('stock_holdings_user_stock_market_unique');
            $table->dropIndex(['marketplace']);
            $table->dropColumn('marketplace');
            $table->unique(['user_id', 'stock_id']);
        });
    }
};
