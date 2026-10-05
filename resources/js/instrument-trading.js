// Progressive enhancement: the existing protected POST routes still work without JS.
export async function requestTrade(form, fetcher = fetch) {
    const origin = globalThis.location?.origin;
    if (origin && new URL(form.action, origin).origin !== origin) throw new Error('Trade endpoint must be on this site.');
    const response = await fetcher(form.action, {
        method: 'POST', body: new FormData(form), credentials: 'same-origin', signal: AbortSignal.timeout(20000),
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    });
    if (response.redirected || !response.headers.get('content-type')?.includes('application/json')) {
        throw new Error('The server did not confirm the trade result. Refresh the page and check order history before trying again.');
    }
    const result = await response.json();
    if (!response.ok || result.success !== true) {
        const message = Object.values(result.errors || {}).flat().find(Boolean) || result.message || 'The trading action was not accepted.';
        return { success: false, message, status: response.status };
    }
    return result;
}

const boot = () => {
    const desk = document.querySelector('[data-trade-workstation]');
    if (!desk) return;
    const updateRiskInputs = () => desk.querySelectorAll('[data-risk-inputs]').forEach(group => {
        const mode = group.querySelector('[data-risk-mode]').value;
        group.querySelectorAll('[data-risk-basis]').forEach(fields => {
            fields.disabled = fields.dataset.riskBasis !== mode;
            fields.hidden = fields.disabled;
            fields.style.display = fields.disabled ? 'none' : '';
        });
    });
    desk.addEventListener('change', event => { if (event.target.matches('[data-risk-mode]')) updateRiskInputs(); });
    updateRiskInputs();
    let busy = false, refreshing = false, blocked = false;
    const entry = desk.querySelector('form[data-trade-ajax="open"]');
    const calculator = entry?.querySelector('[data-entry-calculator]');
    let estimateVersion = 0, estimateTimer, estimateAbort;
    const estimate = async () => {
        if (!calculator) return;
        const version = ++estimateVersion;
        estimateAbort?.abort();
        const status = calculator.querySelector('[data-entry-status]');
        status.style.color = '';
        const clear = () => calculator.querySelectorAll('[data-entry-value]').forEach(node => node.textContent = '—');
        const size = Number(entry.elements.quantity.value);
        if (!Number.isFinite(size) || size <= 0) { clear(); status.textContent = 'Enter a positive quantity to estimate your position.'; return; }
        clear(); status.textContent = 'Calculating…';
        const url = new URL(location.pathname, location.origin);
        url.searchParams.set('entry_estimate', '1');
        for (const name of ['side','quantity','quantity_mode']) url.searchParams.set(name, entry.elements[name].value);
        const controller = new AbortController(); estimateAbort = controller;
        const timeout = setTimeout(() => controller.abort(), 10000);
        try {
            const response = await fetch(url, { credentials: 'same-origin', cache: 'no-store', signal: controller.signal, headers: { Accept: 'application/json' } });
            if (response.redirected || !response.headers.get('content-type')?.includes('application/json')) throw new Error('Sign in again or refresh to calculate.');
            const result = await response.json();
            if (version !== estimateVersion) return;
            if (!response.ok || !result.success) throw new Error(result.message || Object.values(result.errors || {}).flat()[0] || 'Estimate unavailable.');
            const value = result.estimate;
            for (const node of calculator.querySelectorAll('[data-entry-value]')) {
                const key = node.dataset.entryValue;
                const digits = ['units','price'].includes(key) ? 8 : 2;
                node.textContent = (key === 'units' ? '' : value.currency + ' ') + Number(value[key]).toLocaleString(undefined, { minimumFractionDigits: key === 'units' ? 0 : 2, maximumFractionDigits: digits });
                // Entry price is in the instrument quote currency, supplied separately below.
                if (key === 'price') node.textContent = Number(value.price).toLocaleString(undefined, {maximumFractionDigits:8}) + ' ' + (value.quote_currency || '');
            }
            status.textContent = value.sufficient ? 'Your available balance covers this estimated position.' : 'Insufficient available balance. Reduce quantity or add funds before opening.';
            status.style.color = value.sufficient ? '' : '#ef4444';
        } catch (error) {
            if (version === estimateVersion) { clear(); status.textContent = error.name === 'AbortError' ? 'Estimate timed out. Change the quantity to try again.' : error.message; }
        } finally { clearTimeout(timeout); }
    };
    const scheduleEstimate = () => {
        ++estimateVersion; estimateAbort?.abort(); clearTimeout(estimateTimer);
        calculator?.querySelectorAll('[data-entry-value]').forEach(node => node.textContent = '—');
        if (calculator) calculator.querySelector('[data-entry-status]').textContent = 'Calculating…';
        estimateTimer = setTimeout(estimate, 300);
    };
    entry?.addEventListener('input', event => { if (['quantity','side','quantity_mode'].includes(event.target.name)) scheduleEstimate(); });
    entry?.addEventListener('change', event => { if (['quantity','side','quantity_mode'].includes(event.target.name)) scheduleEstimate(); });
    if (calculator) { estimate(); setInterval(() => { if (!document.hidden && !busy && entry.elements.quantity.value) scheduleEstimate(); }, 15000); }

    const feedback = (message, receipt, error = false) => {
        const node = desk.querySelector('[data-trade-feedback]');
        node.hidden = false;
        node.className = 'mb-4 rounded-xl border p-4 text-xs ' + (error ? 'border-red-500/25 bg-red-500/10' : 'border-emerald-500/25 bg-emerald-500/10');
        node.replaceChildren(document.createTextNode(message));
        if (receipt) {
            const url = new URL(receipt, location.origin);
            if (url.origin === location.origin) {
                const link = document.createElement('a');
                link.href = url.href; link.className = 'ml-2 font-semibold underline'; link.textContent = 'View receipt';
                node.append(link);
            }
        }
    };
    const refresh = async (automatic = false) => {
        if (refreshing || (automatic && (busy || blocked || desk.querySelector('dialog[open]') || document.activeElement?.closest('[data-trade-positions]')))) return;
        refreshing = true;
        try {
        const response = await fetch(location.href, { credentials: 'same-origin', cache: 'no-store', signal: AbortSignal.timeout(20000), headers: { Accept: 'text/html' } });
        if (!response.ok || response.redirected) throw new Error('Position refresh failed.');
        const page = new DOMParser().parseFromString(await response.text(), 'text/html');
        if (automatic && (busy || blocked || desk.querySelector('dialog[open]') || document.activeElement?.closest('[data-trade-positions]'))) return;
        const updated = page.querySelector('[data-trade-workstation]');
        if (!updated?.querySelector('[data-trade-positions]')) throw new Error('Position refresh failed.');
        desk.querySelectorAll('dialog[open]').forEach(dialog => dialog.close());
        desk.querySelector('[data-trade-positions]').replaceWith(updated.querySelector('[data-trade-positions]'));
        const metrics = desk.querySelector('[data-trade-metrics]');
        if (metrics && updated.querySelector('[data-trade-metrics]')) metrics.replaceWith(updated.querySelector('[data-trade-metrics]'));
        desk.dataset.tradeChartData = updated.dataset.tradeChartData;
        const sourceKey = updated.querySelector('[data-trade-ajax="open"] input[name="idempotency_key"]');
        const liveKey = desk.querySelector('[data-trade-ajax="open"] input[name="idempotency_key"]');
        if (!automatic && sourceKey && liveKey) liveKey.value = sourceKey.value;
        desk.querySelectorAll('[data-rcentz-analysis]').forEach(el => {
            window.RcentzCharts?.refreshAnalysis(el, JSON.parse(el.dataset.analysis || '{}'));
        });
        updateRiskInputs();
        window.lucide?.createIcons();
        } finally { refreshing = false; }
    };
    setInterval(() => { if (!document.hidden) refresh(true).catch(() => {}); }, 10000);
    window.addEventListener('focus', () => refresh(true).catch(() => {}));
    desk.addEventListener('submit', async event => {
        const form = event.target.closest('form[data-trade-ajax]');
        if (!form || event.defaultPrevented) return;
        event.preventDefault();
        if (busy || blocked) return;
        busy = true;
        const buttons = [...desk.querySelectorAll('form[data-trade-ajax] button[type="submit"],form[data-trade-ajax] button:not([type])')];
        const states = buttons.map(button => [button, button.disabled]);
        states.forEach(([button]) => button.disabled = true);
        form.setAttribute('aria-busy', 'true');
        feedback('Submitting…');
        try {
            const result = await requestTrade(form);
            if (!result.success) {
                // A confirmed 422 is a rejected order, not an uncertain network result.
                const key = form.querySelector('input[name="idempotency_key"]');
                if (result.status === 422 && key && crypto.randomUUID) key.value = crypto.randomUUID();
                feedback(result.message, null, true);
                return;
            }
            const key = form.querySelector('input[name="idempotency_key"]');
            if (form.dataset.tradeAjax === 'open' && key && result.next_idempotency_key) key.value = result.next_idempotency_key;
            feedback(result.message, result.receipt_url);
            try {
                await refresh();
                if (form.dataset.tradeAjax === 'open') form.querySelector('input[name="quantity"]').value = '';
            } catch (_) {
                feedback(result.message + ' Refresh the page to update positions before another trade.', result.receipt_url);
            }
        } catch (_) {
            blocked = true;
            // Preserve the key; the server might have committed before the connection failed.
            feedback('Trade result is unconfirmed. Refresh and check order history before retrying. Your submission key has been preserved.', null, true);
        } finally {
            states.forEach(([button, disabled]) => { if (button.isConnected) button.disabled = blocked || disabled; });
            form.removeAttribute('aria-busy'); busy = false; scheduleEstimate();
        }
    });
};
if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();
}
