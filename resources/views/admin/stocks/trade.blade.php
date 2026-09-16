<x-admin-layout>
<x-slot name="header">Admin Trading Desk</x-slot>

<div class="ui-page max-w-[1680px]">
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker text-[10px]">Trading Management · Execution Desk</p>
            <div class="mt-1 flex flex-wrap items-center gap-2">
                <h1 class="ui-heading !text-2xl">{{ $stock->symbol }}</h1>
                <span class="rounded-full border border-border bg-muted px-2 py-0.5 text-[8px] font-semibold uppercase tracking-[.11em] text-muted-foreground">{{ $stock->name }}</span>
                <span class="rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2 py-0.5 text-[8px] font-semibold uppercase tracking-[.11em] text-emerald-600">Live desk</span>
            </div>
            <p class="ui-lead !mt-2 !max-w-3xl !text-[13px]">
                Market analysis and execution controls share one workstation. Every trade routes through StockTradeExecutor.
            </p>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('admin.stocks.show',$stock) }}" class="ui-btn ui-btn-secondary">
                <i data-lucide="arrow-left" class="h-4 w-4"></i> Stock
            </a>
            <a href="{{ route('admin.stocks.transactions.index') }}?stock={{ $stock->id }}" class="ui-btn ui-btn-secondary">
                <i data-lucide="receipt-text" class="h-4 w-4"></i> Ledger
            </a>
        </div>
    </section>

    @if($errors->any())
        <section class="mt-4 rounded-xl border border-red-500/20 bg-red-500/5 p-4">
            <p class="text-[11px] font-semibold text-red-600">Execution blocked</p>
            @foreach($errors->all() as $error)
                <p class="mt-1 text-[10px] text-red-600/90">{{ $error }}</p>
            @endforeach
        </section>
    @endif

    <section class="mt-5 grid gap-5 2xl:grid-cols-[minmax(0,1.9fr)_minmax(360px,.75fr)]">
        <div class="min-w-0">
            @include('trading.partials.analysis-chart', [
                'stock'=>$stock,
                'analysis'=>$analysis,
                'chartHeight'=>'h-[420px] lg:h-[520px]',
            ])

            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                <div class="ui-panel p-4">
                    <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Market price</p>
                    <p class="mt-2 text-lg font-semibold tabular-nums">{{ currency_symbol() }}{{ number_format($stock->current_price,2) }}</p>
                </div>
                <div class="ui-panel p-4">
                    <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Admin available</p>
                    <p class="mt-2 text-lg font-semibold tabular-nums">{{ $adminWallet->formatted_available_balance }}</p>
                </div>
                <div class="ui-panel p-4">
                    <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Admin holding</p>
                    <p class="mt-2 text-lg font-semibold tabular-nums">{{ number_format((float)($adminHolding?->quantity ?? 0),6) }}</p>
                </div>
            </div>
        </div>

        <aside>
            <section class="ui-panel overflow-hidden" x-data="{ tab: 'strategy' }">
                <div class="border-b border-border/70 p-2">
                    <div class="grid grid-cols-3 gap-1 rounded-xl bg-muted/30 p-1">
                        <button
                            type="button"
                            @click="tab = 'strategy'"
                            :class="tab === 'strategy' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                            class="rounded-lg px-2 py-2 text-[10px] font-semibold transition"
                        >
                            Strategy
                        </button>
                        <button
                            type="button"
                            @click="tab = 'admin'"
                            :class="tab === 'admin' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                            class="rounded-lg px-2 py-2 text-[10px] font-semibold transition"
                        >
                            Admin
                        </button>
                        <button
                            type="button"
                            @click="tab = 'user'"
                            :class="tab === 'user' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                            class="rounded-lg px-2 py-2 text-[10px] font-semibold transition"
                        >
                            User
                        </button>
                    </div>
                </div>

                <div x-show="tab === 'strategy'" x-cloak>
                    <div class="border-b border-border/70 px-4 py-3">
                        <div class="flex items-center gap-2">
                            <i data-lucide="git-branch" class="h-4 w-4 text-violet-500"></i>
                            <h2 class="text-[12px] font-semibold">Strategy trade</h2>
                        </div>
                        <p class="mt-1 text-[9px] text-muted-foreground">
                            Provider-owned execution. Eligible followers of the exact strategy may mirror it.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('admin.stocks.trade.execute',$stock) }}" class="space-y-3 p-4">
                        @csrf

                        <select name="strategy_id" class="ui-input w-full" required>
                            <option value="">Select strategy</option>
                            @foreach($strategies as $strategy)
                                @php $provider=$strategy->profile?->user; @endphp
                                <option value="{{ $strategy->id }}">
                                    {{ $strategy->name }} · {{ $provider?->name ?? 'No provider' }}
                                </option>
                            @endforeach
                        </select>

                        <div class="grid grid-cols-2 gap-2">
                            <select name="side" class="ui-input" required>
                                <option value="buy">Buy</option>
                                <option value="sell">Sell</option>
                            </select>

                            <input
                                name="quantity"
                                type="number"
                                step="0.000001"
                                min="0.000001"
                                class="ui-input"
                                placeholder="Quantity"
                                required
                            >
                        </div>


                        {{-- V5.7 shared admin trade contract controls --}}
                        @include('admin.stocks.partials.trade-contract-controls', [
                            'entryPrice' => (float) $stock->current_price,
                        ])
                        <button class="ui-btn ui-btn-primary w-full">
                            Execute strategy trade
                        </button>
                    </form>
                </div>

                <div x-show="tab === 'admin'" x-cloak>
                    <div class="border-b border-border/70 px-4 py-3">
                        <div class="flex items-center gap-2">
                            <i data-lucide="shield" class="h-4 w-4 text-sky-500"></i>
                            <h2 class="text-[12px] font-semibold">Direct admin trade</h2>
                        </div>
                        <p class="mt-1 text-[9px] text-muted-foreground">
                            Uses the administrator's own wallet and holding. Never mirrors to customers.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('admin.stocks.trade.direct',$stock) }}" class="space-y-3 p-4">
                        @csrf

                        <div class="grid grid-cols-2 gap-2">
                            <select name="side" class="ui-input" required>
                                <option value="buy">Buy</option>
                                <option value="sell">Sell</option>
                            </select>

                            <input
                                name="quantity"
                                type="number"
                                step="0.000001"
                                min="0.000001"
                                class="ui-input"
                                placeholder="Quantity"
                                required
                            >
                        </div>

                        <div class="rounded-xl border border-border bg-muted/10 p-3">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-[9px] text-muted-foreground">Available</span>
                                <span class="text-[10px] font-semibold tabular-nums">{{ $adminWallet->formatted_available_balance }}</span>
                            </div>
                            <div class="mt-2 flex items-center justify-between gap-3">
                                <span class="text-[9px] text-muted-foreground">{{ $stock->symbol }} holding</span>
                                <span class="text-[10px] font-semibold tabular-nums">{{ number_format((float)($adminHolding?->quantity ?? 0),6) }}</span>
                            </div>
                        </div>


                        {{-- V5.7 shared admin trade contract controls --}}
                        @include('admin.stocks.partials.trade-contract-controls', [
                            'entryPrice' => (float) $stock->current_price,
                        ])
                        <button class="ui-btn ui-btn-primary w-full">
                            Execute admin trade
                        </button>
                    </form>
                </div>

                <div x-show="tab === 'user'" x-cloak>
                    <div class="border-b border-border/70 px-4 py-3">
                        <div class="flex items-center gap-2">
                            <i data-lucide="user-round-cog" class="h-4 w-4 text-amber-500"></i>
                            <h2 class="text-[12px] font-semibold">Trade for user</h2>
                        </div>
                        <p class="mt-1 text-[9px] text-muted-foreground">
                            Executes against the selected customer's real wallet and holdings with admin attribution.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('admin.stocks.trade.user',$stock) }}" class="space-y-3 p-4">
                        @csrf

                        <select name="user_id" class="ui-input w-full" required>
                            <option value="">Select KYC customer</option>
                            @foreach($customers as $customer)
                                @php $holding=$customer->stockHoldings?->first(); @endphp
                                <option value="{{ $customer->id }}">
                                    {{ $customer->name }}
                                    · {{ $customer->wallet?->formatted_available_balance ?? '$0.00' }}
                                    · {{ number_format((float)($holding?->quantity ?? 0),4) }} {{ $stock->symbol }}
                                </option>
                            @endforeach
                        </select>

                        <div class="grid grid-cols-2 gap-2">
                            <select name="side" class="ui-input" required>
                                <option value="buy">Buy</option>
                                <option value="sell">Sell</option>
                            </select>

                            <input
                                name="quantity"
                                type="number"
                                step="0.000001"
                                min="0.000001"
                                class="ui-input"
                                placeholder="Quantity"
                                required
                            >
                        </div>

                        <textarea
                            name="reason"
                            class="ui-input min-h-20 w-full"
                            placeholder="Administrative reason for trading on this account"
                            required
                        ></textarea>


                        {{-- V5.7 shared admin trade contract controls --}}
                        @include('admin.stocks.partials.trade-contract-controls', [
                            'entryPrice' => (float) $stock->current_price,
                        ])
                        <button class="ui-btn ui-btn-primary w-full">
                            Execute for user
                        </button>
                    </form>
                </div>

                <div class="border-t border-border/70 p-3">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-[9px] font-semibold">Account operations</p>
                            <p class="mt-0.5 text-[8px] text-muted-foreground">Credits, debits, profit entries and historical events.</p>
                        </div>

                        <a href="{{ route('admin.users.index') }}" class="ui-btn ui-btn-secondary !h-7 !px-2.5 text-[9px]">
                            Open users
                        </a>
                    </div>
                </div>
            </section>
        </aside>
    </section>
