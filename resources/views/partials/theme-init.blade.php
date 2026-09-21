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

        // Backward compatibility: migrate the old single theme value once into this shell.
        let savedTheme = localStorage.getItem(scopedKey) || localStorage.getItem(previousScopedKey);
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
