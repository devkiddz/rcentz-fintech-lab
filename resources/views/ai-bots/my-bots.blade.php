<x-user-layout>
<x-slot name="header">My AI Bots</x-slot>
<div class="ui-page max-w-[1440px]">
<section class="ui-page-header">
    <div><p class="ui-kicker text-[10px]">AI Trading Bots</p><h1 class="ui-heading !text-xl">My Bots</h1><p class="ui-lead !text-[13px]">Subscribed automation, runtime controls and live execution performance.</p></div>
    <div class="flex flex-wrap gap-2"><a class="ui-btn ui-btn-secondary" href="{{ route('ai-bots.performance') }}"><i data-lucide="activity" class="h-4 w-4"></i> History</a><a class="ui-btn ui-btn-secondary" href="{{ route('ai-bots.marketplace') }}"><i data-lucide="store" class="h-4 w-4"></i> Marketplace</a></div>
</section>
<div class="grid gap-4 xl:grid-cols-2">
@forelse($subscriptions as $subscription)
@php
$m=$subscription->performance_metrics; $bot=$subscription->bot; $product=$subscription->product;
$completed=max(0,(int)$m['completed_count']); $wins=max(0,(int)$m['winning_trades']); $losses=max(0,(int)$m['losing_trades']); $neutral=max(0,$completed-$wins-$losses);
$allocation=(float)($bot?->max_total_spend??0); $spent=(float)($bot?->spent_total??0); $allocationPct=$allocation>0?min(100,($spent/$allocation)*100):0;
$chart=$subscription->price_chart ?? ['quotes'=>[],'executions'=>[],'current'=>0,'previous_close'=>0,'average_entry'=>null];
@endphp
<article class="ui-panel overflow-hidden border border-border/70 bg-gradient-to-br from-background via-background to-muted/10 shadow-sm">
<div class="p-3.5 sm:p-4">
    <div class="flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1 rounded-full border border-sky-500/20 bg-sky-500/10 px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-sky-600"><i data-lucide="candlestick-chart" class="h-3.5 w-3.5"></i>{{ $product->stock->symbol }}</span>
                <span class="inline-flex items-center gap-1 rounded-full border border-border bg-muted/40 px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-muted-foreground"><i data-lucide="cpu" class="h-3.5 w-3.5"></i>{{ strtoupper(str_replace('_',' ',$product->strategy)) }}</span>
                <span class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-[10px] font-semibold {{ $bot?->status==='active'?'border border-emerald-500/20 bg-emerald-500/10 text-emerald-600':'border border-border bg-muted text-muted-foreground' }}">@if($bot?->status==='active')<span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>@else<i data-lucide="pause" class="h-3 w-3"></i>@endif{{ $bot?->status==='active'?'Running':'Paused' }}</span>
            </div>
            <h2 class="mt-2 text-[13px] font-semibold">{{ $product->name }}</h2><p class="mt-0.5 text-[10px] text-muted-foreground">Subscription {{ ucfirst($subscription->status) }}</p>
        </div>
        <details class="relative shrink-0">
            <summary class="list-none cursor-pointer rounded-lg border border-border bg-background p-2 text-muted-foreground transition hover:bg-muted hover:text-foreground">
                <i data-lucide="more-vertical" class="h-4 w-4"></i>
            </summary>
            <div class="absolute right-0 z-40 mt-2 w-48 rounded-xl border border-border bg-background p-1.5 shadow-xl">
                <a href="{{ route('ai-bots.configure',$subscription) }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium hover:bg-muted"><i data-lucide="sliders-horizontal" class="h-3.5 w-3.5"></i> Configure</a>
                <form method="POST" action="{{ route('ai-bots.toggle',$subscription) }}">@csrf<button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-xs font-medium hover:bg-muted"><i data-lucide="{{ $bot?->status==='active'?'pause':'play' }}" class="h-3.5 w-3.5"></i>{{ $bot?->status==='active'?'Pause Bot':'Activate Bot' }}</button></form>
                <form method="POST" action="{{ route('ai-bots.run',$subscription) }}">@csrf<button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-xs font-medium hover:bg-muted"><i data-lucide="zap" class="h-3.5 w-3.5"></i> Run Now</button></form>
                <button type="button" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-xs font-medium hover:bg-muted" onclick="document.getElementById('allocation-dialog-{{ $subscription->id }}').showModal()"><i data-lucide="wallet-cards" class="h-3.5 w-3.5"></i> Allocation Details</button>
                <a href="{{ route('ai-bots.performance') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium hover:bg-muted"><i data-lucide="history" class="h-3.5 w-3.5"></i> Performance</a>
            </div>
        </details>
    </div>

    <section class="mt-3 overflow-hidden rounded-lg border border-border/70 bg-background/35">
        <div class="flex flex-col gap-2 border-b border-border/70 px-3 py-2.5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <i data-lucide="chart-no-axes-combined" class="h-4 w-4 text-sky-500"></i>
                    <p class="text-[9px] font-semibold uppercase tracking-[0.16em] text-muted-foreground">Price action</p>
                    <span class="inline-flex items-center gap-1 text-[10px] text-emerald-600">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Live feed
                    </span>
                </div>
                <div class="mt-1.5 flex flex-wrap items-baseline gap-x-3 gap-y-1">
                    <span class="text-[13px] font-semibold">{{ $product->stock->symbol }} {{ format_currency($chart['current'] ?? 0) }}</span>
                    @php
                        $dayMove = (float)($chart['previous_close'] ?? 0) > 0
                            ? (((float)($chart['current'] ?? 0) - (float)$chart['previous_close']) / (float)$chart['previous_close']) * 100
                            : 0;
                    @endphp
                    <span class="text-xs font-semibold {{ $dayMove >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                        {{ $dayMove >= 0 ? '+' : '' }}{{ number_format($dayMove, 2) }}%
                    </span>
                    @if($chart['average_entry'])
                        <span class="text-[10px] text-muted-foreground">Avg. entry {{ format_currency($chart['average_entry']) }}</span>
                    @endif
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3 text-[10px] text-muted-foreground">
                <span class="inline-flex items-center gap-1.5"><span class="h-0.5 w-4 rounded-full bg-sky-500"></span> Market price</span>
                @if($chart['average_entry'])
                    <span class="inline-flex items-center gap-1.5"><span class="h-0.5 w-4 border-t border-dashed border-amber-500"></span> Avg. entry</span>
                @endif
                <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-emerald-500"></span> Bot buy</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-red-500"></span> Bot sell</span>
            </div>
        </div>

        <div class="p-2">@include('trading.partials.mini-analysis-card',['symbol'=>$product->stock->symbol,'height'=>'h-[230px] sm:h-[280px]'])</div></section>

    <section class="mt-3 overflow-hidden rounded-xl border border-border/70 bg-muted/10" data-bot-tabs>
        <div class="flex items-center gap-1 border-b border-border/70 px-2.5 pt-2.5">
            <button
                type="button"
                class="rounded-t-lg border border-b-0 border-border bg-background px-3 py-2 text-[10px] font-semibold text-foreground"
                data-bot-tab-button="performance"
                aria-selected="true"
            >
                <span class="inline-flex items-center gap-1.5">
                    <i data-lucide="chart-spline" class="h-3.5 w-3.5"></i>
                    Performance
                </span>
            </button>

            <button
                type="button"
                class="rounded-t-lg border border-transparent px-3 py-2 text-[10px] font-semibold text-muted-foreground hover:text-foreground"
                data-bot-tab-button="runtime"
                aria-selected="false"
            >
                <span class="inline-flex items-center gap-1.5">
                    <i data-lucide="gauge" class="h-3.5 w-3.5"></i>
                    Runtime
                </span>
            </button>
        </div>

        <div class="p-3">
            <div data-bot-tab-panel="performance">
                @if($m['is_manual_performance'])
                    <div class="mb-2 inline-flex items-center gap-1.5 rounded-full border border-violet-500/20 bg-violet-500/10 px-2 py-1 text-[9px] font-semibold uppercase tracking-[0.12em] text-violet-600">
                        <i data-lucide="sparkles" class="h-3 w-3"></i>
                        {{ $m['performance_label'] ?: 'Manual Performance' }}
                    </div>
                @endif

                <div class="grid grid-cols-2 gap-2">
                    <div class="rounded-lg border {{ $m['profit_loss']>0?'border-emerald-500/20 bg-emerald-500/5':($m['profit_loss']<0?'border-red-500/20 bg-red-500/5':'border-border bg-background/50') }} p-2.5">
                        <div class="flex items-center gap-1.5 text-[9px] uppercase tracking-[.12em] text-muted-foreground">
                            <i data-lucide="wallet-minimal" class="h-3.5 w-3.5 text-emerald-500"></i>
                            {{ $m['is_manual_performance']?'P/L':'Current P/L' }}
                        </div>
                        <p class="mt-1.5 text-[15px] font-semibold {{ $m['profit_loss']>0?'text-emerald-600':($m['profit_loss']<0?'text-red-600':'') }}">
                            {{ $m['profit_loss']>0?'+':'' }}{{ format_currency($m['profit_loss']) }}
                        </p>
                    </div>

                    <div class="rounded-lg border border-sky-500/20 bg-sky-500/5 p-2.5">
                        <div class="flex items-center gap-1.5 text-[9px] uppercase tracking-[.12em] text-muted-foreground">
                            <i data-lucide="trending-up" class="h-3.5 w-3.5 text-sky-500"></i>
                            {{ $m['is_manual_performance']?'Return':'Current Return' }}
                        </div>
                        <p class="mt-1.5 text-[15px] font-semibold text-sky-600">
                            {{ $m['return_percent']>0?'+':'' }}{{ number_format($m['return_percent'],2) }}%
                        </p>
                    </div>
                </div>

                <div class="mt-3">
                    <div class="flex items-center justify-between gap-3 text-[10px]">
                        <span class="text-muted-foreground">Trading activity</span>
                        <span class="font-medium">{{ $completed }} executions · {{ number_format($m['win_rate'],1) }}%</span>
                    </div>

                    <div class="mt-2 flex h-2 overflow-hidden rounded-full bg-muted">
                        @if($completed>0)
                            <div class="bg-emerald-500" style="width:{{($wins/$completed)*100}}%"></div>
                            <div class="bg-red-500" style="width:{{($losses/$completed)*100}}%"></div>
                            <div class="bg-muted-foreground/30" style="width:{{($neutral/$completed)*100}}%"></div>
                        @endif
                    </div>

                    <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-[10px] text-muted-foreground">
                        <span><b class="text-emerald-600">{{ $wins }}</b> positive</span>
                        <span><b class="text-red-600">{{ $losses }}</b> negative</span>
                        <span><b class="text-foreground">{{ $neutral }}</b> neutral</span>
                    </div>
                </div>
            </div>

            <div class="hidden" data-bot-tab-panel="runtime">
                <div class="grid grid-cols-3 gap-2">
                    <div class="rounded-lg border border-border bg-background/50 p-2.5">
                        <div class="flex items-center gap-1.5 text-[9px] uppercase tracking-[.12em] text-muted-foreground">
                            <i data-lucide="coins" class="h-3.5 w-3.5"></i>
                            Per Trade
                        </div>
                        <p class="mt-1.5 text-[13px] font-semibold">{{ format_currency($bot?->amount_per_trade??0) }}</p>
                    </div>

                    <div class="rounded-lg border border-border bg-background/50 p-2.5">
                        <div class="flex items-center gap-1.5 text-[9px] uppercase tracking-[.12em] text-muted-foreground">
                            <i data-lucide="rows-4" class="h-3.5 w-3.5"></i>
                            Daily Limit
                        </div>
                        <p class="mt-1.5 text-[13px] font-semibold">{{ $bot?->max_daily_trades??0 }}</p>
                    </div>

                    <div class="rounded-lg border border-border bg-background/50 p-2.5">
                        <div class="flex items-center gap-1.5 text-[9px] uppercase tracking-[.12em] text-muted-foreground">
                            <i data-lucide="clock-3" class="h-3.5 w-3.5"></i>
                            Next Run
                        </div>
                        <p class="mt-1.5 text-[13px] font-semibold">
                            {{ !$bot?->next_run_at ? '—' : ($bot->next_run_at->isPast() && $bot?->status === 'active' ? 'Due now' : $bot->next_run_at->format('M d · H:i')) }}
                        </p>
                    </div>
                </div>

                <div class="mt-2 flex items-center justify-between rounded-lg border border-border bg-background/35 px-3 py-2 text-[10px]">
                    <span class="text-muted-foreground">
                        {{ $product->strategy==='dca' ? 'Run interval' : 'Check interval' }}
                    </span>
                    <span class="font-medium">{{ $bot?->interval_minutes??0 }} min</span>
                </div>
            </div>
        </div>
    </section>

    <dialog id="allocation-dialog-{{ $subscription->id }}" class="w-[min(92vw,440px)] rounded-2xl border border-border bg-background p-0 text-foreground shadow-2xl backdrop:bg-black/60">
        <div class="border-b border-border px-4 py-3">
            <div class="flex items-start justify-between gap-3">
                <div><p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-muted-foreground">Bot allocation</p><h3 class="mt-1 text-sm font-semibold">{{ $product->name }}</h3></div>
                <button type="button" class="rounded-lg border border-border p-2 text-muted-foreground hover:bg-muted hover:text-foreground" onclick="document.getElementById('allocation-dialog-{{ $subscription->id }}').close()"><i data-lucide="x" class="h-4 w-4"></i></button>
            </div>
        </div>
        <div class="space-y-3 p-4">
            <div class="grid grid-cols-2 gap-2">
                <div class="rounded-xl border border-border bg-muted/20 p-3"><p class="text-[9px] uppercase tracking-[0.12em] text-muted-foreground">Allocation Cap</p><p class="mt-1.5 text-sm font-semibold">{{ $allocation>0?format_currency($allocation):'Open' }}</p></div>
                <div class="rounded-xl border border-border bg-muted/20 p-3"><p class="text-[9px] uppercase tracking-[0.12em] text-muted-foreground">Deployed</p><p class="mt-1.5 text-sm font-semibold">{{ format_currency($spent) }}</p></div>
                <div class="rounded-xl border border-border bg-muted/20 p-3"><p class="text-[9px] uppercase tracking-[0.12em] text-muted-foreground">Available Capacity</p><p class="mt-1.5 text-sm font-semibold">{{ $allocation>0?format_currency(max(0,$allocation-$spent)):'—' }}</p></div>
                <div class="rounded-xl border border-border bg-muted/20 p-3"><p class="text-[9px] uppercase tracking-[0.12em] text-muted-foreground">Used</p><p class="mt-1.5 text-sm font-semibold">{{ number_format($allocationPct,1) }}%</p></div>
            </div>
            <div><div class="flex justify-between text-[10px]"><span class="text-muted-foreground">Allocation used</span><span class="font-medium">{{ number_format($allocationPct,1) }}%</span></div><div class="mt-2 h-2 overflow-hidden rounded-full bg-muted"><div class="h-full rounded-full bg-sky-500" style="width:{{ $allocationPct }}%"></div></div></div>
        </div>
    </dialog>
