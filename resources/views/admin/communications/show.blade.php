<x-admin-layout>
<div class="ui-page max-w-[1750px]">
    @if(session('success'))<div class="mb-3 rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-xs text-emerald-600">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="mb-3 rounded-xl border border-red-500/20 bg-red-500/10 px-4 py-3 text-xs text-red-600">{{ $errors->first() }}</div>@endif

    @php
        $activeCustomer = $conversation->participants
            ->first(fn ($participant) => $participant->user && ! $participant->user->isAdmin())
            ?->user;
        $activeDisplayName = $activeCustomer?->name ?: $conversation->creator?->name ?: 'Customer';
    @endphp

    <style>
        .rcentz-admin-messenger-shell {
            position: relative;
            min-height: 700px;
        }
        .rcentz-admin-inbox {
            background: color-mix(in srgb, currentColor 2%, transparent);
        }
        .rcentz-admin-info {
            display: none;
        }
        .rcentz-chat-menu {
            min-width: 14.5rem;
            width: max-content;
            max-width: calc(100vw - 2rem);
            white-space: nowrap;
            z-index: 70;
        }
        .rcentz-chat-menu button,
        .rcentz-chat-menu a {
            white-space: nowrap;
        }

        /* MS7 R5: compiled-CSS-safe Telegram-style per-message controls. */
        .rcentz-message-row { position: relative; }
        .rcentz-message-tools {
            position: absolute;
            top: .25rem;
            z-index: 80;
            display: flex;
            align-items: center;
            gap: .3rem;
            opacity: .22;
            transition: opacity .14s ease, transform .14s ease;
        }
        .rcentz-message-row:hover .rcentz-message-tools,
        .rcentz-message-tools:focus-within { opacity: 1; }
        .rcentz-message-tools-left { right: calc(100% + .45rem); }
        .rcentz-message-tools-right { left: calc(100% + .45rem); }
        .rcentz-message-tool {
            display: inline-flex;
            width: 1.8rem;
            height: 1.8rem;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            border: 1px solid color-mix(in srgb, currentColor 18%, transparent);
            border-radius: 9999px;
            background: color-mix(in srgb, var(--background, #0b1018) 94%, transparent);
            color: currentColor;
            box-shadow: 0 5px 18px rgba(0,0,0,.18);
            cursor: pointer;
        }
        .rcentz-message-tool:hover,
        .rcentz-message-tool:focus-visible { background: color-mix(in srgb, currentColor 9%, transparent); outline: none; }
        .rcentz-message-actions { position: relative; }
        .rcentz-message-actions > summary { list-style: none; }
        .rcentz-message-actions > summary::-webkit-details-marker { display: none; }
        .rcentz-message-action-menu {
            position: absolute;
            top: calc(100% + .4rem);
            min-width: 14.75rem;
            width: max-content;
            max-width: calc(100vw - 2rem);
            z-index: 120;
            white-space: nowrap;
        }
        .rcentz-message-tools-left .rcentz-message-action-menu { right: 0; }
        .rcentz-message-tools-right .rcentz-message-action-menu { left: 0; }
        .rcentz-message-action-menu button { white-space: nowrap; }
        @media (hover: none), (max-width: 767px) {
            .rcentz-message-tools { opacity: .72; }
            .rcentz-message-tools .rcentz-quick-reply { display: none; }
        }
        .rcentz-admin-inbox-backdrop {
            display: none;
        }
        @media (max-width: 1023px) {
            .rcentz-admin-inbox {
                position: absolute;
                inset: 0 auto 0 0;
                z-index: 55;
                display: flex;
                width: min(88vw, 340px);
                transform: translateX(-105%);
                transition: transform .2s ease;
                box-shadow: 18px 0 50px rgba(0,0,0,.25);
            }
            .rcentz-admin-messenger-shell[data-inbox-open="true"] .rcentz-admin-inbox {
                transform: translateX(0);
            }
            .rcentz-admin-messenger-shell[data-inbox-open="true"] .rcentz-admin-inbox-backdrop {
                position: absolute;
                inset: 0;
                z-index: 50;
                display: block;
                background: rgba(0,0,0,.45);
            }
        }
        @media (min-width: 1024px) {
            .rcentz-admin-messenger-shell {
                display: grid;
                height: calc(100vh - 135px);
                grid-template-columns: 320px minmax(0,1fr);
            }
            .rcentz-admin-inbox {
                display: flex;
                min-width: 0;
                transition: opacity .16s ease, width .16s ease;
            }
            .rcentz-admin-messenger-shell[data-inbox-collapsed="true"] {
                grid-template-columns: 0 minmax(0,1fr);
            }
            .rcentz-admin-messenger-shell[data-inbox-collapsed="true"] .rcentz-admin-inbox {
                width: 0;
                overflow: hidden;
                border-right: 0;
                opacity: 0;
                pointer-events: none;
            }
        }
        @media (min-width: 1536px) {
            .rcentz-admin-messenger-shell {
                grid-template-columns: 320px minmax(0,1fr) 330px;
            }
            .rcentz-admin-messenger-shell[data-inbox-collapsed="true"] {
                grid-template-columns: 0 minmax(0,1fr) 330px;
            }
            .rcentz-admin-info {
                display: flex;
            }
        }
    </style>
    <section id="admin-messenger-shell" class="rcentz-admin-messenger-shell overflow-hidden rounded-2xl border border-border bg-background shadow-sm" data-inbox-open="false" data-inbox-collapsed="false">
        <button type="button" class="rcentz-admin-inbox-backdrop" data-admin-inbox-close aria-label="Close inbox"></button>
        <aside id="admin-inbox-panel" class="rcentz-admin-inbox min-h-0 border-r border-border bg-muted/10 flex-col">
            <div class="flex items-center justify-between border-b border-border p-4"><div><p class="ui-kicker">{{ $workspace === 'messages' ? 'Messages' : 'Support' }}</p><p class="mt-1 text-sm font-semibold">{{ $workspace === 'messages' ? 'Private conversations' : 'Support tickets' }}</p></div><div class="flex items-center gap-1"><a href="{{ route($routeBase.'.index') }}" class="flex h-9 w-9 items-center justify-center rounded-full border border-border hover:bg-muted" title="Open full inbox"><i data-lucide="inbox" class="h-4 w-4"></i></a><button type="button" class="flex h-9 w-9 items-center justify-center rounded-full border border-border hover:bg-muted lg:hidden" data-admin-inbox-close title="Close inbox"><i data-lucide="x" class="h-4 w-4"></i></button></div></div>
            <div class="min-h-0 flex-1 overflow-y-auto">
                @foreach($conversationList as $chat)
                    @php
                        $last = $chat->visible_latest_message;
                        $preview = $last?->body ?: ($last ? 'Attachment' : 'No messages yet');
                        $chatCustomer = $chat->participants
                            ->first(fn ($participant) => $participant->user && ! $participant->user->isAdmin())
                            ?->user;
                        $chatDisplayName = $chatCustomer?->name ?: $chat->creator?->name ?: 'Customer';
                    @endphp
                    <a href="{{ route($routeBase.'.show',$chat) }}" class="flex gap-3 border-b border-border/60 px-4 py-3 {{ $chat->id===$conversation->id?'bg-red-500/[.07]':'hover:bg-muted/35' }}">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-500/10 text-xs font-bold text-red-600">{{ strtoupper(mb_substr($chatDisplayName,0,1)) }}</div>
                        <div class="min-w-0 flex-1">
                            <div class="flex justify-between gap-2"><p class="truncate text-xs font-semibold">{{ $chatDisplayName }}</p><span class="text-[9px] text-muted-foreground">{{ $chat->last_message_at?->format('H:i') }}</span></div>
                            <p class="mt-0.5 truncate text-[9px] font-medium text-muted-foreground">{{ $chat->subject }} · {{ $chat->ticket_number ?: 'Private' }}</p>
                            <div class="mt-1 flex gap-2"><p class="min-w-0 flex-1 truncate text-[10px] text-muted-foreground">{{ $preview }}</p>@if(($chat->unread_count??0)>0)<span class="rounded-full bg-red-500 px-1.5 py-0.5 text-[8px] font-bold text-white">{{ $chat->unread_count }}</span>@endif</div>
                        </div>
                    </a>
                @endforeach
            </div>
        </aside>

        <main class="flex min-h-[700px] min-w-0 flex-col bg-muted/5 lg:min-h-0">
            <header class="flex items-center justify-between gap-3 border-b border-border bg-background/95 px-4 py-3 backdrop-blur sm:px-5">
                <div class="flex min-w-0 items-center gap-3">
                    <button type="button" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full hover:bg-muted" data-admin-inbox-toggle title="Toggle inbox"><i data-lucide="panel-left" class="h-4 w-4"></i></button>
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-500/10 text-xs font-bold text-red-600">{{ strtoupper(mb_substr($activeDisplayName,0,1)) }}</div>
                    <div class="min-w-0">
                        <h1 class="truncate text-sm font-semibold">{{ $activeDisplayName }}</h1>
                        <p class="mt-0.5 truncate text-[10px] text-muted-foreground">{{ $workspace === 'messages' ? $conversation->subject.' · Private conversation' : $conversation->subject.' · '.$conversation->ticket_number.' · '.ucfirst($conversation->status) }}</p>
                    </div>
                </div>
                <details class="relative"><summary class="flex h-9 w-9 cursor-pointer list-none items-center justify-center rounded-full hover:bg-muted"><i data-lucide="more-vertical" class="h-4 w-4"></i></summary><div class="rcentz-chat-menu absolute right-0 mt-2 rounded-xl border border-border bg-background p-1.5 shadow-2xl"><form method="POST" action="{{ route($routeBase.'.archive',$conversation) }}">@csrf @method('PATCH')<input type="hidden" name="archived" value="{{ $participant?->archived_at ? 0 : 1 }}"><button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-xs hover:bg-muted"><i data-lucide="archive" class="h-4 w-4"></i>{{ $participant?->archived_at?'Move to inbox':'Archive chat' }}</button></form><form method="POST" action="{{ route($routeBase.'.delete-for-me',$conversation) }}" onsubmit="return confirm('Delete this conversation from your admin inbox?');">@csrf @method('DELETE')<button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-xs text-red-600 hover:bg-red-500/10"><i data-lucide="trash-2" class="h-4 w-4"></i>Delete chat for me</button></form></div></details>
            </header>

            <div id="admin-chat-scroll" class="min-h-0 flex-1 space-y-2 overflow-y-auto px-3 py-5 sm:px-5">
                @foreach($conversation->messages as $message)
                    @php
                        $mine=$message->sender_user_id===auth()->id();
                        $images=$message->attachments->filter(fn($a)=>!$a->revoked_at&&$a->is_image);
                        $files=$message->attachments->filter(fn($a)=>!$a->revoked_at&&!$a->is_image);
                        $readByOther=$mine&&$conversation->participants->where('user_id','!=',auth()->id())->contains(fn($p)=>(int)($p->last_read_message_id??0)>=$message->id);
                        $replyVisible=$message->replyTo&&!$message->replyTo->deleted_for_everyone_at; $replyText=$replyVisible?($message->replyTo->body?:'Attachment'):null;
                    @endphp
                    <div id="message-{{ $message->id }}" data-message-mine="{{ $mine ? '1' : '0' }}" class="rcentz-message-row flex {{ $mine?'justify-end':'justify-start' }}">
                        <div class="relative max-w-[88%] sm:max-w-[76%]">
                            <div class="rounded-2xl px-3.5 py-2.5 shadow-sm {{ $mine?'rounded-br-md bg-red-500/[.10] ring-1 ring-red-500/15':'rounded-bl-md bg-background ring-1 ring-border' }}">
                                @if($replyVisible)<button type="button" onclick="document.getElementById('message-{{ $message->replyTo->id }}')?.scrollIntoView({behavior:'smooth',block:'center'})" class="mb-2 block w-full rounded-lg border-l-2 border-red-500 bg-muted/35 px-3 py-2 text-left"><span class="block text-[9px] font-semibold text-red-600">{{ $message->replyTo->sender?->name??'Message' }}</span><span class="mt-0.5 block truncate text-[10px] text-muted-foreground">{{ $replyText }}</span></button>@endif
                                    @if($images->isNotEmpty())<div class="mb-2 grid gap-1 overflow-hidden rounded-xl {{ $images->count()>1?'grid-cols-2':'grid-cols-1' }}">@foreach($images as $attachment)<button type="button" class="overflow-hidden bg-muted" data-media-preview="{{ route($routeBase.'.attachments.preview',$attachment) }}" data-media-name="{{ $attachment->original_name }}"><img src="{{ route($routeBase.'.attachments.preview',$attachment) }}" alt="{{ $attachment->original_name }}" class="h-44 w-full object-cover sm:h-52"></button>@endforeach</div>@endif
                                    @if($message->body!=='')<p class="whitespace-pre-wrap break-words text-[13px] leading-5">{{ $message->body }}</p>@endif
                                    @if($files->isNotEmpty())<div class="mt-2 space-y-1.5">@foreach($files as $attachment)<a href="{{ route($routeBase.'.attachments.download',$attachment) }}" class="flex items-center gap-2 rounded-lg bg-background/70 px-3 py-2 text-xs ring-1 ring-border/70"><span class="flex h-8 w-8 items-center justify-center rounded-lg bg-muted"><i data-lucide="file-text" class="h-4 w-4"></i></span><span class="min-w-0 flex-1"><span class="block truncate font-medium">{{ $attachment->original_name }}</span><span class="text-[9px] text-muted-foreground">{{ number_format($attachment->size_bytes/1024,1) }} KB</span></span><i data-lucide="download" class="h-3.5 w-3.5"></i></a>@endforeach</div>@endif
                                <div class="mt-1.5 flex items-center justify-end gap-1 text-[8px] text-muted-foreground"><span>{{ $message->sent_at?->format('H:i') }}</span>@if($mine)<i data-lucide="{{ $readByOther?'check-check':'check' }}" class="h-3 w-3 {{ $readByOther?'text-sky-500':'' }}"></i>@endif</div>
                            </div>
                            <div class="rcentz-message-tools {{ $mine ? 'rcentz-message-tools-left' : 'rcentz-message-tools-right' }}" aria-label="Message actions">
                                <button type="button" class="rcentz-message-tool rcentz-quick-reply" data-reply-id="{{ $message->id }}" data-reply-name="{{ $message->sender?->name }}" data-reply-text="{{ \Illuminate\Support\Str::limit($message->body ?: 'Attachment',90) }}" title="Reply" aria-label="Reply"><i data-lucide="reply" class="h-3.5 w-3.5"></i></button>
                                <details class="rcentz-message-actions">
                                    <summary class="rcentz-message-tool" title="Message actions" aria-label="Message actions"><i data-lucide="more-vertical" class="h-3.5 w-3.5"></i></summary>
                                    <div class="rcentz-message-action-menu rcentz-chat-menu rounded-xl border border-border bg-background p-1.5 shadow-2xl">
                                        <button type="button" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-xs hover:bg-muted" data-reply-id="{{ $message->id }}" data-reply-name="{{ $message->sender?->name }}" data-reply-text="{{ \Illuminate\Support\Str::limit($message->body ?: 'Attachment',90) }}"><i data-lucide="reply" class="h-4 w-4"></i>Reply</button>
                                        @if($mine&&$message->body!=='')<button type="button" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-xs hover:bg-muted" data-edit-url="{{ route($routeBase.'.messages.edit',[$conversation,$message]) }}" data-edit-text="{{ $message->body }}"><i data-lucide="pencil" class="h-4 w-4"></i>Edit</button>@endif
                                        <form method="POST" action="{{ route($routeBase.'.messages.delete-for-me',[$conversation,$message]) }}">@csrf @method('DELETE')<button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-xs hover:bg-muted"><i data-lucide="eye-off" class="h-4 w-4"></i>Delete for me</button></form>
                                        @if($mine||auth()->user()->isAdmin())<form method="POST" action="{{ route($routeBase.'.messages.delete-for-everyone',[$conversation,$message]) }}" onsubmit="return confirm('Delete this message for everyone? It will disappear completely and attached files will be removed.');">@csrf @method('DELETE')<button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-xs text-red-600 hover:bg-red-500/10"><i data-lucide="trash-2" class="h-4 w-4"></i>Delete for everyone</button></form>@endif
                                    </div>
                                </details>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($conversation->status!=='closed')
                <form method="POST" action="{{ route($routeBase.'.reply',$conversation) }}" enctype="multipart/form-data" class="border-t border-border bg-background p-3 sm:p-4" id="admin-chat-composer" data-reply-action="{{ route($routeBase.'.reply',$conversation) }}">
                    @csrf
                    <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key',(string)\Illuminate\Support\Str::uuid()) }}">
                    <input type="hidden" name="reply_to_message_id" id="admin-reply-to-message-id" value="{{ old('reply_to_message_id') }}">
                    <div id="admin-edit-preview" class="mb-2 hidden items-center gap-3 rounded-xl border-l-2 border-sky-500 bg-muted/35 px-3 py-2"><div class="min-w-0 flex-1"><p class="text-[9px] font-semibold text-sky-600">Editing message</p><p class="truncate text-[10px] text-muted-foreground">Changes save silently in the conversation.</p></div><button type="button" id="admin-clear-edit" class="flex h-7 w-7 items-center justify-center rounded-full hover:bg-muted"><i data-lucide="x" class="h-3.5 w-3.5"></i></button></div>
                    <div id="admin-reply-preview" class="mb-2 hidden items-center gap-3 rounded-xl border-l-2 border-red-500 bg-muted/35 px-3 py-2"><div class="min-w-0 flex-1"><p id="admin-reply-preview-name" class="text-[9px] font-semibold text-red-600"></p><p id="admin-reply-preview-text" class="truncate text-[10px] text-muted-foreground"></p></div><button type="button" id="admin-clear-reply" class="flex h-7 w-7 items-center justify-center rounded-full hover:bg-muted"><i data-lucide="x" class="h-3.5 w-3.5"></i></button></div>
                    <div class="flex items-end gap-2"><label class="flex h-10 w-10 shrink-0 cursor-pointer items-center justify-center rounded-full hover:bg-muted"><input type="file" name="attachments[]" multiple class="sr-only" id="admin-chat-files" accept=".jpg,.jpeg,.png,.webp,.pdf,.txt,.csv,.doc,.docx,.xls,.xlsx"><i data-lucide="paperclip" class="h-5 w-5 text-muted-foreground"></i></label><div class="min-w-0 flex-1 rounded-2xl border border-border bg-muted/20 px-4 py-2"><textarea name="message" rows="1" class="max-h-32 min-h-6 w-full resize-none bg-transparent text-sm outline-none" placeholder="Message customer...">{{ old('message') }}</textarea><p id="admin-chat-file-count" class="hidden pt-1 text-[9px] text-muted-foreground"></p></div><button class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-600 text-white hover:bg-red-700"><i data-lucide="send" class="h-4 w-4"></i></button></div>
                </form>
            @endif
        </main>

        <aside class="rcentz-admin-info min-h-0 border-l border-border bg-background flex-col">
            <div class="min-h-0 flex-1 space-y-5 overflow-y-auto p-4">
                @if($conversation->type==='support_ticket')
                    <section class="rounded-xl border border-border p-4"><p class="ui-kicker">Ticket controls</p><form method="POST" action="{{ route($routeBase.'.update',$conversation) }}" class="mt-3 space-y-3">@csrf @method('PATCH')<div><label class="ui-label">Status</label><select name="status" class="ui-input w-full">@foreach(['open','pending','resolved','closed'] as $status)<option value="{{ $status }}" @selected($conversation->status===$status)>{{ ucfirst($status) }}</option>@endforeach</select></div><div><label class="ui-label">Priority</label><select name="priority" class="ui-input w-full">@foreach(['low','normal','high','urgent'] as $priority)<option value="{{ $priority }}" @selected($conversation->priority===$priority)>{{ ucfirst($priority) }}</option>@endforeach</select></div><div><label class="ui-label">Assigned admin</label><select name="assigned_to_user_id" class="ui-input w-full"><option value="">Unassigned</option>@foreach($admins as $admin)<option value="{{ $admin->id }}" @selected($conversation->assigned_to_user_id===$admin->id)>{{ $admin->name }}</option>@endforeach</select></div><button class="ui-btn ui-btn-primary w-full justify-center">Update ticket</button></form></section>
                @endif
                <section><div class="flex items-center justify-between"><p class="ui-kicker">Media</p><span class="text-[9px] text-muted-foreground">{{ $media['images']->count() }}</span></div>@if($media['images']->isEmpty())<p class="mt-2 text-[10px] text-muted-foreground">No shared images.</p>@else<div class="mt-3 grid grid-cols-3 gap-1.5">@foreach($media['images'] as $attachment)<button type="button" class="aspect-square overflow-hidden rounded-lg bg-muted" data-media-preview="{{ route($routeBase.'.attachments.preview',$attachment) }}" data-media-name="{{ $attachment->original_name }}"><img src="{{ route($routeBase.'.attachments.preview',$attachment) }}" class="h-full w-full object-cover" alt="{{ $attachment->original_name }}"></button>@endforeach</div>@endif</section>
                <section class="border-t border-border pt-4"><div class="flex items-center justify-between"><p class="ui-kicker">Files</p><span class="text-[9px] text-muted-foreground">{{ $media['files']->count() }}</span></div><div class="mt-2 space-y-2">@forelse($media['files'] as $attachment)<a href="{{ route($routeBase.'.attachments.download',$attachment) }}" class="flex items-center gap-2 rounded-xl border border-border p-2.5 hover:bg-muted/30"><i data-lucide="file" class="h-4 w-4"></i><span class="min-w-0 flex-1 truncate text-[10px] font-semibold">{{ $attachment->original_name }}</span></a>@empty<p class="text-[10px] text-muted-foreground">No shared files.</p>@endforelse</div></section>
                <section class="border-t border-border pt-4"><p class="ui-kicker">Participants</p><div class="mt-2 space-y-2">@foreach($conversation->participants as $p)<div class="rounded-xl border border-border p-3"><p class="text-xs font-semibold">{{ $p->user?->name }}</p><p class="mt-1 text-[9px] text-muted-foreground">{{ ucfirst($p->role) }} · {{ $p->last_read_at?->format('M j H:i')?:'Unread' }}</p></div>@endforeach</div></section>
                <section class="border-t border-border pt-4"><p class="ui-kicker">Recent activity</p><div class="mt-2 space-y-2">@foreach($conversation->events->take(8) as $event)<div><p class="text-[10px] font-semibold">{{ ucwords(str_replace('.',' ',$event->type)) }}</p><p class="text-[8px] text-muted-foreground">{{ $event->actor?->name??'System' }} · {{ $event->occurred_at?->format('M j H:i') }}</p></div>@endforeach</div></section>
            </div>
        </aside>
    </section>
</div>

<div id="admin-media-lightbox" class="fixed inset-0 z-[90] hidden items-center justify-center bg-black/90 p-4"><button type="button" id="admin-media-lightbox-close" class="absolute right-5 top-5 flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white"><i data-lucide="x" class="h-5 w-5"></i></button><div class="max-h-full max-w-5xl text-center"><img id="admin-media-lightbox-image" class="max-h-[82vh] max-w-full rounded-xl object-contain" alt=""><p id="admin-media-lightbox-name" class="mt-3 text-xs text-white/70"></p></div></div>
<script>
(()=>{const shell=document.getElementById('admin-messenger-shell');const toggleInbox=()=>{if(!shell)return;if(window.matchMedia('(min-width: 1024px)').matches){shell.dataset.inboxCollapsed=shell.dataset.inboxCollapsed==='true'?'false':'true';}else{shell.dataset.inboxOpen=shell.dataset.inboxOpen==='true'?'false':'true';}};document.querySelectorAll('[data-admin-inbox-toggle]').forEach((button)=>button.addEventListener('click',toggleInbox));document.querySelectorAll('[data-admin-inbox-close]').forEach((button)=>button.addEventListener('click',()=>{if(shell)shell.dataset.inboxOpen='false';}));const scroll=document.getElementById('admin-chat-scroll');if(scroll)scroll.scrollTop=scroll.scrollHeight;const form=document.getElementById('admin-chat-composer'),textarea=form?.querySelector('textarea[name="message"]'),files=document.getElementById('admin-chat-files'),editPreview=document.getElementById('admin-edit-preview');const resetEdit=(clearText=true)=>{if(!form)return;form.action=form.dataset.replyAction;form.querySelector('input[name="_method"]')?.remove();editPreview?.classList.add('hidden');editPreview?.classList.remove('flex');if(files)files.disabled=false;if(clearText&&textarea)textarea.value='';};document.querySelectorAll('[data-edit-url]').forEach((b)=>b.addEventListener('click',()=>{resetEdit(false);document.getElementById('admin-reply-to-message-id').value='';document.getElementById('admin-reply-preview').classList.add('hidden');form.action=b.dataset.editUrl;const method=document.createElement('input');method.type='hidden';method.name='_method';method.value='PATCH';form.appendChild(method);if(textarea)textarea.value=b.dataset.editText||'';if(files)files.disabled=true;editPreview?.classList.remove('hidden');editPreview?.classList.add('flex');textarea?.focus();}));document.getElementById('admin-clear-edit')?.addEventListener('click',()=>resetEdit(true));const replyInput=document.getElementById('admin-reply-to-message-id'),preview=document.getElementById('admin-reply-preview'),name=document.getElementById('admin-reply-preview-name'),text=document.getElementById('admin-reply-preview-text');document.querySelectorAll('[data-reply-id]').forEach((b)=>b.addEventListener('click',()=>{resetEdit(true);replyInput.value=b.dataset.replyId;name.textContent=b.dataset.replyName||'Message';text.textContent=b.dataset.replyText||'Attachment';preview.classList.remove('hidden');preview.classList.add('flex');textarea?.focus();}));document.getElementById('admin-clear-reply')?.addEventListener('click',()=>{replyInput.value='';preview.classList.add('hidden');preview.classList.remove('flex');});const fileCount=document.getElementById('admin-chat-file-count');files?.addEventListener('change',()=>{if(!files.files.length){fileCount.classList.add('hidden');return;}fileCount.textContent=`${files.files.length} attachment${files.files.length===1?'':'s'} selected`;fileCount.classList.remove('hidden');});const box=document.getElementById('admin-media-lightbox'),img=document.getElementById('admin-media-lightbox-image'),label=document.getElementById('admin-media-lightbox-name');document.querySelectorAll('[data-media-preview]').forEach((b)=>b.addEventListener('click',()=>{img.src=b.dataset.mediaPreview;img.alt=b.dataset.mediaName||'Shared image';label.textContent=b.dataset.mediaName||'';box.classList.remove('hidden');box.classList.add('flex');}));const close=()=>{box.classList.add('hidden');box.classList.remove('flex');img.src='';};document.getElementById('admin-media-lightbox-close')?.addEventListener('click',close);box?.addEventListener('click',(e)=>{if(e.target===box)close();});})();
</script>

{{-- RCENTZ_MS7_R6_MESSAGE_ACTION_SCROLL_REPAIR --}}
<style id="rcentz-ms7-r6-message-action-scroll-repair">
    /*
     * MS7 R6: keep message actions inside the message row so adjacent panes,
     * overflow containers and mobile viewports cannot clip them.
     */
    [data-rcentz-message-row="r6"] {
        position: relative !important;
        box-sizing: border-box !important;
        padding-bottom: 2.15rem !important;
        overflow: visible !important;
    }

    [data-rcentz-message-row="r6"] > .rcentz-message-tools {
        position: absolute !important;
        inset: auto .45rem .3rem auto !important;
        left: auto !important;
        top: auto !important;
        right: .45rem !important;
        bottom: .3rem !important;
        transform: none !important;
        display: flex !important;
        align-items: center !important;
        justify-content: flex-end !important;
        gap: .2rem !important;
        width: auto !important;
        max-width: calc(100% - .9rem) !important;
        margin: 0 !important;
        padding: 0 !important;
        opacity: .42 !important;
        visibility: visible !important;
        pointer-events: auto !important;
        z-index: 35 !important;
        white-space: nowrap !important;
    }

    [data-rcentz-message-row="r6"]:hover > .rcentz-message-tools,
    [data-rcentz-message-row="r6"]:focus-within > .rcentz-message-tools,
    [data-rcentz-message-row="r6"] > .rcentz-message-tools:focus-within {
        opacity: 1 !important;
    }

    .rcentz-message-tools > button,
    .rcentz-message-tools > a,
    .rcentz-message-tools > details,
    .rcentz-message-tools [data-reply-id],
    .rcentz-message-tools summary {
        visibility: visible !important;
        pointer-events: auto !important;
    }

    .rcentz-message-tools > button,
    .rcentz-message-tools > a,
    .rcentz-message-tools [data-reply-id],
    .rcentz-message-tools summary {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        min-width: 1.9rem !important;
        min-height: 1.9rem !important;
        border-radius: .55rem !important;
    }

    .rcentz-message-tools > details {
        display: inline-block !important;
        position: relative !important;
    }

    .rcentz-message-tools > details:not([open]) > .rcentz-message-action-menu {
        display: none !important;
    }

    .rcentz-message-tools > details[open] > .rcentz-message-action-menu,
    .rcentz-message-action-menu.rcentz-r6-floating-menu {
        display: block !important;
    }

    .rcentz-message-action-menu {
        min-width: 13.5rem !important;
        width: max-content !important;
        max-width: min(18rem, calc(100vw - 1.25rem)) !important;
        white-space: nowrap !important;
        z-index: 99999 !important;
    }

    .rcentz-message-action-menu.rcentz-r6-floating-menu {
        position: fixed !important;
        inset: auto !important;
        margin: 0 !important;
        z-index: 99999 !important;
    }

    .rcentz-r6-chat-scroll {
        min-height: 0 !important;
        overflow-y: auto !important;
        overflow-x: hidden !important;
        overscroll-behavior-y: contain !important;
        -webkit-overflow-scrolling: touch !important;
        scrollbar-gutter: stable !important;
        touch-action: pan-y !important;
    }

    @media (max-width: 768px) {
        [data-rcentz-message-row="r6"] {
            padding-bottom: 2.35rem !important;
        }

        [data-rcentz-message-row="r6"] > .rcentz-message-tools {
            opacity: .72 !important;
            right: .3rem !important;
            bottom: .22rem !important;
        }

        .rcentz-message-tools > button,
        .rcentz-message-tools > a,
        .rcentz-message-tools [data-reply-id],
        .rcentz-message-tools summary {
            min-width: 2.15rem !important;
            min-height: 2.15rem !important;
        }
    }
</style>
<script id="rcentz-ms7-r7-stable-message-menu">
(() => {
    const toolSelector = '.rcentz-message-tools';
    const menuSelector = '.rcentz-message-action-menu';
    let scrollRegion = null;
    let active = null;
    let raf = 0;

    const commonAncestor = (nodes) => {
        if (!nodes.length) return null;
        let candidate = nodes[0];
        while (candidate && !nodes.every((node) => candidate.contains(node))) {
            candidate = candidate.parentElement;
        }
        return candidate;
    };

    const locateMessageRow = (tools) => {
        return tools.closest('[id^="message-"]')
            || tools.closest('[data-message-id]')
            || tools.closest('[data-message]')
            || tools.parentElement;
    };

    const restoreActiveMenu = () => {
        if (!active) return;
        const { details, summary, menu, placeholder } = active;
        menu.classList.remove('rcentz-r6-floating-menu');
        menu.classList.remove('rcentz-r7-floating-menu');
        menu.style.removeProperty('left');
        menu.style.removeProperty('top');
        menu.style.removeProperty('display');
        menu.style.removeProperty('visibility');
        menu.style.removeProperty('pointer-events');
        menu.style.removeProperty('max-height');
        menu.style.removeProperty('overflow-y');
        if (placeholder && placeholder.parentNode) {
            placeholder.parentNode.replaceChild(menu, placeholder);
        } else if (details && document.documentElement.contains(details)) {
            details.appendChild(menu);
        }
        if (details) details.open = false;
        if (summary) summary.setAttribute('aria-expanded', 'false');
        active = null;
    };

    const positionActiveMenu = () => {
        if (!active) return;
        const { summary, menu } = active;
        if (!summary || !menu || !document.documentElement.contains(summary)) {
            restoreActiveMenu();
            return;
        }

        const anchor = summary.getBoundingClientRect();
        const menuRect = menu.getBoundingClientRect();
        const pad = 8;
        const width = Math.min(Math.max(menuRect.width || 216, 180), Math.max(180, window.innerWidth - pad * 2));
        const height = Math.min(menuRect.height || 180, Math.max(120, window.innerHeight - pad * 2));
        let left = anchor.right - width;
        left = Math.max(pad, Math.min(left, window.innerWidth - width - pad));
        let top = anchor.bottom + 6;

        if (top + height > window.innerHeight - pad) {
            top = Math.max(pad, anchor.top - height - 6);
        }

        menu.style.left = left + 'px';
        menu.style.top = top + 'px';
        menu.style.maxHeight = Math.max(120, window.innerHeight - top - pad) + 'px';
        menu.style.overflowY = 'auto';
    };

    const schedulePosition = () => {
        if (!active || raf) return;
        raf = requestAnimationFrame(() => {
            raf = 0;
            positionActiveMenu();
        });
    };

    const openMenu = (details, summary, menu) => {
        if (active && active.details === details) {
            restoreActiveMenu();
            return;
        }
        restoreActiveMenu();

        // Do not use native <details> state. It races with moving the menu to body.
        details.open = false;
        const placeholder = document.createComment('rcentz-r7-menu-home');
        menu.parentNode.insertBefore(placeholder, menu);
        document.body.appendChild(menu);

        menu.classList.add('rcentz-r6-floating-menu');
        menu.classList.add('rcentz-r7-floating-menu');
        menu.style.display = 'block';
        menu.style.visibility = 'visible';
        menu.style.pointerEvents = 'auto';
        summary.setAttribute('aria-expanded', 'true');

        active = { details, summary, menu, placeholder };
        positionActiveMenu();
    };

    const wireMenus = (tools) => {
        tools.querySelectorAll('details').forEach((details) => {
            if (details.dataset.rcentzR7Wired === '1') return;
            const summary = details.querySelector(':scope > summary');
            const menu = details.querySelector(':scope > ' + menuSelector);
            if (!summary || !menu) return;

            details.dataset.rcentzR7Wired = '1';
            details.open = false;
            summary.setAttribute('aria-haspopup', 'menu');
            summary.setAttribute('aria-expanded', 'false');

            summary.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                openMenu(details, summary, menu);
            });

            summary.addEventListener('keydown', (event) => {
                if (event.key !== 'Enter' && event.key !== ' ') return;
                event.preventDefault();
                event.stopPropagation();
                openMenu(details, summary, menu);
            });

            // If anything else tries to toggle the native details element, neutralize it.
            details.addEventListener('toggle', () => {
                if (details.open) details.open = false;
            });
        });
    };

    const repairActions = () => {
        const toolsList = Array.from(document.querySelectorAll(toolSelector));
        const rows = [];

        toolsList.forEach((tools) => {
            const row = locateMessageRow(tools);
            if (!row) return;

            row.dataset.rcentzMessageRow = 'r6';
            if (tools.parentElement !== row) row.appendChild(tools);
            tools.removeAttribute('hidden');
            tools.setAttribute('aria-hidden', 'false');
            wireMenus(tools);
            rows.push(row);
        });

        return [...new Set(rows)];
    };

    const findScrollRegion = (rows) => {
        if (!rows.length) return null;
        let p = rows[0].parentElement;
        while (p && p !== document.body) {
            const cls = typeof p.className === 'string' ? p.className : '';
            const style = window.getComputedStyle(p);
            if (cls.includes('overflow-y-auto') || cls.includes('overflow-auto') || ['auto', 'scroll'].includes(style.overflowY)) {
                return p;
            }
            p = p.parentElement;
        }
        const common = commonAncestor(rows);
        if (!common) return null;
        return common === rows[0] ? common.parentElement : common;
    };

    const findComposer = (region) => {
        let scope = region?.parentElement;
        for (let i = 0; i < 3 && scope; i += 1, scope = scope.parentElement) {
            const field = scope.querySelector('textarea[name="message"], textarea, input[name="message"]');
            if (field) return field.closest('form') || field;
        }
        return null;
    };

    const sizeScrollRegion = () => {
        if (!scrollRegion || !document.documentElement.contains(scrollRegion)) return;
        let ancestor = scrollRegion;
        for (let i = 0; i < 5 && ancestor; i += 1, ancestor = ancestor.parentElement) {
            ancestor.style.minHeight = '0';
        }

        const rect = scrollRegion.getBoundingClientRect();
        const composer = findComposer(scrollRegion);
        let reserve = 16;
        if (composer && !scrollRegion.contains(composer)) {
            reserve += composer.getBoundingClientRect().height + 12;
        }
        const available = Math.max(220, window.innerHeight - rect.top - reserve);
        scrollRegion.classList.add('rcentz-r6-chat-scroll');
        scrollRegion.style.height = available + 'px';
        scrollRegion.style.maxHeight = available + 'px';
    };

    const boot = () => {
        const rows = repairActions();
        scrollRegion = findScrollRegion(rows);
        if (scrollRegion) sizeScrollRegion();
    };

    // Outside pointer closes. Clicks inside the summary/menu never do.
    document.addEventListener('pointerdown', (event) => {
        if (!active) return;
        if (active.menu.contains(event.target) || active.summary.contains(event.target)) return;
        restoreActiveMenu();
    }, true);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') restoreActiveMenu();
    });

    window.addEventListener('resize', () => {
        sizeScrollRegion();
        schedulePosition();
    }, { passive: true });

    // Reposition on any scroll instead of closing the menu.
    window.addEventListener('scroll', schedulePosition, true);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot, { once: true });
    } else {
        boot();
    }
})();
</script>
{{-- RCENTZ_MS7_R7_STABLE_MESSAGE_MENU --}}


