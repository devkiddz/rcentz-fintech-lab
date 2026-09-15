<x-user-layout>
<x-slot name="header">My Copied Strategies</x-slot>
<div class="ui-page max-w-[1440px]">
<section class="ui-page-header">
    <div>
        <p class="ui-kicker text-[10px]">Copy Trading</p>
        <h1 class="ui-heading !text-xl">My Copied Strategies</h1>
        <p class="ui-lead !text-[13px]">Allocation exposure, mirrored activity and live market context.</p>
    </div>
    <div class="flex gap-2">
        <a class="ui-btn ui-btn-secondary" href="{{ route('copy-trading.executions') }}"><i data-lucide="history" class="h-4 w-4"></i> History</a>
        <a class="ui-btn ui-btn-secondary" href="{{ route('copy-trading.marketplace') }}"><i data-lucide="store" class="h-4 w-4"></i> Marketplace</a>
    </div>
</section>

<div class="grid gap-4 xl:grid-cols-2">
@forelse($relationships as $relationship)
    @php
        $m = $relationship->performance_metrics;
        $allocation = (float) $relationship->allocation_limit;
        $used = (float) $relationship->used_amount;
        $usedPct = $allocation > 0 ? min(100, ($used / $allocation) * 100) : 0;
        $positive = (int) ($m['positive_count'] ?? $m['winning_trades'] ?? 0);
        $negative = (int) ($m['negative_count'] ?? $m['losing_trades'] ?? 0);
        $series = $relationship->market_series ?? [];
    @endphp

    <article class="ui-panel overflow-hidden border border-border/70">
        <div class="p-4">
            @if($m['is_manual_performance'])
                <div class="mb-2 inline-flex items-center gap-1.5 rounded-full border border-violet-500/20 bg-violet-500/10 px-2 py-1 text-[9px] font-semibold uppercase tracking-[0.12em] text-violet-600">
                    <i data-lucide="sparkles" class="h-3 w-3"></i>{{ $m['performance_label'] ?: 'Manual Performance' }}
                </div>
            @endif

            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="flex flex-wrap gap-2">
                        <span class="rounded-full border border-amber-500/20 bg-amber-500/10 px-2 py-1 text-[10px] font-semibold text-amber-600">{{ ucfirst($relationship->strategy?->risk_level ?? 'medium') }} risk</span>
                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-[10px] font-semibold {{ $relationship->status === 'active' ? 'border border-emerald-500/20 bg-emerald-500/10 text-emerald-600' : 'border border-border bg-muted text-muted-foreground' }}">
                            @if($relationship->status === 'active')<span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>@endif
                            {{ ucfirst($relationship->status) }}
                        </span>
                    </div>
                    <h2 class="mt-2.5 text-base font-semibold">{{ $relationship->strategy?->name }}</h2>
                    <p class="mt-1 text-xs text-muted-foreground">Provider · {{ $relationship->provider?->name }}</p>
                </div>
                <a href="{{ route('copy-trading.relationships.edit',$relationship) }}" class="ui-btn ui-btn-secondary !h-8 !px-3"><i data-lucide="sliders-horizontal" class="h-3.5 w-3.5"></i> Manage</a>
            </div>
        </div>

        <div class="border-y border-border/70 bg-muted/10">
            <div class="flex items-center justify-between px-4 pt-3">
                <div>
                    <p class="text-[9px] uppercase tracking-[.12em] text-muted-foreground">Mirrored market</p>
                    <p class="mt-1 text-xs font-semibold">{{ $relationship->market_symbol ?: 'No completed execution yet' }}</p>
                </div>
                <span class="text-[9px] uppercase tracking-[.11em] text-muted-foreground">{{ str_replace('_',' ',$marketStatus ?? 'closed') }}</span>
            </div>
            <div class="relative h-[150px]">
                @if(count($series) >= 2)
                    @include('trading.partials.mini-analysis-card',['symbol'=>$relationship->market_symbol,'height'=>'h-[140px]'])
                @else
                    <div class="absolute inset-0 flex items-center justify-center p-4 text-center">
                        <div>
                            <p class="text-[10px] font-medium">Building copied market history</p>
                            <p class="mt-1 text-[9px] text-muted-foreground">The chart becomes available after mirrored executions produce a market symbol and price history.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="p-4">
            <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-4">
                @foreach([
                    ['Current P/L', ($m['profit_loss'] > 0 ? '+' : '').format_currency($m['profit_loss'])],
                    ['Return', ($m['return_percent'] > 0 ? '+' : '').number_format($m['return_percent'],2).'%'],
                    ['Positive', $positive],
                    ['Negative', $negative],
                ] as [$label,$value])
                    <div class="rounded-lg border border-border bg-muted/10 p-2.5">
                        <p class="text-[8px] uppercase tracking-[.11em] text-muted-foreground">{{ $label }}</p>
                        <p class="mt-1 text-[12px] font-semibold">{{ $value }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-3 grid grid-cols-2 gap-2.5 sm:grid-cols-4">
                @foreach([
                    ['Allocation', format_currency($allocation)],
                    ['Used', format_currency($used)],
                    ['Copy %', number_format((float)$relationship->copy_ratio_percent,0).'%'],
                    ['Max / Trade', format_currency($relationship->max_trade_amount)],
                ] as [$label,$value])
                    <div class="rounded-lg border border-border bg-background/60 p-2.5">
                        <p class="text-[8px] uppercase tracking-[.11em] text-muted-foreground">{{ $label }}</p>
                        <p class="mt-1 text-[11px] font-semibold">{{ $value }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-3">
                <div class="flex justify-between text-[10px]">
                    <span class="text-muted-foreground">Allocation used</span>
                    <span class="font-medium">{{ number_format($usedPct,1) }}%</span>
                </div>
                <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-muted">
                    <div class="h-full bg-sky-500" style="width:{{ $usedPct }}%"></div>
                </div>
            </div>
        </div>
    </article>
@empty
    <div class="ui-panel p-8 text-center text-sm text-muted-foreground xl:col-span-2">No copied strategies yet.</div>
@endforelse
</div>
</div>
</x-user-layout>
