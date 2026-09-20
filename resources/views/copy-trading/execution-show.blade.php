<x-user-layout>
<x-slot name="header">Copy Execution</x-slot>
<div class="ui-page max-w-[1280px]">
<section class="ui-page-header">
    <div>
        <p class="ui-kicker text-[10px]">Execution History</p>
        <h1 class="ui-heading !text-xl">{{ $execution->relationship?->strategy?->name ?? 'Copied Strategy' }}</h1>
        <p class="ui-lead !text-[13px]">Mirrored execution context from the unified multi-asset broker ledger.</p>
    </div>
    <a href="{{ route('copy-trading.executions') }}" class="ui-btn ui-btn-secondary">Back to History</a>
</section>

<div class="grid gap-4 xl:grid-cols-[1.45fr_.55fr]">
    <section class="ui-panel overflow-hidden">
        <div class="flex items-center justify-between gap-3 border-b border-border/70 px-4 py-3">
            <div>
                <p class="text-[10px] uppercase tracking-[.12em] text-muted-foreground">Copied execution #{{ $execution->id }}</p>
                <div class="mt-1 flex flex-wrap items-center gap-2">
                    <h2 class="text-base font-semibold">{{ strtoupper($side) }} {{ $symbol }}</h2>
                    <span class="rounded-full border border-sky-500/20 bg-sky-500/10 px-2 py-1 text-[9px] font-semibold uppercase tracking-[.1em] text-sky-600">{{ $assetClass }}</span>
                    <span class="rounded-full border border-border bg-muted px-2 py-1 text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">{{ $marketplace }}</span>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="rounded-full border border-border bg-muted px-2 py-1 text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">{{ str_replace('_',' ',$marketStatus) }}</span>
                <span class="rounded-full px-2 py-1 text-[10px] font-semibold {{ $execution->status === 'completed' ? 'border border-emerald-500/20 bg-emerald-500/10 text-emerald-600' : ($execution->status === 'failed' ? 'border border-red-500/20 bg-red-500/10 text-red-600' : 'border border-amber-500/20 bg-amber-500/10 text-amber-600') }}">{{ ucfirst($execution->status) }}</span>
            </div>
        </div>

        @if($instrument)
            <div class="grid gap-px bg-border/60 sm:grid-cols-4">
                <div class="bg-card p-4"><p class="text-[9px] uppercase tracking-[.12em] text-muted-foreground">Instrument</p><p class="mt-1 text-sm font-semibold">{{ $symbol }}</p></div>
                <div class="bg-card p-4"><p class="text-[9px] uppercase tracking-[.12em] text-muted-foreground">Asset class</p><p class="mt-1 text-sm font-semibold">{{ $assetClass }}</p></div>
                <div class="bg-card p-4"><p class="text-[9px] uppercase tracking-[.12em] text-muted-foreground">Entry / Fill</p><p class="mt-1 text-sm font-semibold">{{ $entryPriceDisplay }}</p></div>
                <div class="bg-card p-4"><p class="text-[9px] uppercase tracking-[.12em] text-muted-foreground">Current mark</p><p class="mt-1 text-sm font-semibold">{{ $currentPriceDisplay }}</p></div>
            </div>
        @endif

        <div class="p-4">
            <div class="rounded-xl border border-border bg-muted/10 p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-[9px] uppercase tracking-[.12em] text-muted-foreground">Execution authority</p>
                        <p class="mt-1 text-xs font-semibold">Copy Relationship → BrokerOrder → Market Execution</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[9px] uppercase tracking-[.12em] text-muted-foreground">Broker order</p>
                        <p class="mt-1 text-xs font-semibold">#{{ $execution->follower_broker_order_id ?? 'Legacy' }}</p>
                    </div>
                </div>
                <p class="mt-3 text-[10px] leading-5 text-muted-foreground">Stock compatibility references remain available for historical rows, while new Stock, Forex and Crypto copies use the unified execution ledger.</p>
            </div>
        </div>
    </section>

    <aside class="ui-panel p-4">
        <div class="grid grid-cols-2 gap-2">
            @foreach([
                ['Amount', format_currency($execution->executed_amount)],
                ['Quantity', number_format((float)$quantity,8)],
                ['Fill', $entryPriceDisplay],
                ['Current', $currentPriceDisplay],
                ['Current P/L', ($profitLoss > 0 ? '+' : '').format_currency($profitLoss)],
                ['Current Return', ($returnPercent > 0 ? '+' : '').number_format($returnPercent,2).'%'],
            ] as [$label,$value])
                <div class="rounded-lg border border-border bg-muted/10 p-2.5">
                    <p class="text-[8px] uppercase tracking-[.11em] text-muted-foreground">{{ $label }}</p>
                    <p class="mt-1 text-[12px] font-semibold">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-4 border-t border-border pt-3 text-[10px] text-muted-foreground">
            <p>Provider: <span class="font-medium text-foreground">{{ $execution->relationship?->provider?->name }}</span></p>
            <p class="mt-1">Strategy: <span class="font-medium text-foreground">{{ $execution->relationship?->strategy?->name }}</span></p>
            <p class="mt-1">Executed: <span class="font-medium text-foreground">{{ optional($execution->executed_at)->format('M d, Y · H:i') }}</span></p>
            @if($position)
                <p class="mt-1">Position: <span class="font-medium text-foreground">#{{ $position->id }} · {{ strtoupper($position->status) }}</span></p>
            @endif
        </div>
    </aside>
</div>
</div>
</x-user-layout>
