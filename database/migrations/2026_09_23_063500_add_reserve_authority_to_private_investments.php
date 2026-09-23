<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('private_investment_assets', function (Blueprint $table) {
            $table->string('valuation_mode', 20)
                ->default('manual')
                ->after('market_instrument_id');

            $table->boolean('is_reserve_backing')
                ->default(true)
                ->after('valuation_mode');

            $table->decimal('reserve_quantity', 28, 8)
                ->default(1)
                ->after('is_reserve_backing');

            $table->string('reserve_unit', 32)
                ->nullable()
                ->after('reserve_quantity');

            $table->decimal('acquisition_unit_price', 24, 8)
                ->nullable()
                ->after('reserve_unit');

            $table->decimal('current_unit_price', 24, 8)
                ->nullable()
                ->after('acquisition_unit_price');

            $table->timestamp('last_valued_at')
                ->nullable()
                ->after('current_unit_price');

            $table->index(
                ['instrument_id', 'is_reserve_backing', 'status'],
                'private_inv_reserve_active_idx'
            );

            $table->index(
                ['market_instrument_id', 'valuation_mode'],
                'private_inv_reserve_market_mode_idx'
            );
        });

        Schema::create('private_investment_reserve_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('instrument_id')
                ->constrained('private_investment_instruments')
                ->cascadeOnDelete();

            $table->foreignId('asset_id')
                ->nullable()
                ->constrained('private_investment_assets')
                ->nullOnDelete();

            $table->string('action', 50);
            $table->string('valuation_mode', 20)->nullable();

            $table->decimal('previous_quantity', 28, 8)->nullable();
            $table->decimal('new_quantity', 28, 8)->nullable();

            $table->decimal('previous_unit_price', 24, 8)->nullable();
            $table->decimal('new_unit_price', 24, 8)->nullable();

            $table->decimal('previous_valuation', 20, 2)->nullable();
            $table->decimal('new_valuation', 20, 2)->nullable();

            $table->decimal('market_price', 24, 8)->nullable();

            $table->text('reason');

            $table->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->json('metadata')->nullable();
            $table->timestamp('effective_at');
            $table->timestamps();

            $table->index(
                ['instrument_id', 'effective_at'],
                'private_inv_reserve_event_instrument_idx'
            );

            $table->index(
                ['asset_id', 'effective_at'],
                'private_inv_reserve_event_asset_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('private_investment_reserve_events');

        Schema::table('private_investment_assets', function (Blueprint $table) {
            $table->dropIndex('private_inv_reserve_active_idx');
            $table->dropIndex('private_inv_reserve_market_mode_idx');

            $table->dropColumn([
                'valuation_mode',
                'is_reserve_backing',
                'reserve_quantity',
                'reserve_unit',
                'acquisition_unit_price',
                'current_unit_price',
                'last_valued_at',
            ]);
        });
    }
};