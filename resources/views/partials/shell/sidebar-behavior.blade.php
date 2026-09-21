<style>
    #sidebar {
        scrollbar-width: thin;
        scrollbar-color: hsl(var(--border)) transparent;
    }

    #sidebar summary::-webkit-details-marker {
        display: none;
    }

    /* MS10 R2E: direction-aware shell geometry. */
    html[dir="rtl"] #sidebar { left: auto !important; right: 0 !important; }
    html[dir="rtl"] #sidebar.-translate-x-full { transform: translateX(100%) !important; }
    html[dir="rtl"] #sidebar.translate-x-0 { transform: translateX(0) !important; }
    html[dir="rtl"] #workspace-main { margin-left: 0 !important; }
    html[dir="rtl"] #sidebar .sidebar-subnav { margin-left: 0 !important; padding-left: 0 !important; border-left: 0 !important; margin-right: .8rem !important; padding-right: 1rem !important; border-right: 1px solid hsl(var(--border) / .7) !important; }
    html[dir="rtl"] #sidebar .sidebar-subnav::before { left: auto !important; right: .14rem !important; }
    html[dir="rtl"] .text-left { text-align: right !important; }
    html[dir="rtl"] .text-right { text-align: left !important; }
    html[dir="rtl"] [data-language-switcher] { direction: rtl; }
    html[dir="rtl"] [data-language-switcher] [dir="ltr"] { direction: ltr; }

    @media (min-width: 1024px) {
        html[dir="rtl"] #workspace-main { margin-right: 18rem !important; }
        html[dir="rtl"] body.sidebar-collapsed #workspace-main { margin-right: 5.25rem !important; margin-left: 0 !important; }
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
        body.sidebar-collapsed #sidebar .sidebar-profile-copy,
        body.sidebar-collapsed #sidebar .sidebar-brand-full {
            display: none !important;
        }

        body.sidebar-collapsed #sidebar .sidebar-brand-compact {
            display: flex !important;
        }

        body.sidebar-collapsed #sidebar .sidebar-header,
        body.sidebar-collapsed #sidebar .sidebar-profile {
            justify-content: center !important;
            padding-left: .75rem !important;
            padding-right: .75rem !important;
        }

        body.sidebar-collapsed #sidebar [data-sidebar-nav] > a,
        body.sidebar-collapsed #sidebar summary,
        body.sidebar-collapsed #sidebar .sidebar-utility {
            justify-content: center !important;
            padding-left: .75rem !important;
            padding-right: .75rem !important;
        }

        body.sidebar-collapsed .sidebar-desktop-expanded-icon {
            display: none !important;
        }

        body.sidebar-collapsed .sidebar-desktop-collapsed-icon {
            display: block !important;
        }
    }
</style>

