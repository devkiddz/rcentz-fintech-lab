<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('public_investment_base_assets')) {
            Schema::create('public_investment_base_assets', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('market_instrument_id');
                $table->unique(
                    'market_instrument_id',
                    'pub_inv_base_market_unique'
                );
                $table->foreign(
                    'market_instrument_id',
                    'pub_inv_base_market_fk'
                )
                    ->references('id')
                    ->on('market_instruments')
                    ->restrictOnDelete();

                $table->string('status', 20)->default('active');

                $table->unsignedBigInteger('created_by_user_id')->nullable();
                $table->foreign(
                    'created_by_user_id',
                    'pub_inv_base_creator_fk'
                )
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();

                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(
                    'status',
                    'pub_inv_base_status_idx'
                );
            });
        }

        if (! Schema::hasColumn(
            'private_investment_assets',
            'public_investment_base_asset_id'
        )) {
            Schema::table('private_investment_assets', function (Blueprint $table) {
                $table->unsignedBigInteger(
                    'public_investment_base_asset_id'
                )
                    ->nullable()
                    ->after('market_instrument_id');
            });
        }

        $schema = DB::getDatabaseName();

        $fkExists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $schema)
            ->where('TABLE_NAME', 'private_investment_assets')
            ->where('CONSTRAINT_NAME', 'priv_inv_asset_pub_base_fk')
            ->exists();

        if (! $fkExists) {
            Schema::table('private_investment_assets', function (Blueprint $table) {
                $table->foreign(
                    'public_investment_base_asset_id',
                    'priv_inv_asset_pub_base_fk'
                )
                    ->references('id')
                    ->on('public_investment_base_assets')
                    ->nullOnDelete();
            });
        }

        $indexExists = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $schema)
            ->where('TABLE_NAME', 'private_investment_assets')
            ->where('INDEX_NAME', 'priv_inv_pub_base_mode_idx')
            ->exists();

        if (! $indexExists) {
            Schema::table('private_investment_assets', function (Blueprint $table) {
                $table->index(
                    [
                        'public_investment_base_asset_id',
                        'valuation_mode',
                    ],
                    'priv_inv_pub_base_mode_idx'
                );
            });
        }

        $marketIds = DB::table('private_investment_assets')
            ->where('valuation_mode', 'market_linked')
            ->whereNotNull('market_instrument_id')
            ->distinct()
            ->orderBy('market_instrument_id')
            ->pluck('market_instrument_id');

        foreach ($marketIds as $marketId) {
            $baseAssetId = DB::table('public_investment_base_assets')
                ->where('market_instrument_id', $marketId)
                ->value('id');

            if (! $baseAssetId) {
                $baseAssetId = DB::table('public_investment_base_assets')
                    ->insertGetId([
                        'market_instrument_id' => $marketId,
                        'status' => 'active',
                        'created_by_user_id' => null,
                        'metadata' => json_encode([
                            'source' =>
                                'migration_existing_market_linked_reserve',
                        ]),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
            }

            DB::table('private_investment_assets')
                ->where('valuation_mode', 'market_linked')
                ->where('market_instrument_id', $marketId)
                ->whereNull('public_investment_base_asset_id')
                ->update([
                    'public_investment_base_asset_id' => $baseAssetId,
                ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn(
            'private_investment_assets',
            'public_investment_base_asset_id'
        )) {
            $schema = DB::getDatabaseName();

            $fkExists = DB::table('information_schema.TABLE_CONSTRAINTS')
                ->where('CONSTRAINT_SCHEMA', $schema)
                ->where('TABLE_NAME', 'private_investment_assets')
                ->where('CONSTRAINT_NAME', 'priv_inv_asset_pub_base_fk')
                ->exists();

            $indexExists = DB::table('information_schema.STATISTICS')
                ->where('TABLE_SCHEMA', $schema)
                ->where('TABLE_NAME', 'private_investment_assets')
                ->where('INDEX_NAME', 'priv_inv_pub_base_mode_idx')
                ->exists();

            Schema::table('private_investment_assets', function (Blueprint $table) use ($fkExists, $indexExists) {
                if ($fkExists) {
                    $table->dropForeign('priv_inv_asset_pub_base_fk');
                }

                if ($indexExists) {
                    $table->dropIndex('priv_inv_pub_base_mode_idx');
                }

                $table->dropColumn('public_investment_base_asset_id');
            });
        }

        Schema::dropIfExists('public_investment_base_assets');
    }
};
