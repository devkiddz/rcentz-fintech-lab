import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

const themeMedia = window.matchMedia('(prefers-color-scheme: dark)');

function getThemeScope() {
    const path = window.location.pathname;

    // Admin pages keep their own preference.
    if (path === '/admin' || path.startsWith('/admin/')) {
        return 'admin';
    }

    // Authenticated customer pages — including impersonated customer sessions —
    // use a separate preference from the admin workspace.
    const customerShell = document.querySelector('[data-theme-scope="customer"], #sidebar');

    if (customerShell && !document.body.classList.contains('admin-workspace')) {
        return 'customer';
    }

    return 'public';
}

function getThemeStorageKey(scope = null) {
    const resolvedScope = scope ?? getThemeScope();
    return `rcentz_theme:${resolvedScope}`;
}

function applyTheme(theme) {
    const shouldUseDark = theme === 'dark' || (theme === null && themeMedia.matches);
    document.documentElement.classList.toggle('dark', shouldUseDark);
    document.documentElement.dataset.theme = shouldUseDark ? 'dark' : 'light';
}

function getStoredTheme(scope = null) {
    const key = getThemeStorageKey(scope);
    return localStorage.getItem(key);
}

function setTheme(theme, scope = null) {
    const key = getThemeStorageKey(scope);

    if (theme === null || theme === 'system') {
        localStorage.removeItem(key);
        applyTheme(null);
    } else {
        localStorage.setItem(key, theme);
        applyTheme(theme);
    }

    window.dispatchEvent(new CustomEvent('rcentz:theme-changed', {
        detail: {
            theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light',
            scope: scope ?? getThemeScope(),
        },
    }));

    window.setTimeout(() => {
        if (window.lucide) {
            window.lucide.createIcons();
        }
    }, 0);
}

function toggleTheme() {
    const nextTheme = document.documentElement.classList.contains('dark') ? 'light' : 'dark';
    setTheme(nextTheme);
}

// One public theme API for every shell.
// Keep the older global toggleTheme alias so existing Blade layouts continue to work.
window.AxausTheme = {
    apply: applyTheme,
    get: getStoredTheme,
    set: setTheme,
    toggle: toggleTheme,
    getScope: getThemeScope,
    getStorageKey: getThemeStorageKey,
};

window.toggleTheme = toggleTheme;

themeMedia.addEventListener('change', () => {
    if (!getStoredTheme()) {
        applyTheme(null);
    }
});

document.addEventListener('DOMContentLoaded', () => {
    // theme-init.blade.php applies the theme before paint.
    // Re-resolve here because DOM state can now identify the exact shell.
    const storedTheme = getStoredTheme();
    applyTheme(storedTheme);

    if (window.lucide) {
        window.lucide.createIcons();
    }
});

// Refresh the CSRF token periodically for long-lived authenticated pages.
setInterval(() => {
    fetch('/refresh-csrf', {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
    })
        .then((response) => {
            if (!response.ok) {
                throw new Error(`CSRF refresh failed with ${response.status}`);
            }
            return response.json();
        })
        .then((data) => {
            if (!data.token) return;

            document.querySelectorAll('input[name="_token"]').forEach((input) => {
                input.value = data.token;
            });
            document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', data.token);
        })
        .catch(() => {
            // A failed refresh should not interrupt the current page.
        });
}, 600000);
