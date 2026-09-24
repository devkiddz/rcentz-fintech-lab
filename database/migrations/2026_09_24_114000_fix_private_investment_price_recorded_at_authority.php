<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // recorded_at is immutable business/history time.
        // It must not change merely because OHLC/source metadata is updated.
        DB::statement(
            'ALTER TABLE private_investment_prices
             MODIFY recorded_at DATETIME NOT NULL'
        );
    }

    public function down(): void
    {
        DB::statement(
            'ALTER TABLE private_investment_prices
             MODIFY recorded_at TIMESTAMP NOT NULL
             DEFAULT CURRENT_TIMESTAMP
             ON UPDATE CURRENT_TIMESTAMP'
        );
    }
};
