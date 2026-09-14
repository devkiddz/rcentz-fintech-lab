<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('linked_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 50);
            $table->string('network', 50);
            $table->string('address', 255);
            $table->string('label', 80)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->enum('status', ['active', 'disconnected'])->default('active');
            $table->timestamps();

            $table->unique(['user_id', 'network', 'address']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('linked_wallets');
    }
};
