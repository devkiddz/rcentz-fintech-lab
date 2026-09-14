<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_price_history', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 10)->notNull();
            $table->decimal('open', 10, 2)->notNull();
            $table->decimal('high', 10, 2)->notNull();
            $table->decimal('low', 10, 2)->notNull();
            $table->decimal('close', 10, 2)->notNull();
            $table->bigInteger('volume')->nullable();
            $table->timestamp('timestamp')->notNull();
            $table->string('interval', 10)->default('1D'); // 1D, 1W, 1M, etc.
            $table->timestamps();
            
            $table->index(['symbol', 'timestamp']);
            $table->index(['symbol', 'interval']);
            $table->index(['timestamp']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_price_history');
    }
};