{{-- RCENTZ_MS7_R9_INFLOW_ACTION_TRAY --}}
<style id="rcentz-ms7-r9-inflow-action-tray">
    /*
     * MS7 R9: message actions expand in-flow beneath the bubble controls.
     * No native details behaviour, no body portal, no clipping/z-index race.
     */
    [data-rcentz-message-row="r9"] {
        position: relative !important;
        overflow: visible !important;
        padding-bottom: 0 !important;
    }

    [data-rcentz-message-row="r9"] > .rcentz-message-tools,
    .rcentz-message-tools.rcentz-r9-tools {
        position: static !important;
        inset: auto !important;
        left: auto !important;
        right: auto !important;
        top: auto !important;
        bottom: auto !important;
        transform: none !important;
        display: flex !important;
        flex-wrap: wrap !important;
        align-items: center !important;
        justify-content: flex-end !important;
        gap: .25rem !important;
        width: 100% !important;
        max-width: 100% !important;
        margin: .3rem 0 0 !important;
        padding: 0 !important;
        opacity: .46 !important;
        visibility: visible !important;
        pointer-events: auto !important;
        z-index: auto !important;
        white-space: normal !important;
    }

    [data-rcentz-message-row="r9"]:hover > .rcentz-message-tools,
    [data-rcentz-message-row="r9"]:focus-within > .rcentz-message-tools,
    .rcentz-message-tools.rcentz-r9-tools:focus-within {
        opacity: 1 !important;
    }

    .rcentz-r9-actions {
        position: static !important;
        display: flex !important;
        flex-direction: column !important;
        align-items: flex-end !important;
        max-width: 100% !important;
    }

    .rcentz-r9-trigger,
    .rcentz-message-tools.rcentz-r9-tools > .rcentz-quick-reply {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        min-width: 1.9rem !important;
        min-height: 1.9rem !important;
        border-radius: .55rem !important;
        visibility: visible !important;
        pointer-events: auto !important;
        cursor: pointer !important;
    }

    .rcentz-r9-menu {
        display: none !important;
        position: static !important;
        inset: auto !important;
        float: none !important;
        width: min(15.5rem, 100%) !important;
        min-width: min(13rem, 100%) !important;
        max-width: 100% !important;
        margin: .4rem 0 0 auto !important;
        white-space: nowrap !important;
        visibility: visible !important;
        pointer-events: auto !important;
        z-index: auto !important;
    }

    .rcentz-r9-actions.rcentz-r9-open > .rcentz-r9-menu {
        display: block !important;
    }

    .rcentz-r9-menu button,
    .rcentz-r9-menu a {
        white-space: nowrap !important;
    }

    #chat-scroll.rcentz-r9-chat-scroll {
        min-height: 0 !important;
        overflow-y: auto !important;
        overflow-x: hidden !important;
        overscroll-behavior-y: contain !important;
        -webkit-overflow-scrolling: touch !important;
        touch-action: pan-y !important;
        scrollbar-gutter: stable !important;
    }

    @media (hover: none), (max-width: 767px) {
        [data-rcentz-message-row="r9"] > .rcentz-message-tools,
        .rcentz-message-tools.rcentz-r9-tools {
            opacity: .78 !important;
        }

        .rcentz-r9-trigger,
        .rcentz-message-tools.rcentz-r9-tools > .rcentz-quick-reply {
            min-width: 2.15rem !important;
            min-height: 2.15rem !important;
        }

        .rcentz-r9-menu {
            width: min(17rem, 100%) !important;
        }
    }
