<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('investment_base_basket_drivers', function(Blueprint $t) {
            $t->unsignedBigInteger('reference_id')->primary();
            $t->foreign('reference_id')->references('id')->on('private_market_references')->restrictOnDelete();
            $t->unsignedBigInteger('market_instrument_id');
            $t->foreign('market_instrument_id')->references('id')->on('market_instruments')->restrictOnDelete();
            $t->decimal('base_anchor',24,8);
            $t->decimal('basket_anchor',24,8);
            $t->boolean('enabled')->default(true);
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('investment_base_basket_drivers'); }
};
