<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('basket_market_references', function(Blueprint $t) {
            $t->unsignedBigInteger('instrument_id')->primary();$t->string('symbol',64);$t->string('asset_class',32);
            $t->string('base_asset',32);$t->string('quote_asset',32);$t->decimal('price',30,12);
            $t->dateTime('observed_at');$t->dateTime('received_at');$t->string('source',64);
            $t->decimal('momentum_percent',16,8)->default(0);$t->decimal('volatility_percent',16,8)->default(0);
            $t->unsignedInteger('samples')->default(0);$t->unsignedInteger('window_seconds')->default(0);
        });
        Schema::create('basket_reference_observations',function(Blueprint $t) {
            $t->id();$t->unsignedBigInteger('instrument_id');$t->dateTime('observed_at');
            $t->decimal('price',30,12);$t->string('source',64);
            $t->unique(['instrument_id','observed_at']);
        });
        Schema::create('basket_presentation',function(Blueprint $t) {$t->unsignedInteger('id')->primary();$t->dateTime('activated_at');});
        \Illuminate\Support\Facades\DB::table('basket_presentation')->insert(['id'=>1,'activated_at'=>now()->utc()->format('Y-m-d H:i:s')]);
    }
    public function down(): void {Schema::dropIfExists('basket_presentation');Schema::dropIfExists('basket_reference_observations');Schema::dropIfExists('basket_market_references');}
};
