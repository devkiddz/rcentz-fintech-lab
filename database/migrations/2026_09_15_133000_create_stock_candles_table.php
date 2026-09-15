<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_candles', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 20);
            $table->string('interval', 10)->default('15m');
            $table->timestamp('started_at');
            $table->decimal('open', 18, 6);
            $table->decimal('high', 18, 6);
            $table->decimal('low', 18, 6);
            $table->decimal('close', 18, 6);
            $table->unsignedInteger('sample_count')->default(1);
            $table->timestamps();

            $table->unique(['symbol', 'interval', 'started_at'], 'stock_candles_symbol_interval_started_unique');
            $table->index(['symbol', 'interval', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_candles');
    }
};
