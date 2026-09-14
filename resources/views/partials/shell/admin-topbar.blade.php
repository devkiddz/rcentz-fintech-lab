<header class="shell-topbar">
    <div class="shell-topbar-inner">
        <div class="flex min-w-0 items-center gap-3">
            <button
                type="button"
                onclick="toggleSidebar()"
                class="shell-icon-button lg:hidden"
                aria-label="Open admin navigation"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>

            <div class="min-w-0">
                @if (isset($header))
                    <div class="truncate text-sm font-medium tracking-tight text-foreground sm:text-base">
                        {{ $header }}
                    </div>
                @else
                    <div class="truncate text-sm font-medium tracking-tight text-foreground sm:text-base">
                        Admin workspace
                    </div>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-2">
            <span class="hidden rounded-md border border-border bg-muted/40 px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-muted-foreground sm:inline-flex">
                Administrator
            </span>
            @include('partials.shell.theme-toggle')
        </div>
    </div>
</header>