<script>
    (() => {
        const scope = window.location.pathname.startsWith('/admin') ? 'admin' : 'customer';
        const desktopStorageKey = `rcentz_sidebar_collapsed:${scope}`;
        const desktopBreakpoint = 1024;


        const elements = () => ({
            sidebar: document.getElementById('sidebar'),
            overlay: document.getElementById('sidebar-overlay'),
            mobileToggles: document.querySelectorAll('[data-sidebar-mobile-toggle]'),
            desktopToggle: document.querySelector('[data-sidebar-desktop-toggle]'),
        });

        const setMobileState = (open) => {
            const { sidebar, overlay, mobileToggles } = elements();
            if (!sidebar || !overlay) return;

            sidebar.classList.toggle('translate-x-0', open);
            sidebar.classList.toggle('-translate-x-full', !open);
            overlay.classList.toggle('hidden', !open);
            overlay.setAttribute('aria-hidden', open ? 'false' : 'true');
            document.body.classList.toggle('overflow-hidden', open && window.innerWidth < desktopBreakpoint);

            mobileToggles.forEach((button) => {
                button.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
        };

        const syncDesktopState = () => {
            const { desktopToggle } = elements();
            const collapsed = document.body.classList.contains('sidebar-collapsed');
            if (!desktopToggle) return;

            desktopToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            desktopToggle.setAttribute('aria-label', collapsed ? 'Expand admin navigation' : 'Collapse admin navigation');
            desktopToggle.setAttribute('title', collapsed ? 'Expand admin navigation' : 'Collapse admin navigation');
        };

        window.openSidebar = function () {
            if (window.innerWidth >= desktopBreakpoint) {
                document.body.classList.remove('sidebar-collapsed');
                localStorage.setItem(desktopStorageKey, '0');
                syncDesktopState();
                return;
            }

            setMobileState(true);
        };

        window.closeSidebar = function () {
            if (window.innerWidth >= desktopBreakpoint) return;
            setMobileState(false);
        };

        window.toggleSidebar = function () {
            if (window.innerWidth >= desktopBreakpoint) {
                window.toggleDesktopSidebar();
                return;
            }

            const { sidebar } = elements();
            if (!sidebar) return;
            setMobileState(!sidebar.classList.contains('translate-x-0'));
        };

        window.adminGoBack = function (fallbackUrl) {
            try {
                const referrer = document.referrer ? new URL(document.referrer) : null;
                const cameFromAdmin = referrer
                    && referrer.origin === window.location.origin
                    && referrer.pathname.startsWith('/admin');

                if (cameFromAdmin && window.history.length > 1) {
                    window.history.back();
                    return;
                }
            } catch (_) {
                // Fall through to the safe admin fallback.
            }

            window.location.assign(fallbackUrl || '/admin');
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
            if (window.innerWidth < desktopBreakpoint) {
                window.toggleSidebar();
                return;
            }

            document.body.classList.toggle('sidebar-collapsed');
            localStorage.setItem(
                desktopStorageKey,
                document.body.classList.contains('sidebar-collapsed') ? '1' : '0'
            );
            syncDesktopState();
        };

        document.addEventListener('DOMContentLoaded', () => {
            if (window.innerWidth >= desktopBreakpoint && localStorage.getItem(desktopStorageKey) === '1') {
                document.body.classList.add('sidebar-collapsed');
            }
            syncDesktopState();

            document.querySelectorAll('[data-nav-group]').forEach((group) => {
                const key = group.dataset.navGroup;
                const storageKey = `rcentz_nav_group:${scope}:${key}`;
                const routeOpened = group.hasAttribute('open');
                const saved = localStorage.getItem(storageKey);
                const summary = group.querySelector(':scope > summary');

                if (!routeOpened && saved === '1' && !document.body.classList.contains('sidebar-collapsed')) {
                    group.open = true;
                }

                if (summary) {
                    summary.addEventListener('click', (event) => {
                        if (window.innerWidth >= desktopBreakpoint && document.body.classList.contains('sidebar-collapsed')) {
                            event.preventDefault();
                            document.body.classList.remove('sidebar-collapsed');
                            localStorage.setItem(desktopStorageKey, '0');
                            syncDesktopState();
                            window.setTimeout(() => {
                                group.open = true;
                                localStorage.setItem(storageKey, '1');
                            }, 30);
                        }
                    });
                }

                group.addEventListener('toggle', () => {
                    if (!document.body.classList.contains('sidebar-collapsed')) {
                        localStorage.setItem(storageKey, group.open ? '1' : '0');
                    }
                });
            });

            document.querySelectorAll('#sidebar a').forEach((link) => {
                link.addEventListener('click', () => {
                    if (window.innerWidth < desktopBreakpoint) setMobileState(false);
                });
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && window.innerWidth < desktopBreakpoint) {
                    setMobileState(false);
                }
            });

            window.addEventListener('resize', () => {
                if (window.innerWidth >= desktopBreakpoint) {
                    setMobileState(false);
                    if (localStorage.getItem(desktopStorageKey) === '1') {
                        document.body.classList.add('sidebar-collapsed');
                    }
                } else {
                    document.body.classList.remove('sidebar-collapsed');
                    setMobileState(false);
                }
                syncDesktopState();
            });

            if (window.lucide) lucide.createIcons();
        });
    })();
</script>
