<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['users'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Required table {$table} is unavailable.");
            }
        }

        if (! Schema::hasTable('communication_conversations')) {
            Schema::create('communication_conversations', function (Blueprint $table) {
                $table->id();
                $table->string('public_id', 26)->unique();
                $table->string('type', 32)->default('support_ticket')->index();
                $table->string('ticket_number', 40)->nullable()->unique();
                $table->string('subject', 180);
                $table->string('category', 100)->nullable()->index();
                $table->string('status', 24)->default('open')->index();
                $table->string('priority', 20)->default('normal')->index();
                $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('idempotency_key', 120)->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('last_message_at')->nullable()->index();
                $table->timestamp('closed_at')->nullable();
                $table->timestamps();

                $table->unique(
                    ['created_by_user_id', 'idempotency_key'],
                    'comm_conv_creator_idem_unique'
                );
                $table->index(['type', 'status', 'priority'], 'comm_conv_type_status_priority_idx');
            });
        }

        if (! Schema::hasTable('communication_messages')) {
            Schema::create('communication_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('conversation_id')->constrained('communication_conversations')->cascadeOnDelete();
                $table->foreignId('sender_user_id')->constrained('users')->cascadeOnDelete();
                $table->text('body');
                $table->string('kind', 24)->default('message');
                $table->string('client_message_key', 120)->nullable();
                $table->timestamp('sent_at');
                $table->timestamp('edited_at')->nullable();
                $table->timestamps();

                $table->unique(
                    ['conversation_id', 'sender_user_id', 'client_message_key'],
                    'comm_msg_sender_idem_unique'
                );
                $table->index(['conversation_id', 'sent_at'], 'comm_msg_conv_sent_idx');
            });
        }

        if (! Schema::hasTable('communication_participants')) {
            Schema::create('communication_participants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('conversation_id')->constrained('communication_conversations')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('role', 24)->default('participant');
                $table->foreignId('last_read_message_id')->nullable()
                    ->constrained('communication_messages')->nullOnDelete();
                $table->timestamp('joined_at');
                $table->timestamp('last_read_at')->nullable();
                $table->timestamps();

                $table->unique(['conversation_id', 'user_id'], 'comm_participant_unique');
                $table->index(['user_id', 'last_read_at'], 'comm_participant_user_read_idx');
            });
        }

        if (! Schema::hasTable('communication_attachments')) {
            Schema::create('communication_attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('message_id')->constrained('communication_messages')->cascadeOnDelete();
                $table->string('disk', 40)->default('local');
                $table->string('path', 500);
                $table->string('original_name', 255);
                $table->string('mime_type', 120)->nullable();
                $table->unsignedBigInteger('size_bytes')->default(0);
                $table->string('checksum', 64)->nullable();
                $table->timestamps();

                $table->index('message_id');
                $table->index('checksum');
            });
        }

        if (! Schema::hasTable('communication_events')) {
            Schema::create('communication_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('conversation_id')->constrained('communication_conversations')->cascadeOnDelete();
                $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('message_id')->nullable()->constrained('communication_messages')->nullOnDelete();
                $table->string('type', 60)->index();
                $table->json('metadata')->nullable();
                $table->timestamp('occurred_at')->index();
                $table->timestamps();

                $table->index(['conversation_id', 'occurred_at'], 'comm_event_conv_time_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('communication_conversations')
            && DB::table('communication_conversations')->exists()) {
            throw new RuntimeException(
                'Cannot roll back Communication authority while conversation history exists.'
            );
        }

        Schema::dropIfExists('communication_events');
        Schema::dropIfExists('communication_attachments');
        Schema::dropIfExists('communication_participants');
        Schema::dropIfExists('communication_messages');
        Schema::dropIfExists('communication_conversations');
    }
};
