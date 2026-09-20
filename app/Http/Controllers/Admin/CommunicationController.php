<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommunicationAttachment;
use App\Models\CommunicationConversation;
use App\Models\CommunicationMessage;
use App\Models\User;
use App\Services\CommunicationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CommunicationController extends Controller
{
    public function messagesIndex(Request $request, CommunicationService $communications)
    {
        return $this->indexWorkspace($request, $communications, 'direct', 'admin.messages');
    }

    public function supportIndex(Request $request, CommunicationService $communications)
    {
        return $this->indexWorkspace($request, $communications, 'support_ticket', 'admin.support');
    }

    public function storeDirect(Request $request, CommunicationService $communications)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'subject' => ['required', 'string', 'max:180'],
            'message' => ['required', 'string', 'min:1', 'max:10000'],
            'attachments' => ['nullable', 'array', 'max:8'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,txt,csv,doc,docx,xls,xlsx'],
            'idempotency_key' => ['required', 'string', 'max:120'],
        ]);

        $customer = User::query()->where('is_admin', false)->findOrFail($data['user_id']);

        try {
            $conversation = $communications->createDirectConversation(
                Auth::user(),
                $customer,
                $data['subject'],
                $data['message'],
                $data['idempotency_key'],
                [],
                array_values(array_filter((array) $request->file('attachments', [])))
            );
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['communication' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.messages.show', $conversation)
            ->with('success', 'Private conversation created.');
    }

    public function showMessage(CommunicationConversation $conversation, CommunicationService $communications)
    {
        abort_unless($conversation->type === 'direct', 404);

        return $this->showWorkspace($conversation, $communications, 'admin.messages');
    }

    public function showSupport(CommunicationConversation $conversation, CommunicationService $communications)
    {
        abort_unless($conversation->type === 'support_ticket', 404);

        return $this->showWorkspace($conversation, $communications, 'admin.support');
    }

    public function replyMessage(Request $request, CommunicationConversation $conversation, CommunicationService $communications)
    {
        abort_unless($conversation->type === 'direct', 404);

        return $this->reply($request, $conversation, $communications);
    }

    public function replySupport(Request $request, CommunicationConversation $conversation, CommunicationService $communications)
    {
        abort_unless($conversation->type === 'support_ticket', 404);

        return $this->reply($request, $conversation, $communications);
    }

    public function editDirectMessage(
        Request $request,
        CommunicationConversation $conversation,
        CommunicationMessage $message,
        CommunicationService $communications
    ) {
        abort_unless($conversation->type === 'direct', 404);
        return $this->editMessage($request, $conversation, $message, $communications);
    }

    public function editSupportMessage(
        Request $request,
        CommunicationConversation $conversation,
        CommunicationMessage $message,
        CommunicationService $communications
    ) {
        abort_unless($conversation->type === 'support_ticket', 404);
        return $this->editMessage($request, $conversation, $message, $communications);
    }

    public function update(
        Request $request,
        CommunicationConversation $conversation,
        CommunicationService $communications
    ) {
        abort_unless($conversation->type === 'support_ticket', 404);

        $data = $request->validate([
            'status' => ['required', Rule::in(['open', 'pending', 'resolved', 'closed'])],
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'assigned_to_user_id' => ['nullable', 'exists:users,id'],
        ]);

        $assignee = null;
        if (! empty($data['assigned_to_user_id'])) {
            $assignee = User::query()->where('is_admin', true)->findOrFail($data['assigned_to_user_id']);
        }

        try {
            $communications->updateTicket(
                Auth::user(),
                $conversation,
                $data['status'],
                $data['priority'],
                $assignee
            );
        } catch (\Throwable $e) {
            return back()->withErrors(['communication' => $e->getMessage()]);
        }

        return back()->with('success', 'Ticket controls updated.');
    }

    public function archive(
        Request $request,
        CommunicationConversation $conversation,
        CommunicationService $communications
    ) {
        $this->assertWorkspaceConversation($conversation);
        $communications->setArchived(Auth::user(), $conversation, $request->boolean('archived'));

        return back()->with('success', $request->boolean('archived') ? 'Conversation archived.' : 'Conversation restored to inbox.');
    }

    public function deleteForMe(
        CommunicationConversation $conversation,
        CommunicationService $communications
    ) {
        $this->assertWorkspaceConversation($conversation);
        $communications->hideConversationForMe(Auth::user(), $conversation);

        $target = $conversation->type === 'support_ticket' ? 'admin.support.index' : 'admin.messages.index';

        return redirect()->route($target)->with('success', 'Conversation deleted from your inbox.');
    }

    public function deleteMessageForMe(
        CommunicationConversation $conversation,
        CommunicationMessage $message,
        CommunicationService $communications
    ) {
        $this->assertWorkspaceConversation($conversation);
        abort_unless($message->conversation_id === $conversation->id, 404);
        $communications->hideMessageForMe(Auth::user(), $conversation, $message);

        return back()->with('success', 'Message deleted for you.');
    }

    public function deleteMessageForEveryone(
        CommunicationConversation $conversation,
        CommunicationMessage $message,
        CommunicationService $communications
    ) {
        $this->assertWorkspaceConversation($conversation);
        abort_unless($message->conversation_id === $conversation->id, 404);

        try {
            $communications->deleteMessageForEveryone(Auth::user(), $conversation, $message);
        } catch (\Throwable $e) {
            return back()->withErrors(['communication' => $e->getMessage()]);
        }

        return back()->with('success', 'Message deleted for everyone.');
    }

    public function attachment(
        CommunicationAttachment $attachment,
        CommunicationService $communications
    ) {
        $attachment->loadMissing('message.conversation');
        abort_unless($attachment->message?->conversation, 404);
        $this->assertWorkspaceConversation($attachment->message->conversation);
        abort_unless($communications->canViewAttachment(Auth::user(), $attachment), 403);
        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);

        return Storage::disk($attachment->disk)->download(
            $attachment->path,
            $attachment->original_name,
            ['Content-Type' => $attachment->mime_type ?: 'application/octet-stream']
        );
    }

    public function previewAttachment(
        CommunicationAttachment $attachment,
        CommunicationService $communications
    ) {
        $attachment->loadMissing('message.conversation');
        abort_unless($attachment->message?->conversation, 404);
        $this->assertWorkspaceConversation($attachment->message->conversation);
        abort_unless($communications->canViewAttachment(Auth::user(), $attachment), 403);
        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);

        return Storage::disk($attachment->disk)->response(
            $attachment->path,
            $attachment->original_name,
            ['Content-Type' => $attachment->mime_type ?: 'application/octet-stream'],
            'inline'
        );
    }

    private function editMessage(
        Request $request,
        CommunicationConversation $conversation,
        CommunicationMessage $message,
        CommunicationService $communications
    ) {
        abort_unless($message->conversation_id === $conversation->id, 404);

        $data = $request->validate([
            'message' => ['required', 'string', 'min:1', 'max:10000'],
        ]);

        try {
            $communications->editMessage(Auth::user(), $conversation, $message, $data['message']);
        } catch (\Throwable $e) {
            return back()->withErrors(['communication' => $e->getMessage()]);
        }

        return back()->with('success', 'Message saved.');
    }

    private function assertWorkspaceConversation(CommunicationConversation $conversation): void
    {
        $routeName = (string) request()->route()?->getName();

        if (str_starts_with($routeName, 'admin.messages.')) {
            abort_unless($conversation->type === 'direct', 404);
        }

        if (str_starts_with($routeName, 'admin.support.')) {
            abort_unless($conversation->type === 'support_ticket', 404);
        }
    }

    private function indexWorkspace(
        Request $request,
        CommunicationService $communications,
        string $type,
        string $routeBase
    ) {
        $admin = Auth::user();
        $archived = $request->boolean('archived');
        $workspace = $type === 'direct' ? 'messages' : 'support';

        $query = CommunicationConversation::query()
            ->with(['creator', 'assignee', 'participants.user'])
            ->withCount(['messages', 'participants'])
            ->where('type', $type)
            ->whereDoesntHave('participants', fn ($participant) => $participant
                ->where('user_id', $admin->id)
                ->whereNotNull('conversation_hidden_at'))
            ->when($archived,
                fn ($builder) => $builder->whereHas('participants', fn ($participant) => $participant
                    ->where('user_id', $admin->id)
                    ->whereNotNull('archived_at')),
                fn ($builder) => $builder->whereDoesntHave('participants', fn ($participant) => $participant
                    ->where('user_id', $admin->id)
                    ->whereNotNull('archived_at'))
            )
            ->latest('last_message_at')
            ->latest('id');

        if ($workspace === 'support' && $request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($workspace === 'support' && $request->filled('priority')) {
            $query->where('priority', $request->string('priority')->toString());
        }

        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());
            $query->where(function ($builder) use ($search) {
                $builder->where('subject', 'like', "%{$search}%")
                    ->orWhere('ticket_number', 'like', "%{$search}%")
                    ->orWhereHas('creator', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('participants.user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $conversations = $query->paginate(30)->withQueryString();
        $conversations->getCollection()->each(function (CommunicationConversation $conversation) use ($admin, $communications) {
            $conversation->unread_count = $communications->unreadCount($admin, $conversation);
            $conversation->visible_latest_message = $conversation->messages()
                ->visibleTo($admin)
                ->latest('id')
                ->first();
        });

        if ($workspace === 'support') {
            $metrics = [
                ['Open', CommunicationConversation::query()->supportTickets()->where('status', 'open')->count()],
                ['Pending', CommunicationConversation::query()->supportTickets()->where('status', 'pending')->count()],
                ['Resolved', CommunicationConversation::query()->supportTickets()->where('status', 'resolved')->count()],
                ['Unassigned', CommunicationConversation::query()->supportTickets()->whereNull('assigned_to_user_id')->count()],
            ];
        } else {
            $directQuery = CommunicationConversation::query()->where('type', 'direct');
            $metrics = [
                ['Conversations', (clone $directQuery)->count()],
                ['Open', (clone $directQuery)->where('status', 'open')->count()],
                ['Messages', CommunicationMessage::query()->whereHas('conversation', fn ($q) => $q->where('type', 'direct'))->count()],
                ['Customers', DB::table('communication_participants')
                    ->join('users', 'users.id', '=', 'communication_participants.user_id')
                    ->join('communication_conversations', 'communication_conversations.id', '=', 'communication_participants.conversation_id')
                    ->where('communication_conversations.type', 'direct')
                    ->where('users.is_admin', false)
                    ->distinct()
                    ->count('users.id')],
            ];
        }

        $customers = $workspace === 'messages'
            ? User::query()->where('is_admin', false)->orderBy('name')->get(['id', 'name', 'email'])
            : collect();

        return view('admin.communications.index', compact(
            'conversations',
            'metrics',
            'customers',
            'archived',
            'workspace',
            'routeBase'
        ));
    }

    private function showWorkspace(
        CommunicationConversation $conversation,
        CommunicationService $communications,
        string $routeBase
    ) {
        $admin = Auth::user();
        $workspace = $conversation->type === 'direct' ? 'messages' : 'support';

        $communications->markRead($admin, $conversation);

        $conversation->load([
            'creator',
            'assignee',
            'participants.user',
            'messages' => fn ($query) => $query
                ->visibleTo($admin)
                ->with(['sender', 'attachments', 'replyTo.sender', 'replyTo.attachments'])
                ->orderBy('id'),
            'events' => fn ($query) => $query->with('actor')->latest('id')->limit(40),
        ]);

        $admins = User::query()->where('is_admin', true)->orderBy('name')->get(['id', 'name', 'email']);
        $participant = $conversation->participants->firstWhere('user_id', $admin->id);
        $media = $communications->mediaFor($admin, $conversation);
        $conversationList = $this->conversationList($admin, $communications, $conversation, $conversation->type);

        return view('admin.communications.show', compact(
            'conversation',
            'admins',
            'participant',
            'media',
            'conversationList',
            'workspace',
            'routeBase'
        ));
    }

    private function reply(
        Request $request,
        CommunicationConversation $conversation,
        CommunicationService $communications
    ) {
        $data = $request->validate([
            'message' => ['nullable', 'string', 'max:10000', 'required_without:attachments'],
            'attachments' => ['nullable', 'array', 'max:8', 'required_without:message'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,txt,csv,doc,docx,xls,xlsx'],
            'reply_to_message_id' => ['nullable', 'integer', 'exists:communication_messages,id'],
            'idempotency_key' => ['required', 'string', 'max:120'],
        ]);

        $replyTo = ! empty($data['reply_to_message_id'])
            ? CommunicationMessage::query()->findOrFail($data['reply_to_message_id'])
            : null;

        try {
            $communications->sendMessage(
                Auth::user(),
                $conversation,
                $data['message'] ?? null,
                $data['idempotency_key'],
                array_values(array_filter((array) $request->file('attachments', []))),
                $replyTo
            );
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['communication' => $e->getMessage()]);
        }

        return back()->with('success', 'Reply sent.');
    }

    private function conversationList(
        User $admin,
        CommunicationService $communications,
        CommunicationConversation $current,
        string $type
    ) {
        $list = CommunicationConversation::query()
            ->with(['creator', 'participants.user'])
            ->where('type', $type)
            ->whereDoesntHave('participants', fn ($participant) => $participant
                ->where('user_id', $admin->id)
                ->where(function ($state) {
                    $state->whereNotNull('conversation_hidden_at')
                        ->orWhereNotNull('archived_at');
                }))
            ->latest('last_message_at')
            ->latest('id')
            ->limit(50)
            ->get();

        if (! $list->contains('id', $current->id)) {
            $list->prepend($current);
        }

        $list->each(function (CommunicationConversation $item) use ($admin, $communications) {
            $item->unread_count = $communications->unreadCount($admin, $item);
            $item->visible_latest_message = $item->messages()->visibleTo($admin)->latest('id')->first();
        });

        return $list;
    }
}
