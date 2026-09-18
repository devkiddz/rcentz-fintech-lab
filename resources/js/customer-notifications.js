const CUSTOMER_NOTIFICATION_API = '/notifications/api';
const CUSTOMER_NOTIFICATION_MARK_ALL = '/notifications/mark-all-read';
const CUSTOMER_NOTIFICATION_POLL_MS = 60000;

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

const requestJson = async (url, options = {}) => {
    const response = await fetch(url, {
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            ...(options.method && options.method !== 'GET' ? { 'X-CSRF-TOKEN': csrfToken() } : {}),
            ...(options.headers || {}),
        },
        ...options,
    });

    if (!response.ok) {
        throw new Error(`Notification request failed with ${response.status}`);
    }

    return response.json();
};

const notificationActionUrl = (notification) => {
    const value = notification?.data?.action_url;
    return typeof value === 'string' && value.trim() !== '' ? value : null;
};

const createIcon = (name, classes = 'h-4 w-4') => {
    const icon = document.createElement('i');
    icon.dataset.lucide = name || 'info';
    icon.className = classes;
    return icon;
};

const renderEmpty = (list) => {
    list.replaceChildren();

    const wrapper = document.createElement('div');
    wrapper.className = 'p-6 text-center';

    const iconBox = document.createElement('div');
    iconBox.className = 'mx-auto mb-3 flex h-9 w-9 items-center justify-center rounded-lg border border-border bg-muted/40 text-muted-foreground';
    iconBox.appendChild(createIcon('bell-off'));

    const title = document.createElement('p');
    title.className = 'text-xs font-medium text-foreground';
    title.textContent = 'No notifications yet';

    const copy = document.createElement('p');
    copy.className = 'mt-1 text-[11px] leading-4 text-muted-foreground';
    copy.textContent = 'New account activity will appear here.';

    wrapper.append(iconBox, title, copy);
    list.appendChild(wrapper);
};

const renderError = (list, retry) => {
    list.replaceChildren();

    const wrapper = document.createElement('div');
    wrapper.className = 'p-6 text-center';

    const iconBox = document.createElement('div');
    iconBox.className = 'mx-auto mb-3 flex h-9 w-9 items-center justify-center rounded-lg border border-red-500/20 bg-red-500/5 text-red-500';
    iconBox.appendChild(createIcon('triangle-alert'));

    const title = document.createElement('p');
    title.className = 'text-xs font-medium text-foreground';
    title.textContent = 'Could not load notifications';

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'mt-3 text-xs font-medium text-foreground underline underline-offset-4';
    button.textContent = 'Try again';
    button.addEventListener('click', retry);

    wrapper.append(iconBox, title, button);
    list.appendChild(wrapper);
};

const renderNotification = (notification, refresh) => {
    const actionUrl = notificationActionUrl(notification);
    const row = document.createElement(actionUrl ? 'a' : 'button');

    if (actionUrl) {
        row.href = actionUrl;
    } else {
        row.type = 'button';
    }

    row.className = [
        'group flex w-full items-start gap-3 border-b border-border/70 px-4 py-3 text-left transition last:border-b-0 hover:bg-muted/40',
        notification.is_read ? '' : 'bg-primary/[.035]',
    ].join(' ');

    const iconBox = document.createElement('span');
    iconBox.className = 'mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-border bg-background text-muted-foreground';
    iconBox.appendChild(createIcon(notification.icon || 'info', 'h-3.5 w-3.5'));

    const body = document.createElement('span');
    body.className = 'min-w-0 flex-1';

    const titleLine = document.createElement('span');
    titleLine.className = 'flex items-start gap-2';

    const title = document.createElement('span');
    title.className = 'min-w-0 flex-1 truncate text-xs font-semibold text-foreground';
    title.textContent = notification.title || 'Notification';
    titleLine.appendChild(title);

    if (!notification.is_read) {
        const dot = document.createElement('span');
        dot.className = 'mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-primary';
        dot.title = 'Unread';
        titleLine.appendChild(dot);
    }

    const message = document.createElement('span');
    message.className = 'mt-1 line-clamp-2 block text-[11px] leading-4 text-muted-foreground';
    message.textContent = notification.message || '';

    const meta = document.createElement('span');
    meta.className = 'mt-1.5 block text-[10px] text-muted-foreground/80';
    meta.textContent = notification.formatted_time || '';

    body.append(titleLine, message, meta);
    row.append(iconBox, body);

    row.addEventListener('click', async (event) => {
        if (notification.is_read) return;

        if (actionUrl) {
            event.preventDefault();
        }

        try {
            await requestJson(`/notifications/${notification.id}/read`, { method: 'PATCH' });
            notification.is_read = true;

            if (actionUrl) {
                window.location.assign(actionUrl);
                return;
            }

            await refresh();
        } catch (_) {
            if (actionUrl) {
                window.location.assign(actionUrl);
            }
        }
    });

    return row;
};

