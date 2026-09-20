<?php

namespace App\Console\Commands;

use App\Models\CommunicationAttachment;
use App\Models\CommunicationConversation;
use App\Models\CommunicationMessage;
use App\Models\CommunicationMessageUserState;
use App\Models\User;
use App\Services\CommunicationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

class InspectCommunicationAcceptance extends Command
{
    protected $signature = 'communication:inspect-ms7-acceptance';
    protected $description = 'Inspect persistent MS7 communication and WhatsApp-style chat authority.';

    public function handle(CommunicationService $communications): int
    {
        $admin = User::query()->where('email', 'qa.communication.admin@rcentz.test')->first();
        $directCustomer = User::query()->where('email', 'qa.communication.direct@rcentz.test')->first();

        $cases = [
            'OPEN TICKET' => CommunicationConversation::query()->where('subject', 'MS7 QA wallet support ticket')->first(),
            'RESOLVED' => CommunicationConversation::query()->where('subject', 'MS7 QA resolved support ticket')->first(),
            'DIRECT CHAT' => CommunicationConversation::query()->where('subject', 'MS7 QA private account conversation')->first(),
        ];

        $rows = [];
        $caseFailures = 0;

        foreach ($cases as $label => $conversation) {
            if (! $conversation) {
                $rows[] = [$label, 'MISSING', '—', '—', '—', 'FAIL'];
                $caseFailures++;
                continue;
            }

            $conversation->load(['creator', 'participants.user']);
            $messages = $conversation->messages()->count();
            $activeAttachments = CommunicationAttachment::query()
                ->whereNull('revoked_at')
                ->whereHas('message', fn ($message) => $message->where('conversation_id', $conversation->id))
                ->count();

            $expected = match ($label) {
                'OPEN TICKET' => $conversation->type === 'support_ticket'
                    && $conversation->status === 'open'
                    && filled($conversation->ticket_number)
                    && $messages >= 1
                    && $activeAttachments >= 1
                    && $admin
                    && $communications->unreadCount($admin, $conversation) >= 1,
                'RESOLVED' => $conversation->type === 'support_ticket'
                    && $conversation->status === 'resolved'
                    && filled($conversation->ticket_number)
                    && $messages >= 2
                    && $conversation->assigned_to_user_id === $admin?->id,
                'DIRECT CHAT' => $conversation->type === 'direct'
                    && $conversation->ticket_number === null
                    && $admin
                    && $conversation->participants()->where('user_id', $admin->id)->exists()
                    && $directCustomer
                    && $conversation->participants()->where('user_id', $directCustomer->id)->exists()
                    && $messages >= 7,
                default => false,
            };

            if (! $expected) $caseFailures++;

            $rows[] = [
                $label,
                $conversation->creator?->email,
                $conversation->ticket_number ?: 'PRIVATE',
                $conversation->status,
                $messages,
                $expected ? 'PASS' : 'FAIL',
            ];
        }

        $missingTicketNumbers = CommunicationConversation::query()
            ->where('type', 'support_ticket')->whereNull('ticket_number')->count();

        $supportMissingCustomer = CommunicationConversation::query()
            ->where('type', 'support_ticket')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')->from('communication_participants')
                    ->whereColumn('communication_participants.conversation_id', 'communication_conversations.id')
                    ->whereColumn('communication_participants.user_id', 'communication_conversations.created_by_user_id');
            })->count();

        $messagesFromNonParticipants = CommunicationMessage::query()
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')->from('communication_participants')
                    ->whereColumn('communication_participants.conversation_id', 'communication_messages.conversation_id')
                    ->whereColumn('communication_participants.user_id', 'communication_messages.sender_user_id');
            })->count();

        $duplicateConversationKeys = DB::query()->fromSub(
            CommunicationConversation::query()
                ->selectRaw('created_by_user_id, idempotency_key, COUNT(*) as row_count')
                ->whereNotNull('idempotency_key')
                ->groupBy('created_by_user_id', 'idempotency_key'),
            'duplicates'
        )->where('row_count', '>', 1)->count();

        $duplicateMessageKeys = DB::query()->fromSub(
            CommunicationMessage::query()
                ->selectRaw('conversation_id, sender_user_id, client_message_key, COUNT(*) as row_count')
                ->whereNotNull('client_message_key')
                ->groupBy('conversation_id', 'sender_user_id', 'client_message_key'),
            'duplicates'
        )->where('row_count', '>', 1)->count();

        $missingActiveAttachmentFiles = 0;
        foreach (CommunicationAttachment::query()->whereNull('revoked_at')->get() as $attachment) {
            if (! Storage::disk($attachment->disk)->exists($attachment->path)) $missingActiveAttachmentFiles++;
        }

        $revokedAttachmentFilesStillStored = 0;
        foreach (CommunicationAttachment::query()->whereNotNull('revoked_at')->get() as $attachment) {
            if (Storage::disk($attachment->disk)->exists($attachment->path)) $revokedAttachmentFilesStillStored++;
        }

        $conversationsMissingEvents = CommunicationConversation::query()
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')->from('communication_events')
                    ->whereColumn('communication_events.conversation_id', 'communication_conversations.id');
            })->count();

        $directWithTicketNumber = CommunicationConversation::query()
            ->where('type', 'direct')->whereNotNull('ticket_number')->count();

        $crossConversationReplies = CommunicationMessage::query()
            ->whereNotNull('reply_to_message_id')
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('communication_messages as reply_target')
                    ->whereColumn('reply_target.id', 'communication_messages.reply_to_message_id')
                    ->whereColumn('reply_target.conversation_id', '!=', 'communication_messages.conversation_id');
            })->count();

        $deletedMessagesWithBody = CommunicationMessage::query()
            ->whereNotNull('deleted_for_everyone_at')
            ->where('body', '!=', '')
            ->count();

        $deletedMessagesWithActiveAttachment = CommunicationMessage::query()
            ->whereNotNull('deleted_for_everyone_at')
            ->whereHas('attachments', fn ($attachment) => $attachment->whereNull('revoked_at'))
            ->count();

        $statesForNonParticipants = CommunicationMessageUserState::query()
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('communication_messages')
                    ->join('communication_participants', function ($join) {
                        $join->on('communication_participants.conversation_id', '=', 'communication_messages.conversation_id');
                    })
                    ->whereColumn('communication_messages.id', 'communication_message_user_states.message_id')
                    ->whereColumn('communication_participants.user_id', 'communication_message_user_states.user_id');
            })->count();

        $globallyDeletedVisibleToParticipant = 0;
        foreach (CommunicationMessage::query()->whereNotNull('deleted_for_everyone_at')->with('conversation.participants.user')->get() as $deletedMessage) {
            foreach ($deletedMessage->conversation?->participants ?? collect() as $participant) {
                if ($participant->user && CommunicationMessage::query()
                    ->whereKey($deletedMessage->id)
                    ->visibleTo($participant->user)
                    ->exists()) {
                    $globallyDeletedVisibleToParticipant++;
                }
            }
        }

        $separatedRoutesMissing = collect([
            'messages.index',
            'messages.show',
            'messages.messages.edit',
            'support.index',
            'support.show',
            'support.messages.edit',
            'admin.messages.index',
            'admin.messages.show',
            'admin.messages.messages.edit',
            'admin.support.index',
            'admin.support.show',
            'admin.support.messages.edit',
        ])->filter(fn (string $name) => ! Route::has($name))->count();

        $r1Failures = $this->r1Failures($communications, $admin, $directCustomer);

        $checks = [
            ['Support tickets missing ticket number', $missingTicketNumbers],
            ['Support tickets missing customer participant', $supportMissingCustomer],
            ['Messages authored by non-participants', $messagesFromNonParticipants],
            ['Duplicate conversation idempotency keys', $duplicateConversationKeys],
            ['Duplicate message idempotency keys', $duplicateMessageKeys],
            ['Active attachment rows missing stored file', $missingActiveAttachmentFiles],
            ['Revoked attachments still stored', $revokedAttachmentFilesStillStored],
            ['Conversations missing lifecycle event', $conversationsMissingEvents],
            ['Direct conversations carrying ticket number', $directWithTicketNumber],
            ['Cross-conversation reply targets', $crossConversationReplies],
            ['Deleted-for-everyone messages retaining body', $deletedMessagesWithBody],
            ['Deleted-for-everyone messages retaining active files', $deletedMessagesWithActiveAttachment],
            ['Per-user message states for non-participants', $statesForNonParticipants],
            ['Globally deleted messages visible to participants', $globallyDeletedVisibleToParticipant],
            ['Separated Messages / Support routes missing', $separatedRoutesMissing],
            ['MS7 R1 chat-experience failures', $r1Failures],
            ['Persistent QA case failures', $caseFailures],
        ];

        $this->newLine();
        $this->info('Persistent MS7 Communication acceptance cases:');
        $this->table(['Case', 'Customer', 'Reference', 'Status', 'Messages', 'Result'], $rows);
        $this->newLine();
        $this->table(['MS7 Communication authority check', 'Count'], $checks);

        $failed = collect($checks)->contains(fn ($row) => (int) $row[1] !== 0);

        if ($failed) {
            $this->error('MS7_COMMUNICATION_ACCEPTANCE_FAILED');
            return self::FAILURE;
        }

        $this->info('MS7 Communication is READY with separated Messages and Support workspaces, messenger-style reply/edit actions, ghost delete-for-everyone, silent edit presentation, media gallery, multiple attachments, read state and persistent ticket/direct-chat authority.');
        return self::SUCCESS;
    }

    private function r1Failures(CommunicationService $communications, ?User $admin, ?User $customer): int
    {
        if (! $admin || ! $customer) return 1;

        $direct = CommunicationConversation::query()->where('subject', 'MS7 QA private account conversation')->first();
        $recovery = CommunicationConversation::query()->where('subject', 'MS7 R1 QA deleted chat recovery probe')->first();
        if (! $direct || ! $recovery) return 1;

        $initial = $direct->messages()->where('client_message_key', 'MS7-QA-DIRECT-V1:initial')->first();
        $threaded = $direct->messages()->where('client_message_key', 'MS7-R1-QA-THREADED-REPLY-V1')->first();
        $deleteMe = $direct->messages()->where('client_message_key', 'MS7-R1-QA-DELETE-ME-V1')->first();
        $deleteAll = $direct->messages()->where('client_message_key', 'MS7-R1-QA-DELETE-EVERYONE-V1')->first();
        $edited = $direct->messages()->where('client_message_key', 'MS7-R4-QA-EDITABLE-V1')->first();
        $galleryImage = $initial?->attachments()->where('original_name', 'ms7-r1-gallery-image.png')->first();
        $galleryFile = $initial?->attachments()->where('original_name', 'ms7-r1-shared-note.txt')->first();
        $revoked = $deleteAll?->attachments()->where('original_name', 'ms7-r1-revoked-document.pdf')->first();
        $customerParticipant = $direct->participants()->where('user_id', $customer->id)->first();

        $recoveryInitial = $recovery->messages()->where('client_message_key', 'MS7-R1-QA-CHAT-DELETE-PROBE-V1:initial')->first();
        $recoveryIncoming = $recovery->messages()->where('client_message_key', 'MS7-R1-QA-CHAT-RECOVERY-INCOMING-V1')->first();
        $recoveryParticipant = $recovery->participants()->where('user_id', $customer->id)->first();

        $conditions = [
            $initial && $threaded && $threaded->reply_to_message_id === $initial->id,
            $galleryImage && ! $galleryImage->revoked_at && Storage::disk($galleryImage->disk)->exists($galleryImage->path),
            $galleryFile && ! $galleryFile->revoked_at && Storage::disk($galleryFile->disk)->exists($galleryFile->path),
            $deleteMe && ! $deleteMe->deleted_for_everyone_at
                && $deleteMe->userStates()->where('user_id', $customer->id)->whereNotNull('hidden_at')->exists(),
            $deleteAll && $deleteAll->deleted_for_everyone_at && $deleteAll->body === '',
            $revoked && $revoked->revoked_at && ! Storage::disk($revoked->disk)->exists($revoked->path),
            $customerParticipant && $customerParticipant->archived_at === null,
            $recoveryInitial && $recoveryIncoming && $recoveryParticipant && $recoveryParticipant->conversation_hidden_at === null,
            $recoveryInitial && $recoveryInitial->userStates()->where('user_id', $customer->id)->whereNotNull('hidden_at')->exists(),
            $recoveryIncoming && ! $recoveryIncoming->userStates()->where('user_id', $customer->id)->whereNotNull('hidden_at')->exists(),

            $direct->events()->where('type', 'message.deleted_for_everyone')->where('message_id', $deleteAll?->id)->exists(),
            $edited && $edited->edited_at && $edited->body === 'MS7 R4 final edited message with ghost edit presentation.',
            $edited && $direct->events()->where('type', 'message.edited')->where('message_id', $edited->id)->exists(),
        ];

        return collect($conditions)->filter(fn ($passed) => ! $passed)->count();
    }
}
