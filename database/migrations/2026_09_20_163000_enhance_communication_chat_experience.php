<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['communication_messages', 'communication_participants', 'communication_attachments', 'users'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Required table {$table} is unavailable.");
            }
        }

        Schema::table('communication_messages', function (Blueprint $table) {
            if (! Schema::hasColumn('communication_messages', 'reply_to_message_id')) {
                $table->unsignedBigInteger('reply_to_message_id')->nullable()->after('kind');
                $table->foreign('reply_to_message_id', 'comm_msg_reply_fk')
                    ->references('id')->on('communication_messages')->nullOnDelete();
                $table->index('reply_to_message_id', 'comm_msg_reply_idx');
            }

            if (! Schema::hasColumn('communication_messages', 'deleted_for_everyone_at')) {
                $table->timestamp('deleted_for_everyone_at')->nullable()->after('edited_at')->index();
            }

            if (! Schema::hasColumn('communication_messages', 'deleted_by_user_id')) {
                $table->unsignedBigInteger('deleted_by_user_id')->nullable()->after('deleted_for_everyone_at');
                $table->foreign('deleted_by_user_id', 'comm_msg_deleted_by_fk')
                    ->references('id')->on('users')->nullOnDelete();
            }
        });

        Schema::table('communication_participants', function (Blueprint $table) {
            if (! Schema::hasColumn('communication_participants', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('last_read_at')->index();
            }

            if (! Schema::hasColumn('communication_participants', 'conversation_hidden_at')) {
                $table->timestamp('conversation_hidden_at')->nullable()->after('archived_at')->index();
            }
        });

        Schema::table('communication_attachments', function (Blueprint $table) {
            if (! Schema::hasColumn('communication_attachments', 'revoked_at')) {
                $table->timestamp('revoked_at')->nullable()->after('checksum')->index();
            }

            if (! Schema::hasColumn('communication_attachments', 'revoked_by_user_id')) {
                $table->unsignedBigInteger('revoked_by_user_id')->nullable()->after('revoked_at');
                $table->foreign('revoked_by_user_id', 'comm_attachment_revoked_by_fk')
                    ->references('id')->on('users')->nullOnDelete();
            }
        });

        if (! Schema::hasTable('communication_message_user_states')) {
            Schema::create('communication_message_user_states', function (Blueprint $table) {
                $table->id();
                $table->foreignId('message_id')->constrained('communication_messages')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamp('hidden_at')->nullable()->index();
                $table->timestamps();

                $table->unique(['message_id', 'user_id'], 'comm_msg_user_state_unique');
                $table->index(['user_id', 'hidden_at'], 'comm_msg_user_hidden_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('communication_message_user_states')
            && DB::table('communication_message_user_states')->exists()) {
            throw new RuntimeException('Cannot roll back chat visibility authority while per-user message state exists.');
        }

        Schema::dropIfExists('communication_message_user_states');

        Schema::table('communication_attachments', function (Blueprint $table) {
            if (Schema::hasColumn('communication_attachments', 'revoked_by_user_id')) {
                $table->dropForeign('comm_attachment_revoked_by_fk');
                $table->dropColumn('revoked_by_user_id');
            }
            if (Schema::hasColumn('communication_attachments', 'revoked_at')) {
                $table->dropColumn('revoked_at');
            }
        });

        Schema::table('communication_participants', function (Blueprint $table) {
            if (Schema::hasColumn('communication_participants', 'conversation_hidden_at')) {
                $table->dropColumn('conversation_hidden_at');
            }
            if (Schema::hasColumn('communication_participants', 'archived_at')) {
                $table->dropColumn('archived_at');
            }
        });

        Schema::table('communication_messages', function (Blueprint $table) {
            if (Schema::hasColumn('communication_messages', 'deleted_by_user_id')) {
                $table->dropForeign('comm_msg_deleted_by_fk');
                $table->dropColumn('deleted_by_user_id');
            }
            if (Schema::hasColumn('communication_messages', 'deleted_for_everyone_at')) {
                $table->dropColumn('deleted_for_everyone_at');
            }
            if (Schema::hasColumn('communication_messages', 'reply_to_message_id')) {
                $table->dropForeign('comm_msg_reply_fk');
                $table->dropIndex('comm_msg_reply_idx');
                $table->dropColumn('reply_to_message_id');
            }
        });
    }
};