</style>
<script id="rcentz-ms7-r9-inflow-action-tray-runtime">
(() => {
    const closeAllTrays = (except = null) => {
        document.querySelectorAll('.rcentz-r9-actions.rcentz-r9-open').forEach((actions) => {
            if (actions !== except) {
                actions.classList.remove('rcentz-r9-open');
                actions.querySelector('.rcentz-r9-trigger')?.setAttribute('aria-expanded', 'false');
            }
        });
    };

    const locateMessageRow = (tools) => {
        return tools.closest('[id^="message-"]')
            || tools.closest('[data-message-id]')
            || tools.closest('[data-message]')
            || tools.parentElement;
    };

    const convertDetails = (tools) => {
        tools.querySelectorAll('details.rcentz-message-actions').forEach((details) => {
            if (details.dataset.rcentzR9Converted === '1') return;

            const summary = details.querySelector(':scope > summary');
            const menu = details.querySelector(':scope > .rcentz-message-action-menu');
            if (!summary || !menu) return;

            const wrapper = document.createElement('div');
            wrapper.className = 'rcentz-message-actions rcentz-r9-actions';
            wrapper.dataset.rcentzR9Converted = '1';

            const trigger = document.createElement('button');
            trigger.type = 'button';
            trigger.className = summary.className + ' rcentz-r9-trigger';
            trigger.innerHTML = summary.innerHTML;
            trigger.title = summary.getAttribute('title') || 'Message actions';
            trigger.setAttribute('aria-label', summary.getAttribute('aria-label') || 'Message actions');
            trigger.setAttribute('aria-haspopup', 'menu');
            trigger.setAttribute('aria-expanded', 'false');

            menu.classList.add('rcentz-r9-menu');
            menu.classList.remove('rcentz-r6-floating-menu', 'rcentz-r7-floating-menu');
            menu.style.removeProperty('left');
            menu.style.removeProperty('top');
            menu.style.removeProperty('display');
            menu.style.removeProperty('visibility');
            menu.style.removeProperty('pointer-events');
            menu.style.removeProperty('max-height');
            menu.style.removeProperty('overflow-y');

            trigger.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                const opening = !wrapper.classList.contains('rcentz-r9-open');
                closeAllTrays(wrapper);
                wrapper.classList.toggle('rcentz-r9-open', opening);
                trigger.setAttribute('aria-expanded', opening ? 'true' : 'false');
                if (opening) {
                    requestAnimationFrame(() => wrapper.scrollIntoView({ block: 'nearest', behavior: 'smooth' }));
                }
            });

            menu.addEventListener('click', (event) => {
                event.stopPropagation();
            });

            wrapper.appendChild(trigger);
            wrapper.appendChild(menu);
            details.replaceWith(wrapper);
        });
    };

    const repairMessageActions = () => {
        document.querySelectorAll('.rcentz-message-tools').forEach((tools) => {
            const row = locateMessageRow(tools);
            if (!row) return;

            row.dataset.rcentzMessageRow = 'r9';
            tools.classList.add('rcentz-r9-tools');
            tools.removeAttribute('hidden');
            tools.setAttribute('aria-hidden', 'false');

            if (tools.parentElement !== row) row.appendChild(tools);
            convertDetails(tools);
        });
    };

    const sizeChatScroll = () => {
        const scroll = document.getElementById('chat-scroll');
        if (!scroll) return;

        let ancestor = scroll;
        for (let i = 0; i < 6 && ancestor; i += 1, ancestor = ancestor.parentElement) {
            ancestor.style.minHeight = '0';
        }

        const rect = scroll.getBoundingClientRect();
        let composer = null;
        let scope = scroll.parentElement;
        for (let i = 0; i < 4 && scope && !composer; i += 1, scope = scope.parentElement) {
            composer = scope.querySelector('#chat-composer, form textarea[name="message"]')?.closest('form') || null;
        }

        let reserve = 18;
        if (composer && !scroll.contains(composer)) reserve += composer.getBoundingClientRect().height + 12;
        const available = Math.max(220, window.innerHeight - rect.top - reserve);

        scroll.classList.add('rcentz-r9-chat-scroll');
        scroll.style.height = available + 'px';
        scroll.style.maxHeight = available + 'px';
    };

    const boot = () => {
        repairMessageActions();
        sizeChatScroll();
    };

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.rcentz-r9-actions')) closeAllTrays();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeAllTrays();
    });

    window.addEventListener('resize', sizeChatScroll, { passive: true });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot, { once: true });
    } else {
        boot();
    }
})();
</script>
{{-- /RCENTZ_MS7_R9_INFLOW_ACTION_TRAY --}}


