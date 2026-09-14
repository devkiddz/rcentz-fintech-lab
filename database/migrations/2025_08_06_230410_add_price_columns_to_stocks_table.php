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
        Schema::table('stocks', function (Blueprint $table) {
            $table->decimal('high', 10, 2)->nullable()->after('volume');
            $table->decimal('low', 10, 2)->nullable()->after('high');
            $table->decimal('open', 10, 2)->nullable()->after('low');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropColumn(['high', 'low', 'open']);
        });
    }
};
