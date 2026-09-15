<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('copy_relationships', function (Blueprint $table) {
            $table->unsignedInteger('duration_minutes')->nullable()->after('copy_ratio_percent');
            $table->timestamp('ends_at')->nullable()->after('started_at');
            $table->timestamp('completed_at')->nullable()->after('ends_at');
            $table->index(['status', 'ends_at'], 'copy_relationship_status_ends_idx');
        });
    }

    public function down(): void
    {
        Schema::table('copy_relationships', function (Blueprint $table) {
            $table->dropIndex('copy_relationship_status_ends_idx');
            $table->dropColumn(['duration_minutes', 'ends_at', 'completed_at']);
        });
    }
};
