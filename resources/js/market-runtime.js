// Parent-first automatic market runtime.
// MarketInstrument IDs are the generic authority. Legacy stock-symbol hooks remain
// supported so the mature stock trading UI can migrate without a big-bang rewrite.

const runtimeSelector = [
    '[data-market-runtime]',
    '[data-market-price-instrument]',
    '[data-market-previous-instrument]',
    '[data-market-change-instrument]',
    '[data-market-change-percent-instrument]',
    '[data-market-analysis-instrument]',
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

const movementState = new WeakMap();
const movementBadges = new WeakMap();
const movementTimers = new WeakMap();

const applyMovementTick = (el, row) => {
    if (!el || !row || row.unavailable) return;

    const current = Number(row.price);
    if (!Number.isFinite(current)) return;

    const previous = movementState.get(el);
    movementState.set(el, current);

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
    badge.title = 'Movement since the previous authoritative price update';

    const oldTimer = movementTimers.get(el);
    if (oldTimer) window.clearTimeout(oldTimer);

    movementTimers.set(el, window.setTimeout(() => {
        badge.classList.remove('opacity-100');
        badge.classList.add('opacity-0');
    }, 2200));
};

const collect = () => {
    const instruments = new Set();
    const instrumentAnalyses = new Set();
    const symbols = new Set();
    const positions = new Set();
    const analyses = new Set();

    document.querySelectorAll('[data-market-price-instrument],[data-market-previous-instrument],[data-market-change-instrument],[data-market-change-percent-instrument]').forEach((el) => {
        const id = el.dataset.marketPriceInstrument || el.dataset.marketPreviousInstrument || el.dataset.marketChangeInstrument || el.dataset.marketChangePercentInstrument;
        if (id) instruments.add(String(id));
    });

    document.querySelectorAll('[data-market-analysis-instrument]').forEach((el) => {
        const id = String(el.dataset.marketAnalysisInstrument || '');
        const marketplace = String(el.dataset.marketplace || 'live').toLowerCase();
        if (id) {
            instruments.add(id);
            instrumentAnalyses.add(id + ':' + marketplace);
        }
    });

    document.querySelectorAll('[data-market-price-symbol],[data-market-previous-symbol],[data-market-change-symbol],[data-market-change-percent-symbol]').forEach((el) => {
        const symbol = String(el.dataset.marketPriceSymbol || el.dataset.marketPreviousSymbol || el.dataset.marketChangeSymbol || el.dataset.marketChangePercentSymbol || '').toUpperCase();
        if (symbol) symbols.add(symbol);
    });

    document.querySelectorAll('[data-market-position-pnl],[data-market-position-return],[data-market-position-cmp],[data-market-position-difference],[data-market-position-label]').forEach((el) => {
        const id = el.dataset.marketPositionPnl || el.dataset.marketPositionReturn || el.dataset.marketPositionCmp || el.dataset.marketPositionDifference || el.dataset.marketPositionLabel;
        if (id) positions.add(String(id));
    });

    document.querySelectorAll('[data-market-analysis-symbol]').forEach((el) => {
        if (el.dataset.marketAnalysisInstrument) return;
        const symbol = String(el.dataset.marketAnalysisSymbol || '').toUpperCase();
        const marketplace = String(el.dataset.marketplace || 'live').toLowerCase();
        if (symbol) {
            symbols.add(symbol);
            analyses.add(symbol + ':' + marketplace);
        }
    });

    return {
        instruments: [...instruments],
        instrumentAnalyses: [...instrumentAnalyses],
        symbols: [...symbols],
        positions: [...positions],
        analyses: [...analyses],
    };
};

const instrumentRowFor = (payload, el, id) => {
    const marketplace = String(el.dataset.marketplace || payload.active_marketplace || 'live').toLowerCase();
    return payload.instruments?.[id]?.[marketplace] || null;
};

const stockRowFor = (payload, el, symbol) => {
    const marketplace = String(el.dataset.marketplace || payload.active_marketplace || 'live').toLowerCase();
    return payload.stocks?.[symbol]?.[marketplace] || null;
};

const applyPriceRow = (el, row, field) => {
    if (!row || row.unavailable) return;

    if (field === 'price') {
        applyMovementTick(el, row);
        el.textContent = row.formatted_price;
        return;
    }

    if (field === 'previous') {
        el.textContent = row.formatted_previous;
        return;
    }

    if (field === 'change') {
        el.textContent = row.formatted_change;
        tone(el, row.change);
        return;
    }

    if (field === 'change_percent') {
        el.textContent = row.formatted_change_percent;
        tone(el, row.change_percent);
    }
};

const apply = (payload) => {
    document.querySelectorAll('[data-market-price-instrument]').forEach((el) => {
        const id = String(el.dataset.marketPriceInstrument || '');
        applyPriceRow(el, instrumentRowFor(payload, el, id), 'price');
    });

    document.querySelectorAll('[data-market-previous-instrument]').forEach((el) => {
        const id = String(el.dataset.marketPreviousInstrument || '');
        applyPriceRow(el, instrumentRowFor(payload, el, id), 'previous');
    });

    document.querySelectorAll('[data-market-change-instrument]').forEach((el) => {
        const id = String(el.dataset.marketChangeInstrument || '');
        applyPriceRow(el, instrumentRowFor(payload, el, id), 'change');
    });

    document.querySelectorAll('[data-market-change-percent-instrument]').forEach((el) => {
        const id = String(el.dataset.marketChangePercentInstrument || '');
        applyPriceRow(el, instrumentRowFor(payload, el, id), 'change_percent');
    });

    // Legacy stock-specific DOM hooks remain supported.
    document.querySelectorAll('[data-market-price-symbol]').forEach((el) => {
        if (el.dataset.marketPriceInstrument) return;
        const symbol = String(el.dataset.marketPriceSymbol || '').toUpperCase();
        applyPriceRow(el, stockRowFor(payload, el, symbol), 'price');
    });

    document.querySelectorAll('[data-market-previous-symbol]').forEach((el) => {
        const symbol = String(el.dataset.marketPreviousSymbol || '').toUpperCase();
        applyPriceRow(el, stockRowFor(payload, el, symbol), 'previous');
    });

    document.querySelectorAll('[data-market-change-symbol]').forEach((el) => {
        const symbol = String(el.dataset.marketChangeSymbol || '').toUpperCase();
        applyPriceRow(el, stockRowFor(payload, el, symbol), 'change');
    });

    document.querySelectorAll('[data-market-change-percent-symbol]').forEach((el) => {
        if (el.dataset.marketChangePercentInstrument) return;
        const symbol = String(el.dataset.marketChangePercentSymbol || '').toUpperCase();
        applyPriceRow(el, stockRowFor(payload, el, symbol), 'change_percent');
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

    document.querySelectorAll('[data-market-analysis-instrument]').forEach((el) => {
        const id = String(el.dataset.marketAnalysisInstrument || '');
        const marketplace = String(el.dataset.marketplace || 'live').toLowerCase();
        const next = payload.instrument_analysis?.[id + ':' + marketplace];
        if (next && window.RcentzCharts?.refreshAnalysis) {
            window.RcentzCharts.refreshAnalysis(el, next);
        }
    });

    document.querySelectorAll('[data-market-analysis-symbol]').forEach((el) => {
        if (el.dataset.marketAnalysisInstrument) return;
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
        if (request.instruments.length) params.set('instruments', request.instruments.join(','));
        if (request.instrumentAnalyses.length) params.set('instrument_analysis', request.instrumentAnalyses.join(','));
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
