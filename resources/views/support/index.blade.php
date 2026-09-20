<x-user-layout>
    <x-slot name="header">Support</x-slot>

    <div class="ui-page max-w-[1450px]">
        <section class="ui-page-header">
            <div>
                <p class="ui-kicker">Customer support</p>
                <h1 class="ui-heading">Support Tickets</h1>
                <p class="ui-lead">Create and track support requests separately from your private Messages inbox.</p>
            </div>
            <div class="ui-header-actions">
                <a href="{{ route('messages.index') }}" class="ui-btn ui-btn-secondary"><i data-lucide="messages-square" class="h-4 w-4"></i>Messages</a>
                <a href="{{ route('support.index', $archived ? [] : ['archived' => 1]) }}" class="ui-btn ui-btn-secondary">
                    <i data-lucide="{{ $archived ? 'message-square' : 'archive' }}" class="h-4 w-4"></i>
                    {{ $archived ? 'Active tickets' : 'Archived' }}
                </a>
            </div>
        </section>

        @if(session('success'))<div class="mb-4 rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-xs text-emerald-600">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="mb-4 rounded-xl border border-red-500/20 bg-red-500/10 px-4 py-3 text-xs text-red-600">{{ session('error') }}</div>@endif

        <section class="grid gap-4 xl:grid-cols-[minmax(0,1.15fr)_minmax(360px,.85fr)]">
            <article class="ui-panel overflow-hidden">
                <div class="flex items-center justify-between border-b border-border px-5 py-4">
                    <div>
                        <p class="ui-kicker">{{ $archived ? 'Archived tickets' : 'Tickets' }}</p>
                        <h2 class="mt-1 text-lg font-semibold">Support queue</h2>
                    </div>
                    <span class="rounded-full border border-border px-2.5 py-1 text-[10px] font-semibold text-muted-foreground">{{ $conversations->total() }}</span>
                </div>

                <div class="divide-y divide-border">
                    @forelse($conversations as $conversation)
                        @php
                            $last = $conversation->visible_latest_message;
                            $preview = $last?->body ?: ($last?->attachments()->count() ? 'Attachment' : 'No messages yet.');
                            $initial = strtoupper(mb_substr($conversation->subject, 0, 1));
                        @endphp
                        <a href="{{ route('support.show', $conversation) }}" class="group flex gap-3 px-4 py-4 transition hover:bg-muted/35 sm:px-5">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-red-500/10 text-sm font-bold text-red-600 dark:text-red-400">
                                {{ $initial }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-foreground">{{ $conversation->subject }}</p>
                                        <p class="mt-1 truncate text-xs text-muted-foreground">{{ $preview }}</p>
                                    </div>
                                    <div class="shrink-0 text-right">
                                        <p class="text-[9px] text-muted-foreground">{{ $conversation->last_message_at?->format('H:i') }}</p>
                                        @if(($conversation->unread_count ?? 0) > 0)
                                            <span class="mt-1 inline-flex min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 py-0.5 text-[9px] font-bold text-white">{{ $conversation->unread_count }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="mt-2 flex flex-wrap items-center gap-2 text-[9px] uppercase tracking-[.1em] text-muted-foreground">
                                    <span>{{ $conversation->ticket_number }}</span>
                                    <span>·</span>
                                    <span>{{ ucfirst($conversation->status) }}</span>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="px-6 py-16 text-center">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-muted"><i data-lucide="messages-square" class="h-5 w-5 text-muted-foreground"></i></div>
                            <p class="mt-4 text-sm font-semibold">{{ $archived ? 'No archived support tickets' : 'No support tickets yet' }}</p>
                            <p class="mt-1 text-xs text-muted-foreground">{{ $archived ? 'Archived support tickets will appear here.' : 'Create a support ticket when you need help from the support team.' }}</p>
                        </div>
                    @endforelse
                </div>

                @if($conversations->hasPages())
                    <div class="border-t border-border p-4">{{ $conversations->links() }}</div>
                @endif
            </article>

            <article class="ui-panel overflow-hidden">
                <div class="border-b border-border px-5 py-4 sm:px-6">
                    <p class="ui-kicker">New support ticket</p>
                    <h2 class="mt-1 text-lg font-semibold">Start a ticket</h2>
                    <p class="mt-1 text-xs text-muted-foreground">Your request becomes a persistent conversation immediately. Email delivery is optional.</p>
                </div>

                <form action="{{ route('support.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4 p-5 sm:p-6">
                    @csrf
                    <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', (string) \Illuminate\Support\Str::uuid()) }}">

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="ui-label" for="category">Category</label>
                            <select id="category" name="category" class="ui-input w-full" required>
                                <option value="" disabled {{ old('category') ? '' : 'selected' }}>Choose topic</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category }}" @selected(old('category') === $category)>{{ $category }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="ui-label" for="subject">Subject</label>
                            <input id="subject" name="subject" class="ui-input w-full" value="{{ old('subject') }}" placeholder="What do you need help with?" required>
                        </div>
                    </div>

                    <div>
                        <label class="ui-label" for="message">Message</label>
                        <textarea id="message" name="message" rows="7" class="ui-input min-h-40 w-full resize-y" placeholder="Describe the issue or request..." required>{{ old('message') }}</textarea>
                    </div>

                    <label class="block cursor-pointer rounded-xl border border-dashed border-border bg-muted/20 p-4 transition hover:bg-muted/35">
                        <input type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.webp,.pdf,.txt,.csv,.doc,.docx,.xls,.xlsx" class="sr-only" data-file-input>
                        <span class="flex items-center gap-3">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-background"><i data-lucide="paperclip" class="h-4 w-4"></i></span>
                            <span>
                                <span class="block text-xs font-semibold">Attach media or files</span>
                                <span class="mt-0.5 block text-[10px] text-muted-foreground" data-file-label>Up to 8 files · 10 MB each</span>
                            </span>
                        </span>
                    </label>

                    @if($errors->any())
                        <div class="rounded-xl border border-red-500/20 bg-red-500/10 px-4 py-3 text-xs text-red-600">{{ $errors->first() }}</div>
                    @endif

                    <button class="ui-btn ui-btn-primary w-full justify-center">
                        <i data-lucide="send" class="h-4 w-4"></i>
                        Create ticket
                    </button>
                </form>
            </article>
        </section>
    </div>

    <script>
        document.querySelectorAll('[data-file-input]').forEach((input) => {
            input.addEventListener('change', () => {
                const label = input.closest('label')?.querySelector('[data-file-label]');
                if (!label) return;
                label.textContent = input.files.length ? `${input.files.length} file${input.files.length === 1 ? '' : 's'} selected` : 'Up to 8 files · 10 MB each';
            });
        });
    </script>
</x-user-layout>
