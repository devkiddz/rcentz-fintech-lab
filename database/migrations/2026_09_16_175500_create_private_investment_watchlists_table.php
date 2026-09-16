<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('private_investment_watchlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('instrument_id')->constrained('private_investment_instruments')->cascadeOnDelete();
            $table->decimal('target_price', 20, 6)->nullable();
            $table->string('priority', 20)->default('normal');
            $table->text('note')->nullable();
            $table->timestamp('last_reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id','instrument_id'],'private_inv_watchlist_unique');
            $table->index(['user_id','priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('private_investment_watchlists');
    }
};
