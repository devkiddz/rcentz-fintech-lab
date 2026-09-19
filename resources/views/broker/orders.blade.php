<x-user-layout>
<x-slot name="header">Orders</x-slot>
<div class="ui-page max-w-[1400px]">
    <section class="ui-page-header">
        <div><p class="ui-kicker text-[10px]">Broker · Orders</p><h1 class="ui-heading !text-2xl">Order History</h1><p class="ui-lead !text-[13px]">Customer intent, execution state and fill linkage across Stocks, Forex and Crypto.</p></div>
        <div class="flex gap-2"><a href="{{ route('broker.portfolio') }}" class="ui-btn ui-btn-secondary">Portfolio</a><a href="{{ route('broker.activity') }}" class="ui-btn ui-btn-secondary">Execution Activity</a><a href="{{ route('instruments.index') }}" class="ui-btn ui-btn-primary">New Trade</a></div>
    </section>
    <section class="ui-panel overflow-hidden">
        <div class="divide-y divide-border/70">
            @forelse($orders as $order)
                <a href="{{ route('broker.orders.show',['publicId'=>$order->public_id]) }}" class="grid gap-3 px-5 py-4 transition-colors hover:bg-muted/20 lg:grid-cols-[1.3fr_.6fr_.7fr_.8fr_.8fr_auto] lg:items-center">
                    <div><p class="text-xs font-semibold">{{ $order->marketInstrument?->display_symbol ?? 'Instrument' }}</p><p class="mt-1 text-[9px] text-muted-foreground">{{ $order->public_id }}</p></div>
                    <div><p class="text-[9px] uppercase text-muted-foreground">Side</p><p class="mt-1 text-xs font-semibold {{ $order->side==='buy'?'text-emerald-600':'text-red-600' }}">{{ strtoupper($order->side) }}</p></div>
                    <div><p class="text-[9px] uppercase text-muted-foreground">Quantity</p><p class="mt-1 text-xs font-semibold">{{ number_format((float)$order->quantity,8) }} {{ str_replace('_',' ',$order->quantity_mode) }}</p></div>
                    <div><p class="text-[9px] uppercase text-muted-foreground">Status</p><p class="mt-1 text-xs font-semibold">{{ strtoupper($order->status) }}</p></div>
                    <div><p class="text-[9px] uppercase text-muted-foreground">Settlement</p><p class="mt-1 text-xs font-semibold">{{ $order->settlement_currency ?: '—' }} {{ $order->settlement_amount!==null ? number_format((float)$order->settlement_amount,2) : '—' }}</p></div>
                    <div class="text-right"><span class="ui-btn ui-btn-secondary">View</span></div>
                </a>
            @empty
                <div class="px-5 py-12 text-center text-sm text-muted-foreground">No broker orders yet.</div>
            @endforelse
        </div>
    </section>
    <div class="mt-4">{{ $orders->links() }}</div>
</div>
</x-user-layout>
