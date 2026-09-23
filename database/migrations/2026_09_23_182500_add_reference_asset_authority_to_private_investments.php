<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('private_investment_instruments', function (Blueprint $table) {
            $table->foreignId('reference_asset_id')
                ->nullable()
                ->after('category')
                ->constrained('private_investment_assets')
                ->nullOnDelete();
        });

        $instruments = DB::table('private_investment_instruments')
            ->orderBy('id')
            ->get(['id']);

        foreach ($instruments as $instrument) {
            $assets = DB::table('private_investment_assets')
                ->where('instrument_id', $instrument->id)
                ->where('status', 'active')
                ->where('is_reserve_backing', true)
                ->orderBy('id')
                ->get(['id', 'valuation_mode', 'market_instrument_id']);

            $referenceId = null;

            if ($assets->count() === 1) {
                $referenceId = $assets->first()->id;
            } else {
                $marketLinked = $assets->filter(
                    fn ($asset) => $asset->valuation_mode === 'market_linked'
                        && $asset->market_instrument_id !== null
                )->values();

                if ($marketLinked->count() === 1) {
                    $referenceId = $marketLinked->first()->id;
                }
            }

            if ($referenceId !== null) {
                DB::table('private_investment_instruments')
                    ->where('id', $instrument->id)
                    ->update(['reference_asset_id' => $referenceId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('private_investment_instruments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reference_asset_id');
        });
    }
};
