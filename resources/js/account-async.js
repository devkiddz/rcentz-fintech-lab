// Only explicitly marked account forms and regions opt in. Financial requests are never retried.
export async function accountAction(form, fetcher = fetch) {
    const endpoint = new URL(form.action, location.origin);
    if (endpoint.origin !== location.origin) throw new Error('Account action must stay on this site.');
    const response = await fetcher(endpoint, {
        method: 'POST', body: new FormData(form), credentials: 'same-origin',
        signal: AbortSignal.timeout(20000),
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-Account-Action': '1' },
    });
    if (response.redirected || !response.headers.get('content-type')?.includes('application/json')) throw new Error('Unconfirmed action.');
    const result = await response.json();
    if (response.status >= 500 || result.unconfirmed || (response.ok && result.success !== true)) throw new Error('Unconfirmed action.');
    return { ...result, success: response.ok && result.success === true, status: response.status,
        message: Object.values(result.errors || {}).flat().find(Boolean) || result.message || 'The action was not accepted.' };
}

const boot = () => {
    let region = document.querySelector('[data-account-async]');
    if (!region) return;
    const feedback = document.querySelector('[data-account-async-feedback]');
    const drafts = new WeakSet();
    let busy = false, refreshing = false, blocked = false, failures = 0;
    const report = (message, url) => {
        feedback.hidden = false; feedback.replaceChildren(document.createTextNode(message));
        if (url) {
            const target = new URL(url, location.origin);
            if (target.origin === location.origin && target.href !== location.href) {
                const link = document.createElement('a'); link.href = target.href;
                link.textContent = ' View details'; link.className = 'underline'; feedback.append(link);
            }
        }
    };
    const editing = () => region.querySelector('dialog[open]') || document.activeElement?.closest('form')
        || [...region.querySelectorAll('form')].some(form => drafts.has(form));
    // Refresh is GET-only, same URL (including pagination/filter query), and never overlaps.
    const refresh = async (afterAction = false) => {
        if (refreshing || editing() || blocked || (busy && !afterAction)) return false;
        refreshing = true;
        try {
            const response = await fetch(location.href, { credentials: 'same-origin', cache: 'no-store',
                signal: AbortSignal.timeout(15000), headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok || response.redirected) throw new Error('Account refresh unavailable.');
            const page = new DOMParser().parseFromString(await response.text(), 'text/html');
            const next = page.querySelector('[data-account-async]');
            if (!next || next.dataset.accountAsync !== region.dataset.accountAsync) throw new Error('Account refresh unavailable.');
            // Editing may have started during the GET. Do not remove that input or dialog.
            if (editing() || blocked || (busy && !afterAction)) return false;
            // Preserve live chart widgets; their established market runtime owns chart updates.
            const selectors = ['[data-rcentz-analysis]', '[data-rcentz-candles]', '[data-rcentz-sparkline]', '[data-bot-price-chart]'];
            const widgets = [];
            for (const selector of selectors) {
                const current = [...region.querySelectorAll(selector)];
                const incoming = [...next.querySelectorAll(selector)];
                if (current.length === incoming.length) current.forEach((node, i) => {
                    const oldWidget = selector === '[data-rcentz-analysis]' ? (node.closest('section') || node) : node;
                    const freshWidget = selector === '[data-rcentz-analysis]' ? (incoming[i].closest('section') || incoming[i]) : incoming[i];
                    if (!next.contains(freshWidget)) return;
                    // Investment summaries belong to the incoming valuation snapshot, not the preserved canvas.
                    const summary = freshWidget.querySelector('[data-investment-chart-summary]');
                    const oldSummary = oldWidget.querySelector('[data-investment-chart-summary]');
                    if (summary && oldSummary) oldSummary.replaceChildren(...[...summary.childNodes].map(child => child.cloneNode(true)));
                    const placeholder = document.createElement('span'); freshWidget.replaceWith(placeholder);
                    widgets.push([placeholder, oldWidget, selector === '[data-rcentz-analysis]' ? { node, payload: incoming[i].dataset.analysis } : null]);
                });
            }
            // Never execute scripts from fetched HTML.
            next.querySelectorAll('script').forEach(node => node.remove());
            const scroll = [...region.querySelectorAll('*')].filter(node => node.scrollTop || node.scrollLeft)
                .map(node => [node.id, node.getAttribute('class'), node.scrollTop, node.scrollLeft]);
            for (const [placeholder, node] of widgets) placeholder.replaceWith(node);
            // Alpine initializes only new content; preserved chart widgets keep their instances.
            region.replaceWith(next); region = next;
            if (window.Alpine) window.Alpine.initTree(region);
            for (const [id, cls, top, left] of scroll) {
                const node = id ? document.getElementById(id) : [...region.querySelectorAll('*')].find(el => el.getAttribute('class') === cls);
                if (node) { node.scrollTop = top; node.scrollLeft = left; }
            }
            window.RcentzCharts?.mount(region);
            for (const [, , analysis] of widgets) {
                if (analysis?.payload) { try { window.RcentzCharts?.refreshAnalysis(analysis.node, JSON.parse(analysis.payload)); } catch (_) {} }
            }
            window.lucide?.createIcons(); failures = 0;
            return true;
        } finally { refreshing = false; }
    };
    document.addEventListener('input', event => {
        const form = event.target.closest('form'); if (form && region.contains(form)) drafts.add(form);
    });
    document.addEventListener('change', event => {
        const form = event.target.closest('form'); if (form && region.contains(form)) drafts.add(form);
    });
    document.addEventListener('reset', event => drafts.delete(event.target));
    document.addEventListener('click', event => {
        const button = event.target.closest('[data-bot-tab-button]');
        if (!button || !region.contains(button)) return;
        const tabs = button.closest('[data-bot-tabs]');
        tabs.querySelectorAll('[data-bot-tab-button]').forEach(node => {
            const active = node === button; node.setAttribute('aria-selected', String(active));
            for (const cls of ['border-border', 'bg-background', 'text-foreground']) node.classList.toggle(cls, active);
            for (const cls of ['border-transparent', 'text-muted-foreground']) node.classList.toggle(cls, !active);
        });
        tabs.querySelectorAll('[data-bot-tab-panel]').forEach(panel => panel.classList.toggle('hidden', panel.dataset.botTabPanel !== button.dataset.botTabButton));
    });
    document.addEventListener('submit', async event => {
        const form = event.target.closest('form[data-account-action]');
        if (!form || !region.contains(form) || event.defaultPrevented || form.method.toUpperCase() === 'GET') return;
        event.preventDefault(); if (busy || blocked) return;
        busy = true; form.setAttribute('aria-busy', 'true');
        const buttons = [...region.querySelectorAll('form[data-account-action] button')].map(node => [node, node.disabled]);
        buttons.forEach(([node]) => node.disabled = true); report('Submitting…');
        try {
            const result = await accountAction(form);
            if (!result.success) {
                const rejectedKey = form.querySelector('[name="idempotency_key"]');
                if (result.status === 422 && rejectedKey && crypto.randomUUID) rejectedKey.value = crypto.randomUUID();
                report(result.message); return;
            }
            const key = form.querySelector('[name="idempotency_key"]');
            if (key && result.next_idempotency_key) key.value = result.next_idempotency_key;
            // A new broker close needs a new key too, while rejected/unconfirmed submissions keep theirs.
            else if (key && crypto.randomUUID) key.value = crypto.randomUUID();
            drafts.delete(form);
            const focused = document.activeElement; if (form.contains(focused)) focused.blur();
            form.closest('dialog')?.close();
            report(result.message, result.receipt_url);
            try { if (!await refresh(true)) report(result.message + ' Updates will resume when unfinished forms are saved or reset.', result.receipt_url); }
            catch (_) { report(result.message + ' Saved successfully. Refresh to see current balances and records.', result.receipt_url); }
        } catch (_) {
            blocked = true;
            report('Result unconfirmed. Check Orders or account activity, then reload before retrying. Your submission key has been preserved.');
        } finally {
            buttons.forEach(([node, disabled]) => { if (node.isConnected) node.disabled = blocked || disabled; });
            form.removeAttribute('aria-busy'); busy = false;
        }
    });
    const poll = async () => {
        if (!document.hidden && !busy && !blocked) {
            try { await refresh(); } catch (_) { failures++; if (failures === 3) report('Live updates are unavailable. Your last displayed values may be out of date. Refresh or sign in again.'); }
        }
        setTimeout(poll, Math.min(60000, 15000 * Math.max(1, failures)));
    };
    setTimeout(poll, 15000);
    window.addEventListener('focus', () => { if (!busy) refresh().catch(() => {}); });
};
if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();
}
