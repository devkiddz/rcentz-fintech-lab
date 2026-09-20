<?php

namespace App\Http\Controllers;

use App\Models\CommunicationAttachment;
use App\Models\CommunicationConversation;
use App\Models\CommunicationMessage;
use App\Services\CommunicationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MessageController extends Controller
{
    public function index(Request $request, CommunicationService $communications)
    {
        $user = $request->user();
        $archived = $request->boolean('archived');

        $conversations = CommunicationConversation::query()
            ->with(['creator', 'participants.user'])
            ->where('type', 'direct')
            ->whereHas('participants', function ($query) use ($user, $archived) {
                $query->where('user_id', $user->id)
                    ->whereNull('conversation_hidden_at')
                    ->when($archived,
                        fn ($participant) => $participant->whereNotNull('archived_at'),
                        fn ($participant) => $participant->whereNull('archived_at')
                    );
            })
            ->latest('last_message_at')
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        $conversations->getCollection()->each(function (CommunicationConversation $conversation) use ($user, $communications) {
            $conversation->unread_count = $communications->unreadCount($user, $conversation);
            $conversation->visible_latest_message = $conversation->messages()
                ->visibleTo($user)
                ->with('sender')
                ->latest('id')
                ->first();
        });

        return view('messages.index', compact('conversations', 'archived'));
    }

    public function show(Request $request, CommunicationConversation $conversation, CommunicationService $communications)
    {
        $this->assertDirect($conversation);
        $user = $request->user();
        abort_unless($communications->canAccess($user, $conversation), 403);

        $communications->markRead($user, $conversation);

        $conversation->load([
            'creator',
            'assignee',
            'participants.user',
            'messages' => fn ($query) => $query
                ->visibleTo($user)
                ->with(['sender', 'attachments', 'replyTo.sender', 'replyTo.attachments'])
                ->orderBy('id'),
            'events' => fn ($query) => $query->latest('id')->limit(20),
        ]);

        $participant = $conversation->participants->firstWhere('user_id', $user->id);
        $media = $communications->mediaFor($user, $conversation);
        $conversationList = $this->conversationList($user, $communications, $conversation);
        $workspace = 'messages';
        $routeBase = 'messages';

        return view('support.show', compact(
            'conversation',
            'participant',
            'media',
            'conversationList',
            'workspace',
            'routeBase'
        ));
    }

    public function reply(Request $request, CommunicationConversation $conversation, CommunicationService $communications)
    {
        $this->assertDirect($conversation);
        abort_unless($communications->canAccess($request->user(), $conversation), 403);

        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:10000', 'required_without:attachments'],
            'attachments' => ['nullable', 'array', 'max:8', 'required_without:message'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,txt,csv,doc,docx,xls,xlsx'],
            'reply_to_message_id' => ['nullable', 'integer', 'exists:communication_messages,id'],
            'idempotency_key' => ['required', 'string', 'max:120'],
        ]);

        $replyTo = ! empty($validated['reply_to_message_id'])
            ? CommunicationMessage::query()->findOrFail($validated['reply_to_message_id'])
            : null;

        try {
            $communications->sendMessage(
                $request->user(),
                $conversation,
                $validated['message'] ?? null,
                $validated['idempotency_key'],
                array_values(array_filter((array) $request->file('attachments', []))),
                $replyTo
            );
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Message sent.');
    }

    public function editMessage(
        Request $request,
        CommunicationConversation $conversation,
        CommunicationMessage $message,
        CommunicationService $communications
    ) {
        $this->assertDirect($conversation);
        abort_unless($message->conversation_id === $conversation->id, 404);

        $data = $request->validate([
            'message' => ['required', 'string', 'min:1', 'max:10000'],
        ]);

        try {
            $communications->editMessage($request->user(), $conversation, $message, $data['message']);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Message saved.');
    }

    public function archive(Request $request, CommunicationConversation $conversation, CommunicationService $communications)
    {
        $this->assertDirect($conversation);
        abort_unless($communications->canAccess($request->user(), $conversation), 403);

        $communications->setArchived($request->user(), $conversation, $request->boolean('archived'));

        return back()->with('success', $request->boolean('archived') ? 'Conversation archived.' : 'Conversation restored to messages.');
    }

    public function deleteForMe(Request $request, CommunicationConversation $conversation, CommunicationService $communications)
    {
        $this->assertDirect($conversation);
        abort_unless($communications->canAccess($request->user(), $conversation), 403);
        $communications->hideConversationForMe($request->user(), $conversation);

        return redirect()->route('messages.index')->with('success', 'Conversation deleted for you. New messages can make it appear again.');
    }

    public function deleteMessageForMe(Request $request, CommunicationConversation $conversation, CommunicationMessage $message, CommunicationService $communications)
    {
        $this->assertDirect($conversation);
        abort_unless($message->conversation_id === $conversation->id, 404);
        $communications->hideMessageForMe($request->user(), $conversation, $message);

        return back()->with('success', 'Message deleted for you.');
    }

    public function deleteMessageForEveryone(Request $request, CommunicationConversation $conversation, CommunicationMessage $message, CommunicationService $communications)
    {
        $this->assertDirect($conversation);
        abort_unless($message->conversation_id === $conversation->id, 404);

        try {
            $communications->deleteMessageForEveryone($request->user(), $conversation, $message);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Message deleted for everyone.');
    }

    public function attachment(Request $request, CommunicationAttachment $attachment, CommunicationService $communications)
    {
        $this->assertDirectAttachment($attachment);
        abort_unless($communications->canViewAttachment($request->user(), $attachment), 403);
        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);

        return Storage::disk($attachment->disk)->download(
            $attachment->path,
            $attachment->original_name,
            ['Content-Type' => $attachment->mime_type ?: 'application/octet-stream']
        );
    }

    public function previewAttachment(Request $request, CommunicationAttachment $attachment, CommunicationService $communications)
    {
        $this->assertDirectAttachment($attachment);
        abort_unless($communications->canViewAttachment($request->user(), $attachment), 403);
        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);

        return Storage::disk($attachment->disk)->response(
            $attachment->path,
            $attachment->original_name,
            ['Content-Type' => $attachment->mime_type ?: 'application/octet-stream'],
            'inline'
        );
    }

    private function conversationList($user, CommunicationService $communications, CommunicationConversation $current)
    {
        $list = CommunicationConversation::query()
            ->with(['creator', 'participants.user'])
            ->where('type', 'direct')
            ->whereHas('participants', fn ($query) => $query
                ->where('user_id', $user->id)
                ->whereNull('conversation_hidden_at')
                ->whereNull('archived_at'))
            ->latest('last_message_at')
            ->latest('id')
            ->limit(40)
            ->get();

        if (! $list->contains('id', $current->id)) {
            $list->prepend($current);
        }

        $list->each(function (CommunicationConversation $item) use ($user, $communications) {
            $item->unread_count = $communications->unreadCount($user, $item);
            $item->visible_latest_message = $item->messages()->visibleTo($user)->latest('id')->first();
        });

        return $list;
    }

    private function assertDirect(CommunicationConversation $conversation): void
    {
        abort_unless($conversation->type === 'direct', 404);
    }

    private function assertDirectAttachment(CommunicationAttachment $attachment): void
    {
        $attachment->loadMissing('message.conversation');
        abort_unless($attachment->message?->conversation?->type === 'direct', 404);
    }
}
