<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('private_investment_holdings', 'locked_until')) {
            Schema::table('private_investment_holdings', function (Blueprint $table) {
                $table->timestamp('locked_until')->nullable()->after('started_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('private_investment_holdings', 'locked_until')) {
            Schema::table('private_investment_holdings', function (Blueprint $table) {
                $table->dropColumn('locked_until');
            });
        }
    }
};
