<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('trading_bots', 'paper_intent')) {
            Schema::table('trading_bots', fn (Blueprint $table) => $table->string('paper_intent', 16)->nullable());
        }
    }
    public function down(): void
    {
        if (Schema::hasColumn('trading_bots', 'paper_intent')) {
            Schema::table('trading_bots', fn (Blueprint $table) => $table->dropColumn('paper_intent'));
        }
    }
};
