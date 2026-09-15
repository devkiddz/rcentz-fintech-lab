<x-user-layout>
<x-slot name="header">Buy {{ $stock->symbol }}</x-slot>

@php
    $availableBalance = (float) $wallet->available_balance;
    $price = (float) $stock->current_price;
    $latestQuote = App\Models\StockQuote::getLatestQuote($stock->symbol);
@endphp

<div class="ui-page max-w-[1280px]">
    <section class="ui-page-header">
        <div>
            <div class="flex items-center gap-2">
                <p class="ui-kicker text-[10px]">Stock Order</p>
                <span class="rounded-full border border-sky-500/20 bg-sky-500/10 px-2 py-1 text-[9px] font-semibold text-sky-600">{{ $stock->symbol }}</span>
            </div>
            <h1 class="ui-heading !text-2xl">Buy {{ $stock->company_name }}</h1>
            <p class="ui-lead !text-[13px]">{{ $stock->sector }} · Live execution at the current stored market price.</p>
        </div>

        <div class="ui-panel min-w-[240px] p-3.5">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <p class="text-[9px] uppercase tracking-[.13em] text-muted-foreground">Current price</p>
                    <p class="mt-1 text-2xl font-semibold tabular-nums">{{ currency_symbol() }}{{ number_format($price,2) }}</p>
                </div>
                <p class="text-xs font-semibold {{ $stock->change_percentage >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                    {{ $stock->change_percentage >= 0 ? '+' : '' }}{{ number_format($stock->change_percentage,2) }}%
                </p>
            </div>
        </div>
    </section>


    <div class="mb-4">
        @include('trading.partials.analysis-chart',['stock'=>$stock,'analysis'=>$analysis,'chartHeight'=>'h-[300px] sm:h-[360px] lg:h-[420px]'])
    </div>

    <form action="{{ route('trading.execute-buy',$stock) }}" method="POST" id="buy-stock-form" class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_390px]">
        @csrf

        <div class="space-y-4">
            <section class="ui-panel overflow-hidden">
                <div class="border-b border-border px-4 py-3">
                    <p class="text-[9px] uppercase tracking-[.13em] text-muted-foreground">Order composer</p>
                    <h2 class="mt-1 text-sm font-semibold">Choose your position size</h2>
                </div>

                <div class="p-4">
                    <label for="quantity" class="ui-label !text-[10px]">Number of shares</label>

                    <div class="grid gap-3 sm:grid-cols-[1fr_220px]">
                        <div>
                            <input
                                id="quantity"
                                name="quantity"
                                type="number"
                                min="1"
                                max="10000"
                                step="1"
                                value="{{ old('quantity',1) }}"
                                class="ui-input !h-14 !text-lg !font-semibold"
                                placeholder="0"
                                required
                            >
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach([1,5,10,25] as $quick)
                                    <button type="button" data-quick-shares="{{ $quick }}" class="ui-btn ui-btn-secondary !h-7 !px-2.5 !text-[10px]">{{ $quick }}</button>
                                @endforeach
                                <span class="ml-auto self-center text-[9px] text-muted-foreground">Maximum 10,000 shares</span>
                            </div>
                            @error('quantity')
                                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="rounded-xl border border-border bg-muted/20 p-3">
                            <p class="text-[9px] uppercase tracking-[.12em] text-muted-foreground">Live value</p>
                            <p id="inline-order-value" class="mt-1 text-xl font-semibold tabular-nums">{{ currency_symbol() }}{{ number_format($price,2) }}</p>
                            <div class="mt-2 flex items-center justify-between text-[10px]">
                                <span class="text-muted-foreground">Price / share</span>
                                <span class="font-medium tabular-nums">{{ currency_symbol() }}{{ number_format($price,2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="ui-panel p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[9px] uppercase tracking-[.13em] text-muted-foreground">Market snapshot</p>
                        <h2 class="mt-1 text-sm font-semibold">{{ $stock->symbol }} trading context</h2>
                    </div>
                    <i data-lucide="chart-no-axes-combined" class="h-4 w-4 text-sky-500"></i>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-2.5 sm:grid-cols-4">
                    @foreach([
                        ['Current Price', currency_symbol().number_format($price,2)],
                        ['Day Change', ($stock->change_percentage >= 0 ? '+' : '').number_format($stock->change_percentage,2).'%'],
                        ['Volume', number_format((float)$stock->volume)],
                        ['Market Cap', $stock->market_cap ? currency_symbol().number_format((float)$stock->market_cap) : 'N/A'],
                    ] as [$label,$value])
                        <div class="rounded-lg border border-border bg-muted/10 p-2.5">
                            <p class="text-[8px] uppercase tracking-[.11em] text-muted-foreground">{{ $label }}</p>
                            <p class="mt-1 text-[11px] font-semibold tabular-nums">{{ $value }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            @if($latestQuote && $latestQuote->total_recommendations > 0)
            <section class="ui-panel p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[9px] uppercase tracking-[.13em] text-muted-foreground">Analyst sentiment</p>
                        <h2 class="mt-1 text-sm font-semibold">{{ $latestQuote->recommendation_label }}</h2>
                    </div>
                    <span class="rounded-full border border-violet-500/20 bg-violet-500/10 px-2 py-1 text-[10px] font-semibold text-violet-600">
                        {{ number_format($latestQuote->recommendation_percentage,1) }}% buy rating
                    </span>
                </div>

                <div class="mt-4 h-1.5 overflow-hidden rounded-full bg-muted">
                    <div class="h-full bg-violet-500" style="width:{{ min(100,$latestQuote->recommendation_percentage) }}%"></div>
                </div>

                <div class="mt-3 grid grid-cols-5 gap-2">
                    @foreach([
                        ['Strong Buy',$latestQuote->strong_buy],
                        ['Buy',$latestQuote->buy],
                        ['Hold',$latestQuote->hold],
                        ['Sell',$latestQuote->sell],
                        ['Strong Sell',$latestQuote->strong_sell],
                    ] as [$label,$value])
                        <div class="rounded-lg border border-border p-2 text-center">
                            <p class="text-[8px] text-muted-foreground">{{ $label }}</p>
                            <p class="mt-1 text-xs font-semibold">{{ $value }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
            @endif


            <section class="ui-panel p-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[9px] uppercase tracking-[.13em] text-muted-foreground">Analysis & signal</p>
                        <h2 class="mt-1 text-sm font-semibold">What the stored market data says</h2>
                    </div>
                    <span class="rounded-full border px-2 py-1 text-[9px] font-semibold {{ $analysis['trend']==='Bullish' ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-600' : ($analysis['trend']==='Bearish' ? 'border-red-500/20 bg-red-500/10 text-red-600' : 'border-border bg-muted text-muted-foreground') }}">
                        {{ $analysis['trend'] }}
                    </span>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-2.5 md:grid-cols-4">
                    @foreach([
                        ['Momentum',$analysis['momentum_label'],($analysis['momentum_percent']>=0?'+':'').number_format($analysis['momentum_percent'],2).'%'],
                        ['Support',$analysis['support'] ? currency_symbol().number_format($analysis['support'],2) : '—','Recent range'],
                        ['Resistance',$analysis['resistance'] ? currency_symbol().number_format($analysis['resistance'],2) : '—','Recent range'],
                        ['Risk / Reward',$analysis['risk_reward'],'Derived from range'],
                    ] as [$label,$value,$meta])
                        <div class="rounded-xl border border-border bg-muted/10 p-2.5">
                            <p class="text-[8px] uppercase tracking-[.11em] text-muted-foreground">{{ $label }}</p>
                            <p class="mt-1 text-xs font-semibold">{{ $value }}</p>
                            <p class="mt-1 text-[9px] text-muted-foreground">{{ $meta }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            @if($userHolding)
            <section class="ui-panel p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[9px] uppercase tracking-[.13em] text-muted-foreground">Existing position</p>
                        <h2 class="mt-1 text-sm font-semibold">Current {{ $stock->symbol }} holding</h2>
                    </div>
                    <i data-lucide="pie-chart" class="h-4 w-4 text-amber-500"></i>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-2.5 sm:grid-cols-4">
                    @foreach([
                        ['Shares', number_format((float)$userHolding->quantity,6)],
                        ['Average Price', currency_symbol().number_format((float)$userHolding->average_buy_price,2)],
                        ['Current Value', currency_symbol().number_format((float)$userHolding->current_value,2)],
                        ['Unrealized P/L', (($userHolding->unrealized_gain_loss >= 0 ? '+' : '').currency_symbol().number_format((float)$userHolding->unrealized_gain_loss,2))],
                    ] as [$label,$value])
                        <div class="rounded-lg border border-border bg-muted/10 p-2.5">
                            <p class="text-[8px] uppercase tracking-[.11em] text-muted-foreground">{{ $label }}</p>
                            <p class="mt-1 text-[11px] font-semibold">{{ $value }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
            @endif
        </div>

        <aside class="space-y-4">
            <section class="ui-panel overflow-hidden">
                <div class="border-b border-border px-4 py-3">
                    <p class="text-[9px] uppercase tracking-[.13em] text-muted-foreground">Live order calculator</p>
                    <h2 class="mt-1 text-sm font-semibold">Purchase preview</h2>
                </div>

                <div class="p-4">
                    <div class="grid grid-cols-2 gap-2.5">
                        <div class="rounded-xl border border-sky-500/20 bg-sky-500/5 p-3">
                            <p class="text-[8px] uppercase tracking-[.11em] text-muted-foreground">Shares</p>
                            <p id="shares-display" class="mt-1 text-lg font-semibold tabular-nums">1</p>
                        </div>
                        <div class="rounded-xl border border-sky-500/20 bg-sky-500/5 p-3">
                            <p class="text-[8px] uppercase tracking-[.11em] text-muted-foreground">Order value</p>
                            <p id="total-display" class="mt-1 text-lg font-semibold tabular-nums">{{ currency_symbol() }}{{ number_format($price,2) }}</p>
                        </div>
                    </div>

                    <div class="mt-3 space-y-2.5 rounded-xl border border-border bg-muted/10 p-3">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-muted-foreground">Price per share</span>
                            <span class="font-semibold tabular-nums">{{ currency_symbol() }}{{ number_format($price,2) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-muted-foreground">Available balance</span>
                            <span class="font-semibold tabular-nums">{{ currency_symbol() }}{{ number_format($availableBalance,2) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-muted-foreground">Reserved balance</span>
                            <span class="font-semibold tabular-nums">{{ currency_symbol() }}{{ number_format((float)$wallet->reserved_balance,2) }}</span>
                        </div>
                        <div class="border-t border-border pt-2.5">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-medium">Balance after purchase</span>
                                <span id="remaining-display" class="text-sm font-semibold tabular-nums">{{ currency_symbol() }}{{ number_format(max(0,$availableBalance-$price),2) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="flex items-center justify-between text-[10px]">
                            <span class="text-muted-foreground">Available balance used</span>
                            <span id="balance-used-percent" class="font-semibold">0.0%</span>
                        </div>
                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-muted">
                            <div id="balance-used-bar" class="h-full bg-sky-500 transition-all duration-200" style="width:0%"></div>
                        </div>
                    </div>

                    <div id="order-message" class="mt-4 rounded-xl border border-border bg-muted/10 px-3 py-2.5 text-[10px] text-muted-foreground">
                        Enter the number of shares to preview your total purchase value and remaining balance.
                    </div>


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


<div class="mt-4 border-t border-border pt-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[9px] uppercase tracking-[.13em] text-muted-foreground">Trade horizon</p>
                                <h3 class="mt-1 text-xs font-semibold">How long should this position stay open?</h3>
                            </div>
                            <span class="rounded-full border border-amber-500/20 bg-amber-500/10 px-2 py-1 text-[9px] font-semibold text-amber-600">Plan after buy</span>
                        </div>

                        <div class="mt-3 grid grid-cols-3 gap-2 sm:grid-cols-6">
                            @foreach([[15,'15m'],[30,'30m'],[60,'1h'],[240,'4h'],[1440,'1d'],[10080,'1w']] as [$minutes,$label])
                                <button type="button"
                                        data-plan-minutes="{{ $minutes }}"
                                        class="trade-horizon-btn ui-btn ui-btn-secondary !h-8 !px-2 !text-[10px] {{ $minutes === 60 ? '!border-sky-500/40 !bg-sky-500/10' : '' }}">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>

                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <div>
                                <label for="custom-plan-minutes" class="ui-label !text-[10px]">Custom minutes</label>
                                <input id="custom-plan-minutes" type="number" min="1" max="10080" class="ui-input" placeholder="e.g. 90">
                            </div>
                            <div>
                                <label for="plan_mode" class="ui-label !text-[10px]">At expiry</label>
                                <select id="plan_mode" name="plan_mode" class="ui-input">
                                    <option value="reminder">Notify me to review & sell</option>
                                    <option value="automatic">Automatically sell this quantity</option>
                                </select>
                            </div>
                        </div>

                        <input type="hidden" id="plan_duration_minutes" name="plan_duration_minutes" value="60">

                        <div class="mt-3 flex items-center justify-between rounded-lg border border-border bg-muted/10 px-3 py-2.5">
                            <div>
                                <p class="text-[9px] uppercase tracking-[.11em] text-muted-foreground">Planned action</p>
                                <p class="mt-1 text-[11px] font-semibold">Sell this purchased quantity</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[9px] uppercase tracking-[.11em] text-muted-foreground">Due after</p>
                                <p id="plan-horizon-label" class="mt-1 text-[11px] font-semibold">1 hour</p>
                            </div>
                        </div>
                    </div>

                    <button id="buy-submit" type="submit" class="ui-btn ui-btn-primary mt-4 h-11 w-full">
                        <i data-lucide="shopping-cart" class="h-4 w-4"></i>
                        Buy Shares
                    </button>
                </div>
            </section>

            <section class="ui-panel p-4">
                <div class="flex items-start gap-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-border bg-muted">
                        <i data-lucide="shield-check" class="h-4 w-4 text-emerald-600"></i>
                    </div>
                    <div>
                        <p class="text-xs font-semibold">Execution truth</p>
                        <p class="mt-1 text-[10px] leading-4 text-muted-foreground">
                            The server re-checks your available balance and the current stored stock price before creating the transaction.
                        </p>
                    </div>
                </div>
            </section>
        </aside>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const quantityInput = document.getElementById('quantity');
    const sharesDisplay = document.getElementById('shares-display');
    const totalDisplay = document.getElementById('total-display');
    const inlineOrderValue = document.getElementById('inline-order-value');
    const remainingDisplay = document.getElementById('remaining-display');
    const balanceUsedPercent = document.getElementById('balance-used-percent');
    const balanceUsedBar = document.getElementById('balance-used-bar');
    const orderMessage = document.getElementById('order-message');
    const submitButton = document.getElementById('buy-submit');
    const quickButtons = document.querySelectorAll('[data-quick-shares]');

    const currentPrice = @json($price);
    const availableBalance = @json($availableBalance);
    const currency = @json(currency_symbol());

    function money(value) {
        return currency + Number(value).toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function updateCalculator() {
        const quantity = Math.max(0, Math.floor(Number(quantityInput.value) || 0));
        const total = quantity * currentPrice;
        const remaining = availableBalance - total;
        const usedPercent = availableBalance > 0 ? Math.min(100, (total / availableBalance) * 100) : 0;
        const affordable = quantity > 0 && total <= availableBalance;

        sharesDisplay.textContent = quantity.toLocaleString();
        totalDisplay.textContent = money(total);
        inlineOrderValue.textContent = money(total);
        remainingDisplay.textContent = money(Math.max(0, remaining));
        balanceUsedPercent.textContent = usedPercent.toFixed(1) + '%';
        balanceUsedBar.style.width = usedPercent + '%';

        balanceUsedBar.classList.toggle('bg-red-500', total > availableBalance);
        balanceUsedBar.classList.toggle('bg-sky-500', total <= availableBalance);
        remainingDisplay.classList.toggle('text-red-600', remaining < 0);
        remainingDisplay.classList.toggle('text-emerald-600', remaining >= 0 && quantity > 0);

        submitButton.disabled = !affordable;
        submitButton.classList.toggle('opacity-50', !affordable);
        submitButton.classList.toggle('cursor-not-allowed', !affordable);

        if (quantity <= 0) {
            orderMessage.textContent = 'Enter the number of shares to preview your total purchase value and remaining balance.';
            orderMessage.className = 'mt-4 rounded-xl border border-border bg-muted/10 px-3 py-2.5 text-[10px] text-muted-foreground';
        } else if (total > availableBalance) {
            orderMessage.textContent = 'This order exceeds your available balance by ' + money(total - availableBalance) + '. Reduce the number of shares.';
            orderMessage.className = 'mt-4 rounded-xl border border-red-500/20 bg-red-500/10 px-3 py-2.5 text-[10px] text-red-600';
        } else {
            orderMessage.textContent = quantity.toLocaleString() + ' share' + (quantity === 1 ? '' : 's') + ' × ' + money(currentPrice) + ' = ' + money(total) + '.';
            orderMessage.className = 'mt-4 rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-3 py-2.5 text-[10px] text-emerald-600';
        }
    }

    quantityInput.addEventListener('input', updateCalculator);

    quickButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            quantityInput.value = button.dataset.quickShares;
            updateCalculator();
            quantityInput.focus();
        });
    });

    updateCalculator();

    const planMinutesInput = document.getElementById('plan_duration_minutes');
    const planLabel = document.getElementById('plan-horizon-label');
    const customPlanMinutes = document.getElementById('custom-plan-minutes');
    const horizonButtons = document.querySelectorAll('[data-plan-minutes]');

    function prettyDuration(minutes) {
        minutes = Number(minutes) || 0;
        if (minutes % 10080 === 0 && minutes >= 10080) return (minutes / 10080) + ' week' + (minutes === 10080 ? '' : 's');
        if (minutes % 1440 === 0 && minutes >= 1440) return (minutes / 1440) + ' day' + (minutes === 1440 ? '' : 's');
        if (minutes % 60 === 0 && minutes >= 60) return (minutes / 60) + ' hour' + (minutes === 60 ? '' : 's');
        return minutes + ' minutes';
    }

    function setHorizon(minutes, sourceButton) {
        minutes = Math.max(1, Math.min(10080, Math.floor(Number(minutes) || 60)));
        planMinutesInput.value = minutes;
        planLabel.textContent = prettyDuration(minutes);
        horizonButtons.forEach(btn => btn.classList.remove('!border-sky-500/40','!bg-sky-500/10'));
        if (sourceButton) sourceButton.classList.add('!border-sky-500/40','!bg-sky-500/10');
    }

    horizonButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            customPlanMinutes.value = '';
            setHorizon(button.dataset.planMinutes, button);
        });
    });

    customPlanMinutes.addEventListener('input', function () {
        if (customPlanMinutes.value) setHorizon(customPlanMinutes.value, null);
    });

});
</script>
</x-user-layout>
