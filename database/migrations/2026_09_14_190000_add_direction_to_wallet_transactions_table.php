<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('wallet_transactions', 'direction')) {
            Schema::table('wallet_transactions', function (Blueprint $table) {
                $table->enum('direction', ['credit', 'debit'])->nullable()->after('type');
                $table->index(['wallet_id', 'direction', 'created_at']);
            });
        }

        DB::table('wallet_transactions')
            ->whereNull('direction')
            ->whereIn('type', ['deposit', 'refund', 'dividend'])
            ->update(['direction' => 'credit']);

        DB::table('wallet_transactions')
            ->whereNull('direction')
            ->where('type', 'withdrawal')
            ->update(['direction' => 'debit']);

        DB::table('wallet_transactions')
            ->whereNull('direction')
            ->where('type', 'investment')
            ->where('description', 'like', 'Sale of%')
            ->update(['direction' => 'credit']);

        DB::table('wallet_transactions')
            ->whereNull('direction')
            ->where('type', 'investment')
            ->update(['direction' => 'debit']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('wallet_transactions', 'direction')) {
            Schema::table('wallet_transactions', function (Blueprint $table) {
                $table->dropIndex(['wallet_id', 'direction', 'created_at']);
                $table->dropColumn('direction');
            });
        }
    }
};
