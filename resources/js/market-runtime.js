// V5.14.3 automatic market runtime.
// Polling is intentionally lightweight and uses the server-side tickIfDue lock,
// so an open browser can drive Controlled Market locally even when schedule:work
// is not running. Production scheduler and browser heartbeat share one clock.

const runtimeSelector = [
    '[data-market-runtime]',
    '[data-market-price-symbol]',
    '[data-market-position-pnl]',
    '[data-market-analysis-symbol]',
].join(',');

const nodesExist = () => document.querySelector(runtimeSelector) !== null;

const tone = (el, value) => {
    if (!el) return;
    el.classList.remove('text-emerald-600', 'text-red-600');
    if (Number(value) > 0) el.classList.add('text-emerald-600');
    if (Number(value) < 0) el.classList.add('text-red-600');
};

// V5.20.1 price movement tick.
// This is deliberately a transient tick-to-tick indicator, not the session/day change.
// The authoritative price remains the server runtime payload; the browser only compares
// the previous rendered value with the newest authoritative value.
const movementState = new WeakMap();
const movementBadges = new WeakMap();
const movementTimers = new WeakMap();

const applyMovementTick = (el, row) => {
    if (!el || !row) return;

    const current = Number(row.price);
    if (!Number.isFinite(current)) return;

    const previous = movementState.get(el);
    movementState.set(el, current);

    // First authoritative snapshot seeds the comparator without displaying movement.
    if (!Number.isFinite(previous) || previous === current) return;

    const delta = current - previous;
    const percent = previous !== 0 ? (delta / previous) * 100 : 0;
    const rising = delta > 0;
    const arrow = rising ? '↑' : '↓';
    const sign = rising ? '+' : '';

    let badge = movementBadges.get(el);
    if (!badge || !badge.isConnected) {
        badge = document.createElement('span');
        badge.setAttribute('data-price-movement-tick', '');
        badge.setAttribute('aria-live', 'polite');
        badge.className = 'ml-1.5 inline-flex items-center rounded-md border px-1.5 py-0.5 align-middle text-[9px] font-semibold tabular-nums transition-opacity duration-300';
        el.insertAdjacentElement('afterend', badge);
        movementBadges.set(el, badge);
    }

    badge.classList.remove(
        'border-emerald-500/20', 'bg-emerald-500/10', 'text-emerald-600',
        'border-red-500/20', 'bg-red-500/10', 'text-red-600',
        'opacity-0', 'opacity-100'
    );

    badge.classList.add(
        rising ? 'border-emerald-500/20' : 'border-red-500/20',
        rising ? 'bg-emerald-500/10' : 'bg-red-500/10',
        rising ? 'text-emerald-600' : 'text-red-600',
        'opacity-100'
    );

    badge.textContent = arrow + ' ' + sign + percent.toFixed(2) + '%';
    badge.title = 'Movement since the previous price update';

    const oldTimer = movementTimers.get(el);
    if (oldTimer) window.clearTimeout(oldTimer);

    movementTimers.set(el, window.setTimeout(() => {
        badge.classList.remove('opacity-100');
        badge.classList.add('opacity-0');
    }, 2200));
};

const collect = () => {
    const symbols = new Set();
    const positions = new Set();
    const analyses = new Set();

    document.querySelectorAll('[data-market-price-symbol],[data-market-previous-symbol],[data-market-change-symbol],[data-market-change-percent-symbol]').forEach((el) => {
        const symbol = String(el.dataset.marketPriceSymbol || el.dataset.marketPreviousSymbol || el.dataset.marketChangeSymbol || el.dataset.marketChangePercentSymbol || '').toUpperCase();
        if (symbol) symbols.add(symbol);
    });

    document.querySelectorAll('[data-market-position-pnl],[data-market-position-return],[data-market-position-cmp],[data-market-position-difference],[data-market-position-label]').forEach((el) => {
        const id = el.dataset.marketPositionPnl || el.dataset.marketPositionReturn || el.dataset.marketPositionCmp || el.dataset.marketPositionDifference || el.dataset.marketPositionLabel;
        if (id) positions.add(String(id));
    });

    document.querySelectorAll('[data-market-analysis-symbol]').forEach((el) => {
        const symbol = String(el.dataset.marketAnalysisSymbol || '').toUpperCase();
        const marketplace = String(el.dataset.marketplace || 'live').toLowerCase();
        if (symbol) {
            symbols.add(symbol);
            analyses.add(symbol + ':' + marketplace);
        }
    });

    return { symbols: [...symbols], positions: [...positions], analyses: [...analyses] };
};

