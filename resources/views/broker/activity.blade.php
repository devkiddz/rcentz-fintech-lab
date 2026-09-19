<x-user-layout>
<x-slot name="header">Execution Activity</x-slot>
<div class="ui-page max-w-[1450px]">
    <section class="ui-page-header">
        <div><p class="ui-kicker text-[10px]">Broker · Activity</p><h1 class="ui-heading !text-2xl">Execution Ledger</h1><p class="ui-lead !text-[13px]">Normalized fills across Stocks, Forex and Crypto, including legacy Stock executions already bridged into the unified ledger.</p></div>
        <div class="flex gap-2"><a href="{{ route('broker.portfolio') }}" class="ui-btn ui-btn-secondary">Portfolio</a><a href="{{ route('broker.orders') }}" class="ui-btn ui-btn-secondary">Orders</a><a href="{{ route('instruments.index') }}" class="ui-btn ui-btn-primary">Markets</a></div>
    </section>
    <section class="ui-panel overflow-hidden">
        <div class="divide-y divide-border/70">
            @forelse($executions as $execution)
                @php $order=$orderMap->get($execution->id); $pnl=(float)($execution->realized_profit_loss ?? 0); @endphp
                <div class="grid gap-3 px-5 py-4 lg:grid-cols-[1.2fr_.55fr_.7fr_.8fr_.8fr_.7fr] lg:items-center">
                    <div><div class="flex items-center gap-2"><span class="rounded-full border border-border bg-muted/20 px-2 py-1 text-[9px] font-semibold uppercase">{{ strtoupper($execution->marketInstrument?->asset_class ?? 'market') }}</span><p class="text-xs font-semibold">{{ $execution->marketInstrument?->display_symbol ?? 'Instrument' }}</p></div><p class="mt-1 text-[9px] text-muted-foreground">Receipt #{{ $execution->id }}{{ $order ? ' · Order '.$order->public_id : '' }}</p></div>
                    <div><p class="text-[9px] uppercase text-muted-foreground">Side</p><p class="mt-1 text-xs font-semibold {{ $execution->side==='buy'?'text-emerald-600':'text-red-600' }}">{{ strtoupper($execution->side) }}</p></div>
                    <div><p class="text-[9px] uppercase text-muted-foreground">Quantity</p><p class="mt-1 text-xs font-semibold">{{ number_format((float)$execution->quantity,8) }}</p></div>
                    <div><p class="text-[9px] uppercase text-muted-foreground">Fill price</p><p class="mt-1 text-xs font-semibold">{{ number_format((float)$execution->price,8) }}</p></div>
                    <div><p class="text-[9px] uppercase text-muted-foreground">Settlement</p><p class="mt-1 text-xs font-semibold">{{ $execution->settlement_currency }} {{ number_format((float)($execution->settlement_amount ?? $execution->gross_value),2) }}</p></div>
                    <div><p class="text-[9px] uppercase text-muted-foreground">Realized P/L</p><p class="mt-1 text-xs font-semibold {{ $pnl>=0?'text-emerald-600':'text-red-600' }}">{{ $execution->realized_profit_loss===null?'—':(($pnl>=0?'+':'').number_format($pnl,2)) }}</p><p class="mt-1 text-[9px] text-muted-foreground">{{ $execution->executed_at?->format('M j · H:i') }}</p></div>
                </div>
            @empty
                <div class="px-5 py-12 text-center text-sm text-muted-foreground">No execution receipts yet.</div>
            @endforelse
        </div>
    </section>
    <div class="mt-4">{{ $executions->links() }}</div>
</div>
</x-user-layout>
