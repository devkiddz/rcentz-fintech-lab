<header class="shell-topbar">
    <div class="shell-topbar-inner">
        <div class="flex min-w-0 items-center gap-3">
            <button
                type="button"
                onclick="toggleSidebar()"
                class="shell-icon-button lg:hidden"
                aria-label="Open navigation"
            >
                <i data-lucide="menu" class="h-4 w-4"></i>
            </button>

            <div class="min-w-0">
                @if (isset($header))
                    <div class="truncate text-sm font-medium tracking-tight text-foreground sm:text-base">
                        {{ $header }}
                    </div>
                @else
                    <div class="truncate text-sm font-medium tracking-tight text-foreground sm:text-base">
                        {{ site_name() }}
                    </div>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-2 sm:gap-2.5">
            @if(auth()->user()->isAdmin() && !app('impersonate')->isImpersonating())
                <a href="{{ route('admin.dashboard') }}" class="hidden items-center gap-1.5 rounded-full border border-red-500/20 bg-red-500/[.06] px-3 py-1.5 text-[10px] font-semibold text-red-600 hover:bg-red-500/10 sm:inline-flex dark:text-red-400">
                    <i data-lucide="shield-check" class="h-3.5 w-3.5"></i>
                    <span>Return to Admin</span>
                </a>
            @endif

            @include('partials.shell.theme-toggle')

            @if(app('impersonate')->isImpersonating())
                <a
                    href="{{ route('impersonate.leave') }}"
                    class="shell-impersonation-pill"
                    title="Return to administrator account"
                >
                    <i data-lucide="log-out" class="h-3.5 w-3.5"></i>
                    <span class="hidden sm:inline">Stop impersonating</span>
                    <span class="sm:hidden">Exit</span>
                </a>
            @endif

            <div class="relative" data-customer-notifications>
                <button
                    type="button"
                    class="shell-icon-button relative"
                    data-notification-toggle
                    aria-label="Open notifications"
                    aria-expanded="false"
                    aria-controls="customer-notifications-dropdown"
                >
                    <i data-lucide="bell" class="h-4 w-4"></i>
                    <span
                        class="absolute -right-1 -top-1 hidden min-w-4 rounded-full bg-red-500 px-1 text-center text-[10px] font-semibold leading-4 text-white"
                        data-notification-badge
                    >0</span>
                </button>

                <div
                    id="customer-notifications-dropdown"
                    class="absolute right-0 z-50 mt-2 hidden w-[min(23rem,calc(100vw-2rem))] overflow-hidden rounded-xl border border-border bg-card shadow-xl"
                    data-notification-dropdown
                >
                    <div class="flex items-center justify-between gap-4 border-b border-border px-4 py-3">
                        <div>
                            <h3 class="text-sm font-medium text-foreground">Notifications</h3>
                            <p class="mt-0.5 text-[11px] text-muted-foreground" data-notification-summary>Recent account activity</p>
                        </div>
                        <button
                            type="button"
                            class="text-xs font-medium text-muted-foreground transition hover:text-foreground disabled:cursor-not-allowed disabled:opacity-40"
                            data-notification-mark-all
                            disabled
                        >
                            Mark all read
                        </button>
                    </div>

                    <div class="max-h-80 overflow-y-auto" data-notification-list>
                        <div class="p-5 text-center">
                            <div class="mx-auto mb-2 flex h-8 w-8 items-center justify-center rounded-lg border border-border bg-muted/50">
                                <i data-lucide="loader-2" class="h-4 w-4 animate-spin text-muted-foreground"></i>
                            </div>
                            <p class="text-xs text-muted-foreground">Loading notifications...</p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-3 border-t border-border px-4 py-3">
                        <span class="text-[10px] text-muted-foreground">Account alerts and activity</span>
                        <a
                            href="{{ route('notifications.index') }}"
                            class="text-xs font-medium text-foreground transition hover:text-primary"
                        >
                            View all
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
