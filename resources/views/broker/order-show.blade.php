<x-user-layout>
<x-slot name="header">Order receipt</x-slot>
<div data-account-async-feedback role="status" aria-live="polite" hidden class="mx-4 my-3 rounded-xl border border-border p-3 text-sm"></div>
<div data-account-async="broker/order-show">
@php
    $instrument=$order->marketInstrument;
    $tradeLabels=\App\Services\TradeOrderLabels::forOrder($order);
    $exitReturn=\App\Services\TradeOrderLabels::realizedReturn($order);
    $intent=$order->execution?->metadata['order_intent'] ?? null;
    $direction=$order->execution?->metadata['direction'] ?? null;
    $isDirectional=($order->metadata['execution_model'] ?? null)==='paper_v1';
    $filled=$order->status==='filled';
    $failed=in_array($order->status,['failed','rejected'],true);
    $currency=strtoupper($order->settlement_currency ?: 'USD');
    $quote=strtoupper($instrument?->quote_asset ?: 'USD');
    $precision=max(0,min(8,(int)($instrument?->price_precision ?? 2)));
    $units=$instrument?->isStock() ? 'shares' : 'units';
    $money=fn($value)=>$value===null ? '—' : $currency.' '.number_format((float)$value,2);
@endphp
<div class="ui-page max-w-5xl">
    <section class="ui-page-header">
        <div><p class="ui-kicker">Trading · Receipt</p><h1 class="ui-heading !text-2xl">{{ \App\Services\BasketDisplay::recordSymbol($instrument, $order->marketplace, $order->created_at) }} · {{ $tradeLabels['primary'] }}{{ $tradeLabels['direction'] ? ' · '.$tradeLabels['direction'] : '' }}</h1><p class="ui-lead !text-[13px]">A record of this order’s execution. Manage open trades on the instrument page.</p></div>
        <div class="flex flex-wrap gap-2">
            @if($instrument)<a href="{{ route('broker.workstation',['assetClass'=>$instrument->asset_class,'symbol'=>$instrument->symbol]) }}" class="ui-btn ui-btn-primary">Back to trading</a>@endif
            <a href="{{ route('broker.orders') }}" class="ui-btn ui-btn-secondary">Order history</a>
        </div>
    </section>
    <section class="ui-panel overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border/70 px-5 py-4">
            <div><h2 class="text-base font-semibold">{{ $filled ? 'Order filled' : ($failed ? 'Order not filled' : 'Order '.str_replace('_',' ',$order->status)) }}</h2><p class="mt-1 text-xs text-muted-foreground">{{ $filled ? 'The execution completed and the account was updated.' : ($failed ? 'This order did not create a filled trade.' : 'This order has not been confirmed as filled.') }}</p></div>
            <span class="rounded-full border px-3 py-1 text-xs font-semibold {{ $filled ? 'border-emerald-500/25 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400' : ($failed ? 'border-red-500/25 bg-red-500/10 text-red-700 dark:text-red-400' : 'border-border bg-muted/20') }}">{{ ucfirst(str_replace('_',' ',$order->status)) }}</span>
        </div>
        <div class="grid grid-cols-2 gap-4 p-5 lg:grid-cols-4">
            @foreach([
                ['Filled quantity',number_format((float)$order->filled_quantity,8).' '.$units],
                ['Average fill price',$order->average_fill_price!==null ? $quote.' '.number_format((float)$order->average_fill_price,$precision) : '—'],
                ['Trading fee',$money($order->fee)],
                ['Executed at',$order->filled_at?->format('M j, Y · H:i:s') ?: '—'],
            ] as [$label,$value])<div class="min-w-0"><p class="text-[10px] uppercase tracking-wide text-muted-foreground">{{ $label }}</p><p class="mt-2 break-words text-sm font-semibold tabular-nums">{{ $value }}</p></div>@endforeach
        </div>
        @if($order->failure_message)<div class="mx-5 mb-5 rounded-xl border border-red-500/20 bg-red-500/10 p-3 text-xs text-red-700 dark:text-red-400">{{ $order->failure_message }}</div>@endif
        <dl class="divide-y divide-border/70 border-t border-border/70">
            @foreach([
                ['Instrument',\App\Services\BasketDisplay::recordName($instrument, $order->marketplace, $order->created_at)],
                ['Action',$isDirectional ? ucfirst($intent ?: 'open').' '.ucfirst($direction ?: '') : ($order->side==='buy' ? 'Buy asset exposure' : 'Sell owned exposure')],
                ['Execution side',$tradeLabels['execution_side']],
                ['Exit reason',$tradeLabels['reason'] ?: '—'],
                ['Requested size',number_format((float)$order->quantity,8).' '.str_replace('_',' ',$order->quantity_mode)],
                [$isDirectional ? ($intent==='close' ? 'Collateral released' : 'Collateral reserved') : 'Settlement amount',$money($order->settlement_amount)],
                ['Realized profit/loss',$exitReturn['formatted'] ?? '—'],
                ['Return on released collateral',$exitReturn['formatted_percent'] ?? '—'],
            ] as [$label,$value])<div class="flex justify-between gap-4 px-5 py-3 text-xs"><dt class="text-muted-foreground">{{ $label }}</dt><dd class="text-right font-semibold">{{ $value }}</dd></div>@endforeach
        </dl>
    </section>
    <details class="ui-panel mt-4 overflow-hidden">
        <summary class="cursor-pointer px-5 py-4 text-sm font-semibold">Order reference &amp; activity</summary>
        <div class="space-y-2 border-t border-border/70 px-5 py-4 text-xs"><p class="break-all"><span class="text-muted-foreground">Order reference:</span> {{ $order->public_id }}</p><p><span class="text-muted-foreground">Execution reference:</span> {{ $order->market_execution_transaction_id ?: '—' }}</p><p><span class="text-muted-foreground">Order type:</span> {{ ucfirst($order->order_type) }}</p></div>
        <div class="divide-y divide-border/70 border-t border-border/70">
            @forelse($order->events->sortBy('id') as $event)
                <div class="flex flex-wrap items-center justify-between gap-2 px-5 py-3 text-xs"><p>{{ ucfirst(str_replace('_',' ',$event->event_type)) }}</p><time class="text-muted-foreground">{{ $event->created_at?->format('M j · H:i:s') }}</time></div>
            @empty
                <p class="px-5 py-4 text-xs text-muted-foreground">No activity recorded.</p>
            @endforelse
        </div>
    </details>
</div>
</div>
</x-user-layout>