const marketRowFor = (payload, el, symbol) => {
    const marketplace = String(el.dataset.marketplace || payload.active_marketplace || 'live').toLowerCase();
    return payload.stocks?.[symbol]?.[marketplace] || null;
};

const apply = (payload) => {
    document.querySelectorAll('[data-market-price-symbol]').forEach((el) => {
        const symbol = String(el.dataset.marketPriceSymbol || '').toUpperCase();
        const row = marketRowFor(payload, el, symbol);
        if (row) {
            applyMovementTick(el, row);
            el.textContent = row.formatted_price;
        }
    });

    document.querySelectorAll('[data-market-previous-symbol]').forEach((el) => {
        const symbol = String(el.dataset.marketPreviousSymbol || '').toUpperCase();
        const row = marketRowFor(payload, el, symbol);
        if (row) el.textContent = row.formatted_previous;
    });

    document.querySelectorAll('[data-market-change-symbol]').forEach((el) => {
        const symbol = String(el.dataset.marketChangeSymbol || '').toUpperCase();
        const row = marketRowFor(payload, el, symbol);
        if (!row) return;
        el.textContent = row.formatted_change;
        tone(el, row.change);
    });

    document.querySelectorAll('[data-market-change-percent-symbol]').forEach((el) => {
        const symbol = String(el.dataset.marketChangePercentSymbol || '').toUpperCase();
        const row = marketRowFor(payload, el, symbol);
        if (!row) return;
        el.textContent = row.formatted_change_percent;
        tone(el, row.change_percent);
    });

    Object.entries(payload.positions || {}).forEach(([id, row]) => {
        document.querySelectorAll('[data-market-position-label="' + id + '"]').forEach((el) => { el.textContent = row.label; });
        document.querySelectorAll('[data-market-position-cmp="' + id + '"]').forEach((el) => { el.textContent = row.formatted_cmp; });
        document.querySelectorAll('[data-market-position-difference="' + id + '"]').forEach((el) => { el.textContent = row.formatted_difference; tone(el, row.difference); });
        document.querySelectorAll('[data-market-position-pnl="' + id + '"]').forEach((el) => { el.textContent = row.formatted_pnl; tone(el, row.pnl); });
        document.querySelectorAll('[data-market-position-return="' + id + '"]').forEach((el) => {
            el.textContent = row.formatted_return;
            tone(el, row.return_percent);
        });
    });

    document.querySelectorAll('[data-market-analysis-symbol]').forEach((el) => {
        const symbol = String(el.dataset.marketAnalysisSymbol || '').toUpperCase();
        const marketplace = String(el.dataset.marketplace || 'live').toLowerCase();
        const next = payload.analysis?.[symbol + ':' + marketplace];
        if (next && window.RcentzCharts?.refreshAnalysis) {
            window.RcentzCharts.refreshAnalysis(el, next);
        }
    });
};

let busy = false;
let timer = null;

const poll = async () => {
    if (busy || document.hidden || !nodesExist()) return;
    busy = true;
    try {
        const request = collect();
        const params = new URLSearchParams();
        if (request.symbols.length) params.set('symbols', request.symbols.join(','));
        if (request.positions.length) params.set('positions', request.positions.join(','));
        if (request.analyses.length) params.set('analysis', request.analyses.join(','));

        const response = await fetch('/market-runtime?' + params.toString(), {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
            cache: 'no-store',
        });

        if (!response.ok) return;
        apply(await response.json());
    } catch (_) {
        // Runtime refresh is enhancement-only; never break trading UI on network loss.
    } finally {
        busy = false;
    }
};

const start = () => {
    if (!nodesExist()) return;
    poll();
    if (!timer) timer = window.setInterval(poll, 3000);
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
} else {
    start();
}

document.addEventListener('visibilitychange', () => {
    if (!document.hidden) poll();
});
