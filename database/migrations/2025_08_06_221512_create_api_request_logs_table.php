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
        Schema::create('api_request_logs', function (Blueprint $table) {
            $table->id();
            $table->string('api_provider', 50)->notNull(); // finnhub, alpha_vantage
            $table->string('endpoint', 100)->notNull();
            $table->string('symbol', 10)->nullable();
            $table->integer('response_time_ms')->nullable();
            $table->integer('status_code')->nullable();
            $table->boolean('success')->default(true);
            $table->text('error_message')->nullable();
            $table->json('request_params')->nullable();
            $table->json('response_data')->nullable();
            $table->string('rate_limit_type', 50)->nullable(); // market_data, fundamental, general
            $table->timestamp('requested_at')->notNull();
            $table->timestamps();
            
            $table->index(['api_provider', 'requested_at']);
            $table->index(['api_provider', 'success']);
            $table->index(['symbol']);
            $table->index(['rate_limit_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_request_logs');
    }
};
