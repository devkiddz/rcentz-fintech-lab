<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('private_market_references', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 32)->unique();
            $table->string('name');
            $table->string('category', 64);
            $table->string('location')->nullable();
            $table->string('reference_unit', 64)->default('unit');
            $table->string('currency', 3)->default('USD');
            $table->text('description')->nullable();
            $table->decimal('current_price', 24, 8);
            $table->decimal('previous_price', 24, 8)->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamp('last_valued_at')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'category'], 'private_market_reference_status_category_idx');
        });

        Schema::create('private_market_reference_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reference_id')->constrained('private_market_references')->cascadeOnDelete();
            $table->decimal('previous_price', 24, 8)->nullable();
            $table->decimal('price', 24, 8);
            $table->decimal('change_amount', 24, 8)->default(0);
            $table->decimal('change_percent', 18, 8)->default(0);
            $table->text('reason');
            $table->foreignId('valued_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['reference_id', 'recorded_at'], 'private_market_reference_price_timeline_idx');
        });

        Schema::table('private_investment_assets', function (Blueprint $table) {
            $table->foreignId('private_market_reference_id')
                ->nullable()
                ->after('market_instrument_id')
                ->constrained('private_market_references')
                ->nullOnDelete();

            $table->index(
                ['private_market_reference_id', 'valuation_mode'],
                'private_inv_reserve_private_mode_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('private_investment_assets', function (Blueprint $table) {
            $table->dropIndex('private_inv_reserve_private_mode_idx');
            $table->dropConstrainedForeignId('private_market_reference_id');
        });

        Schema::dropIfExists('private_market_reference_prices');
        Schema::dropIfExists('private_market_references');
    }
};
