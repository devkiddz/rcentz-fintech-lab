<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_products', function (Blueprint $table) {
            $table->string('manual_performance_label', 60)
                ->nullable()
                ->after('manual_return_percent');
        });

        Schema::table('copy_strategies', function (Blueprint $table) {
            $table->string('manual_performance_label', 60)
                ->nullable()
                ->after('manual_return_percent');
        });
    }

    public function down(): void
    {
        Schema::table('bot_products', function (Blueprint $table) {
            $table->dropColumn('manual_performance_label');
        });

        Schema::table('copy_strategies', function (Blueprint $table) {
            $table->dropColumn('manual_performance_label');
        });
    }
};