</div>


{{-- V5.7 admin trade contract parity --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-admin-trade-contract]').forEach((root) => {
        const form = root.closest('form');
        if (!form) return;

        const entry = Number(root.dataset.entryPrice || 0);
        if (!Number.isFinite(entry) || entry <= 0) return;

        const side = form.querySelector('select[name="side"]');
        const buyFields = root.querySelector('[data-role="buy-fields"]');
        const sellNotice = root.querySelector('[data-role="sell-notice"]');

        const slPct = root.querySelector('[data-role="sl-percent"]');
        const slPrice = root.querySelector('[data-role="sl-price"]');
        const slSummary = root.querySelector('[data-role="sl-summary"]');
        const tpPct = root.querySelector('[data-role="tp-percent"]');
        const tpPrice = root.querySelector('[data-role="tp-price"]');
        const tpSummary = root.querySelector('[data-role="tp-summary"]');

        const duration = root.querySelector('[data-role="duration-minutes"]');
        const customDuration = root.querySelector('[data-role="custom-duration"]');
        const durationLabel = root.querySelector('[data-role="duration-label"]');
        const durationButtons = Array.from(root.querySelectorAll('[data-duration-minutes]'));
        const clearDuration = root.querySelector('[data-clear-duration]');

        let syncing = false;
        const money = (value) => Number.isFinite(value) ? value.toFixed(2) : '';
        const pct = (value) => Number.isFinite(value) ? value.toFixed(2) : '';

        const setSummary = (el, text, valid = true) => {
            if (!el) return;
            el.textContent = text;
            el.classList.toggle('text-red-500', !valid);
            el.classList.toggle('text-muted-foreground', valid);
        };

        const prettyDuration = (minutes) => {
            minutes = Number(minutes) || 0;
            if (!minutes) return 'Regular market close';
            if (minutes % 10080 === 0 && minutes >= 10080) return (minutes / 10080) + ' week' + (minutes === 10080 ? '' : 's');
            if (minutes % 1440 === 0 && minutes >= 1440) return (minutes / 1440) + ' day' + (minutes === 1440 ? '' : 's');
            if (minutes % 60 === 0 && minutes >= 60) return (minutes / 60) + ' hour' + (minutes === 60 ? '' : 's');
            return minutes + ' minutes';
        };

        const syncSlFromPercent = () => {
            if (syncing) return;
            syncing = true;
            const value = Number(slPct?.value);
            if (Number.isFinite(value) && value > 0 && value <= 100) {
                const price = entry * (1 - value / 100);
                slPrice.value = money(Math.max(0, price));
                setSummary(slSummary, pct(value) + '% below EMP → ' + money(Math.max(0, price)));
            } else {
                if (slPrice) slPrice.value = '';
                setSummary(slSummary, 'Enter % or market price');
            }
            syncing = false;
        };

        const syncSlFromPrice = () => {
            if (syncing) return;
            syncing = true;
            const value = Number(slPrice?.value);
            if (Number.isFinite(value) && value > 0 && value < entry) {
                const percent = ((entry - value) / entry) * 100;
                slPct.value = pct(percent);
                setSummary(slSummary, money(value) + ' = ' + pct(percent) + '% below EMP');
            } else if (slPrice?.value !== '') {
                slPct.value = '';
                setSummary(slSummary, 'Stop Loss must be below EMP (' + money(entry) + ')', false);
            } else {
                slPct.value = '';
                setSummary(slSummary, 'Enter % or market price');
            }
            syncing = false;
        };

        const syncTpFromPercent = () => {
            if (syncing) return;
            syncing = true;
            const value = Number(tpPct?.value);
            if (Number.isFinite(value) && value > 0 && value <= 100) {
                const price = entry * (1 + value / 100);
                tpPrice.value = money(price);
                setSummary(tpSummary, pct(value) + '% above EMP → ' + money(price));
            } else {
                if (tpPrice) tpPrice.value = '';
                setSummary(tpSummary, 'Enter % or market price');
            }
            syncing = false;
        };

        const syncTpFromPrice = () => {
            if (syncing) return;
            syncing = true;
            const value = Number(tpPrice?.value);
            if (Number.isFinite(value) && value > entry) {
                const percent = ((value - entry) / entry) * 100;
                tpPct.value = pct(percent);
                setSummary(tpSummary, money(value) + ' = ' + pct(percent) + '% above EMP');
            } else if (tpPrice?.value !== '') {
                tpPct.value = '';
                setSummary(tpSummary, 'Take Profit must be above EMP (' + money(entry) + ')', false);
            } else {
                tpPct.value = '';
                setSummary(tpSummary, 'Enter % or market price');
            }
            syncing = false;
        };

        const setDuration = (minutes, button = null) => {
            const n = Math.max(1, Math.min(43200, Math.floor(Number(minutes) || 0)));
            if (!n) return;
            duration.value = n;
            if (durationLabel) durationLabel.textContent = prettyDuration(n);
            durationButtons.forEach((btn) => btn.classList.remove('!border-sky-500/40', '!bg-sky-500/10'));
            if (button) button.classList.add('!border-sky-500/40', '!bg-sky-500/10');
        };

        const clearHorizon = () => {
            duration.value = '';
            if (customDuration) customDuration.value = '';
            if (durationLabel) durationLabel.textContent = 'Regular market close';
            durationButtons.forEach((btn) => btn.classList.remove('!border-sky-500/40', '!bg-sky-500/10'));
        };

        const applySideMode = () => {
            const isBuy = !side || side.value === 'buy';
            buyFields?.classList.toggle('hidden', !isBuy);
            sellNotice?.classList.toggle('hidden', isBuy);

            [slPct, slPrice, tpPct, tpPrice, duration, customDuration].forEach((field) => {
                if (field) field.disabled = !isBuy;
            });
            durationButtons.forEach((button) => button.disabled = !isBuy);
            if (clearDuration) clearDuration.disabled = !isBuy;
        };

        slPct?.addEventListener('input', syncSlFromPercent);
        slPrice?.addEventListener('input', syncSlFromPrice);
        tpPct?.addEventListener('input', syncTpFromPercent);
        tpPrice?.addEventListener('input', syncTpFromPrice);

        durationButtons.forEach((button) => {
            button.addEventListener('click', () => {
                if (customDuration) customDuration.value = '';
                setDuration(button.dataset.durationMinutes, button);
            });
        });

        customDuration?.addEventListener('input', () => {
            if (customDuration.value) setDuration(customDuration.value, null);
            else clearHorizon();
        });

        clearDuration?.addEventListener('click', clearHorizon);
        side?.addEventListener('change', applySideMode);

        clearHorizon();
        applySideMode();
    });
});
</script>

</x-admin-layout>
