@php
    $communicationLauncherIsAdmin = auth()->check() && auth()->user()->isAdmin();
    $communicationLauncherMessagesRoute = $communicationLauncherIsAdmin ? 'admin.messages.index' : 'messages.index';
    $communicationLauncherSupportRoute = $communicationLauncherIsAdmin ? 'admin.support.index' : 'support.index';
    $communicationLauncherReady = auth()->check()
        && \Illuminate\Support\Facades\Route::has($communicationLauncherMessagesRoute)
        && \Illuminate\Support\Facades\Route::has($communicationLauncherSupportRoute);
@endphp

@if($communicationLauncherReady)
<div id="rcentz-communication-launcher"
     class="rcentz-communication-launcher"
     data-storage-key="rcentz:communication-launcher:{{ $communicationLauncherIsAdmin ? 'admin' : 'customer' }}:v2">
    <button id="rcentz-communication-launcher-button"
            type="button"
            class="rcentz-communication-launcher-button"
            aria-label="Open communication shortcuts"
            aria-controls="rcentz-communication-launcher-menu"
            aria-expanded="false"
            title="Drag to move · tap to open">
        <i data-lucide="message-circle-more" aria-hidden="true"></i>
        <span class="rcentz-communication-launcher-pulse" aria-hidden="true"></span>
    </button>

    <div id="rcentz-communication-launcher-menu"
         class="rcentz-communication-launcher-menu"
         role="menu"
         aria-label="Communication shortcuts"
         hidden>
        <div class="rcentz-communication-launcher-menu-head">
            <div>
                <strong>Communication</strong>
                <span>{{ $communicationLauncherIsAdmin ? 'Customer communication' : 'How can we help?' }}</span>
            </div>
            <i data-lucide="grip-vertical" aria-hidden="true"></i>
        </div>

        <a href="{{ route($communicationLauncherMessagesRoute) }}"
           class="rcentz-communication-launcher-link"
           role="menuitem">
            <span class="rcentz-communication-launcher-link-icon">
                <i data-lucide="messages-square" aria-hidden="true"></i>
            </span>
            <span>
                <strong>Messages</strong>
                <small>{{ $communicationLauncherIsAdmin ? 'Direct customer conversations' : 'Private conversations' }}</small>
            </span>
            <i data-lucide="chevron-right" class="rcentz-communication-launcher-chevron" aria-hidden="true"></i>
        </a>

        <a href="{{ route($communicationLauncherSupportRoute) }}"
           class="rcentz-communication-launcher-link"
           role="menuitem">
            <span class="rcentz-communication-launcher-link-icon">
                <i data-lucide="headphones" aria-hidden="true"></i>
            </span>
            <span>
                <strong>{{ $communicationLauncherIsAdmin ? 'Support Tickets' : 'Support Center' }}</strong>
                <small>{{ $communicationLauncherIsAdmin ? 'Review and resolve tickets' : 'Open or follow a support ticket' }}</small>
            </span>
            <i data-lucide="chevron-right" class="rcentz-communication-launcher-chevron" aria-hidden="true"></i>
        </a>

        <p class="rcentz-communication-launcher-hint">Drag the bubble anywhere. Its position is remembered on this device.</p>
    </div>
</div>

