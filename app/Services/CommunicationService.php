<?php

namespace App\Services;

use App\Models\CommunicationAttachment;
use App\Models\CommunicationConversation;
use App\Models\CommunicationEvent;
use App\Models\CommunicationMessage;
use App\Models\CommunicationMessageUserState;
use App\Models\CommunicationParticipant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class CommunicationService
{
    public function createSupportTicket(
        User $customer,
        array $data,
        array|UploadedFile|null $attachments = null
    ): CommunicationConversation {
        if ($customer->isAdmin()) {
            throw new RuntimeException('Administrators cannot create customer support tickets.');
        }

        $key = trim((string) ($data['idempotency_key'] ?? ''));
        if ($key === '') {
            throw new RuntimeException('A support request idempotency key is required.');
        }

        $existing = CommunicationConversation::query()
            ->where('created_by_user_id', $customer->id)
            ->where('idempotency_key', $key)
            ->first();

        if ($existing) {
            return $existing->fresh(['messages.attachments', 'participants.user']);
        }

        $uploads = $this->normalizeUploads($attachments);
        $stored = $this->storeUploads($uploads, 'pending-'.$customer->id);

        try {
            return DB::transaction(function () use ($customer, $data, $key, $stored) {
                $conversation = CommunicationConversation::query()->create([
                    'public_id' => (string) Str::ulid(),
                    'type' => 'support_ticket',
                    'ticket_number' => $this->ticketNumber(),
                    'subject' => trim((string) $data['subject']),
                    'category' => trim((string) $data['category']),
                    'status' => 'open',
                    'priority' => $data['priority'] ?? 'normal',
                    'created_by_user_id' => $customer->id,
                    'assigned_to_user_id' => null,
                    'idempotency_key' => $key,
                    'metadata' => $data['metadata'] ?? null,
                    'last_message_at' => now(),
                ]);

                $message = CommunicationMessage::query()->create([
                    'conversation_id' => $conversation->id,
                    'sender_user_id' => $customer->id,
                    'body' => trim((string) $data['message']),
                    'kind' => 'message',
                    'client_message_key' => $key.':initial',
                    'sent_at' => now(),
                ]);

                CommunicationParticipant::query()->create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $customer->id,
                    'role' => 'customer',
                    'last_read_message_id' => $message->id,
                    'joined_at' => now(),
                    'last_read_at' => now(),
                ]);

                $this->persistStoredAttachments($message, $stored);

                $this->event($conversation, $customer, 'conversation.created', [
                    'type' => 'support_ticket',
                    'ticket_number' => $conversation->ticket_number,
                ], $message);

                return $conversation->fresh([
                    'messages.attachments',
                    'participants.user',
                    'creator',
                    'assignee',
                ]);
            });
        } catch (\Throwable $e) {
            $this->deleteStoredUploads($stored);
            throw $e;
        }
    }

    public function createDirectConversation(
        User $admin,
        User $customer,
        string $subject,
        string $messageBody,
        string $idempotencyKey,
        array $metadata = [],
        array|UploadedFile|null $attachments = null
    ): CommunicationConversation {
        $this->assertAdmin($admin);

        if ($customer->isAdmin()) {
            throw new RuntimeException('Direct customer conversations require a non-admin customer.');
        }

        $existing = CommunicationConversation::query()
            ->where('created_by_user_id', $admin->id)
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existing) {
            return $existing->fresh(['messages.attachments', 'participants.user']);
        }

        $uploads = $this->normalizeUploads($attachments);
        $stored = $this->storeUploads($uploads, 'direct-'.$admin->id.'-'.$customer->id);

        try {
            return DB::transaction(function () use ($admin, $customer, $subject, $messageBody, $idempotencyKey, $metadata, $stored) {
                $conversation = CommunicationConversation::query()->create([
                    'public_id' => (string) Str::ulid(),
                    'type' => 'direct',
                    'ticket_number' => null,
                    'subject' => trim($subject),
                    'category' => 'Direct message',
                    'status' => 'open',
                    'priority' => 'normal',
                    'created_by_user_id' => $admin->id,
                    'assigned_to_user_id' => $admin->id,
                    'idempotency_key' => $idempotencyKey,
                    'metadata' => $metadata ?: null,
                    'last_message_at' => now(),
                ]);

                $message = CommunicationMessage::query()->create([
                    'conversation_id' => $conversation->id,
                    'sender_user_id' => $admin->id,
                    'body' => trim($messageBody),
                    'kind' => 'message',
                    'client_message_key' => $idempotencyKey.':initial',
                    'sent_at' => now(),
                ]);

                CommunicationParticipant::query()->create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $admin->id,
                    'role' => 'admin',
                    'last_read_message_id' => $message->id,
                    'joined_at' => now(),
                    'last_read_at' => now(),
                ]);

                CommunicationParticipant::query()->create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $customer->id,
                    'role' => 'customer',
                    'last_read_message_id' => null,
                    'joined_at' => now(),
                    'last_read_at' => null,
                ]);

                $this->persistStoredAttachments($message, $stored);

                $this->event($conversation, $admin, 'conversation.created', [
                    'type' => 'direct',
                    'customer_user_id' => $customer->id,
                ], $message);

                return $conversation->fresh(['messages.attachments', 'participants.user']);
            });
        } catch (\Throwable $e) {
            $this->deleteStoredUploads($stored);
            throw $e;
        }
    }

    public function sendMessage(
        User $sender,
        CommunicationConversation $conversation,
        ?string $body,
        string $clientMessageKey,
        array|UploadedFile|null $attachments = null,
        ?CommunicationMessage $replyTo = null
    ): CommunicationMessage {
        $body = trim((string) $body);
        $clientMessageKey = trim($clientMessageKey);
        $uploads = $this->normalizeUploads($attachments);

        if ($body === '' && $uploads === []) {
            throw new RuntimeException('A message or attachment is required.');
        }

        if ($clientMessageKey === '') {
            throw new RuntimeException('A message idempotency key is required.');
        }

        if (! $this->canAccess($sender, $conversation)) {
            throw new RuntimeException('You do not have access to this conversation.');
        }

        if ($conversation->status === 'closed') {
            throw new RuntimeException('Closed conversations cannot receive new messages.');
        }

        if ($replyTo) {
            $this->assertMessageInConversation($conversation, $replyTo);
        }

        $existing = CommunicationMessage::query()
            ->where('conversation_id', $conversation->id)
            ->where('sender_user_id', $sender->id)
            ->where('client_message_key', $clientMessageKey)
            ->first();

        if ($existing) {
            if ((int) ($existing->reply_to_message_id ?? 0) !== (int) ($replyTo?->id ?? 0)) {
                throw new RuntimeException('This message key was already used for a different reply target.');
            }
            if (! $existing->deleted_for_everyone_at && trim((string) $existing->body) !== $body) {
                throw new RuntimeException('This message key was already used for different content.');
            }

            return $existing->fresh(['attachments', 'sender', 'replyTo.sender']);
        }

        $stored = $this->storeUploads($uploads, $conversation->public_id);

        try {
            return DB::transaction(function () use (
                $sender,
                $conversation,
                $body,
                $clientMessageKey,
                $stored,
                $replyTo
            ) {
                $conversation = CommunicationConversation::query()
                    ->lockForUpdate()
                    ->findOrFail($conversation->id);

                $participant = CommunicationParticipant::query()->firstOrCreate(
                    [
                        'conversation_id' => $conversation->id,
                        'user_id' => $sender->id,
                    ],
                    [
                        'role' => $sender->isAdmin() ? 'admin' : 'customer',
                        'joined_at' => now(),
                    ]
                );

                if (! $sender->isAdmin() && $participant->role === 'admin') {
                    throw new RuntimeException('Invalid participant authority.');
                }

                $message = CommunicationMessage::query()->create([
                    'conversation_id' => $conversation->id,
                    'sender_user_id' => $sender->id,
                    'body' => $body,
                    'kind' => 'message',
                    'reply_to_message_id' => $replyTo?->id,
                    'client_message_key' => $clientMessageKey,
                    'sent_at' => now(),
                ]);

                $this->persistStoredAttachments($message, $stored);

                $updates = ['last_message_at' => $message->sent_at];

                if (! $sender->isAdmin() && $conversation->status === 'resolved') {
                    $updates['status'] = 'open';
                    $updates['closed_at'] = null;
                    $this->event($conversation, $sender, 'conversation.reopened', [
                        'previous_status' => 'resolved',
                    ], $message);
                }

                $conversation->update($updates);

                $participant->update([
                    'last_read_message_id' => $message->id,
                    'last_read_at' => now(),
                    'conversation_hidden_at' => null,
                ]);

                CommunicationParticipant::query()
                    ->where('conversation_id', $conversation->id)
                    ->where('user_id', '!=', $sender->id)
                    ->whereNotNull('conversation_hidden_at')
                    ->update(['conversation_hidden_at' => null]);

                $this->event($conversation, $sender, 'message.sent', [
                    'kind' => 'message',
                    'attachment_count' => count($stored),
                    'reply_to_message_id' => $replyTo?->id,
                ], $message);

                return $message->fresh(['attachments', 'sender', 'replyTo.sender']);
            });
        } catch (\Throwable $e) {
            $this->deleteStoredUploads($stored);
            throw $e;
        }
    }

    public function editMessage(
        User $actor,
        CommunicationConversation $conversation,
        CommunicationMessage $message,
        string $body
    ): CommunicationMessage {
        $body = trim($body);

        if ($body === '') {
            throw new RuntimeException('Edited message text cannot be empty.');
        }

        if (! $this->canAccess($actor, $conversation)) {
            throw new RuntimeException('You do not have access to this conversation.');
        }

        $this->assertMessageInConversation($conversation, $message);

        if ($message->sender_user_id !== $actor->id) {
            throw new RuntimeException('You can only edit messages you sent.');
        }

        return DB::transaction(function () use ($actor, $conversation, $message, $body) {
            $message = CommunicationMessage::query()
                ->lockForUpdate()
                ->findOrFail($message->id);

            if ($message->sender_user_id !== $actor->id) {
                throw new RuntimeException('You can only edit messages you sent.');
            }

            if ($message->deleted_for_everyone_at) {
                throw new RuntimeException('Deleted messages cannot be edited.');
            }

            if (trim((string) $message->body) === $body) {
                return $message->fresh(['attachments', 'sender', 'replyTo.sender']);
            }

            $before = (string) $message->body;
            $message->update([
                'body' => $body,
                'edited_at' => now(),
            ]);

            $this->event($conversation, $actor, 'message.edited', [
                'before_sha256' => hash('sha256', $before),
                'after_sha256' => hash('sha256', $body),
                'before_length' => mb_strlen($before),
                'after_length' => mb_strlen($body),
            ], $message);

            return $message->fresh(['attachments', 'sender', 'replyTo.sender']);
        });
    }

    public function updateTicket(
        User $admin,
        CommunicationConversation $conversation,
        string $status,
        string $priority,
        ?User $assignee = null
    ): CommunicationConversation {
        $this->assertAdmin($admin);

        if ($conversation->type !== 'support_ticket') {
            throw new RuntimeException('Only support tickets have ticket lifecycle controls.');
        }

        if (! in_array($status, ['open', 'pending', 'resolved', 'closed'], true)) {
            throw new RuntimeException('Unsupported ticket status.');
        }

        if (! in_array($priority, ['low', 'normal', 'high', 'urgent'], true)) {
            throw new RuntimeException('Unsupported ticket priority.');
        }

        if ($assignee && ! $assignee->isAdmin()) {
            throw new RuntimeException('Ticket assignee must be an administrator.');
        }

        return DB::transaction(function () use ($admin, $conversation, $status, $priority, $assignee) {
            $conversation = CommunicationConversation::query()
                ->lockForUpdate()
                ->findOrFail($conversation->id);

            $before = [
                'status' => $conversation->status,
                'priority' => $conversation->priority,
                'assigned_to_user_id' => $conversation->assigned_to_user_id,
            ];

            $unchanged = $conversation->status === $status
                && $conversation->priority === $priority
                && (int) ($conversation->assigned_to_user_id ?? 0) === (int) ($assignee?->id ?? 0);

            if (! $unchanged) {
                $conversation->update([
                    'status' => $status,
                    'priority' => $priority,
                    'assigned_to_user_id' => $assignee?->id,
                    'closed_at' => $status === 'closed' ? now() : null,
                ]);
            }

            CommunicationParticipant::query()->firstOrCreate(
                [
                    'conversation_id' => $conversation->id,
                    'user_id' => $admin->id,
                ],
                [
                    'role' => 'admin',
                    'joined_at' => now(),
                ]
            );

            if ($assignee) {
                CommunicationParticipant::query()->firstOrCreate(
                    [
                        'conversation_id' => $conversation->id,
                        'user_id' => $assignee->id,
                    ],
                    [
                        'role' => 'admin',
                        'joined_at' => now(),
                    ]
                );
            }

            if (! $unchanged) {
                $this->event($conversation, $admin, 'ticket.updated', [
                    'before' => $before,
                    'after' => [
                        'status' => $status,
                        'priority' => $priority,
                        'assigned_to_user_id' => $assignee?->id,
                    ],
                ]);
            }

            return $conversation->fresh(['creator', 'assignee', 'participants.user']);
        });
    }

    public function markRead(User $user, CommunicationConversation $conversation): CommunicationParticipant
    {
        if (! $this->canAccess($user, $conversation)) {
            throw new RuntimeException('You do not have access to this conversation.');
        }

        $participant = $this->participant($user, $conversation);

        $latestMessageId = CommunicationMessage::query()
            ->visibleTo($user)
            ->where('conversation_id', $conversation->id)
            ->max('id');

        $participant->update([
            'last_read_message_id' => $latestMessageId ?: null,
            'last_read_at' => now(),
            'conversation_hidden_at' => null,
        ]);

        return $participant->fresh();
    }

    public function unreadCount(User $user, CommunicationConversation $conversation): int
    {
        $participant = CommunicationParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $participant && ! $user->isAdmin()) {
            return 0;
        }

        $lastReadId = (int) ($participant?->last_read_message_id ?? 0);

        return CommunicationMessage::query()
            ->visibleTo($user)
            ->where('conversation_id', $conversation->id)
            ->where('sender_user_id', '!=', $user->id)
            ->whereNull('deleted_for_everyone_at')
            ->where('id', '>', $lastReadId)
            ->count();
    }

    public function canAccess(User $user, CommunicationConversation $conversation): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return CommunicationParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    public function canViewAttachment(User $user, CommunicationAttachment $attachment): bool
    {
        $attachment->loadMissing('message.conversation');
        $message = $attachment->message;
        $conversation = $message?->conversation;

        if (! $conversation || ! $this->canAccess($user, $conversation)) {
            return false;
        }

        if ($attachment->revoked_at || $message->deleted_for_everyone_at) {
            return false;
        }

        return ! CommunicationMessageUserState::query()
            ->where('message_id', $message->id)
            ->where('user_id', $user->id)
            ->whereNotNull('hidden_at')
            ->exists();
    }

    public function mediaFor(User $user, CommunicationConversation $conversation): array
    {
        if (! $this->canAccess($user, $conversation)) {
            throw new RuntimeException('You do not have access to this conversation.');
        }

        $attachments = CommunicationAttachment::query()
            ->with(['message.sender'])
            ->whereNull('revoked_at')
            ->whereHas('message', function ($message) use ($conversation, $user) {
                $message->where('conversation_id', $conversation->id)
                    ->whereNull('deleted_for_everyone_at')
                    ->visibleTo($user);
            })
            ->latest('id')
            ->get();

        return [
            'images' => $attachments->filter(fn (CommunicationAttachment $attachment) => $attachment->is_image)->values(),
            'files' => $attachments->reject(fn (CommunicationAttachment $attachment) => $attachment->is_image)->values(),
        ];
    }

    public function setArchived(User $user, CommunicationConversation $conversation, bool $archived): CommunicationParticipant
    {
        if (! $this->canAccess($user, $conversation)) {
            throw new RuntimeException('You do not have access to this conversation.');
        }

        $participant = $this->participant($user, $conversation);
        $next = $archived ? now() : null;

        if (($participant->archived_at !== null) !== $archived) {
            $participant->update(['archived_at' => $next]);
            $this->event($conversation, $user, $archived ? 'conversation.archived' : 'conversation.unarchived');
        }

        return $participant->fresh();
    }

    public function hideConversationForMe(User $user, CommunicationConversation $conversation): void
    {
        if (! $this->canAccess($user, $conversation)) {
            throw new RuntimeException('You do not have access to this conversation.');
        }

        DB::transaction(function () use ($user, $conversation) {
            $participant = $this->participant($user, $conversation);

            $messageIds = CommunicationMessage::query()
                ->where('conversation_id', $conversation->id)
                ->pluck('id');

            foreach ($messageIds as $messageId) {
                CommunicationMessageUserState::query()->updateOrCreate(
                    ['message_id' => $messageId, 'user_id' => $user->id],
                    ['hidden_at' => now()]
                );
            }

            $participant->update([
                'conversation_hidden_at' => now(),
                'archived_at' => null,
            ]);

            $this->event($conversation, $user, 'conversation.deleted_for_user', [
                'hidden_message_count' => $messageIds->count(),
            ]);
        });
    }

    public function hideMessageForMe(User $user, CommunicationConversation $conversation, CommunicationMessage $message): void
    {
        if (! $this->canAccess($user, $conversation)) {
            throw new RuntimeException('You do not have access to this conversation.');
        }

        $this->assertMessageInConversation($conversation, $message);

        CommunicationMessageUserState::query()->updateOrCreate(
            ['message_id' => $message->id, 'user_id' => $user->id],
            ['hidden_at' => now()]
        );

        $this->event($conversation, $user, 'message.deleted_for_user', [], $message);
    }

    public function deleteMessageForEveryone(User $actor, CommunicationConversation $conversation, CommunicationMessage $message): CommunicationMessage
    {
        if (! $this->canAccess($actor, $conversation)) {
            throw new RuntimeException('You do not have access to this conversation.');
        }

        $this->assertMessageInConversation($conversation, $message);

        if ($message->sender_user_id !== $actor->id && ! $actor->isAdmin()) {
            throw new RuntimeException('Only the sender or an administrator can delete this message for everyone.');
        }

        return DB::transaction(function () use ($actor, $conversation, $message) {
            $message = CommunicationMessage::query()
                ->with('attachments')
                ->lockForUpdate()
                ->findOrFail($message->id);

            if ($message->deleted_for_everyone_at) {
                return $message;
            }

            $bodyHash = hash('sha256', (string) $message->body);
            $attachmentCount = $message->attachments->count();

            foreach ($message->attachments as $attachment) {
                if (! $attachment->revoked_at && Storage::disk($attachment->disk)->exists($attachment->path)) {
                    Storage::disk($attachment->disk)->delete($attachment->path);
                }

                $attachment->update([
                    'revoked_at' => now(),
                    'revoked_by_user_id' => $actor->id,
                ]);
            }

            $message->update([
                'body' => '',
                'deleted_for_everyone_at' => now(),
                'deleted_by_user_id' => $actor->id,
            ]);

            $this->event($conversation, $actor, 'message.deleted_for_everyone', [
                'body_sha256' => $bodyHash,
                'attachment_count' => $attachmentCount,
            ], $message);

            return $message->fresh(['attachments', 'sender', 'replyTo.sender']);
        });
    }

    public function attachContent(
        CommunicationMessage $message,
        string $originalName,
        string $mimeType,
        string $content
    ): CommunicationAttachment {
        $checksum = hash('sha256', $content);

        $existing = CommunicationAttachment::query()
            ->where('message_id', $message->id)
            ->where('original_name', $originalName)
            ->where('checksum', $checksum)
            ->first();

        if ($existing) {
            return $existing;
        }

        $conversation = $message->conversation()->firstOrFail();
        $path = 'communication/'.$conversation->public_id.'/'.Str::uuid().'-'.$this->safeName($originalName);

        Storage::disk('local')->put($path, $content);

        try {
            return $this->createAttachmentRecord(
                $message,
                $path,
                $originalName,
                $mimeType,
                strlen($content),
                $checksum
            );
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }
    }

    private function participant(User $user, CommunicationConversation $conversation): CommunicationParticipant
    {
        if (! $user->isAdmin()) {
            return CommunicationParticipant::query()
                ->where('conversation_id', $conversation->id)
                ->where('user_id', $user->id)
                ->firstOrFail();
        }

        return CommunicationParticipant::query()->firstOrCreate(
            [
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
            ],
            [
                'role' => 'admin',
                'joined_at' => now(),
            ]
        );
    }

    private function assertMessageInConversation(CommunicationConversation $conversation, CommunicationMessage $message): void
    {
        if ($message->conversation_id !== $conversation->id) {
            throw new RuntimeException('The selected message does not belong to this conversation.');
        }
    }

    private function normalizeUploads(array|UploadedFile|null $attachments): array
    {
        if ($attachments instanceof UploadedFile) {
            return [$attachments];
        }

        return array_values(array_filter((array) $attachments, fn ($file) => $file instanceof UploadedFile));
    }

    private function storeUploads(array $uploads, string $scope): array
    {
        $stored = [];

        try {
            foreach ($uploads as $attachment) {
                $stored[] = [
                    'file' => $attachment,
                    'path' => $this->storeUploadPath($attachment, $scope),
                ];
            }
        } catch (\Throwable $e) {
            $this->deleteStoredUploads($stored);
            throw $e;
        }

        return $stored;
    }

    private function persistStoredAttachments(CommunicationMessage $message, array $stored): void
    {
        foreach ($stored as $item) {
            /** @var UploadedFile $attachment */
            $attachment = $item['file'];
            $this->createAttachmentRecord(
                $message,
                $item['path'],
                $attachment->getClientOriginalName(),
                $attachment->getMimeType(),
                (int) $attachment->getSize()
            );
        }
    }

    private function deleteStoredUploads(array $stored): void
    {
        foreach ($stored as $item) {
            $path = $item['path'] ?? null;
            if ($path && Storage::disk('local')->exists($path)) {
                Storage::disk('local')->delete($path);
            }
        }
    }

    private function storeUploadPath(UploadedFile $attachment, string $scope): string
    {
        $name = Str::uuid().'-'.$this->safeName($attachment->getClientOriginalName());
        $path = 'communication/'.$scope.'/'.$name;

        $stream = fopen($attachment->getRealPath(), 'rb');
        if ($stream === false) {
            throw new RuntimeException('Attachment could not be read.');
        }

        try {
            if (! Storage::disk('local')->put($path, $stream)) {
                throw new RuntimeException('Attachment could not be stored.');
            }
        } finally {
            fclose($stream);
        }

        return $path;
    }

    private function createAttachmentRecord(
        CommunicationMessage $message,
        string $path,
        string $originalName,
        ?string $mimeType,
        int $sizeBytes,
        ?string $checksum = null
    ): CommunicationAttachment {
        $checksum ??= Storage::disk('local')->exists($path)
            ? hash('sha256', (string) Storage::disk('local')->get($path))
            : null;

        return CommunicationAttachment::query()->create([
            'message_id' => $message->id,
            'disk' => 'local',
            'path' => $path,
            'original_name' => $originalName,
            'mime_type' => $mimeType,
            'size_bytes' => $sizeBytes,
            'checksum' => $checksum,
        ]);
    }

    private function event(
        CommunicationConversation $conversation,
        ?User $actor,
        string $type,
        array $metadata = [],
        ?CommunicationMessage $message = null
    ): CommunicationEvent {
        return CommunicationEvent::query()->create([
            'conversation_id' => $conversation->id,
            'actor_user_id' => $actor?->id,
            'message_id' => $message?->id,
            'type' => $type,
            'metadata' => $metadata ?: null,
            'occurred_at' => now(),
        ]);
    }

    private function ticketNumber(): string
    {
        do {
            $number = 'TKT-'.now()->format('Ymd').'-'.strtoupper(Str::random(7));
        } while (CommunicationConversation::query()->where('ticket_number', $number)->exists());

        return $number;
    }

    private function safeName(string $name): string
    {
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '-', basename($name)) ?: 'attachment';
        return substr($name, 0, 180);
    }

    private function assertAdmin(User $user): void
    {
        if (! $user->isAdmin()) {
            throw new RuntimeException('Administrator authority is required.');
        }
    }
}
