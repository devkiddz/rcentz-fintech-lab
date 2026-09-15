<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // V4.3 introduced "completed" as a lifecycle state, but the original
        // MySQL enum only allowed active / paused / stopped.
        DB::statement("
            ALTER TABLE copy_relationships
            MODIFY status ENUM('active','paused','stopped','completed')
            NOT NULL DEFAULT 'active'
        ");

        // A duration-based copy relationship is now an immutable CONTRACT.
        // A follower can therefore have multiple sequential contracts for the
        // same strategy. Application guards prevent simultaneous active ones.
        if (Schema::hasIndex('copy_relationships', 'copy_relationships_follower_strategy_unique')) {
            Schema::table('copy_relationships', function (Blueprint $table) {
                $table->dropUnique('copy_relationships_follower_strategy_unique');
            });
        }

        if (! Schema::hasIndex('copy_relationships', 'copy_relationships_follower_strategy_idx')) {
            Schema::table('copy_relationships', function (Blueprint $table) {
                $table->index(
                    ['follower_id', 'copy_strategy_id'],
                    'copy_relationships_follower_strategy_idx'
                );
            });
        }

        // Pre-V4.3 rows have no agreed duration. Do not invent an expiry date
        // for them. Close those legacy open relationships cleanly so a user can
        // start a new, explicit duration contract without rewriting history.
        DB::table('copy_relationships')
            ->where('status', 'active')
            ->whereNull('duration_minutes')
            ->whereNull('ends_at')
            ->update([
                'status' => 'stopped',
                'stopped_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('copy_relationships')
            ->where('status', 'completed')
            ->update([
                'status' => 'stopped',
                'completed_at' => null,
                'stopped_at' => DB::raw('COALESCE(stopped_at, NOW())'),
                'updated_at' => now(),
            ]);

        DB::statement("
            ALTER TABLE copy_relationships
            MODIFY status ENUM('active','paused','stopped')
            NOT NULL DEFAULT 'active'
        ");

        if (Schema::hasIndex('copy_relationships', 'copy_relationships_follower_strategy_idx')) {
            Schema::table('copy_relationships', function (Blueprint $table) {
                $table->dropIndex('copy_relationships_follower_strategy_idx');
            });
        }

        // The old unique constraint is intentionally not recreated here:
        // multiple historical contracts may now exist for one strategy.
    }
};
