<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('investment_basket_bindings', function (Blueprint $t) {
            $t->unsignedBigInteger('instrument_id')->primary();
            $t->foreign('instrument_id')->references('id')->on('private_investment_instruments')->cascadeOnDelete();
            $t->string('marketplace', 20)->default('controlled');
            $t->boolean('enabled')->default(true);
            $t->dateTime('last_synced_at')->nullable();
            $t->string('last_status', 40)->default('waiting');
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('investment_basket_bindings'); }
};
