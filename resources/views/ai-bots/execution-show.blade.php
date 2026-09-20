<x-user-layout>
<x-slot name="header">Bot Execution</x-slot>
<div class="ui-page max-w-5xl">
<section class="ui-page-header">
    <div>
        <p class="ui-kicker text-[10px]">Execution History</p>
        <h1 class="ui-heading !text-xl">{{ $execution->subscription?->product?->name ?? $execution->bot?->name }}</h1>
        <p class="ui-lead !text-[13px]">Execution context with live mark-to-market movement.</p>
    </div>
    <a href="{{ route('ai-bots.performance') }}" class="ui-btn ui-btn-secondary">Back to Performance</a>
</section>

<div class="grid gap-4 lg:grid-cols-[1.35fr_.65fr]">
    <section class="ui-panel overflow-hidden">
        <div class="flex items-start justify-between gap-4 border-b border-border/70 p-4">
            <div>
                <p class="text-[10px] uppercase tracking-[.13em] text-muted-foreground">Execution #{{ $execution->id }}</p>
                <h2 class="mt-1 text-base font-semibold">{{ strtoupper($execution->action) }} {{ $execution->marketInstrument?->display_symbol ?? $execution->bot?->marketInstrument?->display_symbol ?? $execution->bot?->stock?->symbol ?? '—' }}</h2>
            </div>
            <span class="rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2 py-1 text-[10px] font-semibold text-emerald-600">{{ ucfirst($execution->status) }}</span>
        </div>

        <div class="border-b border-border/70 p-3">
            <div class="flex items-baseline gap-3">
                <span class="text-sm font-semibold">{{ $currentPriceDisplay }}</span>
                <span class="text-xs {{ $profitLoss>=0?'text-emerald-600':'text-red-600' }}">{{ $profitLoss>=0?'+':'' }}{{ format_currency($profitLoss) }}</span>
                <span class="text-xs text-muted-foreground">{{ $returnPercent>=0?'+':'' }}{{ number_format($returnPercent,2) }}%</span>
            </div>
        </div>

        <div class="relative h-[240px] p-3">
            @if(count($quoteHistory ?? []) >= 2)
                <div class="h-full w-full"
                     data-rcentz-candles
                     data-quotes='@json($quoteHistory)'
                     data-entry="{{ (float)$execution->price }}"></div>
            @else
                <div class="absolute inset-0 flex items-center justify-center p-6">
                    <div class="max-w-sm rounded-2xl border border-border bg-background/80 px-5 py-4 text-center shadow-sm backdrop-blur">
                        <div class="mx-auto flex h-8 w-8 items-center justify-center rounded-full border border-sky-500/20 bg-sky-500/10 text-sky-500">
                            <i data-lucide="chart-no-axes-combined" class="h-4 w-4"></i>
                        </div>
                        <p class="mt-3 text-xs font-medium">Building live price history</p>
                        <p class="mt-1 text-[10px] leading-4 text-muted-foreground">
                            This execution chart will appear as regular-session quotes are collected.
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </section>

    <aside class="ui-panel p-4">
        <div class="grid grid-cols-2 gap-2">
            @foreach([
                ['Amount',format_currency($execution->amount)],
                ['Quantity',number_format((float)$execution->quantity,6)],
                ['Entry Price',$entryPriceDisplay],
                ['Current Price',$currentPriceDisplay],
                ['Current P/L',($profitLoss>0?'+':'').format_currency($profitLoss)],
                ['Current Return',($returnPercent>0?'+':'').number_format($returnPercent,2).'%'],
            ] as [$label,$value])
                <div class="rounded-lg border border-border bg-muted/15 p-3">
                    <p class="text-[9px] uppercase tracking-[.12em] text-muted-foreground">{{ $label }}</p>
                    <p class="mt-1 text-[13px] font-semibold">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        @if($execution->reason)
            <div class="mt-3 rounded-lg border border-amber-500/20 bg-amber-500/5 p-3 text-[11px] text-muted-foreground">{{ $execution->reason }}</div>
        @endif

        <div class="mt-3 border-t border-border pt-3 text-[10px] text-muted-foreground">
            Executed {{ optional($execution->executed_at)->format('M d, Y · h:i A') ?? '—' }}
        </div>
    </aside>
</div>
</div>


@include('ai-bots.partials.lightweight-charts')
</x-user-layout>