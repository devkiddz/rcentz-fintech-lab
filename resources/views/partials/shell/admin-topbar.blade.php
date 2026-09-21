<header class="admin-shell-topbar">
    <div class="admin-shell-topbar-inner">
        <div class="flex min-w-0 items-center gap-2.5">
            <button
                type="button"
                onclick="toggleSidebar()"
                data-sidebar-mobile-toggle
                aria-controls="sidebar"
                aria-expanded="false"
                class="admin-shell-icon lg:hidden"
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
                class="admin-shell-icon hidden lg:inline-flex"
                title="Collapse admin navigation"
                aria-label="Collapse admin navigation"
            >
                <i data-lucide="panel-left-close" class="sidebar-desktop-expanded-icon h-4 w-4"></i>
                <i data-lucide="panel-left-open" class="sidebar-desktop-collapsed-icon hidden h-4 w-4"></i>
            </button>

            <button
                type="button"
                onclick="adminGoBack('{{ route('admin.dashboard') }}')"
                class="admin-shell-icon hidden sm:inline-flex"
                aria-label="Go back"
                title="Back"
            >
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
            </button>

            <div class="min-w-0 pl-0.5">
                <div class="flex min-w-0 items-center gap-2">
                    <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2 py-1 text-[9px] font-bold uppercase tracking-[.16em]" style="background:color-mix(in srgb,var(--brand-primary) 9%,transparent);color:color-mix(in srgb,var(--brand-primary) 82%,hsl(var(--foreground)))">
                        <i data-lucide="shield-check" class="h-3 w-3"></i>
                        Admin
                    </span>
                    <span class="truncate text-sm font-semibold tracking-tight text-foreground sm:text-base">{{ isset($header) ? $header : 'Operations Overview' }}</span>
                </div>
                <p class="mt-0.5 hidden truncate text-[10px] text-muted-foreground md:block">Platform control · customers · finance · markets · automation</p>
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-1.5 sm:gap-2">
            <a href="{{ route('home') }}" target="_blank" class="admin-shell-chip hidden md:inline-flex" title="Open public website">
                <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
                <span class="hidden xl:inline">Public site</span>
            </a>

            @include('partials.shell.admin-notification-bell')

            <a href="{{ route('admin.settings.index') }}" class="admin-shell-icon" title="Platform settings" aria-label="Platform settings">
                <i data-lucide="settings" class="h-4 w-4"></i>
            </a>

            @include('partials.shell.theme-toggle')

            <a href="{{ route('admin.profile.edit') }}" class="ml-0.5 hidden items-center gap-2.5 rounded-xl bg-muted/55 px-2 py-1.5 transition hover:bg-muted sm:flex" title="Admin profile">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg text-xs font-semibold text-white" style="background:var(--brand-primary)">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                <div class="hidden min-w-0 text-left 2xl:block">
                    <p class="max-w-32 truncate text-[11px] font-semibold text-foreground">{{ auth()->user()->name }}</p>
                    <p class="text-[9px] text-muted-foreground">Administrator</p>
                </div>
            </a>
        </div>
    </div>
</header>
