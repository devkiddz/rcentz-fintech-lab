<script>
    (() => {
        const path = window.location.pathname;

        let scope = 'public';

        if (path === '/admin' || path.startsWith('/admin/')) {
            scope = 'admin';
        } else {
            // All non-admin authenticated/customer routes share the customer theme.
            // Impersonation does not change this because the browser is on a customer route.
            scope = 'customer';
        }

        const scopedKey = `rcentz_theme:${scope}`;

        // Backward compatibility:
        // migrate the old single "theme" value once into the current scope.
        let savedTheme = localStorage.getItem(scopedKey);
        const legacyTheme = localStorage.getItem('theme');

        if (!savedTheme && legacyTheme) {
            savedTheme = legacyTheme;
            localStorage.setItem(scopedKey, legacyTheme);
        }

        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const shouldUseDark = savedTheme ? savedTheme === 'dark' : prefersDark;

        document.documentElement.classList.toggle('dark', shouldUseDark);
        document.documentElement.dataset.theme = shouldUseDark ? 'dark' : 'light';
        document.documentElement.dataset.themeScope = scope;
    })();
</script>
