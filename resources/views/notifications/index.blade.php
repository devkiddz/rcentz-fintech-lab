<x-user-layout>
    <x-slot name="header">
        Notifications
    </x-slot>

    <div class="mx-auto max-w-5xl space-y-4">
        <section class="ui-panel overflow-hidden">
            <div class="flex flex-col gap-4 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div>
                    <p class="ui-kicker">Notification center</p>
                    <h1 class="mt-1 text-xl font-semibold text-foreground">Account notifications</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        @if($unreadCount > 0)
                            {{ $unreadCount }} unread notification{{ $unreadCount === 1 ? '' : 's' }}.
                        @else
                            You're all caught up.
                        @endif
                    </p>
                </div>

                @if($unreadCount > 0)
                    <button
                        type="button"
                        onclick="window.CustomerNotifications.markAllRead({reload: true})"
                        class="ui-btn ui-btn-secondary self-start sm:self-auto"
                    >
                        <i data-lucide="check-check" class="h-4 w-4"></i>
                        Mark all read
                    </button>
                @endif
            </div>
        </section>

        <section class="ui-panel overflow-hidden">
            @forelse($notifications as $notification)
                @php
                    $actionUrl = data_get($notification->data, 'action_url');
                    $typeLabel = \Illuminate\Support\Str::of($notification->type)->replace('_', ' ')->title();
                @endphp

                <article class="border-b border-border px-5 py-4 last:border-b-0 sm:px-6 {{ $notification->is_read ? '' : 'bg-primary/[.025]' }}">
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-border bg-muted/30 text-muted-foreground">
                            <i data-lucide="{{ $notification->icon }}" class="h-4 w-4"></i>
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-start justify-between gap-x-4 gap-y-2">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h2 class="text-sm font-semibold text-foreground">{{ $notification->title }}</h2>
                                        @if(!$notification->is_read)
                                            <span class="inline-flex items-center rounded-full bg-primary/10 px-2 py-0.5 text-[9px] font-semibold uppercase tracking-[.1em] text-primary">New</span>
                                        @endif
                                        <span class="inline-flex items-center rounded-full border border-border bg-muted/20 px-2 py-0.5 text-[9px] font-medium text-muted-foreground">{{ $typeLabel }}</span>
                                    </div>

                                    <p class="mt-1.5 max-w-3xl text-sm leading-6 text-muted-foreground">{{ $notification->message }}</p>
                                    <p class="mt-2 text-[11px] text-muted-foreground">{{ $notification->formatted_time }}</p>
                                </div>

                                <div class="flex shrink-0 items-center gap-1.5">
                                    @if($actionUrl)
                                        <a href="{{ $actionUrl }}" class="ui-btn ui-btn-ghost ui-btn-sm">
                                            Open
                                            <i data-lucide="arrow-up-right" class="h-3.5 w-3.5"></i>
                                        </a>
                                    @endif

                                    @if(!$notification->is_read)
                                        <button
                                            type="button"
                                            onclick="window.CustomerNotifications.markAsRead({{ $notification->id }}, {reload: true})"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-border text-muted-foreground transition hover:bg-muted hover:text-foreground"
                                            title="Mark as read"
                                            aria-label="Mark notification as read"
                                        >
                                            <i data-lucide="check" class="h-3.5 w-3.5"></i>
                                        </button>
                                    @endif

                                    <button
                                        type="button"
                                        onclick="window.CustomerNotifications.deleteNotification({{ $notification->id }}, {reload: true, confirmDelete: true})"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-border text-muted-foreground transition hover:border-red-500/30 hover:bg-red-500/5 hover:text-red-600"
                                        title="Delete notification"
                                        aria-label="Delete notification"
                                    >
                                        <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <div class="ui-empty-state py-14">
                    <div class="ui-empty-icon"><i data-lucide="bell-off" class="h-5 w-5"></i></div>
                    <h2 class="font-medium text-foreground">No notifications yet</h2>
                    <p class="mt-1 max-w-sm text-sm text-muted-foreground">Account alerts, Signal deliveries and other activity will appear here.</p>
                </div>
            @endforelse
        </section>

        @if($notifications->hasPages())
            <div>
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</x-user-layout>
