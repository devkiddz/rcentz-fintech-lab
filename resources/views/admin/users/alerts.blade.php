<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="ui-kicker">Private account communication</p>
                <h1 class="text-xl font-semibold">{{ $user->name }}</h1>
                <p class="mt-1 text-xs text-muted-foreground">{{ $user->email }}</p>
            </div>
            <a href="{{ route('admin.users.index') }}" class="ui-btn ui-btn-secondary">Back to users</a>
        </div>
    </x-slot>

    <div class="ui-page max-w-6xl">
        @if(session('success'))<div class="mb-4 rounded-xl border border-emerald-500/20 bg-emerald-500/10 p-4 text-sm text-emerald-700">{{ session('success') }}</div>@endif
        <div class="grid gap-4 lg:grid-cols-[.8fr_1.2fr]">
            <section class="ui-panel p-5">
                <p class="ui-kicker">Send direct alert</p>
                <h2 class="mt-1 text-lg font-semibold">Customer dashboard notice</h2>
                <p class="mt-1 text-xs text-muted-foreground">This is not chat. It appears only inside this customer's private alert window.</p>

                <form method="POST" action="{{ route('admin.users.alerts.store',$user) }}" class="mt-5 space-y-4">
                    @csrf
                    <div><label class="ui-label">Title</label><input class="ui-input mt-1 w-full" name="title" required></div>
                    <div><label class="ui-label">Message</label><textarea class="ui-input mt-1 w-full" name="message" rows="5" required></textarea></div>
                    <div><label class="ui-label">Priority</label><select class="ui-input mt-1 w-full" name="priority"><option value="normal">Normal</option><option value="important">Important</option><option value="urgent">Urgent</option></select></div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div><label class="ui-label">Action label</label><input class="ui-input mt-1 w-full" name="action_label" placeholder="Optional"></div>
                        <div><label class="ui-label">Action URL</label><input class="ui-input mt-1 w-full" name="action_url" placeholder="Optional"></div>
                    </div>
                    <div><label class="ui-label">Expires</label><input class="ui-input mt-1 w-full" name="expires_at" type="datetime-local"></div>
                    <button class="ui-btn ui-btn-primary w-full justify-center">Push to Customer Dashboard</button>
                </form>
            </section>

            <section class="ui-panel overflow-hidden">
                <div class="border-b border-border p-5"><p class="ui-kicker">History</p><h2 class="mt-1 text-lg font-semibold">Direct alerts</h2></div>
                <div class="divide-y divide-border">
                    @forelse($alerts as $alert)
                        <article class="p-5">
                            <div class="flex items-start justify-between gap-4">
                                <div><p class="font-semibold">{{ $alert->title }}</p><p class="mt-1 text-sm text-muted-foreground">{{ $alert->message }}</p></div>
                                <span class="rounded-full bg-muted px-2 py-1 text-[9px] font-semibold uppercase">{{ $alert->priority }}</span>
                            </div>
                            <p class="mt-3 text-[10px] text-muted-foreground">{{ $alert->created_at->format('M j, Y H:i') }} · {{ $alert->read_at ? 'Read' : 'Unread' }}{{ $alert->dismissed_at ? ' · Dismissed' : '' }}</p>
                        </article>
                    @empty
                        <div class="p-8 text-sm text-muted-foreground">No private alerts yet.</div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-admin-layout>
