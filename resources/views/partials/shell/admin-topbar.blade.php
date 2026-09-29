<header class="admin-shell-topbar">
    <div class="admin-shell-topbar-inner justify-between">
        <div class="flex shrink-0 items-center gap-1">
            <button
                type="button"
                onclick="toggleSidebar()"
                data-sidebar-mobile-toggle
                aria-controls="sidebar"
                aria-expanded="false"
                class="admin-shell-icon lg:hidden"
                aria-label="{{ localize('ui.r2e.admin.menu', 'Menu') }}"
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
                title="{{ localize('ui.r2e.admin.collapse_sidebar', 'Collapse sidebar') }}"
                aria-label="{{ localize('ui.r2e.admin.collapse_sidebar', 'Collapse sidebar') }}"
            >
                <i data-lucide="panel-left-close" class="sidebar-desktop-expanded-icon h-4 w-4"></i>
                <i data-lucide="panel-left-open" class="sidebar-desktop-collapsed-icon hidden h-4 w-4"></i>
            </button>
        </div>

        <div class="ml-auto flex shrink-0 items-center gap-1">
            @include('partials.shell.admin-notification-bell')

            <a href="{{ route('admin.settings.index') }}"
               class="admin-shell-icon hidden sm:inline-flex"
               title="{{ localize('ui.r2e.admin.platform_settings', 'Platform settings') }}"
               aria-label="{{ localize('ui.r2e.admin.platform_settings', 'Platform settings') }}">
                <i data-lucide="settings" class="h-4 w-4"></i>
            </a>

            <div class="hidden sm:block">
                @include('partials.language-switcher')
            </div>

            <div>
                @include('partials.shell.theme-toggle')
            </div>

            <a href="{{ route('admin.profile.edit') }}"
               class="ml-1 hidden h-8 w-8 items-center justify-center rounded-lg text-[11px] font-semibold text-white transition hover:opacity-90 sm:flex"
               style="background:var(--brand-primary)"
               title="{{ localize('ui.r2e.admin.admin_profile', 'Admin Profile') }}">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </a>
        </div>
    </div>
</header>