{{-- RCENTZ_MS7_R12_ASYNC_TELEGRAM_CHAT --}}
<style id="rcentz-ms7-r12-async-telegram-chat">
    /* Telegram-like visual separation. Own messages are right/blue; peer messages left/card. */
    .rcentz-message-row.rcentz-telegram-outgoing {
        justify-content: flex-end !important;
    }
    .rcentz-message-row.rcentz-telegram-incoming {
        justify-content: flex-start !important;
    }
    .rcentz-message-row.rcentz-telegram-outgoing > div:first-child {
        margin-left: auto !important;
        margin-right: 0 !important;
        max-width: min(82%, 46rem) !important;
    }
    .rcentz-message-row.rcentz-telegram-incoming > div:first-child {
        margin-left: 0 !important;
        margin-right: auto !important;
        max-width: min(82%, 46rem) !important;
    }
    .rcentz-message-row.rcentz-telegram-outgoing > div:first-child > div:first-child {
        background: #e7f4ff !important;
        border: 1px solid #cde7fa !important;
        border-radius: 1rem 1rem .35rem 1rem !important;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .06) !important;
    }
    .rcentz-message-row.rcentz-telegram-incoming > div:first-child > div:first-child {
        background: hsl(var(--card)) !important;
        border: 1px solid hsl(var(--border)) !important;
        border-radius: 1rem 1rem 1rem .35rem !important;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .05) !important;
    }
    .dark .rcentz-message-row.rcentz-telegram-outgoing > div:first-child > div:first-child {
        background: #2b5278 !important;
        border-color: #35658f !important;
    }
    .dark .rcentz-message-row.rcentz-telegram-incoming > div:first-child > div:first-child {
        background: #182533 !important;
        border-color: #223445 !important;
    }
    .dark .rcentz-message-row.rcentz-telegram-outgoing > div:first-child > div:first-child .text-foreground,
    .dark .rcentz-message-row.rcentz-telegram-outgoing > div:first-child > div:first-child p {
        color: #f8fbff !important;
    }
    .rcentz-message-row.rcentz-telegram-outgoing .rcentz-message-tools {
        justify-content: flex-end !important;
    }
    .rcentz-message-row.rcentz-telegram-incoming .rcentz-message-tools {
        justify-content: flex-start !important;
    }

    /* Async action feedback without navigating away from the conversation. */
    #chat-composer[data-rcentz-async-busy="true"],
    .rcentz-message-action-menu form[data-rcentz-async-busy="true"] {
        opacity: .62 !important;
        pointer-events: none !important;
    }
    #chat-composer[data-rcentz-async-busy="true"] button[type="submit"] {
        transform: scale(.94);
        opacity: .72;
    }
    #rcentz-chat-async-status {
        position: fixed;
        left: 50%;
        bottom: 5.25rem;
        z-index: 99999;
        transform: translateX(-50%);
        max-width: min(28rem, calc(100vw - 2rem));
        border: 1px solid hsl(var(--border));
        border-radius: .8rem;
        background: hsl(var(--popover));
        color: hsl(var(--popover-foreground));
        padding: .55rem .8rem;
        font-size: .72rem;
        box-shadow: 0 14px 42px rgba(0,0,0,.22);
        display: none;
    }
    #rcentz-chat-async-status[data-show="true"] { display: block; }
    #rcentz-chat-async-status[data-kind="error"] { border-color: rgba(239,68,68,.35); }

    @media (max-width: 767px) {
        .rcentz-message-row.rcentz-telegram-outgoing > div:first-child,
        .rcentz-message-row.rcentz-telegram-incoming > div:first-child {
            max-width: 88% !important;
        }
        #rcentz-chat-async-status { bottom: 5.75rem; }
    }
