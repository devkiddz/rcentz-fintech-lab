<x-admin-layout>
<div class="ui-page max-w-[1550px]">
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker">Communication authority</p>
            <h1 class="ui-heading">{{ $workspace === 'messages' ? 'Messages' : 'Support Tickets' }}</h1>
            <p class="ui-lead">
                {{ $workspace === 'messages'
                    ? 'Private customer conversations with persistent history, media, read state and messenger controls.'
                    : 'Customer support tickets with assignment, priority, status, attachments and persistent audit history.' }}
            </p>
        </div>
        <div class="ui-header-actions">
            @if($workspace === 'messages')
                <a href="{{ route('admin.support.index') }}" class="ui-btn ui-btn-secondary"><i data-lucide="life-buoy" class="h-4 w-4"></i>Support Tickets</a>
            @else
                <a href="{{ route('admin.messages.index') }}" class="ui-btn ui-btn-secondary"><i data-lucide="messages-square" class="h-4 w-4"></i>Messages</a>
            @endif
            <a href="{{ route($routeBase.'.index', $archived ? [] : ['archived' => 1]) }}" class="ui-btn ui-btn-secondary"><i data-lucide="{{ $archived ? 'inbox' : 'archive' }}" class="h-4 w-4"></i>{{ $archived ? 'Active inbox' : 'Archived' }}</a>
        </div>
    </section>

    @if(session('success'))<div class="mb-4 rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-xs text-emerald-600">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="mb-4 rounded-xl border border-red-500/20 bg-red-500/10 px-4 py-3 text-xs text-red-600">{{ $errors->first() }}</div>@endif

    <section class="ui-metric-grid xl:grid-cols-4">
        @foreach($metrics as [$label,$value])
            <div class="ui-metric-card"><p class="ui-kicker">{{ $label }}</p><p class="mt-2 text-2xl font-semibold">{{ $value }}</p></div>
        @endforeach
    </section>

    <section class="mt-4 {{ $workspace === 'messages' ? 'grid gap-4 xl:grid-cols-[minmax(0,1.35fr)_minmax(340px,.65fr)]' : '' }}">
        <article class="ui-panel overflow-hidden">
            <div class="border-b border-border p-4">
                <form method="GET" class="grid gap-2 {{ $workspace === 'support' ? 'md:grid-cols-[1fr_150px_150px_auto]' : 'md:grid-cols-[1fr_auto]' }}">
                    @if($archived)<input type="hidden" name="archived" value="1">@endif
                    <input class="ui-input w-full" name="search" value="{{ request('search') }}" placeholder="{{ $workspace === 'messages' ? 'Search customer, subject or message thread' : 'Search ticket, subject or customer' }}">
                    @if($workspace === 'support')
                        <select class="ui-input w-full" name="status"><option value="">All status</option>@foreach(['open','pending','resolved','closed'] as $status)<option value="{{ $status }}" @selected(request('status')===$status)>{{ ucfirst($status) }}</option>@endforeach</select>
                        <select class="ui-input w-full" name="priority"><option value="">All priority</option>@foreach(['low','normal','high','urgent'] as $priority)<option value="{{ $priority }}" @selected(request('priority')===$priority)>{{ ucfirst($priority) }}</option>@endforeach</select>
                    @endif
                    <button class="ui-btn ui-btn-primary">Filter</button>
                </form>
            </div>

            <div class="divide-y divide-border">
                @forelse($conversations as $conversation)
                    @php
                        $last = $conversation->visible_latest_message;
                        $preview = $last?->body ?: ($last ? 'Attachment' : 'No messages yet');
                        $customer = $conversation->participants
                            ->first(fn ($participant) => $participant->user && ! $participant->user->isAdmin())
                            ?->user;
                        $displayName = $customer?->name ?: $conversation->creator?->name ?: 'Customer';
                    @endphp
                    <a href="{{ route($routeBase.'.show', $conversation) }}" class="flex gap-3 px-4 py-4 transition hover:bg-muted/35 sm:px-5">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-red-500/10 text-xs font-bold text-red-600">{{ strtoupper(mb_substr($displayName, 0, 1)) }}</div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold">{{ $displayName }}</p>
                                    <p class="mt-0.5 truncate text-[10px] font-medium text-muted-foreground">{{ $conversation->subject }}</p>
                                    <p class="mt-1 truncate text-xs text-muted-foreground">{{ $preview }}</p>
                                </div>
                                <div class="shrink-0 text-right"><p class="text-[9px] text-muted-foreground">{{ $conversation->last_message_at?->format('H:i') }}</p>@if(($conversation->unread_count ?? 0)>0)<span class="mt-1 inline-flex min-w-5 justify-center rounded-full bg-red-500 px-1.5 py-0.5 text-[9px] font-bold text-white">{{ $conversation->unread_count }}</span>@endif</div>
                            </div>
                            <div class="mt-2 flex flex-wrap gap-2 text-[9px] uppercase tracking-[.1em] text-muted-foreground">
                                @if($workspace === 'support')
                                    <span>{{ $conversation->ticket_number }}</span><span>·</span><span>{{ ucfirst($conversation->status) }}</span><span>·</span><span>{{ ucfirst($conversation->priority) }}</span><span>·</span>
                                @else
                                    <span>Private</span><span>·</span>
                                @endif
                                <span>{{ $conversation->messages_count }} messages</span>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="px-6 py-16 text-center text-sm text-muted-foreground">No {{ $workspace === 'messages' ? 'private conversations' : 'support tickets' }} match this view.</div>
                @endforelse
            </div>
            @if($conversations->hasPages())<div class="border-t border-border p-4">{{ $conversations->links() }}</div>@endif
        </article>

        @if($workspace === 'messages')
            <article class="ui-panel overflow-hidden">
                <div class="border-b border-border px-5 py-4"><p class="ui-kicker">Private outreach</p><h2 class="mt-1 text-lg font-semibold">Start customer chat</h2><p class="mt-1 text-xs text-muted-foreground">Start a private message thread. Support cases belong in the separate Support Tickets workspace.</p></div>
                <form method="POST" action="{{ route('admin.messages.direct.store') }}" enctype="multipart/form-data" class="space-y-4 p-5">
                    @csrf
                    <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', (string) \Illuminate\Support\Str::uuid()) }}">
                    <div><label class="ui-label">Customer</label><select name="user_id" class="ui-input w-full" required><option value="">Select customer</option>@foreach($customers as $customer)<option value="{{ $customer->id }}" @selected((string)old('user_id')===(string)$customer->id)>{{ $customer->name }} · {{ $customer->email }}</option>@endforeach</select></div>
                    <div><label class="ui-label">Subject</label><input name="subject" class="ui-input w-full" value="{{ old('subject') }}" required></div>
                    <div><label class="ui-label">Message</label><textarea name="message" rows="5" class="ui-input w-full" required>{{ old('message') }}</textarea></div>
                    <label class="block cursor-pointer rounded-xl border border-dashed border-border bg-muted/20 p-4"><input type="file" name="attachments[]" multiple class="sr-only" accept=".jpg,.jpeg,.png,.webp,.pdf,.txt,.csv,.doc,.docx,.xls,.xlsx" data-file-input><span class="flex items-center gap-3"><i data-lucide="paperclip" class="h-4 w-4"></i><span class="text-xs"><strong>Attach files</strong><span class="ml-2 text-muted-foreground" data-file-label>Up to 8</span></span></span></label>
                    <button class="ui-btn ui-btn-primary w-full justify-center"><i data-lucide="message-square-plus" class="h-4 w-4"></i>Start private chat</button>
                </form>
            </article>
        @endif
    </section>
</div>
<script>
document.querySelectorAll('[data-file-input]').forEach((input)=>input.addEventListener('change',()=>{const label=input.closest('label')?.querySelector('[data-file-label]');if(label)label.textContent=input.files.length?`${input.files.length} selected`:'Up to 8';}));
</script>
</x-admin-layout>
