<x-user-layout>
    <x-slot name="header">Messages</x-slot>

    <div class="ui-page max-w-[1200px]">
        <section class="ui-page-header">
            <div>
                <p class="ui-kicker">Private communication</p>
                <h1 class="ui-heading">Messages</h1>
                <p class="ui-lead">Private conversations with the Rcentz team live here. Support tickets stay in the separate Support Center.</p>
            </div>
            <div class="ui-header-actions">
                <a href="{{ route('messages.index', $archived ? [] : ['archived' => 1]) }}" class="ui-btn ui-btn-secondary">
                    <i data-lucide="{{ $archived ? 'messages-square' : 'archive' }}" class="h-4 w-4"></i>
                    {{ $archived ? 'Active messages' : 'Archived' }}
                </a>
                <a href="{{ route('support.index') }}" class="ui-btn ui-btn-secondary">
                    <i data-lucide="life-buoy" class="h-4 w-4"></i>
                    Support
                </a>
            </div>
        </section>

        @if(session('success'))<div class="mb-4 rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-xs text-emerald-600">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="mb-4 rounded-xl border border-red-500/20 bg-red-500/10 px-4 py-3 text-xs text-red-600">{{ session('error') }}</div>@endif

        <section class="ui-panel overflow-hidden">
            <div class="flex items-center justify-between border-b border-border px-5 py-4">
                <div>
                    <p class="ui-kicker">{{ $archived ? 'Archived' : 'Inbox' }}</p>
                    <h2 class="mt-1 text-lg font-semibold">Private conversations</h2>
                </div>
                <span class="rounded-full border border-border px-2.5 py-1 text-[10px] font-semibold text-muted-foreground">{{ $conversations->total() }}</span>
            </div>

            <div class="divide-y divide-border">
                @forelse($conversations as $conversation)
                    @php
                        $last = $conversation->visible_latest_message;
                        $preview = $last?->body ?: ($last ? 'Attachment' : 'No messages yet');
                        $peer = $conversation->participants
                            ->first(fn ($participant) => $participant->user_id !== auth()->id() && $participant->user)
                            ?->user;
                        $displayName = $peer?->name ?: 'Rcentz Team';
                    @endphp
                    <a href="{{ route('messages.show', $conversation) }}" class="group flex gap-3 px-4 py-4 transition hover:bg-muted/35 sm:px-5">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-red-500/10 text-sm font-bold text-red-600 dark:text-red-400">
                            {{ strtoupper(mb_substr($displayName, 0, 1)) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-foreground">{{ $displayName }}</p>
                                    <p class="mt-0.5 truncate text-[10px] font-medium text-muted-foreground">{{ $conversation->subject }}</p>
                                    <p class="mt-1 truncate text-xs text-muted-foreground">{{ $preview }}</p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="text-[9px] text-muted-foreground">{{ $conversation->last_message_at?->format('H:i') }}</p>
                                    @if(($conversation->unread_count ?? 0) > 0)
                                        <span class="mt-1 inline-flex min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 py-0.5 text-[9px] font-bold text-white">{{ $conversation->unread_count }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="px-6 py-16 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-muted"><i data-lucide="messages-square" class="h-5 w-5 text-muted-foreground"></i></div>
                        <p class="mt-4 text-sm font-semibold">{{ $archived ? 'No archived messages' : 'No private messages yet' }}</p>
                        <p class="mx-auto mt-1 max-w-lg text-xs leading-5 text-muted-foreground">Private messages from the Rcentz team will appear here. For account or transaction help, use Support.</p>
                        @if(! $archived)<a href="{{ route('support.index') }}" class="ui-btn ui-btn-secondary mt-4"><i data-lucide="life-buoy" class="h-4 w-4"></i>Open Support</a>@endif
                    </div>
                @endforelse
            </div>

            @if($conversations->hasPages())
                <div class="border-t border-border p-4">{{ $conversations->links() }}</div>
            @endif
        </section>
    </div>
</x-user-layout>