</style>
<div id="rcentz-chat-async-status" role="status" aria-live="polite"></div>
<script id="rcentz-ms7-r12-async-telegram-chat-runtime">
(() => {
    if (window.__RCENTZ_MS7_R12_ASYNC_TELEGRAM_CHAT__) return;
    window.__RCENTZ_MS7_R12_ASYNC_TELEGRAM_CHAT__ = true;

    const statusBox = document.getElementById('rcentz-chat-async-status');
    let statusTimer = null;

    const showStatus = (message, kind = 'ok', duration = 2200) => {
        if (!statusBox) return;
        clearTimeout(statusTimer);
        statusBox.textContent = message;
        statusBox.dataset.kind = kind;
        statusBox.dataset.show = 'true';
        statusTimer = setTimeout(() => { statusBox.dataset.show = 'false'; }, duration);
    };

    const currentScroll = () => document.getElementById('chat-scroll');

    const locateRow = (node) => node?.closest('.rcentz-message-row, [id^="message-"]');

    const markTelegramSides = (root = document) => {
        root.querySelectorAll('.rcentz-message-row, [id^="message-"]').forEach((row) => {
            if (!row.classList.contains('rcentz-message-row')) return;
            const tools = row.querySelector('.rcentz-message-tools');
            const mine = row.dataset.messageMine === '1';
            row.classList.toggle('rcentz-telegram-outgoing', mine);
            row.classList.toggle('rcentz-telegram-incoming', !mine);
        });
    };

    const closeR12Trays = (except = null) => {
        document.querySelectorAll('.rcentz-r9-actions.rcentz-r9-open').forEach((actions) => {
            if (actions === except) return;
            actions.classList.remove('rcentz-r9-open');
            actions.querySelector('.rcentz-r9-trigger')?.setAttribute('aria-expanded', 'false');
        });
    };

    const convertFreshActionMenus = (root = document) => {
        root.querySelectorAll('.rcentz-message-tools').forEach((tools) => {
            const row = locateRow(tools);
            if (!row) return;

            row.dataset.rcentzMessageRow = 'r9';
            tools.classList.add('rcentz-r9-tools');
            tools.removeAttribute('hidden');
            tools.setAttribute('aria-hidden', 'false');
            if (tools.parentElement !== row) row.appendChild(tools);

            tools.querySelectorAll('details.rcentz-message-actions').forEach((details) => {
                const summary = details.querySelector(':scope > summary');
                const menu = details.querySelector(':scope > .rcentz-message-action-menu');
                if (!summary || !menu) return;

                const wrapper = document.createElement('div');
                wrapper.className = 'rcentz-message-actions rcentz-r9-actions';
                wrapper.dataset.rcentzR12Owned = '1';

                const trigger = document.createElement('button');
                trigger.type = 'button';
                trigger.className = (summary.className || '') + ' rcentz-r9-trigger';
                trigger.innerHTML = summary.innerHTML;
                trigger.title = summary.getAttribute('title') || 'Message actions';
                trigger.setAttribute('aria-label', summary.getAttribute('aria-label') || 'Message actions');
                trigger.setAttribute('aria-haspopup', 'menu');
                trigger.setAttribute('aria-expanded', 'false');

                menu.classList.add('rcentz-r9-menu');
                menu.style.removeProperty('display');
                menu.style.removeProperty('position');
                menu.style.removeProperty('left');
                menu.style.removeProperty('top');

                wrapper.appendChild(trigger);
                wrapper.appendChild(menu);
                details.replaceWith(wrapper);
            });
        });
    };

    const enhanceMessages = (root = document) => {
        convertFreshActionMenus(root);
        markTelegramSides(root);
        window.lucide?.createIcons?.();
    };

    const composer = () => document.getElementById('chat-composer');

    const resetComposer = () => {
        const form = composer();
        if (!form) return;
        if (form.dataset.replyAction) form.action = form.dataset.replyAction;
        form.querySelector('input[name="_method"]')?.remove();
        const textarea = form.querySelector('textarea[name="message"]');
        if (textarea) textarea.value = '';
        const replyInput = form.querySelector('input[name="reply_to_message_id"]');
        if (replyInput) replyInput.value = '';
        const files = form.querySelector('input[type="file"][name="attachments[]"]');
        if (files) { files.value = ''; files.disabled = false; }
        const fileCount = document.getElementById('chat-file-count');
        if (fileCount) { fileCount.textContent = ''; fileCount.classList.add('hidden'); }
        ['reply-preview', 'edit-preview'].forEach((id) => {
            const el = document.getElementById(id);
            if (el) { el.classList.add('hidden'); el.classList.remove('flex'); }
        });
        const idempotency = form.querySelector('input[name="idempotency_key"]');
        if (idempotency && window.crypto?.randomUUID) idempotency.value = window.crypto.randomUUID();
    };

    const beginReply = (button) => {
        const form = composer();
        if (!form) return;
        // Exit edit mode without wiping the reply text that follows.
        if (form.dataset.replyAction) form.action = form.dataset.replyAction;
        form.querySelector('input[name="_method"]')?.remove();
        const files = form.querySelector('input[type="file"][name="attachments[]"]');
        if (files) files.disabled = false;
        const editPreview = document.getElementById('edit-preview');
        editPreview?.classList.add('hidden'); editPreview?.classList.remove('flex');

        const input = form.querySelector('input[name="reply_to_message_id"]');
        if (input) input.value = button.dataset.replyId || '';
        const name = document.getElementById('reply-preview-name');
        const text = document.getElementById('reply-preview-text');
        if (name) name.textContent = button.dataset.replyName || 'Message';
        if (text) text.textContent = button.dataset.replyText || 'Attachment';
        const preview = document.getElementById('reply-preview');
        preview?.classList.remove('hidden'); preview?.classList.add('flex');
        form.querySelector('textarea[name="message"]')?.focus();
        closeR12Trays();
    };

    const beginEdit = (button) => {
        const form = composer();
        if (!form || !button.dataset.editUrl) return;
        form.action = button.dataset.editUrl;
        form.querySelector('input[name="_method"]')?.remove();
        const method = document.createElement('input');
        method.type = 'hidden'; method.name = '_method'; method.value = 'PATCH';
        form.appendChild(method);
        const replyInput = form.querySelector('input[name="reply_to_message_id"]');
        if (replyInput) replyInput.value = '';
        const replyPreview = document.getElementById('reply-preview');
        replyPreview?.classList.add('hidden'); replyPreview?.classList.remove('flex');
        const textarea = form.querySelector('textarea[name="message"]');
        if (textarea) textarea.value = button.dataset.editText || '';
        const files = form.querySelector('input[type="file"][name="attachments[]"]');
        if (files) files.disabled = true;
        const editPreview = document.getElementById('edit-preview');
        editPreview?.classList.remove('hidden'); editPreview?.classList.add('flex');
        textarea?.focus();
        closeR12Trays();
    };

    const sourceConversationParts = (doc) => {
        const scroll = doc.getElementById('chat-scroll');
        if (!scroll) return null;
        const main = scroll.closest('main');
        return { scroll, main, info: main?.nextElementSibling?.tagName === 'ASIDE' ? main.nextElementSibling : null };
    };

    const applyServerConversation = (doc, scrollToBottom = true) => {
        const source = sourceConversationParts(doc);
        const targetScroll = currentScroll();
        if (!source || !targetScroll) return false;

        const previousBottomGap = targetScroll.scrollHeight - targetScroll.scrollTop - targetScroll.clientHeight;
        targetScroll.innerHTML = source.scroll.innerHTML;

        const targetMain = targetScroll.closest('main');
        const targetInfo = targetMain?.nextElementSibling?.tagName === 'ASIDE' ? targetMain.nextElementSibling : null;
        if (source.info && targetInfo) targetInfo.innerHTML = source.info.innerHTML;

        enhanceMessages(targetScroll);
        if (targetInfo) window.lucide?.createIcons?.();

        requestAnimationFrame(() => {
            if (scrollToBottom || previousBottomGap < 100) {
                targetScroll.scrollTop = targetScroll.scrollHeight;
            } else {
                targetScroll.scrollTop = Math.max(0, targetScroll.scrollHeight - targetScroll.clientHeight - previousBottomGap);
            }
        });
        return true;
    };

    const asyncSubmit = async (form, submitter = null) => {
        if (form.dataset.rcentzAsyncBusy === 'true') return;
        form.dataset.rcentzAsyncBusy = 'true';
        submitter?.setAttribute('disabled', 'disabled');

        try {
            const data = new FormData(form);
            if (submitter?.name && !data.has(submitter.name)) data.append(submitter.name, submitter.value || '1');
            const response = await fetch(form.action, {
                method: (form.method || 'POST').toUpperCase(),
                body: data,
                credentials: 'same-origin',
                redirect: 'follow',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html,application/xhtml+xml'
                }
            });

            const html = await response.text();
            if (!response.ok) throw new Error('Request failed with status ' + response.status);
            const doc = new DOMParser().parseFromString(html, 'text/html');
            if (!applyServerConversation(doc, true)) throw new Error('Conversation payload was not returned.');

            if (form.id === 'chat-composer') resetComposer();
            closeR12Trays();
            showStatus(form.id === 'chat-composer' ? 'Message updated.' : 'Conversation updated.');
        } catch (error) {
            console.error('RCENTZ async chat action failed', error);
            showStatus('Could not complete that message action. Please try again.', 'error', 3600);
        } finally {
            form.dataset.rcentzAsyncBusy = 'false';
            submitter?.removeAttribute('disabled');
        }
    };

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        const isComposer = form.id === 'chat-composer';
        const isMessageAction = !!form.closest('.rcentz-message-action-menu, .rcentz-r9-menu');
        if (!isComposer && !isMessageAction) return;
        if (event.defaultPrevented) return;
        event.preventDefault();
        asyncSubmit(form, event.submitter || null);
    });

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('.rcentz-r9-actions[data-rcentz-r12-owned="1"] > .rcentz-r9-trigger');
        if (trigger) {
            event.preventDefault();
            event.stopPropagation();
            const wrapper = trigger.parentElement;
            const opening = !wrapper.classList.contains('rcentz-r9-open');
            closeR12Trays(wrapper);
            wrapper.classList.toggle('rcentz-r9-open', opening);
            trigger.setAttribute('aria-expanded', opening ? 'true' : 'false');
            return;
        }

        const reply = event.target.closest('[data-reply-id]');
        if (reply && reply.closest('#chat-scroll')) {
            event.preventDefault();
            beginReply(reply);
            return;
        }

        const edit = event.target.closest('[data-edit-url]');
        if (edit && edit.closest('#chat-scroll')) {
            event.preventDefault();
            beginEdit(edit);
            return;
        }

        if (!event.target.closest('.rcentz-r9-actions')) closeR12Trays();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeR12Trays();
        const form = composer();
        if (!form || event.target !== form.querySelector('textarea[name="message"]')) return;
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            if (form.requestSubmit) form.requestSubmit();
        }
    });

    // Delegated media preview continues working after async conversation refreshes.
    document.addEventListener('click', (event) => {
        const media = event.target.closest('[data-media-preview]');
        if (!media) return;
        const lightbox = document.getElementById('media-lightbox');
        const image = document.getElementById('media-lightbox-image');
        const name = document.getElementById('media-lightbox-name');
        if (!lightbox || !image) return;
        event.preventDefault();
        image.src = media.dataset.mediaPreview || '';
        image.alt = media.dataset.mediaName || 'Shared image';
        if (name) name.textContent = media.dataset.mediaName || '';
        lightbox.classList.remove('hidden'); lightbox.classList.add('flex');
    });

    enhanceMessages(document);
})();
</script>


