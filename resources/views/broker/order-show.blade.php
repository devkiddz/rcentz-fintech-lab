<x-user-layout>
<x-slot name="header">Order {{ $order->public_id }}</x-slot>
<div class="ui-page max-w-5xl">
    <section class="ui-page-header">
        <div><p class="ui-kicker text-[10px]">Broker · Order Receipt</p><h1 class="ui-heading !text-2xl">{{ $order->marketInstrument?->display_symbol }} {{ strtoupper($order->side) }}</h1><p class="ui-lead !text-[13px]">{{ $order->public_id }}</p></div>
        <div class="flex gap-2"><a href="{{ route('broker.orders') }}" class="ui-btn ui-btn-secondary">All Orders</a><a href="{{ route('broker.portfolio') }}" class="ui-btn ui-btn-secondary">Portfolio</a></div>
    </section>
    @if(session('success'))<div class="mb-4 rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-xs text-emerald-700 dark:text-emerald-400">{{ session('success') }}</div>@endif
    <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach([
            ['Status',strtoupper($order->status)],
            ['Requested',number_format((float)$order->quantity,8).' '.str_replace('_',' ',$order->quantity_mode)],
            ['Filled',number_format((float)$order->filled_quantity,8)],
            ['Settlement',($order->settlement_currency ?: '—').' '.($order->settlement_amount!==null?number_format((float)$order->settlement_amount,2):'—')],
        ] as [$label,$value])
            <div class="ui-panel p-4"><p class="text-[10px] uppercase tracking-[.11em] text-muted-foreground">{{ $label }}</p><p class="mt-2 text-sm font-semibold">{{ $value }}</p></div>
        @endforeach
    </section>
    @if($order->failure_message)<div class="mt-4 rounded-xl border border-red-500/20 bg-red-500/10 px-4 py-3 text-xs text-red-700 dark:text-red-400">{{ $order->failure_message }}</div>@endif
    <div class="mt-5 grid gap-5 lg:grid-cols-2">
        <section class="ui-panel overflow-hidden">
            <div class="border-b border-border/70 px-5 py-4"><p class="ui-kicker">Fill</p><h2 class="mt-1 text-[15px] font-semibold">Execution receipt</h2></div>
            <div class="divide-y divide-border/70">
                @foreach([
                    ['Order type',strtoupper($order->order_type)],
                    ['Marketplace',strtoupper($order->marketplace)],
                    ['Average fill',$order->average_fill_price!==null?number_format((float)$order->average_fill_price,8):'—'],
                    ['Gross value',$order->gross_value!==null?number_format((float)$order->gross_value,2):'—'],
                    ['Fee',number_format((float)$order->fee,2)],
                    ['Execution receipt',$order->market_execution_transaction_id ?: '—'],
                    ['Filled at',$order->filled_at?->format('M j, Y · H:i:s') ?: '—'],
                ] as [$label,$value])<div class="flex justify-between gap-4 px-5 py-3 text-xs"><span class="text-muted-foreground">{{ $label }}</span><span class="text-right font-semibold">{{ $value }}</span></div>@endforeach
            </div>
        </section>
        <section class="ui-panel overflow-hidden">
            <div class="border-b border-border/70 px-5 py-4"><p class="ui-kicker">Lifecycle</p><h2 class="mt-1 text-[15px] font-semibold">Order events</h2></div>
            <div class="divide-y divide-border/70">
                @forelse($order->events->sortBy('id') as $event)
                    <div class="px-5 py-4"><div class="flex items-center justify-between gap-3"><p class="text-xs font-semibold">{{ ucfirst(str_replace('_',' ',$event->event_type)) }}</p><span class="text-[9px] text-muted-foreground">{{ $event->created_at?->format('M j · H:i:s') }}</span></div><p class="mt-1 text-[10px] text-muted-foreground">{{ $event->note ?: 'Lifecycle event recorded.' }}</p></div>
                @empty<div class="px-5 py-8 text-center text-xs text-muted-foreground">No order events recorded.</div>@endforelse
            </div>
        </section>
    </div>
</div>
</x-user-layout>
