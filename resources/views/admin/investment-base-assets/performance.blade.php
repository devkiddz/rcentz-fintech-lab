<x-admin-layout>
@php
    $precision = max(
        0,
        min(8, (int) ($analysis['price_precision'] ?? 2))
    );
    $currency = strtoupper(
        (string) ($analysis['quote_asset'] ?? $currency ?? 'USD')
    );
    $current = (float) ($analysis['current_price'] ?? 0);
    $previous = (float) ($analysis['previous_close'] ?? $current);
    $change = (float) ($analysis['change_amount'] ?? 0);
    $changePercent = (float) ($analysis['change_percent'] ?? 0);
    $periodChange = (float) (
        $analysis['period_change_percent'] ?? 0
    );
    $direction = (string) ($analysis['direction'] ?? 'FLAT');
@endphp

<div class="ui-page max-w-[1500px]"
     @if($kind === 'private' && $runtimeRoute)
         data-private-base-performance-runtime="{{ $runtimeRoute }}"
         data-base-currency="{{ $currency }}"
         data-base-precision="{{ $precision }}"
     @endif>
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker">{{ ucfirst($kind) }} Base Asset · Performance</p>
            <div class="mt-1 flex flex-wrap items-center gap-2">
                <h1 class="ui-heading">{{ $title }}</h1>
                <span class="rounded-full border border-border px-2 py-1 text-[9px] font-semibold">{{ $symbol }}</span>
                <span class="rounded-full border border-border px-2 py-1 text-[9px] font-semibold {{ $status === 'active' ? 'text-emerald-600' : 'text-amber-600' }}">{{ strtoupper($status) }}</span>
            </div>
            <p class="ui-lead">
                {{ ucwords(str_replace('_',' ',$category)) }}
                @if($location) · {{ $location }} @endif
                · {{ $usageCount }} linked reserve{{ $usageCount === 1 ? '' : 's' }}
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ $backRoute }}" class="ui-btn ui-btn-secondary">Back to {{ ucfirst($kind) }} Base Assets</a>
            <a href="{{ $manageRoute }}" class="ui-btn ui-btn-primary">{{ $manageLabel }}</a>
        </div>
    </section>

    <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
        <div class="ui-panel p-4"><p class="ui-kicker">Current</p><p class="mt-2 text-lg font-semibold tabular-nums" data-base-current>{{ number_format($current,$precision) }} {{ $currency }}</p></div>
        <div class="ui-panel p-4"><p class="ui-kicker">Previous</p><p class="mt-2 text-lg font-semibold tabular-nums" data-base-previous>{{ number_format($previous,$precision) }} {{ $currency }}</p></div>
        <div class="ui-panel p-4"><p class="ui-kicker">Latest Move</p><p class="mt-2 text-lg font-semibold tabular-nums {{ $change >= 0 ? 'text-emerald-600' : 'text-red-600' }}" data-base-move>{{ $change >= 0 ? '+' : '' }}{{ number_format($change,$precision) }}</p><p class="mt-1 text-[10px]" data-base-move-percent>{{ $changePercent >= 0 ? '+' : '' }}{{ number_format($changePercent,2) }}%</p></div>
        <div class="ui-panel p-4"><p class="ui-kicker">Direction</p><p class="mt-2 text-lg font-semibold {{ $direction === 'UP' ? 'text-emerald-600' : ($direction === 'DOWN' ? 'text-red-600' : '') }}" data-base-direction>{{ $direction === 'UP' ? '↑ UP' : ($direction === 'DOWN' ? '↓ DOWN' : '→ FLAT') }}</p></div>
        <div class="ui-panel p-4"><p class="ui-kicker">Stored Performance</p><p class="mt-2 text-lg font-semibold {{ $periodChange >= 0 ? 'text-emerald-600' : 'text-red-600' }}" data-base-period>{{ $periodChange >= 0 ? '+' : '' }}{{ number_format($periodChange,2) }}%</p></div>
        <div class="ui-panel p-4"><p class="ui-kicker">Range</p><p class="mt-2 text-sm font-semibold" data-base-high>{{ number_format((float)$analysis['period_high'],$precision) }}</p><p class="mt-1 text-[10px] text-muted-foreground">Low <span data-base-low>{{ number_format((float)$analysis['period_low'],$precision) }}</span></p></div>
        <div class="ui-panel p-4"><p class="ui-kicker">History</p><p class="mt-2 text-lg font-semibold" data-base-history>{{ number_format((int)$analysis['history_points']) }}</p><p class="mt-1 text-[10px] text-muted-foreground">price points</p></div>
    </section>

    <section class="mt-4">
        @include(
            'admin.investment-base-assets.partials.performance-chart',
            compact('analysis','symbol','kind')
        )
    </section>

    <section class="ui-panel mt-4 overflow-hidden">
        <div class="border-b border-border p-5">
            <p class="ui-kicker">Performance Ledger</p>
            <h2 class="mt-1 text-lg font-semibold">Recent Price Observations</h2>
            <p class="mt-2 text-xs text-muted-foreground">
                {{ $kind === 'public'
                    ? 'Read from the public market history that owns this Base Asset price.'
                    : 'Written by RCENTZ private valuation events. This is the audit trail behind the chart.' }}
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left">
                <thead class="border-b border-border bg-muted/20 text-[9px] uppercase tracking-[.1em] text-muted-foreground">
                    <tr><th class="px-5 py-3">Time</th><th>Open</th><th>High</th><th>Low</th><th class="pr-5">Close</th></tr>
                </thead>
                <tbody class="divide-y divide-border" data-base-ledger>
                    @forelse(collect($analysis['series'] ?? [])->reverse()->take(25) as $row)
                        <tr class="text-xs">
                            <td class="px-5 py-3 text-muted-foreground">{{ \Carbon\Carbon::parse($row['time'])->format('M j, Y · H:i') }}</td>
                            <td>{{ number_format((float)$row['open'],$precision) }}</td>
                            <td>{{ number_format((float)$row['high'],$precision) }}</td>
                            <td>{{ number_format((float)$row['low'],$precision) }}</td>
                            <td class="pr-5 font-semibold">{{ number_format((float)$row['close'],$precision) }} {{ $currency }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-xs text-muted-foreground">No stored performance observations yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

@if($kind === 'private' && $runtimeRoute)
<script>
(() => {
    const root = document.querySelector('[data-private-base-performance-runtime]');
    if (!root) return;

    const url = root.dataset.privateBasePerformanceRuntime;
    const currency = root.dataset.baseCurrency || 'USD';
    const precision = Number(root.dataset.basePrecision || 2);
    let busy = false;

    const number = (value, digits = precision) => Number(value || 0).toLocaleString(undefined, {
        minimumFractionDigits: digits,
        maximumFractionDigits: digits,
    });

    const tone = (el, value) => {
        if (!el) return;
        el.classList.remove('text-emerald-600', 'text-red-600');
        const n = Number(value || 0);
        if (n > 0) el.classList.add('text-emerald-600');
        if (n < 0) el.classList.add('text-red-600');
    };

    const updateLedger = (analysis) => {
        const body = root.querySelector('[data-base-ledger]');
        if (!body) return;
        const rows = Array.isArray(analysis.series) ? [...analysis.series].reverse().slice(0,25) : [];
        body.innerHTML = rows.map((row) => {
            const d = new Date(row.time);
            const when = Number.isNaN(d.getTime()) ? row.time : d.toLocaleString();
            return `<tr class="text-xs"><td class="px-5 py-3 text-muted-foreground">${when}</td><td>${number(row.open)}</td><td>${number(row.high)}</td><td>${number(row.low)}</td><td class="pr-5 font-semibold">${number(row.close)} ${currency}</td></tr>`;
        }).join('');
    };

    const apply = (analysis) => {
        const current = Number(analysis.current_price || 0);
        const previous = Number(analysis.previous_close || current);
        const move = Number(analysis.change_amount || 0);
        const movePercent = Number(analysis.change_percent || 0);
        const period = Number(analysis.period_change_percent || 0);
        const direction = String(analysis.direction || 'FLAT');

        const set = (selector, value) => {
            const el = root.querySelector(selector);
            if (el) el.textContent = value;
            return el;
        };

        set('[data-base-current]', `${number(current)} ${currency}`);
        set('[data-base-previous]', `${number(previous)} ${currency}`);
        const moveEl = set('[data-base-move]', `${move >= 0 ? '+' : ''}${number(move)}`);
        const movePctEl = set('[data-base-move-percent]', `${movePercent >= 0 ? '+' : ''}${number(movePercent,2)}%`);
        const periodEl = set('[data-base-period]', `${period >= 0 ? '+' : ''}${number(period,2)}%`);
        set('[data-base-high]', number(analysis.period_high || current));
        set('[data-base-low]', number(analysis.period_low || current));
        set('[data-base-history]', String(analysis.history_points || 0));

        const directionEl = set('[data-base-direction]', direction === 'UP' ? '↑ UP' : (direction === 'DOWN' ? '↓ DOWN' : '→ FLAT'));
        tone(moveEl, move);
        tone(movePctEl, movePercent);
        tone(periodEl, period);
        tone(directionEl, direction === 'UP' ? 1 : (direction === 'DOWN' ? -1 : 0));

        const chart = root.querySelector('[data-rcentz-analysis]');
        if (chart && window.RcentzCharts?.refreshAnalysis) {
            window.RcentzCharts.refreshAnalysis(chart, analysis);
        }
        if (analysis.has_chart) {
            root.querySelector('[data-base-chart-empty]')?.classList.add('hidden');
        }
        updateLedger(analysis);
    };

    const poll = async () => {
        if (busy || document.hidden) return;
        busy = true;
        try {
            const response = await fetch(url, {
                method: 'GET',
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
                cache: 'no-store',
            });
            if (!response.ok) return;
            const payload = await response.json();
            if (payload.analysis) apply(payload.analysis);
        } catch (_) {
        } finally {
            busy = false;
        }
    };

    window.setInterval(poll, 3000);
})();
</script>
@endif
</x-admin-layout>
