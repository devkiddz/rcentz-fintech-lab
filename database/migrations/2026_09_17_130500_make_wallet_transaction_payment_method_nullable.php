<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // payment_method_id describes an external deposit/withdrawal rail.
        // Internal investment, lifecycle and admin-ledger movements do not
        // necessarily use an external payment method, so the FK must be nullable.
        if (DB::getDriverName() === 'sqlite') {
            // SQLite uses Laravel's schema rebuild path instead of MySQL MODIFY.
            Schema::table('wallet_transactions', function (Blueprint $table) {
                $table->unsignedBigInteger('payment_method_id')->nullable()->change();
            });

            return;
        }

        DB::statement('ALTER TABLE wallet_transactions MODIFY payment_method_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        // Do not force NOT NULL on rollback because valid internal ledger rows
        // may now intentionally contain NULL payment_method_id values.
    }
};
