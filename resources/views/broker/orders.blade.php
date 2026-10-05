<x-user-layout>
<x-slot name="header">Orders</x-slot>
<div data-account-async-feedback role="status" aria-live="polite" hidden class="mx-4 my-3 rounded-xl border border-border p-3 text-sm"></div>
<div data-account-async="broker/orders">
<div class="ui-page max-w-[1400px]">
<?php $orderTimezone = config('app.timezone', 'UTC'); ?>
    <section class="ui-page-header">
        <div><p class="ui-kicker text-[10px]">Broker · Orders</p><h1 class="ui-heading !text-2xl">Order History</h1><p class="ui-lead !text-[13px]">Orders, fills and trade holding times across Stocks, Forex and Crypto. Times shown in {{ $orderTimezone }}.</p></div>
        <div class="flex gap-2"><a href="{{ route('broker.portfolio') }}" class="ui-btn ui-btn-secondary">Portfolio</a><a href="{{ route('broker.activity') }}" class="ui-btn ui-btn-secondary">Execution Activity</a><a href="{{ route('instruments.index') }}" class="ui-btn ui-btn-primary">New Trade</a></div>
    </section>
    <section class="ui-panel overflow-hidden">
        <div class="divide-y divide-border/70">
            @forelse($orders as $order)
                <?php
                    $tradeLabels = \App\Services\TradeOrderLabels::forOrder($order);
                    $exitReturn = \App\Services\TradeOrderLabels::realizedReturn($order);
                    $trade = $order->execution?->tradePosition;
                    $ownedTrade = $trade && (int)$trade->user_id === (int)$order->user_id;
                    $tradeStart = $ownedTrade ? $trade->opened_at : null;
                    $tradeEnd = $ownedTrade ? $trade->closed_at : null;
                    $holdingTime = null;
                    if ($tradeStart && ($tradeEnd || $trade->is_open)) {
                        $seconds = max(0, (int)(($tradeEnd ?? now())->timestamp - $tradeStart->timestamp));
                        $days = intdiv($seconds, 86400);
                        $holdingTime = ($days ? $days.'d ' : '').sprintf('%02d:%02d:%02d', intdiv($seconds % 86400, 3600), intdiv($seconds % 3600, 60), $seconds % 60);
                    }
                ?>
                <article class="grid gap-3 px-5 py-4 transition-colors hover:bg-muted/20 lg:grid-cols-[1.3fr_.6fr_.7fr_.8fr_.8fr_auto] lg:items-center">
                    <div><p class="text-xs font-semibold">{{ \App\Services\BasketDisplay::recordSymbol($order->marketInstrument, $order->marketplace, $order->created_at) }}</p><p class="mt-1 text-[9px] text-muted-foreground">{{ $order->public_id }}</p></div>
                    <div><p class="text-[9px] uppercase text-muted-foreground">Position</p><p class="mt-1 text-xs font-semibold {{ $tradeLabels['primary']==='BUY'?'text-emerald-600':'text-red-600' }}">{{ $tradeLabels['primary'] }}{{ $tradeLabels['direction'] ? ' · '.$tradeLabels['direction'] : '' }}</p><p class="mt-1 text-[9px] text-muted-foreground">{{ $tradeLabels['reason'] ?: $tradeLabels['action'] }} · Execution {{ $tradeLabels['execution_side'] }}</p></div>
                    <div><p class="text-[9px] uppercase text-muted-foreground">Quantity</p><p class="mt-1 text-xs font-semibold">{{ number_format((float)$order->quantity,8) }} {{ str_replace('_',' ',$order->quantity_mode) }}</p></div>
                    <div><p class="text-[9px] uppercase text-muted-foreground">Status</p><p class="mt-1 text-xs font-semibold">{{ strtoupper($order->status) }}</p></div>
                    <div><p class="text-[9px] uppercase text-muted-foreground">Settlement</p><p class="mt-1 text-xs font-semibold">{{ $order->settlement_currency ?: '—' }} {{ $order->settlement_amount!==null ? number_format((float)$order->settlement_amount,2) : '—' }}</p>@if($exitReturn)<p class="mt-1 text-[9px] font-semibold {{ $exitReturn['positive'] ? 'text-emerald-600' : 'text-red-600' }}" title="Realized profit/loss. Percentage is based on collateral released by this exit.">Realized P/L {{ $exitReturn['formatted'] }}{{ $exitReturn['formatted_percent'] !== null ? ' · '.$exitReturn['formatted_percent'] : '' }}</p>@endif</div>
                    <div class="flex flex-wrap justify-end gap-2" data-order-instrument-link>
    <a class="ui-btn ui-btn-secondary"
       href="{{ route('broker.orders.show', ['publicId'=>$order->public_id]) }}">View receipt</a>
    @if($order->marketInstrument && in_array($order->marketInstrument->asset_class, ['stock','forex','crypto','commodity'], true))
        <a class="ui-btn ui-btn-secondary"
           href="{{ route('broker.workstation', ['assetClass'=>$order->marketInstrument->asset_class, 'symbol'=>$order->marketInstrument->symbol]) }}">View instrument</a>
    @endif
</div>
                    <div style="grid-column:1 / -1" class="grid gap-3 border-t border-border/70 pt-3 text-xs sm:grid-cols-2 lg:grid-cols-4">
                        <div><p class="text-[9px] uppercase text-muted-foreground">Order placed / filled</p><p class="mt-1 tabular-nums">{{ $order->created_at?->copy()->timezone($orderTimezone)->format('d M Y H:i:s') ?? '—' }}</p><p class="mt-1 text-[10px] text-muted-foreground">Filled: {{ $order->filled_at?->copy()->timezone($orderTimezone)->format('d M Y H:i:s') ?? '—' }}</p></div>
                        <div><p class="text-[9px] uppercase text-muted-foreground">Trade opened</p><p class="mt-1 tabular-nums">{{ $tradeStart?->copy()->timezone($orderTimezone)->format('d M Y H:i:s') ?? '—' }}</p></div>
                        <div><p class="text-[9px] uppercase text-muted-foreground">Trade closed</p><p class="mt-1 tabular-nums">{{ $tradeEnd?->copy()->timezone($orderTimezone)->format('d M Y H:i:s') ?? ($ownedTrade && $trade->is_open ? 'Still open' : '—') }}</p></div>
                        <div><p class="text-[9px] uppercase text-muted-foreground">Trade duration · days / hh:mm:ss</p><p class="mt-1 font-semibold tabular-nums">{{ $holdingTime ?? '—' }}</p><p class="mt-1 text-[10px] text-muted-foreground">{{ $ownedTrade && $trade->is_open ? 'Elapsed at page refresh' : 'Total position holding time' }}</p></div>
                    </div>
                </article>
            @empty
                <div class="px-5 py-12 text-center text-sm text-muted-foreground">No broker orders yet.</div>
            @endforelse
        </div>
    </section>
    <div class="mt-4">{{ $orders->links() }}</div>
</div>
</div>
</x-user-layout>
