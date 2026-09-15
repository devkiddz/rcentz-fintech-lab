<x-user-layout>
<x-slot name="header">Copy Trading</x-slot>
<div class="ui-page max-w-[1440px]">
<section class="ui-page-header">
    <div>
        <p class="ui-kicker text-[10px]">Copy Trading</p>
        <h1 class="ui-heading !text-xl">Strategy Marketplace</h1>
        <p class="ui-lead !text-[13px]">Approved provider strategies with transparent allocation, performance and market context.</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('copy-trading.my-copies') }}" class="ui-btn ui-btn-secondary"><i data-lucide="copy" class="h-4 w-4"></i> My Copies</a>
        <a href="{{ route('copy-trading.apply') }}" class="ui-btn ui-btn-secondary"><i data-lucide="badge-check" class="h-4 w-4"></i> Become Provider</a>
    </div>
</section>

<div class="grid gap-4 lg:grid-cols-2">
@forelse($strategies as $strategy)
    @php
        $m = $strategy->performance_metrics;
        $series = $strategy->market_series ?? [];
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
                    <p class="text-[10px] uppercase tracking-[.12em] text-muted-foreground">Approved provider</p>
                    <h2 class="mt-1 text-base font-semibold">{{ $strategy->name }}</h2>
                    <p class="mt-1 text-xs text-muted-foreground">{{ $strategy->profile->user->name }}</p>
                </div>
                <div class="flex gap-2">
                    <span class="rounded-full border border-amber-500/20 bg-amber-500/10 px-2 py-1 text-[10px] font-semibold text-amber-600">{{ ucfirst($strategy->risk_level) }} risk</span>
                    <span class="rounded-full border border-border bg-muted px-2 py-1 text-[10px] font-semibold text-muted-foreground">{{ $strategy->copier_count }} copiers</span>
                </div>
            </div>

            <p class="mt-2 line-clamp-2 text-xs leading-5 text-muted-foreground">{{ $strategy->description }}</p>
        </div>

        <div class="border-y border-border/70 bg-muted/10">
            <div class="flex items-center justify-between px-4 pt-3">
                <div>
                    <p class="text-[9px] uppercase tracking-[.13em] text-muted-foreground">Market context</p>
                    <p class="mt-1 text-xs font-semibold">{{ $strategy->market_symbol ?: 'Waiting for first mirrored trade' }}</p>
                </div>
                <span class="rounded-full border border-border bg-background px-2 py-1 text-[9px] font-semibold uppercase tracking-[.11em] text-muted-foreground">
                    {{ str_replace('_',' ',$marketStatus ?? 'closed') }}
                </span>
            </div>
            <div class="relative h-[130px]">
                @if(count($series) >= 2)
                    @include('trading.partials.mini-analysis-card',['symbol'=>$strategy->market_symbol,'height'=>'h-[120px]'])
                @else
                    <div class="absolute inset-0 flex items-center justify-center px-4 text-center">
                        <div>
                            <p class="text-[10px] font-medium">Market history begins with copied executions</p>
                            <p class="mt-1 text-[9px] text-muted-foreground">A live chart appears when this strategy has mirrored a stock and market data is available.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="p-4">
            <div class="grid grid-cols-4 gap-2">
                @foreach([
                    ['P/L', ($m['profit_loss'] > 0 ? '+' : '').format_currency($m['profit_loss'])],
                    ['Return', ($m['return_percent'] > 0 ? '+' : '').number_format($m['return_percent'],2).'%'],
                    ['Executions', $m['completed_count']],
                    ['Positive Rate', number_format($m['positive_execution_rate'] ?? $m['win_rate'],1).'%'],
                ] as [$label,$value])
                    <div class="rounded-lg border border-border bg-muted/10 p-2.5">
                        <p class="text-[8px] uppercase tracking-[.11em] text-muted-foreground">{{ $label }}</p>
                        <p class="mt-1 text-[11px] font-semibold">{{ $value }}</p>
                    </div>
                @endforeach
            </div>

            <form action="{{ route('copy-trading.follow',$strategy) }}" method="POST" class="mt-4 space-y-2.5 border-t border-border pt-4">
                @csrf
                <div class="grid gap-2.5 sm:grid-cols-3">
                    <div>
                        <label class="ui-label !text-[10px]">Allocation</label>
                        <input name="allocation_limit" class="ui-input" type="number" min="{{ $strategy->minimum_allocation }}" value="{{ $strategy->recommended_allocation ?: $strategy->minimum_allocation }}" required>
                    </div>
                    <div>
                        <label class="ui-label !text-[10px]">Max / Trade</label>
                        <input name="max_trade_amount" class="ui-input" type="number" min="10" value="{{ $strategy->minimum_allocation }}" required>
                    </div>
                    <div>
                        <label class="ui-label !text-[10px]">Copy %</label>
                        <input name="copy_ratio_percent" class="ui-input" type="number" min="1" max="200" value="100" required>
                    </div>
                </div>
                <button class="ui-btn ui-btn-primary w-full"><i data-lucide="copy-check" class="h-4 w-4"></i> Copy Strategy</button>
            </form>
        </div>
    </article>
@empty
    <div class="ui-panel p-8 text-center text-sm text-muted-foreground lg:col-span-2">No approved strategies available.</div>
@endforelse
</div>
</div>
</x-user-layout>
