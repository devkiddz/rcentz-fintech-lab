<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('copy_relationships', function (Blueprint $table) {
            // Legacy V1 identity: one relationship per follower + provider.
            // V3.2 now supports multiple independent strategies from one provider,
            // so this old unique key must be removed.
            $table->dropUnique('copy_relationships_follower_id_provider_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('copy_relationships', function (Blueprint $table) {
            $table->unique(
                ['follower_id', 'provider_id'],
                'copy_relationships_follower_id_provider_id_unique'
            );
        });
    }
};
