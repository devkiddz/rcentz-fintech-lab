<x-user-layout>
<x-slot name="header">Copy Execution</x-slot>
<div class="ui-page max-w-[1280px]">
<section class="ui-page-header">
    <div>
        <p class="ui-kicker text-[10px]">Execution History</p>
        <h1 class="ui-heading !text-xl">{{ $execution->relationship?->strategy?->name ?? 'Copied Strategy' }}</h1>
        <p class="ui-lead !text-[13px]">Mirrored execution context with live market movement.</p>
    </div>
    <a href="{{ route('copy-trading.executions') }}" class="ui-btn ui-btn-secondary">Back to History</a>
</section>

@php
    $trade = $execution->followerTrade;
    $symbol = $trade?->stock?->symbol;
@endphp

<div class="grid gap-4 xl:grid-cols-[1.45fr_.55fr]">
    <section class="ui-panel overflow-hidden">
        <div class="flex items-center justify-between border-b border-border/70 px-4 py-3">
            <div>
                <p class="text-[10px] uppercase tracking-[.12em] text-muted-foreground">Copied execution #{{ $execution->id }}</p>
                <h2 class="mt-1 text-base font-semibold">{{ strtoupper($trade?->type ?? 'trade') }} {{ $symbol }}</h2>
            </div>
            <div class="flex items-center gap-2">
                <span class="rounded-full border border-border bg-muted px-2 py-1 text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">{{ str_replace('_',' ',$marketStatus ?? 'closed') }}</span>
                <span class="rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2 py-1 text-[10px] font-semibold text-emerald-600">{{ ucfirst($execution->status) }}</span>
            </div>
        </div>

        <div class="relative h-[300px] p-3">
            @if(count($quoteHistory ?? []) >= 2)
                @include('trading.partials.mini-analysis-card',['symbol'=>$symbol,'height'=>'h-[280px]'])
            @else
                <div class="absolute inset-0 flex items-center justify-center p-6">
                    <div class="max-w-sm rounded-2xl border border-border bg-background/80 px-5 py-4 text-center">
                        <p class="text-xs font-medium">Building live price history</p>
                        <p class="mt-1 text-[10px] leading-4 text-muted-foreground">The copied execution chart appears as regular-session quotes are collected.</p>
                    </div>
                </div>
            @endif
        </div>
    </section>

    <aside class="ui-panel p-4">
        <div class="grid grid-cols-2 gap-2">
            @foreach([
                ['Amount', format_currency($execution->executed_amount)],
                ['Quantity', number_format((float)($trade?->quantity ?? 0),6)],
                ['Entry', format_currency($trade?->price_per_share ?? 0)],
                ['Current', format_currency($currentPrice)],
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
            <p class="mt-1">Executed: <span class="font-medium text-foreground">{{ optional($execution->executed_at)->format('M d, Y · H:i') }}</span></p>
        </div>
    </aside>
</div>
</div>
</x-user-layout>