</div>
</article>
@empty<div class="ui-panel p-8 text-center text-sm text-muted-foreground">No subscribed bots yet.</div>@endforelse
</div></div>

@once
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-bot-tabs]').forEach((tabs) => {
        const buttons = tabs.querySelectorAll('[data-bot-tab-button]');
        const panels = tabs.querySelectorAll('[data-bot-tab-panel]');

        buttons.forEach((button) => {
            button.addEventListener('click', () => {
                const target = button.dataset.botTabButton;

                buttons.forEach((item) => {
                    const active = item === button;
                    item.setAttribute('aria-selected', active ? 'true' : 'false');
                    item.classList.toggle('border-border', active);
                    item.classList.toggle('border-transparent', !active);
                    item.classList.toggle('bg-background', active);
                    item.classList.toggle('text-foreground', active);
                    item.classList.toggle('text-muted-foreground', !active);
                });

                panels.forEach((panel) => {
                    panel.classList.toggle('hidden', panel.dataset.botTabPanel !== target);
                });
            });
        });
    });
});
</script>
@endonce

@once
<script>
document.addEventListener('DOMContentLoaded', () => {
    const NS = 'http://www.w3.org/2000/svg';

    const make = (name, attrs = {}) => {
        const el = document.createElementNS(NS, name);
        Object.entries(attrs).forEach(([key, value]) => el.setAttribute(key, value));
        return el;
    };

    document.querySelectorAll('[data-bot-price-chart]').forEach((svg) => {
        let payload = {};
        try { payload = JSON.parse(svg.dataset.chart || '{}'); } catch (_) { return; }

        const quotes = Array.isArray(payload.quotes) ? payload.quotes.filter(q => Number.isFinite(Number(q.price))) : [];
        const executions = Array.isArray(payload.executions) ? payload.executions.filter(e => Number.isFinite(Number(e.price))) : [];
        if (!quotes.length) return;

        const W = 760, H = 220;
        const pad = { l: 42, r: 22, t: 18, b: 30 };

        const values = quotes.map(q => Number(q.price));
        if (Number.isFinite(Number(payload.average_entry))) values.push(Number(payload.average_entry));
        executions.forEach(e => values.push(Number(e.price)));

        let min = Math.min(...values);
        let max = Math.max(...values);
        if (min === max) { min *= .995; max *= 1.005; }
        const range = Math.max(max - min, 0.01);
        min -= range * .08;
        max += range * .08;

        const quoteTimes = quotes.map(q => Date.parse(q.time)).filter(Number.isFinite);
        const execTimes = executions.map(e => Date.parse(e.time)).filter(Number.isFinite);
        const allTimes = [...quoteTimes, ...execTimes];
        let tMin = allTimes.length ? Math.min(...allTimes) : 0;
        let tMax = allTimes.length ? Math.max(...allTimes) : 1;
        if (tMin === tMax) tMax = tMin + 1;

        const x = (time, fallbackIndex = 0) => {
            const parsed = Date.parse(time);
            if (Number.isFinite(parsed)) return pad.l + ((parsed - tMin) / (tMax - tMin)) * (W - pad.l - pad.r);
            const denom = Math.max(quotes.length - 1, 1);
            return pad.l + (fallbackIndex / denom) * (W - pad.l - pad.r);
        };
        const y = (price) => pad.t + ((max - Number(price)) / (max - min)) * (H - pad.t - pad.b);

        // Horizontal reference grid.
        const grid = svg.querySelector('[data-grid]');
        [0, .25, .5, .75, 1].forEach((p) => {
            const yy = pad.t + p * (H - pad.t - pad.b);
            grid.appendChild(make('line', {
                x1: pad.l, y1: yy, x2: W - pad.r, y2: yy,
                stroke: 'currentColor', 'stroke-opacity': '.10', 'stroke-width': '1'
            }));
        });

        const pts = quotes.map((q, i) => [x(q.time, i), y(q.price)]);
        if (pts.length === 1) {
            pts.unshift([pad.l, pts[0][1]]);
            pts.push([W - pad.r, pts[1][1]]);
        }

        const lineD = pts.map((p, i) => `${i ? 'L' : 'M'} ${p[0].toFixed(2)} ${p[1].toFixed(2)}`).join(' ');
        svg.querySelector('[data-price-line]').setAttribute('d', lineD);

        const areaD = `${lineD} L ${pts[pts.length-1][0].toFixed(2)} ${(H-pad.b).toFixed(2)} L ${pts[0][0].toFixed(2)} ${(H-pad.b).toFixed(2)} Z`;
        svg.querySelector('[data-price-area]').setAttribute('d', areaD);

        if (Number.isFinite(Number(payload.average_entry))) {
            const ey = y(Number(payload.average_entry));
            svg.querySelector('[data-entry-line]').setAttribute('d', `M ${pad.l} ${ey} L ${W-pad.r} ${ey}`);
        }

        const markers = svg.querySelector('[data-markers]');
        executions.forEach((e, i) => {
            const cx = x(e.time, i);
            const cy = y(e.price);
            const isSell = String(e.action).toLowerCase() === 'sell';
            const circle = make('circle', {
                cx, cy, r: 5.5,
                fill: isSell ? '#ef4444' : '#10b981',
                stroke: 'currentColor', 'stroke-width': '2',
                class: 'text-background'
            });
            const title = make('title');
            title.textContent = `${isSell ? 'Sell' : 'Buy'} ${e.label || ''} · $${Number(e.price).toFixed(2)} · $${Number(e.amount || 0).toFixed(2)}`;
            circle.appendChild(title);
            markers.appendChild(circle);
        });

        // Current point pulse-style ring.
        const last = pts[pts.length - 1];
        markers.appendChild(make('circle', {
            cx: last[0], cy: last[1], r: 8,
            fill: 'none', stroke: '#0ea5e9', 'stroke-opacity': '.30', 'stroke-width': '5'
        }));
        markers.appendChild(make('circle', {
            cx: last[0], cy: last[1], r: 4.5,
            fill: '#0ea5e9'
        }));

        const labels = svg.querySelector('[data-labels]');
        const labelStyle = {
            fill: 'currentColor',
            'font-size': '11',
            'font-family': 'ui-sans-serif, system-ui, sans-serif'
        };

        // Price labels: high / low / current.
        [
            { price: max, text: `$${max.toFixed(2)}`, yy: pad.t + 3 },
            { price: min, text: `$${min.toFixed(2)}`, yy: H - pad.b },
        ].forEach(item => {
            const t = make('text', { x: 0, y: item.yy, ...labelStyle, opacity: '.55' });
            t.textContent = item.text;
            labels.appendChild(t);
        });

        const current = Number(payload.current);
        if (Number.isFinite(current)) {
            const t = make('text', { x: W - pad.r, y: Math.max(14, y(current) - 8), ...labelStyle, 'text-anchor': 'end' });
            t.textContent = `$${current.toFixed(2)}`;
            labels.appendChild(t);
        }

        if (quotes.length) {
            const first = quotes[0], lastQ = quotes[quotes.length - 1];
            const left = make('text', { x: pad.l, y: H - 6, ...labelStyle, opacity: '.55' });
            left.textContent = first.label || '';
            labels.appendChild(left);
            const right = make('text', { x: W - pad.r, y: H - 6, ...labelStyle, opacity: '.55', 'text-anchor': 'end' });
            right.textContent = lastQ.label || '';
            labels.appendChild(right);
        }
    });
});
</script>
@endonce

</x-user-layout>