<style>
.rcentz-communication-launcher {
    position: fixed;
    right: 18px;
    bottom: 88px;
    z-index: 95;
    width: 58px;
    height: 58px;
    pointer-events: none;
}
.rcentz-communication-launcher-button {
    position: absolute;
    inset: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 58px;
    height: 58px;
    padding: 0;
    border: 1px solid hsl(var(--border));
    border-radius: 999px;
    background: hsl(var(--foreground));
    color: hsl(var(--background));
    box-shadow: 0 16px 38px rgba(15,23,42,.22), 0 5px 14px rgba(15,23,42,.14);
    cursor: grab;
    touch-action: none;
    user-select: none;
    -webkit-user-select: none;
    pointer-events: auto;
    transition: transform .16s ease, box-shadow .16s ease;
}
.rcentz-communication-launcher-button:hover,
.rcentz-communication-launcher-button:focus-visible {
    transform: translateY(-1px) scale(1.025);
    box-shadow: 0 20px 44px rgba(15,23,42,.26), 0 7px 18px rgba(15,23,42,.16);
    outline: none;
}
.rcentz-communication-launcher.is-dragging .rcentz-communication-launcher-button {
    cursor: grabbing;
    transform: scale(1.035);
}
.rcentz-communication-launcher-button svg {
    width: 24px;
    height: 24px;
    pointer-events: none;
}
.rcentz-communication-launcher-pulse {
    position: absolute;
    right: 4px;
    top: 4px;
    width: 10px;
    height: 10px;
    border: 2px solid hsl(var(--card));
    border-radius: 999px;
    background: hsl(var(--primary));
    pointer-events: none;
}
.rcentz-communication-launcher-menu {
    position: fixed;
    z-index: 96;
    width: min(292px, calc(100vw - 20px));
    overflow: hidden;
    border: 1px solid hsl(var(--border));
    border-radius: 16px;
    background: hsl(var(--popover));
    color: hsl(var(--popover-foreground));
    box-shadow: 0 24px 58px rgba(15,23,42,.24), 0 8px 22px rgba(15,23,42,.12);
    pointer-events: auto;
}
.rcentz-communication-launcher-menu[hidden] { display: none !important; }
.rcentz-communication-launcher-menu-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 13px 14px 11px;
    border-bottom: 1px solid hsl(var(--border));
}
.rcentz-communication-launcher-menu-head strong,
.rcentz-communication-launcher-menu-head span,
.rcentz-communication-launcher-link strong,
.rcentz-communication-launcher-link small { display: block; }
.rcentz-communication-launcher-menu-head strong {
    font-size: 13px;
    font-weight: 700;
    line-height: 1.25;
}
.rcentz-communication-launcher-menu-head span {
    margin-top: 2px;
    font-size: 10px;
    color: hsl(var(--muted-foreground));
}
.rcentz-communication-launcher-menu-head > svg {
    width: 16px;
    height: 16px;
    flex: 0 0 auto;
    color: hsl(var(--muted-foreground));
}
.rcentz-communication-launcher-link {
    display: grid;
    grid-template-columns: 36px minmax(0,1fr) 16px;
    align-items: center;
    gap: 10px;
    padding: 11px 12px;
    color: inherit;
    text-decoration: none;
    border-bottom: 1px solid hsl(var(--border));
    transition: background-color .14s ease;
}
.rcentz-communication-launcher-link:hover,
.rcentz-communication-launcher-link:focus-visible {
    background: hsl(var(--muted));
    outline: none;
}
.rcentz-communication-launcher-link-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border: 1px solid hsl(var(--border));
    border-radius: 11px;
    background: hsl(var(--muted));
    color: hsl(var(--foreground));
}
.rcentz-communication-launcher-link-icon svg,
.rcentz-communication-launcher-chevron {
    width: 16px;
    height: 16px;
}
.rcentz-communication-launcher-link strong {
    font-size: 12px;
    font-weight: 650;
    line-height: 1.25;
}
.rcentz-communication-launcher-link small {
    margin-top: 3px;
    overflow: hidden;
    color: hsl(var(--muted-foreground));
    font-size: 10px;
    line-height: 1.35;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.rcentz-communication-launcher-chevron { color: hsl(var(--muted-foreground)); }
.rcentz-communication-launcher-hint {
    margin: 0;
    padding: 9px 12px 10px;
    color: hsl(var(--muted-foreground));
    font-size: 9px;
    line-height: 1.4;
}
@media (min-width: 1024px) {
    .rcentz-communication-launcher { bottom: 24px; right: 24px; }
}
</style>

<script>
(function () {
    function bootCommunicationLauncher() {
        const launcher = document.getElementById('rcentz-communication-launcher');
        const button = document.getElementById('rcentz-communication-launcher-button');
        const menu = document.getElementById('rcentz-communication-launcher-menu');
        if (!launcher || !button || !menu || launcher.dataset.ready === '1') return;
        launcher.dataset.ready = '1';

        const storageKey = launcher.dataset.storageKey || 'rcentz:communication-launcher:v2';
        const margin = 10;
        const dragThreshold = 7;
        let pointerId = null;
        let startX = 0;
        let startY = 0;
        let originLeft = 0;
        let originTop = 0;
        let dragging = false;

        function launcherRect() { return launcher.getBoundingClientRect(); }

        function clampPosition(left, top) {
            const width = launcher.offsetWidth || 58;
            const height = launcher.offsetHeight || 58;
            return {
                left: Math.max(margin, Math.min(left, window.innerWidth - width - margin)),
                top: Math.max(margin, Math.min(top, window.innerHeight - height - margin)),
            };
        }

        function applyPosition(left, top, persist) {
            const p = clampPosition(left, top);
            launcher.style.left = p.left + 'px';
            launcher.style.top = p.top + 'px';
            launcher.style.right = 'auto';
            launcher.style.bottom = 'auto';
            if (persist) {
                try { localStorage.setItem(storageKey, JSON.stringify(p)); } catch (_) {}
            }
            if (!menu.hidden) positionMenu();
        }

        function restorePosition() {
            try {
                const raw = localStorage.getItem(storageKey);
                if (!raw) return;
                const saved = JSON.parse(raw);
                if (Number.isFinite(saved.left) && Number.isFinite(saved.top)) {
                    applyPosition(saved.left, saved.top, false);
                }
            } catch (_) {}
        }

        function positionMenu() {
            const rect = launcherRect();
            const menuWidth = Math.min(292, window.innerWidth - 20);
            const estimatedHeight = Math.max(menu.offsetHeight || 190, 190);
            let left = rect.right - menuWidth;
            left = Math.max(margin, Math.min(left, window.innerWidth - menuWidth - margin));

            let top = rect.top - estimatedHeight - 10;
            if (top < margin) top = Math.min(window.innerHeight - estimatedHeight - margin, rect.bottom + 10);
            top = Math.max(margin, top);

            menu.style.left = left + 'px';
            menu.style.top = top + 'px';
        }

        function openMenu() {
            menu.hidden = false;
            button.setAttribute('aria-expanded', 'true');
            positionMenu();
            if (window.lucide && typeof window.lucide.createIcons === 'function') window.lucide.createIcons();
        }

        function closeMenu() {
            menu.hidden = true;
            button.setAttribute('aria-expanded', 'false');
        }

        function toggleMenu() { menu.hidden ? openMenu() : closeMenu(); }

        button.addEventListener('pointerdown', function (event) {
            if (event.button !== undefined && event.button !== 0) return;
            pointerId = event.pointerId;
            const rect = launcherRect();
            startX = event.clientX;
            startY = event.clientY;
            originLeft = rect.left;
            originTop = rect.top;
            dragging = false;
            try { button.setPointerCapture(pointerId); } catch (_) {}
            event.preventDefault();
        });

        button.addEventListener('pointermove', function (event) {
            if (pointerId === null || event.pointerId !== pointerId) return;
            const dx = event.clientX - startX;
            const dy = event.clientY - startY;
            if (!dragging && Math.hypot(dx, dy) >= dragThreshold) {
                dragging = true;
                launcher.classList.add('is-dragging');
                closeMenu();
            }
            if (dragging) {
                applyPosition(originLeft + dx, originTop + dy, false);
                event.preventDefault();
            }
        });

        function finishPointer(event) {
            if (pointerId === null || event.pointerId !== pointerId) return;
            const wasDragging = dragging;
            pointerId = null;
            dragging = false;
            launcher.classList.remove('is-dragging');
            try { button.releasePointerCapture(event.pointerId); } catch (_) {}
            if (wasDragging) {
                const rect = launcherRect();
                applyPosition(rect.left, rect.top, true);
            } else {
                toggleMenu();
            }
            event.preventDefault();
            event.stopPropagation();
        }

        button.addEventListener('pointerup', finishPointer);
        button.addEventListener('pointercancel', function (event) {
            if (pointerId !== null && event.pointerId === pointerId) {
                pointerId = null;
                dragging = false;
                launcher.classList.remove('is-dragging');
            }
        });

        // Keyboard activation without duplicating normal pointer activation.
        button.addEventListener('click', function (event) {
            if (event.detail === 0) {
                toggleMenu();
                event.preventDefault();
            }
        });

        document.addEventListener('pointerdown', function (event) {
            if (menu.hidden) return;
            if (launcher.contains(event.target) || menu.contains(event.target)) return;
            closeMenu();
        }, true);

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') closeMenu();
        });

        window.addEventListener('resize', function () {
            const rect = launcherRect();
            applyPosition(rect.left, rect.top, true);
            if (!menu.hidden) positionMenu();
        });

        window.addEventListener('scroll', function () {
            if (!menu.hidden) positionMenu();
        }, true);

        restorePosition();
        if (window.lucide && typeof window.lucide.createIcons === 'function') window.lucide.createIcons();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootCommunicationLauncher, { once: true });
    } else {
        bootCommunicationLauncher();
    }
})();
</script>
@endif
