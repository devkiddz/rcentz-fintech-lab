<?php

$path = __DIR__.'/../resources/views/trading/buy.blade.php';
$text = file_get_contents($path);

$start = '<div class="mt-4 rounded-xl border border-border bg-muted/10 p-3">';
$marker = '<div class="mt-4 border-t border-border pt-4">';

$startPos = strpos($text, $start);
$markerPos = strpos($text, $marker, $startPos === false ? 0 : $startPos);

if ($startPos === false || $markerPos === false) {
    throw new RuntimeException('Could not locate the existing Risk Management block in trading/buy.blade.php.');
}

$newRisk = <<<'BLADE'
<div
    id="trade-risk-calculator"
    class="mt-4 rounded-xl border border-border bg-muted/10 p-3"
    data-entry-price="{{ number_format((float)$stock->current_price, 8, '.', '') }}"
>
    <div class="flex items-center justify-between gap-3">
        <div>
            <p class="text-[9px] uppercase tracking-[.13em] text-muted-foreground">Risk management</p>
            <h3 class="mt-1 text-xs font-semibold">Entry · Stop loss · Take profit</h3>
        </div>

        <span class="rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2 py-1 text-[9px] font-semibold text-emerald-600">
            First trigger wins
        </span>
    </div>

    <div class="mt-3 rounded-lg border border-border bg-background/60 px-3 py-2.5">
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Entry Market Price (EMP)</p>
                <p class="mt-1 text-sm font-semibold">
                    {{ currency_symbol() }}{{ number_format((float)$stock->current_price, 2) }}
                </p>
            </div>
            <span class="text-[9px] text-muted-foreground">Fixed reference</span>
        </div>
    </div>

    <div class="mt-3 space-y-3">
        <section class="rounded-xl border border-border bg-background/50 p-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <p class="text-xs font-semibold">Stop loss</p>
                    <p class="mt-0.5 text-[9px] text-muted-foreground">Closes below EMP</p>
                </div>
                <span id="stop-loss-summary" class="rounded-full border border-border bg-muted/30 px-2 py-1 text-[9px] text-muted-foreground">
                    Set % or price
                </span>
            </div>

            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-[8px] uppercase tracking-[.1em] text-muted-foreground">Percent</label>
                    <div class="relative">
                        <input
                            id="stop_loss_percent"
                            name="stop_loss_percent"
                            type="number"
                            min="0.01"
                            max="100"
                            step="0.01"
                            class="ui-input w-full pr-8"
                            placeholder="2.00"
                        >
                        <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-muted-foreground">%</span>
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-[8px] uppercase tracking-[.1em] text-muted-foreground">Market price</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[10px] text-muted-foreground">
                            {{ currency_symbol() }}
                        </span>
                        <input
                            id="stop_loss_price_preview"
                            type="number"
                            min="0"
                            step="0.01"
                            class="ui-input w-full pl-7"
                            placeholder="342.40"
                        >
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-border bg-background/50 p-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <p class="text-xs font-semibold">Take profit</p>
                    <p class="mt-0.5 text-[9px] text-muted-foreground">Closes above EMP</p>
                </div>
                <span id="take-profit-summary" class="rounded-full border border-border bg-muted/30 px-2 py-1 text-[9px] text-muted-foreground">
                    Set % or price
                </span>
            </div>

            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-[8px] uppercase tracking-[.1em] text-muted-foreground">Percent</label>
                    <div class="relative">
                        <input
                            id="take_profit_percent"
                            name="take_profit_percent"
                            type="number"
                            min="0.01"
                            max="100"
                            step="0.01"
                            class="ui-input w-full pr-8"
                            placeholder="5.00"
                        >
                        <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-muted-foreground">%</span>
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-[8px] uppercase tracking-[.1em] text-muted-foreground">Market price</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[10px] text-muted-foreground">
                            {{ currency_symbol() }}
                        </span>
                        <input
                            id="take_profit_price_preview"
                            type="number"
                            min="0"
                            step="0.01"
                            class="ui-input w-full pl-7"
                            placeholder="366.86"
                        >
                    </div>
                </div>
            </div>
        </section>
    </div>

    <p class="mt-3 text-[9px] leading-4 text-muted-foreground">
        Enter either the percentage or market price. The other value converts automatically from EMP.
    </p>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('trade-risk-calculator');
    if (!root) return;

    const entry = Number(root.dataset.entryPrice || 0);
    if (!entry || entry <= 0) return;

    const slPct = document.getElementById('stop_loss_percent');
    const slPrice = document.getElementById('stop_loss_price_preview');
    const tpPct = document.getElementById('take_profit_percent');
    const tpPrice = document.getElementById('take_profit_price_preview');
    const slSummary = document.getElementById('stop-loss-summary');
    const tpSummary = document.getElementById('take-profit-summary');

    let syncing = false;

    const money = (value) => Number.isFinite(value) ? value.toFixed(2) : '';
    const pct = (value) => Number.isFinite(value) ? value.toFixed(2) : '';

    const setSummary = (el, text, valid = true) => {
        if (!el) return;
        el.textContent = text;
        el.classList.toggle('text-red-500', !valid);
        el.classList.toggle('text-muted-foreground', valid);
    };

    const syncSlFromPercent = () => {
        if (syncing) return;
        syncing = true;

        const percent = Number(slPct.value);

        if (Number.isFinite(percent) && percent > 0) {
            const price = entry * (1 - percent / 100);
            slPrice.value = money(Math.max(0, price));
            setSummary(slSummary, `${pct(percent)}% → ${money(price)}`);
        } else {
            slPrice.value = '';
            setSummary(slSummary, 'Set % or price');
        }

        syncing = false;
    };

    const syncSlFromPrice = () => {
        if (syncing) return;
        syncing = true;

        const price = Number(slPrice.value);

        if (Number.isFinite(price) && price > 0 && price < entry) {
            const percent = ((entry - price) / entry) * 100;
            slPct.value = pct(percent);
            setSummary(slSummary, `${money(price)} → ${pct(percent)}%`);
        } else if (slPrice.value !== '') {
            slPct.value = '';
            setSummary(slSummary, `Must be below ${money(entry)}`, false);
        } else {
            slPct.value = '';
            setSummary(slSummary, 'Set % or price');
        }

        syncing = false;
    };

    const syncTpFromPercent = () => {
        if (syncing) return;
        syncing = true;

        const percent = Number(tpPct.value);

        if (Number.isFinite(percent) && percent > 0) {
            const price = entry * (1 + percent / 100);
            tpPrice.value = money(price);
            setSummary(tpSummary, `${pct(percent)}% → ${money(price)}`);
        } else {
            tpPrice.value = '';
            setSummary(tpSummary, 'Set % or price');
        }

        syncing = false;
    };

    const syncTpFromPrice = () => {
        if (syncing) return;
        syncing = true;

        const price = Number(tpPrice.value);

        if (Number.isFinite(price) && price > entry) {
            const percent = ((price - entry) / entry) * 100;
            tpPct.value = pct(percent);
            setSummary(tpSummary, `${money(price)} → ${pct(percent)}%`);
        } else if (tpPrice.value !== '') {
            tpPct.value = '';
            setSummary(tpSummary, `Must be above ${money(entry)}`, false);
        } else {
            tpPct.value = '';
            setSummary(tpSummary, 'Set % or price');
        }

        syncing = false;
    };

    slPct?.addEventListener('input', syncSlFromPercent);
    slPrice?.addEventListener('input', syncSlFromPrice);
    tpPct?.addEventListener('input', syncTpFromPercent);
    tpPrice?.addEventListener('input', syncTpFromPrice);
});
</script>

BLADE;

$text = substr($text, 0, $startPos)
    . $newRisk
    . "\n\n"
    . substr($text, $markerPos);

file_put_contents($path, $text);

echo "V5.5.1 responsive EMP / SL / TP risk UI wired.\n";
