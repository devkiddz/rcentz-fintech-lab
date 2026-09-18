@php
    $adminNotificationUser = auth()->user();
    $adminNotifications = $adminNotificationUser
        ? $adminNotificationUser->notifications()->latest()->limit(8)->get()
        : collect();
    $adminUnreadCount = $adminNotificationUser
        ? $adminNotificationUser->notifications()->unread()->count()
        : 0;
@endphp

<details class="group relative" data-admin-notification-center>
    <summary class="relative inline-flex h-9 w-9 cursor-pointer list-none items-center justify-center rounded-lg border border-border bg-card text-muted-foreground transition hover:bg-muted hover:text-foreground [&::-webkit-details-marker]:hidden" title="Admin notifications">
        <i data-lucide="bell" class="h-4 w-4"></i>
        <span data-admin-notification-badge class="{{ $adminUnreadCount > 0 ? '' : 'hidden' }} absolute -right-1 -top-1 min-w-4 rounded-full bg-red-600 px-1 text-center text-[9px] font-bold leading-4 text-white">{{ $adminUnreadCount > 99 ? '99+' : $adminUnreadCount }}</span>
    </summary>

    <div class="absolute right-0 z-[90] mt-2 w-[min(92vw,360px)] overflow-hidden rounded-xl border border-border bg-card text-card-foreground shadow-xl">
        <div class="flex items-center justify-between border-b border-border px-4 py-3">
            <div><p class="text-xs font-semibold">Admin notifications</p><p class="mt-0.5 text-[9px] text-muted-foreground">Operational receipts and platform alerts</p></div>
            @if($adminUnreadCount > 0)
                <button type="button" data-admin-notifications-read-all="{{ route('notifications.mark-all-read') }}" class="text-[9px] font-semibold text-muted-foreground hover:text-foreground">Mark all read</button>
            @endif
        </div>

        <div class="max-h-[420px] divide-y divide-border overflow-y-auto">
            @forelse($adminNotifications as $notification)
                @php
                    $data = (array) ($notification->data ?? []);
                    $destination = !empty($data['signal_id']) && Route::has('admin.signals.show')
                        ? route('admin.signals.show', $data['signal_id'])
                        : route('admin.dashboard');
                @endphp
                <button type="button"
                        data-admin-notification-item
                        data-read-url="{{ route('notifications.read', $notification) }}"
                        data-destination="{{ $destination }}"
                        class="flex w-full items-start gap-3 px-4 py-3 text-left transition hover:bg-muted/30 {{ $notification->is_read ? '' : 'bg-red-500/[.03]' }}">
                    <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-border bg-muted/40"><i data-lucide="{{ $notification->icon }}" class="h-4 w-4"></i></span>
                    <span class="min-w-0 flex-1">
                        <span class="flex items-start justify-between gap-3"><span class="truncate text-[11px] font-semibold">{{ $notification->title }}</span>@unless($notification->is_read)<span data-unread-dot class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-red-500"></span>@endunless</span>
                        <span class="mt-1 block text-[10px] leading-4 text-muted-foreground">{{ $notification->message }}</span>
                        <span class="mt-1.5 block text-[9px] text-muted-foreground">{{ $notification->formatted_time }}</span>
                    </span>
                </button>
            @empty
                <div class="px-5 py-8 text-center"><i data-lucide="bell-off" class="mx-auto h-5 w-5 text-muted-foreground"></i><p class="mt-2 text-xs font-semibold">No admin alerts yet</p><p class="mt-1 text-[10px] text-muted-foreground">Signal delivery receipts will appear here.</p></div>
            @endforelse
        </div>
    </div>
</details>

<script>
    (() => {
        if (window.__adminNotificationCenterBound) return;
        window.__adminNotificationCenterBound = true;

        const token = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        document.addEventListener('click', async (event) => {
            const item = event.target.closest('[data-admin-notification-item]');
            if (item) {
                const destination = item.dataset.destination;
                try {
                    await fetch(item.dataset.readUrl, {
                        method: 'PATCH',
                        headers: { 'X-CSRF-TOKEN': token(), 'Accept': 'application/json' },
                        credentials: 'same-origin',
                    });
                } catch (_) {}
                if (destination) window.location.href = destination;
                return;
            }

            const markAll = event.target.closest('[data-admin-notifications-read-all]');
            if (!markAll) return;

            try {
                const response = await fetch(markAll.dataset.adminNotificationsReadAll, {
                    method: 'PATCH',
                    headers: { 'X-CSRF-TOKEN': token(), 'Accept': 'application/json' },
                    credentials: 'same-origin',
                });
                if (!response.ok) return;

                document.querySelector('[data-admin-notification-badge]')?.classList.add('hidden');
                document.querySelectorAll('[data-unread-dot]').forEach((dot) => dot.remove());
                markAll.remove();
            } catch (_) {}
        });
    })();
</script>
