<?php

namespace App\Console\Commands;

use App\Models\CommunicationConversation;
use App\Models\User;
use App\Models\Wallet;
use App\Services\CommunicationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SeedRealCommunicationCases extends Command
{
    protected $signature = 'communication:seed-ms7-real-cases';
    protected $description = 'Create persistent real MS7 communication acceptance cases through CommunicationService.';

    public function handle(CommunicationService $communications): int
    {
        $password = (string) config('bootstrap.live_test.user_password');
        if (strlen($password) < 10) {
            $this->error('LIVE_TEST_USER_PASSWORD must be configured with at least 10 characters.');
            return self::FAILURE;
        }

        $admin = $this->qaUser('qa.communication.admin@rcentz.test', 'MS7 QA Support Admin', true, $password);
        $openCustomer = $this->qaUser('qa.communication.ticket@rcentz.test', 'MS7 Open Ticket Customer', false, $password);
        $resolvedCustomer = $this->qaUser('qa.communication.resolved@rcentz.test', 'MS7 Resolved Ticket Customer', false, $password);
        $directCustomer = $this->qaUser('qa.communication.direct@rcentz.test', 'MS7 Direct Message Customer', false, $password);

        $open = $communications->createSupportTicket($openCustomer, [
            'category' => 'Wallet',
            'subject' => 'MS7 QA wallet support ticket',
            'message' => 'Synthetic MS7 acceptance ticket proving persistent support, attachment storage and unread admin state.',
            'priority' => 'normal',
            'idempotency_key' => 'MS7-QA-OPEN-TICKET-V1',
            'metadata' => ['qa_case' => 'MS7_OPEN_TICKET'],
        ]);

        $openReplay = $communications->createSupportTicket($openCustomer, [
            'category' => 'Wallet',
            'subject' => 'MS7 QA wallet support ticket',
            'message' => 'Synthetic MS7 acceptance ticket proving persistent support, attachment storage and unread admin state.',
            'priority' => 'normal',
            'idempotency_key' => 'MS7-QA-OPEN-TICKET-V1',
            'metadata' => ['qa_case' => 'MS7_OPEN_TICKET'],
        ]);

        $openInitial = $open->messages()->oldest('id')->firstOrFail();
        $communications->attachContent(
            $openInitial,
            'ms7-open-ticket-evidence.pdf',
            'application/pdf',
            "%PDF-1.4\n% RCENTZ MS7 synthetic QA attachment\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF\n"
        );

        $resolved = $communications->createSupportTicket($resolvedCustomer, [
            'category' => 'Trading',
            'subject' => 'MS7 QA resolved support ticket',
            'message' => 'Synthetic MS7 acceptance ticket proving admin reply, assignment and resolved ticket lifecycle.',
            'priority' => 'high',
            'idempotency_key' => 'MS7-QA-RESOLVED-TICKET-V1',
            'metadata' => ['qa_case' => 'MS7_RESOLVED_TICKET'],
        ]);

        $resolvedReply = $communications->sendMessage(
            $admin,
            $resolved,
            'Support reviewed this synthetic case and confirmed the requested resolution.',
            'MS7-QA-RESOLVED-ADMIN-REPLY-V1'
        );
        $resolvedReplyReplay = $communications->sendMessage(
            $admin,
            $resolved,
            'Support reviewed this synthetic case and confirmed the requested resolution.',
            'MS7-QA-RESOLVED-ADMIN-REPLY-V1'
        );

        $communications->updateTicket($admin, $resolved, 'resolved', 'high', $admin);
        $communications->markRead($admin, $resolved);

        $direct = $communications->createDirectConversation(
            $admin,
            $directCustomer,
            'MS7 QA private account conversation',
            'This is a synthetic private admin-to-customer message for MS7 communication acceptance.',
            'MS7-QA-DIRECT-V1',
            ['qa_case' => 'MS7_DIRECT']
        );

        $directReplay = $communications->createDirectConversation(
            $admin,
            $directCustomer,
            'MS7 QA private account conversation',
            'This is a synthetic private admin-to-customer message for MS7 communication acceptance.',
            'MS7-QA-DIRECT-V1',
            ['qa_case' => 'MS7_DIRECT']
        );

        $directInitial = $direct->messages()->oldest('id')->firstOrFail();

        // Real media-gallery fixture: a tiny valid PNG and a text document.
        $tinyPng = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);
        $communications->attachContent($directInitial, 'ms7-r1-gallery-image.png', 'image/png', $tinyPng ?: 'png');
        $communications->attachContent($directInitial, 'ms7-r1-shared-note.txt', 'text/plain', 'RCENTZ MS7 R1 synthetic shared file.');

        if (! $direct->messages()->where('client_message_key', 'MS7-QA-DIRECT-CUSTOMER-REPLY-V1')->exists()) {
            $communications->markRead($directCustomer, $direct);
        }

        $communications->sendMessage(
            $directCustomer,
            $direct,
            'Customer synthetic reply received. The private thread remains ownership-bound.',
            'MS7-QA-DIRECT-CUSTOMER-REPLY-V1'
        );

        $threadedReply = $communications->sendMessage(
            $directCustomer,
            $direct,
            'This synthetic customer message is a quoted reply to the original admin message.',
            'MS7-R1-QA-THREADED-REPLY-V1',
            null,
            $directInitial
        );

        $deleteForMe = $communications->sendMessage(
            $admin,
            $direct,
            'Synthetic message used to prove delete-for-me does not mutate the shared conversation.',
            'MS7-R1-QA-DELETE-ME-V1'
        );
        $communications->hideMessageForMe($directCustomer, $direct, $deleteForMe);

        $deleteForEveryone = $communications->sendMessage(
            $admin,
            $direct,
            'Synthetic temporary message used to prove delete-for-everyone and attachment revocation.',
            'MS7-R1-QA-DELETE-EVERYONE-V1'
        );
        $communications->attachContent(
            $deleteForEveryone,
            'ms7-r1-revoked-document.pdf',
            'application/pdf',
            "%PDF-1.4\n% RCENTZ MS7 R1 revoked attachment\n%%EOF\n"
        );
        $communications->deleteMessageForEveryone($admin, $direct, $deleteForEveryone);

        // Archive/unarchive must be repeatable and leave the main QA chat visible.
        $communications->setArchived($directCustomer, $direct, true);
        $communications->setArchived($directCustomer, $direct, false);

        // A fresh admin message must leave the customer with a genuine unread state.
        $communications->sendMessage(
            $admin,
            $direct,
            'MS7 R1 admin follow-up proving unread state after threaded reply and deletion actions.',
            'MS7-R1-QA-ADMIN-FOLLOWUP-V1'
        );
        $communications->markRead($admin, $direct);

        // Ghost-edit fixture: the body changes, the backend records edited_at/event,
        // but customer/admin chat surfaces intentionally show no "edited" marker.
        $editable = $direct->messages()
            ->where('client_message_key', 'MS7-R4-QA-EDITABLE-V1')
            ->first();

        if (! $editable) {
            $editable = $communications->sendMessage(
                $admin,
                $direct,
                'MS7 R4 original editable message.',
                'MS7-R4-QA-EDITABLE-V1'
            );
        }

        $communications->editMessage(
            $admin,
            $direct,
            $editable,
            'MS7 R4 final edited message with ghost edit presentation.'
        );

        // Delete-chat-for-me recovery probe: old messages stay hidden locally, a new incoming message makes the chat reappear.
        $recovery = $communications->createDirectConversation(
            $admin,
            $directCustomer,
            'MS7 R1 QA deleted chat recovery probe',
            'Initial synthetic message that will be hidden when the customer deletes this chat for themselves.',
            'MS7-R1-QA-CHAT-DELETE-PROBE-V1',
            ['qa_case' => 'MS7_R1_CHAT_DELETE_RECOVERY']
        );
        $recoveryInitial = $recovery->messages()->oldest('id')->firstOrFail();

        $recoveryParticipant = $recovery->participants()->where('user_id', $directCustomer->id)->firstOrFail();
        if (! $recoveryParticipant->conversation_hidden_at
            && ! $recoveryInitial->userStates()->where('user_id', $directCustomer->id)->whereNotNull('hidden_at')->exists()
            && ! $recovery->messages()->where('client_message_key', 'MS7-R1-QA-CHAT-RECOVERY-INCOMING-V1')->exists()) {
            $communications->hideConversationForMe($directCustomer, $recovery);
        }

        $communications->sendMessage(
            $admin,
            $recovery,
            'New incoming message proving a deleted-for-me conversation can reappear without restoring hidden history.',
            'MS7-R1-QA-CHAT-RECOVERY-INCOMING-V1'
        );

        $rows = [
            [
                'OPEN TICKET', $openCustomer->email, $open->ticket_number, $open->status,
                $open->messages()->count(),
                $open->messages()->withCount('attachments')->get()->sum('attachments_count'),
                $communications->unreadCount($admin, $open),
                $openReplay->id === $open->id ? 'PASS' : 'FAIL',
            ],
            [
                'RESOLVED', $resolvedCustomer->email, $resolved->ticket_number, $resolved->fresh()->status,
                $resolved->messages()->count(),
                $resolved->messages()->withCount('attachments')->get()->sum('attachments_count'),
                $communications->unreadCount($resolvedCustomer, $resolved),
                $resolvedReplyReplay->id === $resolvedReply->id ? 'PASS' : 'FAIL',
            ],
            [
                'DIRECT CHAT', $directCustomer->email, 'PRIVATE', $direct->status,
                $direct->messages()->count(),
                $direct->messages()->withCount('attachments')->get()->sum('attachments_count'),
                $communications->unreadCount($directCustomer, $direct),
                $directReplay->id === $direct->id && $threadedReply->reply_to_message_id === $directInitial->id ? 'PASS' : 'FAIL',
            ],
        ];

        $this->newLine();
        $this->info('Persistent real Communication QA cases — MS7 R1 chat experience');
        $this->line('All @rcentz.test identities and case content are synthetic acceptance fixtures.');
        $this->table(['Case', 'Customer', 'Reference', 'Status', 'Messages', 'Files', 'Unread', 'Replay/Reply'], $rows);
        $this->line('Shared QA password: '.$password);
        $this->line('  '.$openCustomer->email.' → open support ticket + document');
        $this->line('  '.$resolvedCustomer->email.' → resolved ticket + admin reply');
        $this->line('  '.$directCustomer->email.' → private chat + media gallery + quoted reply + ghost edit/delete states');

        return self::SUCCESS;
    }

    private function qaUser(string $email, string $name, bool $admin, string $password): User
    {
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'is_admin' => $admin,
                'country' => 'Nigeria',
                'currency' => 'USD',
                'account_status' => 'active',
            ]
        );

        if (! $admin) {
            Wallet::query()->firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 1000, 'reserved_balance' => 0, 'currency' => 'USD']
            );
        }

        return $user->fresh();
    }
}
