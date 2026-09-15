<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Keep the most recent relationship if old provider-based identity ever
        // created duplicates for the same follower + strategy.
        $duplicates = DB::table('copy_relationships')
            ->select('follower_id', 'copy_strategy_id', DB::raw('COUNT(*) as total'))
            ->groupBy('follower_id', 'copy_strategy_id')
            ->having('total', '>', 1)
            ->get();

        foreach ($duplicates as $duplicate) {
            $keepId = DB::table('copy_relationships')
                ->where('follower_id', $duplicate->follower_id)
                ->where('copy_strategy_id', $duplicate->copy_strategy_id)
                ->max('id');

            DB::table('copy_relationships')
                ->where('follower_id', $duplicate->follower_id)
                ->where('copy_strategy_id', $duplicate->copy_strategy_id)
                ->where('id', '!=', $keepId)
                ->delete();
        }

        Schema::table('copy_relationships', function (Blueprint $table) {
            $table->unique(
                ['follower_id', 'copy_strategy_id'],
                'copy_relationships_follower_strategy_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('copy_relationships', function (Blueprint $table) {
            $table->dropUnique('copy_relationships_follower_strategy_unique');
        });
    }
};
