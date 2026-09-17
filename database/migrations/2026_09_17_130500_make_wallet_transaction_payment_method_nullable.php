<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // payment_method_id describes an external deposit/withdrawal rail.
        // Internal investment, lifecycle and admin-ledger movements do not
        // necessarily use an external payment method, so the FK must be nullable.
        DB::statement('ALTER TABLE wallet_transactions MODIFY payment_method_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        // Do not force NOT NULL on rollback because valid internal ledger rows
        // may now intentionally contain NULL payment_method_id values.
    }
};
