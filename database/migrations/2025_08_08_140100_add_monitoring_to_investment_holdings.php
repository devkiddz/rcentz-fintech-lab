<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('investment_holdings', function (Blueprint $table) {
            $table->decimal('last_notified_change_percentage', 8, 4)->nullable()->after('unrealized_gain_loss_percentage');
            $table->decimal('nav_at_purchase', 10, 4)->nullable()->after('last_notified_change_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('investment_holdings', function (Blueprint $table) {
            $table->dropColumn(['last_notified_change_percentage', 'nav_at_purchase']);
        });
    }
};
