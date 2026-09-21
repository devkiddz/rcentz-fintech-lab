<header class="customer-shell-topbar">
    <div class="customer-shell-topbar-inner">
        <div class="flex min-w-0 items-center gap-2.5">
            <button
                type="button"
                onclick="toggleSidebar()"
                class="customer-shell-icon lg:hidden"
                aria-label="Open navigation"
            >
                <i data-lucide="menu" class="h-4 w-4"></i>
            </button>

            <div class="min-w-0">
                <div class="flex min-w-0 items-center gap-2">
                    <span class="hidden shrink-0 text-[9px] font-semibold uppercase tracking-[.16em] text-muted-foreground sm:inline">Workspace</span>
                    <span class="hidden h-1 w-1 rounded-full bg-muted-foreground/35 sm:block"></span>
                    <div class="truncate text-sm font-semibold tracking-tight text-foreground sm:text-base">{{ isset($header) ? $header : site_name() }}</div>
                </div>
                <p class="mt-0.5 hidden truncate text-[10px] text-muted-foreground md:block">Markets · investments · trading · account</p>
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-1.5 sm:gap-2">
            @if(auth()->user()->isAdmin() && !app('impersonate')->isImpersonating())
                <a href="{{ route('admin.dashboard') }}" class="hidden items-center gap-1.5 rounded-full px-3 py-1.5 text-[10px] font-semibold sm:inline-flex" style="background:color-mix(in srgb,var(--brand-primary) 8%,transparent);color:color-mix(in srgb,var(--brand-primary) 82%,hsl(var(--foreground)))">
                    <i data-lucide="shield-check" class="h-3.5 w-3.5"></i>
                    <span>Admin</span>
                </a>
            @endif

            @if(app('impersonate')->isImpersonating())
                <a href="{{ route('impersonate.leave') }}" class="shell-impersonation-pill" title="Return to administrator account">
                    <i data-lucide="log-out" class="h-3.5 w-3.5"></i>
                    <span class="hidden sm:inline">Stop impersonating</span>
                    <span class="sm:hidden">Exit</span>
                </a>
            @endif

            <div class="relative" data-customer-notifications>
                <button
                    type="button"
                    class="customer-shell-icon relative"
                    data-notification-toggle
                    aria-label="Open notifications"
                    aria-expanded="false"
                    aria-controls="customer-notifications-dropdown"
                >
                    <i data-lucide="bell" class="h-4 w-4"></i>
                    <span class="absolute -right-1 -top-1 hidden min-w-4 rounded-full bg-red-500 px-1 text-center text-[10px] font-semibold leading-4 text-white" data-notification-badge>0</span>
                </button>

                <div
                    id="customer-notifications-dropdown"
                    class="absolute right-0 z-50 mt-2 hidden w-[min(23rem,calc(100vw-2rem))] overflow-hidden rounded-2xl bg-card shadow-2xl ring-1 ring-border/60"
                    data-notification-dropdown
                >
                    <div class="flex items-center justify-between gap-4 px-4 py-3.5">
                        <div>
                            <h3 class="text-sm font-semibold text-foreground">Notifications</h3>
                            <p class="mt-0.5 text-[10px] text-muted-foreground" data-notification-summary>Recent account activity</p>
                        </div>
                        <button type="button" class="text-[10px] font-semibold text-muted-foreground transition hover:text-foreground disabled:cursor-not-allowed disabled:opacity-40" data-notification-mark-all disabled>Mark all read</button>
                    </div>

                    <div class="max-h-80 overflow-y-auto border-y border-border/50" data-notification-list>
                        <div class="p-5 text-center">
                            <div class="mx-auto mb-2 flex h-8 w-8 items-center justify-center rounded-lg bg-muted/60">
                                <i data-lucide="loader-2" class="h-4 w-4 animate-spin text-muted-foreground"></i>
                            </div>
                            <p class="text-xs text-muted-foreground">Loading notifications...</p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-3 px-4 py-3">
                        <span class="text-[9px] text-muted-foreground">Account alerts and activity</span>
                        <a href="{{ route('notifications.index') }}" class="text-[10px] font-semibold text-foreground transition hover:text-primary">View all</a>
                    </div>
                </div>
            </div>

            @include('partials.shell.theme-toggle')

            <a href="{{ route('profile.edit') }}" class="hidden items-center gap-2 rounded-xl bg-muted/55 px-2 py-1.5 transition hover:bg-muted sm:flex" title="Account profile">
                <div class="flex h-8 w-8 items-center justify-center overflow-hidden rounded-lg text-xs font-semibold text-white" style="background:var(--brand-primary)">
                    @if(auth()->user()->profile_image)
                        <img src="{{ asset('storage/' . auth()->user()->profile_image) }}" alt="{{ auth()->user()->name }}" class="h-full w-full object-cover">
                    @else
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    @endif
                </div>
                <span class="hidden max-w-28 truncate text-[11px] font-semibold text-foreground xl:block">{{ auth()->user()->name }}</span>
            </a>
        </div>
    </div>
</header>
