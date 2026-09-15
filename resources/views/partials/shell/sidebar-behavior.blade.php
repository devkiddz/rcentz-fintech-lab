<style>
    #sidebar {
        scrollbar-width: thin;
        scrollbar-color: hsl(var(--border)) transparent;
    }

    #sidebar summary::-webkit-details-marker {
        display: none;
    }

    @media (min-width: 1024px) {
        #sidebar,
        #workspace-main {
            transition: width .22s ease, margin-left .22s ease;
        }

        body.sidebar-collapsed #sidebar {
            width: 5.25rem !important;
        }

        body.sidebar-collapsed #workspace-main {
            margin-left: 5.25rem !important;
        }

        body.sidebar-collapsed #sidebar .sidebar-label,
        body.sidebar-collapsed #sidebar .sidebar-section-label,
        body.sidebar-collapsed #sidebar .sidebar-chevron,
        body.sidebar-collapsed #sidebar .sidebar-subnav,
        body.sidebar-collapsed #sidebar .sidebar-profile-copy {
            display: none !important;
        }

        body.sidebar-collapsed #sidebar [data-sidebar-nav] > a,
        body.sidebar-collapsed #sidebar summary,
        body.sidebar-collapsed #sidebar .sidebar-utility {
            justify-content: center !important;
            padding-left: .75rem !important;
            padding-right: .75rem !important;
        }

        body.sidebar-collapsed #sidebar .sidebar-collapse-icon {
            transform: rotate(180deg);
        }
    }
</style>

<script>
    (() => {
        const scope = window.location.pathname.startsWith('/admin') ? 'admin' : 'customer';

        window.toggleSidebar = function () {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            if (!sidebar || !overlay) return;

            const open = sidebar.classList.contains('translate-x-0');

            sidebar.classList.toggle('translate-x-0', !open);
            sidebar.classList.toggle('-translate-x-full', open);
            overlay.classList.toggle('hidden', open);
        };

        window.openSidebar = function () {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            if (!sidebar || !overlay) return;
            sidebar.classList.remove('-translate-x-full');
            sidebar.classList.add('translate-x-0');
            overlay.classList.remove('hidden');
        };

        window.toggleWorkspaceTheme = function () {
            const html = document.documentElement;
            const next = html.classList.contains('dark') ? 'light' : 'dark';
            html.classList.toggle('dark', next === 'dark');
            html.dataset.theme = next;
            localStorage.setItem(`rcentz_theme:${scope}`, next);
            if (window.lucide) lucide.createIcons();
        };

        window.toggleDesktopSidebar = function () {
            if (window.innerWidth < 1024) return;
            document.body.classList.toggle('sidebar-collapsed');
            localStorage.setItem(
                `rcentz_sidebar_collapsed:${scope}`,
                document.body.classList.contains('sidebar-collapsed') ? '1' : '0'
            );
        };

        document.addEventListener('DOMContentLoaded', () => {
            if (
                window.innerWidth >= 1024 &&
                localStorage.getItem(`rcentz_sidebar_collapsed:${scope}`) === '1'
            ) {
                document.body.classList.add('sidebar-collapsed');
            }

            document.querySelectorAll('[data-nav-group]').forEach((group) => {
                const key = group.dataset.navGroup;
                const storageKey = `rcentz_nav_group:${scope}:${key}`;
                const routeOpened = group.hasAttribute('open');
                const saved = localStorage.getItem(storageKey);

                if (!routeOpened && saved === '1') group.open = true;

                group.addEventListener('toggle', () => {
                    localStorage.setItem(storageKey, group.open ? '1' : '0');
                });
            });

            if (window.lucide) lucide.createIcons();
        });
    })();
</script>
