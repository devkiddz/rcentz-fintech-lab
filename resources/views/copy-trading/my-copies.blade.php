<x-user-layout>
<x-slot name="header">My Copied Strategies</x-slot>

<div class="ui-page max-w-[1440px]">
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker">Copy Trading</p>
            <h1 class="ui-heading">My Copied Strategies</h1>
            <p class="ui-lead">Your copied providers, allocation exposure and mirrored trading activity.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a class="ui-btn ui-btn-secondary" href="{{ route('copy-trading.executions') }}">Execution History</a>
            <a class="ui-btn ui-btn-secondary" href="{{ route('copy-trading.marketplace') }}">Strategy Marketplace</a>
        </div>
    </section>

    <div class="grid gap-5 xl:grid-cols-2">
        @forelse($relationships as $relationship)
            @php
                $m = $relationship->performance_metrics;
                $allocation = (float)$relationship->allocation_limit;
                $used = (float)$relationship->used_amount;
                $usedPct = $allocation > 0 ? min(100, ($used / $allocation) * 100) : 0;
                $completed = max(0, (int)$m['completed_count']);
                $wins = max(0, (int)$m['winning_trades']);
                $losses = max(0, (int)$m['losing_trades']);
                $neutral = max(0, $completed - $wins - $losses);
            @endphp

            <article class="ui-panel overflow-hidden p-5 sm:p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full border border-border bg-muted/40 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-muted-foreground">{{ ucfirst($relationship->strategy?->risk_level ?? 'medium') }} risk</span>
                            <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $relationship->status === 'active' ? 'bg-green-500/10 text-green-600' : 'bg-muted text-muted-foreground' }}">{{ ucfirst($relationship->status) }}</span>
                        </div>
                        <h2 class="mt-3 text-xl font-semibold">{{ $relationship->strategy?->name }}</h2>
                        <p class="mt-1 text-sm text-muted-foreground">Provider · {{ $relationship->provider->name }}</p>
                    </div>

                    <a href="{{ route('copy-trading.relationships.edit',$relationship) }}" class="ui-btn ui-btn-primary">Edit Settings</a>
                </div>

                <div class="mt-6 rounded-2xl bg-muted/25 p-5">
                    <div class="flex items-end justify-between gap-5">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-muted-foreground">{{ $m['is_manual_performance'] ? 'Preview P/L' : 'Current P/L' }}</p>
                            <p class="mt-2 text-3xl font-semibold {{ $m['profit_loss']<0?'text-red-600':($m['profit_loss']>0?'text-green-600':'') }}">{{ $m['profit_loss']>0?'+':'' }}{{ format_currency($m['profit_loss']) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-muted-foreground">{{ $m['is_manual_performance'] ? 'Preview Return' : 'Current Return' }}</p>
                            <p class="mt-2 text-xl font-semibold">{{ $m['return_percent']>0?'+':'' }}{{ number_format($m['return_percent'],2) }}%</p>
                        </div>
                    </div>

                    <div class="mt-5 flex h-2.5 overflow-hidden rounded-full bg-muted">
                        @if($completed > 0)
                            <div class="bg-green-500" style="width: {{ ($wins / $completed) * 100 }}%"></div>
                            <div class="bg-red-500" style="width: {{ ($losses / $completed) * 100 }}%"></div>
                            <div class="bg-muted-foreground/30" style="width: {{ ($neutral / $completed) * 100 }}%"></div>
                        @endif
                    </div>
                    <div class="mt-3 flex flex-wrap justify-between gap-2 text-xs text-muted-foreground">
                        <span>{{ $completed }} mirrored executions</span>
                        <span>{{ number_format($m['win_rate'],1) }}% positive execution rate</span>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-2 gap-x-6 gap-y-5 sm:grid-cols-3">
                    <div><p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-muted-foreground">Allocation</p><p class="mt-1.5 text-lg font-semibold">{{ format_currency($allocation) }}</p></div>
                    <div><p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-muted-foreground">Used</p><p class="mt-1.5 text-lg font-semibold">{{ format_currency($used) }}</p></div>
                    <div><p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-muted-foreground">Copy Percentage</p><p class="mt-1.5 text-lg font-semibold">{{ number_format($relationship->copy_ratio_percent,0) }}%</p></div>
                    <div><p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-muted-foreground">Max Per Trade</p><p class="mt-1.5 text-lg font-semibold">{{ format_currency($relationship->max_trade_amount) }}</p></div>
                    <div><p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-muted-foreground">Positive</p><p class="mt-1.5 text-lg font-semibold">{{ $wins }}</p></div>
                    <div><p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-muted-foreground">Negative</p><p class="mt-1.5 text-lg font-semibold">{{ $losses }}</p></div>

                    <div class="col-span-2 sm:col-span-3">
                        <div class="flex items-center justify-between text-xs"><span class="text-muted-foreground">Allocation used</span><span class="font-medium">{{ number_format($usedPct,1) }}%</span></div>
                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-muted"><div class="h-full rounded-full bg-foreground" style="width: {{ $usedPct }}%"></div></div>
                    </div>
                </div>
            </article>
        @empty
            <div class="ui-panel p-10 text-center xl:col-span-2">
                <p class="font-medium">No copied strategies yet.</p>
                <p class="mt-1 text-sm text-muted-foreground">Choose an approved provider strategy to begin mirroring trades.</p>
                <a href="{{ route('copy-trading.marketplace') }}" class="ui-btn ui-btn-primary mt-5">Explore Strategies</a>
            </div>
        @endforelse
    </div>
</div>
</x-user-layout>