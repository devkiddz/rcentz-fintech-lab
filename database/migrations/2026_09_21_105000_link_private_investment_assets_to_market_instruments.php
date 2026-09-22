<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('private_investment_assets', 'market_instrument_id')) {
            Schema::table('private_investment_assets', function (Blueprint $table) {
                $table->foreignId('market_instrument_id')
                    ->nullable()
                    ->after('instrument_id')
                    ->constrained('market_instruments')
                    ->nullOnDelete();

                $table->index(['market_instrument_id', 'status'], 'private_inv_assets_market_status_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('private_investment_assets', 'market_instrument_id')) {
            Schema::table('private_investment_assets', function (Blueprint $table) {
                $table->dropIndex('private_inv_assets_market_status_idx');
                $table->dropConstrainedForeignId('market_instrument_id');
            });
        }
    }
};
