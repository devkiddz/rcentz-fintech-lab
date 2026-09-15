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
            <span class="text-[9px] text-muted-foreground">Reference price</span>
        </div>
    </div>

    <div class="mt-3 grid gap-3 sm:grid-cols-2">
        <div class="rounded-xl border border-border bg-background/50 p-3">
            <div class="flex items-center justify-between gap-2">
                <label class="ui-label">Stop loss</label>
                <span id="sl-direction" class="text-[9px] text-muted-foreground">Below EMP</span>
            </div>

            <div class="mt-2 grid grid-cols-2 gap-2">
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
                            placeholder="e.g. 2"
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
                            placeholder="Price"
                        >
                    </div>
                </div>
            </div>

            <p id="stop-loss-summary" class="mt-2 text-[9px] leading-4 text-muted-foreground">
                Enter either a percentage or market price. The other value will calculate automatically.
            </p>
        </div>

        <div class="rounded-xl border border-border bg-background/50 p-3">
            <div class="flex items-center justify-between gap-2">
                <label class="ui-label">Take profit</label>
                <span id="tp-direction" class="text-[9px] text-muted-foreground">Above EMP</span>
            </div>

            <div class="mt-2 grid grid-cols-2 gap-2">
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
                            placeholder="e.g. 5"
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
                            placeholder="Price"
                        >
                    </div>
                </div>
            </div>

            <p id="take-profit-summary" class="mt-2 text-[9px] leading-4 text-muted-foreground">
                Enter either a percentage or market price. The other value will calculate automatically.
            </p>
        </div>
    </div>

    <div class="mt-3 rounded-lg border border-border bg-muted/20 px-3 py-2">
        <p class="text-[9px] leading-4 text-muted-foreground">
            <strong class="text-foreground">EMP</strong> stays fixed at entry.
            For this long position, Stop Loss is below EMP and Take Profit is above EMP.
            You can enter either the percentage or the target market price; both represent the same trigger.
        </p>
    </div>
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

    const money = (value) => {
        if (!Number.isFinite(value)) return '';
        return value.toFixed(2);
    };

    const pct = (value) => {
        if (!Number.isFinite(value)) return '';
        return value.toFixed(2);
    };

    const syncSlFromPercent = () => {
        if (syncing) return;
        syncing = true;

        const percent = Number(slPct.value);

        if (Number.isFinite(percent) && percent > 0) {
            const price = entry * (1 - percent / 100);
            slPrice.value = money(Math.max(0, price));
            slSummary.textContent =
                `${pct(percent)}% below EMP → trigger around ${money(price)}.`;
        } else {
            slPrice.value = '';
            slSummary.textContent =
                'Enter either a percentage or market price. The other value will calculate automatically.';
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
            slSummary.textContent =
                `${money(price)} is ${pct(percent)}% below EMP.`;
        } else if (slPrice.value !== '') {
            slPct.value = '';
            slSummary.textContent =
                `For a long trade, Stop Loss must be below EMP (${money(entry)}).`;
        } else {
            slPct.value = '';
            slSummary.textContent =
                'Enter either a percentage or market price. The other value will calculate automatically.';
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
            tpSummary.textContent =
                `${pct(percent)}% above EMP → trigger around ${money(price)}.`;
        } else {
            tpPrice.value = '';
            tpSummary.textContent =
                'Enter either a percentage or market price. The other value will calculate automatically.';
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
            tpSummary.textContent =
                `${money(price)} is ${pct(percent)}% above EMP.`;
        } else if (tpPrice.value !== '') {
            tpPct.value = '';
            tpSummary.textContent =
                `For a long trade, Take Profit must be above EMP (${money(entry)}).`;
        } else {
            tpPct.value = '';
            tpSummary.textContent =
                'Enter either a percentage or market price. The other value will calculate automatically.';
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

echo "V5.5 EMP / SL / TP price-percentage sync wired.\n";
