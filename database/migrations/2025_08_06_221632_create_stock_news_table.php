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
        Schema::create('stock_news', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 10)->notNull();
            $table->string('headline')->notNull();
            $table->text('summary')->nullable();
            $table->text('content')->nullable();
            $table->string('url')->nullable();
            $table->string('source', 100)->nullable();
            $table->string('author', 100)->nullable();
            $table->string('image_url')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('fetched_at')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->decimal('sentiment_score', 3, 2)->nullable(); // -1.00 to 1.00
            $table->timestamps();
            
            $table->index(['symbol', 'published_at']);
            $table->index(['symbol', 'is_featured']);
            $table->index(['published_at']);
            $table->index(['sentiment_score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_news');
    }
};