{{-- RCENTZ_MS9_R3_CONVERSATION_SIDE_LAYOUT_REPAIR --}}
<style id="rcentz-ms9-r3-conversation-side-layout-repair">
    /*
     * R9 moved message controls into the row as a direct flex child and gave
     * that tool rail width:100%. In a horizontal flex row that consumes the
     * line and visually pins the bubble left even when the message is owned
     * by the current viewer. Ownership itself is authoritative via R2's
     * data-message-mine marker; this block only repairs physical layout.
     */
    .rcentz-message-row[data-message-mine="1"],
    .rcentz-message-row.rcentz-telegram-outgoing {
        display: flex !important;
        flex-direction: column !important;
        align-items: flex-end !important;
        justify-content: flex-start !important;
        width: 100% !important;
    }

    .rcentz-message-row[data-message-mine="0"],
    .rcentz-message-row.rcentz-telegram-incoming {
        display: flex !important;
        flex-direction: column !important;
        align-items: flex-start !important;
        justify-content: flex-start !important;
        width: 100% !important;
    }

    .rcentz-message-row[data-message-mine="1"] > div:first-child,
    .rcentz-message-row.rcentz-telegram-outgoing > div:first-child {
        align-self: flex-end !important;
        margin-left: auto !important;
        margin-right: 0 !important;
    }

    .rcentz-message-row[data-message-mine="0"] > div:first-child,
    .rcentz-message-row.rcentz-telegram-incoming > div:first-child {
        align-self: flex-start !important;
        margin-left: 0 !important;
        margin-right: auto !important;
    }

    [data-rcentz-message-row="r9"][data-message-mine="1"] > .rcentz-message-tools,
    [data-rcentz-message-row="r9"].rcentz-telegram-outgoing > .rcentz-message-tools,
    .rcentz-message-row[data-message-mine="1"] > .rcentz-message-tools.rcentz-r9-tools,
    .rcentz-message-row.rcentz-telegram-outgoing > .rcentz-message-tools.rcentz-r9-tools {
        align-self: flex-end !important;
        justify-content: flex-end !important;
        width: auto !important;
        max-width: min(82%, 46rem) !important;
        margin: .3rem 0 0 auto !important;
    }

    [data-rcentz-message-row="r9"][data-message-mine="0"] > .rcentz-message-tools,
    [data-rcentz-message-row="r9"].rcentz-telegram-incoming > .rcentz-message-tools,
    .rcentz-message-row[data-message-mine="0"] > .rcentz-message-tools.rcentz-r9-tools,
    .rcentz-message-row.rcentz-telegram-incoming > .rcentz-message-tools.rcentz-r9-tools {
        align-self: flex-start !important;
        justify-content: flex-start !important;
        width: auto !important;
        max-width: min(82%, 46rem) !important;
        margin: .3rem auto 0 0 !important;
    }

    @media (max-width: 767px) {
        [data-rcentz-message-row="r9"] > .rcentz-message-tools,
        .rcentz-message-tools.rcentz-r9-tools {
            max-width: 88% !important;
        }
    }
</style>
{{-- /RCENTZ_MS9_R3_CONVERSATION_SIDE_LAYOUT_REPAIR --}}

{{-- /RCENTZ_MS7_R12_ASYNC_TELEGRAM_CHAT --}}

</x-admin-layout>
