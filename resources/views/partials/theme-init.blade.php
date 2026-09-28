@php
    $resolvedThemeScope = in_array($themeScope ?? null, ['public', 'customer', 'admin'], true)
        ? $themeScope
        : 'public';
@endphp
<script>
    (() => {
        const scope = @json($resolvedThemeScope);
        const scopedKey = `platform_theme:${scope}`;
        const previousScopedKey = `rcentz_theme:${scope}`;
        const themeMedia = window.matchMedia('(prefers-color-scheme: dark)');

        const applyTheme = (theme) => {
            const shouldUseDark = theme === 'dark' || (theme === null && themeMedia.matches);
            document.documentElement.classList.toggle('dark', shouldUseDark);
            document.documentElement.dataset.theme = shouldUseDark ? 'dark' : 'light';
            document.documentElement.dataset.themeScope = scope;
        };

        const getStoredTheme = () => {
            const storedTheme = localStorage.getItem(scopedKey);

            if (storedTheme) {
                return storedTheme;
            }

            const previousTheme = localStorage.getItem(previousScopedKey);

            if (previousTheme) {
                localStorage.setItem(scopedKey, previousTheme);
                localStorage.removeItem(previousScopedKey);
                return previousTheme;
            }

            return null;
        };

        const setTheme = (theme) => {
            if (theme === null || theme === 'system') {
                localStorage.removeItem(scopedKey);
                localStorage.removeItem(previousScopedKey);
                applyTheme(null);
                return;
            }

            localStorage.setItem(scopedKey, theme);
            localStorage.removeItem(previousScopedKey);
            applyTheme(theme);
        };

        const toggleTheme = () => {
            setTheme(document.documentElement.classList.contains('dark') ? 'light' : 'dark');
        };

        let savedTheme = getStoredTheme();
        const legacyTheme = localStorage.getItem('theme');

        if (!savedTheme && legacyTheme) {
            savedTheme = legacyTheme;
            localStorage.setItem(scopedKey, legacyTheme);
        }

        applyTheme(savedTheme);

        // Theme control is bootstrapped before the Vite bundle so optional
        // services such as realtime can never make the theme switch unavailable.
        window.AxausTheme = {
            apply: applyTheme,
            get: getStoredTheme,
            set: setTheme,
            toggle: toggleTheme,
            getScope: () => scope,
            getStorageKey: () => scopedKey,
        };

        window.toggleTheme = toggleTheme;
    })();
</script>
