<header class="sticky top-0 z-40 border-b border-border bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/80">
    <div class="flex min-h-16 items-center justify-between gap-4 px-4 sm:px-6">
        <div class="flex min-w-0 items-center gap-3">
            <button
                type="button"
                onclick="toggleSidebar()"
                data-sidebar-mobile-toggle
                aria-controls="sidebar"
                aria-expanded="false"
                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-border bg-card text-foreground transition hover:bg-muted lg:hidden"
                aria-label="Open admin navigation"
            >
                <i data-lucide="menu" class="h-4 w-4"></i>
            </button>

            <button
                type="button"
                onclick="toggleDesktopSidebar()"
                data-sidebar-desktop-toggle
                aria-controls="sidebar"
                aria-expanded="true"
                class="hidden h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-border bg-card text-muted-foreground transition hover:bg-muted hover:text-foreground lg:inline-flex"
                title="Collapse admin navigation"
                aria-label="Collapse admin navigation"
            >
                <i data-lucide="panel-left-close" class="sidebar-desktop-expanded-icon h-4 w-4"></i>
                <i data-lucide="panel-left-open" class="sidebar-desktop-collapsed-icon hidden h-4 w-4"></i>
            </button>

            <button
                type="button"
                onclick="adminGoBack('{{ route('admin.dashboard') }}')"
                class="inline-flex h-9 shrink-0 items-center gap-2 rounded-lg border border-border bg-card px-2.5 text-sm font-medium text-foreground transition hover:bg-muted sm:px-3"
                aria-label="Go back"
                title="Back"
            >
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                <span class="hidden sm:inline">Back</span>
            </button>

            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <span class="hidden text-[11px] font-semibold uppercase tracking-[0.16em] text-muted-foreground sm:inline">Admin</span>
                    <span class="hidden h-1 w-1 rounded-full bg-muted-foreground/50 sm:block"></span>
                    <span class="truncate text-sm font-semibold text-foreground sm:text-base">
                        {{ isset($header) ? $header : 'Operations Overview' }}
                    </span>
                </div>
                <p class="hidden truncate text-xs text-muted-foreground md:block">
                    Manage customers, finance, trading products and platform operations.
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('home') }}" target="_blank"
               class="hidden h-9 items-center gap-2 rounded-lg border border-border bg-card px-3 text-sm font-medium text-foreground transition hover:bg-muted md:inline-flex">
                <i data-lucide="external-link" class="h-4 w-4"></i>
                View site
            </a>

            <a href="{{ route('admin.settings.index') }}"
               class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-border bg-card text-muted-foreground transition hover:bg-muted hover:text-foreground"
               title="Platform settings">
                <i data-lucide="settings" class="h-4 w-4"></i>
            </a>

            @include('partials.shell.theme-toggle')

            <div class="hidden h-7 w-px bg-border sm:block"></div>

            <a href="{{ route('admin.profile.edit') }}"
               class="hidden items-center gap-3 rounded-lg px-2 py-1.5 transition hover:bg-muted sm:flex">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-foreground text-xs font-semibold text-background">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="hidden min-w-0 text-left xl:block">
                    <p class="max-w-36 truncate text-xs font-semibold text-foreground">{{ auth()->user()->name }}</p>
                    <p class="text-[11px] text-muted-foreground">Administrator</p>
                </div>
                <i data-lucide="chevron-down" class="hidden h-4 w-4 text-muted-foreground xl:block"></i>
            </a>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-border bg-card text-muted-foreground transition hover:bg-muted hover:text-red-600"
                    title="Sign out">
                    <i data-lucide="log-out" class="h-4 w-4"></i>
                </button>
            </form>
        </div>
    </div>
</header>
