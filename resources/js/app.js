import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

const themeMedia = window.matchMedia('(prefers-color-scheme: dark)');

function applyTheme(theme) {
    const shouldUseDark = theme === 'dark' || (theme === null && themeMedia.matches);
    document.documentElement.classList.toggle('dark', shouldUseDark);
}

window.toggleTheme = function toggleTheme() {
    const nextTheme = document.documentElement.classList.contains('dark') ? 'light' : 'dark';
    localStorage.setItem('theme', nextTheme);
    applyTheme(nextTheme);

    window.setTimeout(() => {
        if (window.lucide) {
            window.lucide.createIcons();
        }
    }, 0);
};

themeMedia.addEventListener('change', () => {
    if (!localStorage.getItem('theme')) {
        applyTheme(null);
    }
});

document.addEventListener('DOMContentLoaded', () => {
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
