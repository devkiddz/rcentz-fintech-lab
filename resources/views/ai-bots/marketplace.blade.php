<x-user-layout>
<x-slot name="header">{{ localize('ui.r2d.bots.ai_trading_bots', 'AI Trading Bots') }}</x-slot>
<div class="ui-page max-w-[1440px]">
<section class="ui-page-header">
    <div>
        <p class="ui-kicker text-[10px]">{{ localize('ui.r2d.bots.ai_trading_bots', 'AI Trading Bots') }}</p>
        <h1 class="ui-heading !text-xl">{{ localize('ui.r2d.bots.marketplace', 'Bot Marketplace') }}</h1>
        <p class="ui-lead !text-[13px]">{{ localize('ui.r2d.bots.marketplace_lead', 'Admin-curated automation with live market context, transparent limits and performance.') }}</p>
    </div>
    <a href="{{ route('ai-bots.my-bots') }}" class="ui-btn ui-btn-secondary"><i data-lucide="bot" class="h-4 w-4"></i> {{ localize('ui.r2d.bots.my_bots', 'My Bots') }}</a>
</section>

<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
@forelse($products as $product)
    @php
        $m = $product->performance_metrics;
        $market = $product->market_context ?? [];
        $symbol = $market['symbol'] ?? $product->marketInstrument?->display_symbol ?? $product->stock?->symbol ?? '—';
    @endphp

    <a href="{{ route('ai-bots.show',$product) }}" class="ui-panel block overflow-hidden border border-border/70 bg-background/60 shadow-sm transition hover:-translate-y-0.5">
        <div class="p-4">
            @if($m['is_manual_performance'])
                <div class="mb-2 inline-flex items-center gap-1.5 rounded-full border border-violet-500/20 bg-violet-500/10 px-2 py-1 text-[9px] font-semibold uppercase tracking-[0.12em] text-violet-600">
                    <i data-lucide="sparkles" class="h-3 w-3"></i>{{ $m['performance_label'] ?: localize('ui.r2d.bots.manual_performance', 'Manual Performance') }}
                </div>
            @endif

            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="flex flex-wrap gap-2">
                        <span class="inline-flex items-center gap-1 rounded-full border border-sky-500/20 bg-sky-500/10 px-2 py-1 text-[10px] font-semibold text-sky-600"><i data-lucide="candlestick-chart" class="h-3.5 w-3.5"></i>{{ $symbol }}</span>
                        <span class="rounded-full border border-amber-500/20 bg-amber-500/10 px-2 py-1 text-[10px] font-semibold text-amber-600">{{ ucfirst($product->risk_level) }} risk</span>
                    </div>
                    <h2 class="mt-2.5 text-base font-semibold">{{ $product->name }}</h2>
                </div>
                <i data-lucide="chevron-right" class="h-4 w-4 text-muted-foreground"></i>
            </div>

            <p class="mt-2 line-clamp-2 text-xs leading-5 text-muted-foreground">{{ $product->description }}</p>
        </div>

        <div class="border-y border-border/70 bg-muted/10">
            <div class="flex items-center justify-between px-3.5 pt-3">
                <div>
                    <p class="text-[9px] uppercase tracking-[.13em] text-muted-foreground">{{ localize('ui.r2d.bots.live_price', 'Live price') }}</p>
                    <p class="mt-1 text-sm font-semibold">{{ $market['current_display'] ?? '—' }}</p>
                </div>
                <span class="text-[10px] font-semibold {{ (float)($market['change_percentage'] ?? 0) >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                    {{ (float)($market['change_percentage'] ?? 0) >= 0 ? '+' : '' }}{{ number_format((float)($market['change_percentage'] ?? 0),2) }}%
                </span>
            </div>
            <div class="relative h-[110px] px-2 pb-2">
                @if(count($product->quote_history ?? []) >= 2)
                    <div class="h-full w-full" data-rcentz-candles data-compact="true" data-quotes='@json($product->quote_history ?? [])'></div>
                @else
                    <div class="absolute inset-0 flex items-center justify-center px-3 pb-2">
                        <div class="text-center">
                            <p class="text-[10px] font-medium text-foreground">{{ localize('ui.r2d.bots.building_history', 'Building live price history') }}</p>
                            <p class="mt-1 text-[9px] text-muted-foreground">Waiting for authoritative {{ strtoupper($market['asset_class'] ?? 'market') }} price history.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="p-4">
            <div class="grid grid-cols-4 gap-2">
                <div class="rounded-lg border border-border bg-muted/10 p-2">
                    <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">{{ localize('ui.r2d.common.pnl', 'P/L') }}</p>
                    <p class="mt-1 text-[12px] font-semibold {{ $m['profit_loss']>0?'text-emerald-600':'' }}">{{ $m['profit_loss']>0?'+':'' }}{{ format_currency($m['profit_loss']) }}</p>
                </div>
                <div class="rounded-lg border border-border bg-muted/10 p-2">
                    <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">{{ localize('ui.r2d.common.return', 'Return') }}</p>
                    <p class="mt-1 text-[12px] font-semibold text-sky-600">{{ $m['return_percent']>0?'+':'' }}{{ number_format($m['return_percent'],2) }}%</p>
                </div>
                <div class="rounded-lg border border-border bg-muted/10 p-2">
                    <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">{{ localize('ui.r2d.bots.trades', 'Trades') }}</p>
                    <p class="mt-1 text-[12px] font-semibold">{{ $m['completed_count'] }}</p>
                </div>
                <div class="rounded-lg border border-border bg-muted/10 p-2">
                    <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">{{ localize('ui.r2d.bots.subscribers', 'Subs') }}</p>
                    <p class="mt-1 text-[12px] font-semibold">{{ $product->subscriber_count }}</p>
                </div>
            </div>

            <div class="mt-3 flex items-end justify-between border-t border-border pt-3 text-xs">
                <div>
                    <span class="text-muted-foreground">{{ localize('ui.r2d.bots.subscription', 'Subscription') }}</span>
                    <p class="mt-1 font-semibold">{{ $product->price>0?format_currency($product->price):localize('ui.r2d.bots.free', 'Free') }}</p>
                </div>
                <div class="text-right">
                    <span class="text-muted-foreground">{{ localize('ui.r2d.bots.minimum_balance', 'Minimum Balance') }}</span>
                    <p class="mt-1 font-semibold">{{ format_currency($product->minimum_balance) }}</p>
                </div>
            </div>
        </div>
    </a>
@empty
    <div class="ui-panel p-8 md:col-span-2 xl:col-span-3 text-center text-sm text-muted-foreground">{{ localize('ui.r2d.bots.no_products', 'No bot products available.') }}</div>
@endforelse
</div>
</div>


@include('ai-bots.partials.lightweight-charts')
</x-user-layout>