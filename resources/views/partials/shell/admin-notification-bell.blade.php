@php
    $adminNotificationUser = auth()->user();
    $adminNotifications = $adminNotificationUser
        ? $adminNotificationUser->notifications()->latest()->limit(8)->get()
        : collect();
    $adminUnreadCount = $adminNotificationUser
        ? $adminNotificationUser->notifications()->unread()->count()
        : 0;
@endphp

<details class="group relative z-[1100]" data-admin-notification-center data-api-url="{{ route('admin.notifications.api') }}">
    <summary class="relative cursor-pointer list-none [&::-webkit-details-marker]:hidden" title="Admin notifications" aria-label="Admin notifications">
        <i data-lucide="bell" class="h-4 w-4"></i>
        <span data-admin-notification-badge class="{{ $adminUnreadCount > 0 ? '' : 'hidden' }} absolute -right-1 -top-1 min-w-4 rounded-full bg-red-600 px-1 text-center text-[9px] font-bold leading-4 text-white">
            {{ $adminUnreadCount > 99 ? '99+' : $adminUnreadCount }}
        </span>
    </summary>

    <div class="admin-notification-panel absolute right-0 mt-2 text-card-foreground">
        <div class="flex items-center justify-between gap-3 px-4 py-3.5">
            <div class="min-w-0">
                <p class="text-sm font-semibold text-foreground">Notifications</p>
                <p class="mt-0.5 truncate text-[10px] text-muted-foreground">Admin activity and platform alerts</p>
            </div>
            @if($adminUnreadCount > 0)
                <button type="button"
                        data-admin-notifications-read-all="{{ route('admin.notifications.mark-all-read') }}"
                        class="shrink-0 text-[10px] font-semibold text-muted-foreground transition hover:text-foreground">
                    Mark all read
                </button>
            @endif
        </div>

        <div class="admin-notification-list max-h-[24rem] overflow-y-auto border-y border-border/55" data-admin-notification-list>
            @forelse($adminNotifications as $notification)
                @php
                    $data = (array) ($notification->data ?? []);
                    $destination = !empty($data['action_url'])
                        ? url($data['action_url'])
                        : (!empty($data['signal_id']) && Route::has('admin.signals.show')
                            ? route('admin.signals.show', $data['signal_id'])
                            : route('admin.dashboard'));
                @endphp
                <button type="button"
                        data-admin-notification-item
                        data-read-url="{{ route('admin.notifications.read', $notification) }}"
                        data-destination="{{ $destination }}"
                        class="group flex w-full items-start gap-3 border-b border-border/45 px-4 py-3 text-left transition last:border-b-0 hover:bg-muted/35 {{ $notification->is_read ? '' : 'bg-muted/20' }}">
                    <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-muted/65 text-muted-foreground">
                        <i data-lucide="{{ $notification->icon ?: 'bell' }}" class="h-3.5 w-3.5"></i>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="flex items-start gap-2">
                            <span class="min-w-0 flex-1 truncate text-[11px] font-semibold text-foreground">{{ $notification->title }}</span>
                            @unless($notification->is_read)
                                <span data-unread-dot class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full" style="background:var(--brand-primary)"></span>
                            @endunless
                        </span>
                        <span class="mt-1 line-clamp-2 block text-[10px] leading-4 text-muted-foreground">{{ $notification->message }}</span>
                        <span class="mt-1.5 block text-[9px] text-muted-foreground/80">{{ $notification->formatted_time }}</span>
                    </span>
                </button>
            @empty
                <div class="px-5 py-10 text-center">
                    <div class="mx-auto flex h-9 w-9 items-center justify-center rounded-lg bg-muted/60 text-muted-foreground">
                        <i data-lucide="bell-off" class="h-4 w-4"></i>
                    </div>
                    <p class="mt-3 text-xs font-semibold text-foreground">No admin alerts</p>
                    <p class="mt-1 text-[10px] text-muted-foreground">New platform activity will appear here.</p>
                </div>
            @endforelse
        </div>

        <div class="flex items-center justify-between gap-3 px-4 py-3">
            <span class="text-[9px] text-muted-foreground">Operational activity</span>
            <span class="text-[9px] text-muted-foreground">{{ $adminNotifications->count() }} recent</span>
        </div>
    </div>
</details>

<script>
    (() => {
        if (window.__adminNotificationCenterBound) return;
        window.__adminNotificationCenterBound = true;

        const token = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const center = document.querySelector('[data-admin-notification-center]');
        const badge = center?.querySelector('[data-admin-notification-badge]');
        const refreshCenter = async () => {
            if (!center?.dataset.apiUrl) return;
            try {
                const response = await fetch(center.dataset.apiUrl,{headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},credentials:'same-origin',cache:'no-store'});
                if (!response.ok) return;
                const payload = await response.json(); const count = Number(payload.unread_count || 0);
                if (badge) { badge.textContent = count > 99 ? '99+' : String(count); badge.classList.toggle('hidden', count < 1); }
            } catch (_) {}
        };
        refreshCenter(); window.setInterval(refreshCenter,20000);

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
