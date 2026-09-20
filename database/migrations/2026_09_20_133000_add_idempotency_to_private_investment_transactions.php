<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('private_investment_transactions', function (Blueprint $table) {
            $table->string('idempotency_key', 120)->nullable()->after('reference');
            $table->unique(
                ['user_id', 'idempotency_key'],
                'pinv_transactions_user_idempotency_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('private_investment_transactions', function (Blueprint $table) {
            $table->dropUnique('pinv_transactions_user_idempotency_unique');
            $table->dropColumn('idempotency_key');
        });
    }
};
