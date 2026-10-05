<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shared_live_quotes', function (Blueprint $t) {
            $t->engine = 'InnoDB';
            $t->unsignedBigInteger('instrument_id')->primary();
            $t->string('symbol', 20);
            $t->decimal('price', 30, 12);
            $t->dateTime('quoted_at');
            $t->dateTime('received_at');
            $t->dateTime('lease_until');
            $t->string('health', 30)->default('fresh');
        });
        Schema::create('shared_live_bars', function (Blueprint $t) {
            $t->engine = 'InnoDB';
            $t->id();
            $t->unsignedBigInteger('instrument_id');
            $t->string('interval', 8);
            $t->dateTime('bar_at');
            foreach (['open','high','low','close'] as $field) $t->decimal($field, 30, 12);
            $t->unique(['instrument_id','interval','bar_at'], 'shared_live_bar_unique');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('shared_live_bars');
        Schema::dropIfExists('shared_live_quotes');
    }
};