const bootCustomerNotificationBell = () => {
    const root = document.querySelector('[data-customer-notifications]');
    if (!root) return;

    const toggle = root.querySelector('[data-notification-toggle]');
    const dropdown = root.querySelector('[data-notification-dropdown]');
    const badge = root.querySelector('[data-notification-badge]');
    const list = root.querySelector('[data-notification-list]');
    const summary = root.querySelector('[data-notification-summary]');
    const markAll = root.querySelector('[data-notification-mark-all]');

    if (!toggle || !dropdown || !badge || !list || !summary || !markAll) return;

    let loading = false;

    const refresh = async () => {
        if (loading) return;
        loading = true;

        try {
            const payload = await requestJson(CUSTOMER_NOTIFICATION_API);
            const notifications = Array.isArray(payload.notifications) ? payload.notifications : [];
            const unreadCount = Number(payload.unread_count || 0);

            badge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
            badge.classList.toggle('hidden', unreadCount <= 0);
            summary.textContent = unreadCount > 0
                ? `${unreadCount} unread notification${unreadCount === 1 ? '' : 's'}`
                : 'Recent account activity';
            markAll.disabled = unreadCount <= 0;

            if (!notifications.length) {
                renderEmpty(list);
            } else {
                list.replaceChildren(...notifications.map((notification) => renderNotification(notification, refresh)));
            }

            if (window.lucide) {
                window.lucide.createIcons();
            }
        } catch (_) {
            renderError(list, refresh);
            if (window.lucide) {
                window.lucide.createIcons();
            }
        } finally {
            loading = false;
        }
    };

    const setOpen = (open) => {
        dropdown.classList.toggle('hidden', !open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');

        if (open) {
            refresh();
        }
    };

    toggle.addEventListener('click', () => {
        setOpen(dropdown.classList.contains('hidden'));
    });

    markAll.addEventListener('click', async () => {
        if (markAll.disabled) return;
        markAll.disabled = true;

        try {
            await requestJson(CUSTOMER_NOTIFICATION_MARK_ALL, { method: 'PATCH' });
            await refresh();
        } finally {
            markAll.disabled = false;
        }
    });

    document.addEventListener('click', (event) => {
        if (!root.contains(event.target)) {
            setOpen(false);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setOpen(false);
            toggle.focus();
        }
    });

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            refresh();
        }
    });

    refresh();

    window.setInterval(() => {
        if (document.visibilityState === 'visible') {
            refresh();
        }
    }, CUSTOMER_NOTIFICATION_POLL_MS);
};

const markAsRead = async (notificationId, options = {}) => {
    await requestJson(`/notifications/${notificationId}/read`, { method: 'PATCH' });
    if (options.reload) window.location.reload();
};

const markAllRead = async (options = {}) => {
    await requestJson(CUSTOMER_NOTIFICATION_MARK_ALL, { method: 'PATCH' });
    if (options.reload) window.location.reload();
};

const deleteNotification = async (notificationId, options = {}) => {
    if (options.confirmDelete && !window.confirm('Delete this notification?')) return;

    await requestJson(`/notifications/${notificationId}`, { method: 'DELETE' });
    if (options.reload) window.location.reload();
};

window.CustomerNotifications = {
    refresh: () => requestJson(CUSTOMER_NOTIFICATION_API),
    markAsRead,
    markAllRead,
    deleteNotification,
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootCustomerNotificationBell);
} else {
    bootCustomerNotificationBell();
}
