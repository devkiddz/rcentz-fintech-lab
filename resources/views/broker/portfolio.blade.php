<x-user-layout>
<x-slot name="header">Trading Portfolio</x-slot>
<div class="ui-page max-w-[1500px]">
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker text-[10px]">Broker · Portfolio</p>
            <h1 class="ui-heading !text-2xl">Liquid Markets Portfolio</h1>
            <p class="ui-lead !max-w-3xl !text-[13px]">Stocks, Forex and Crypto exposure in one account view. Portfolio marks are informational; executable prices are validated again when an order is submitted.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('instruments.index') }}" class="ui-btn ui-btn-primary">New Trade</a>
            <a href="{{ route('broker.positions') }}" class="ui-btn ui-btn-secondary">Positions</a>
            <a href="{{ route('broker.orders') }}" class="ui-btn ui-btn-secondary">Orders</a>
            <a href="{{ route('broker.activity') }}" class="ui-btn ui-btn-secondary">Activity</a>
        </div>
    </section>

    @if(session('success'))
        <div class="mb-4 rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-xs text-emerald-700 dark:text-emerald-400">{{ session('success') }}</div>
    @endif

    <section class="grid grid-cols-2 gap-3 lg:grid-cols-5">
        @foreach([
            ['Holdings',$summary['holdings']],
            ['Open positions',$summary['open_positions']],
            ['Capital deployed',$walletCurrency.' '.number_format($summary['total_invested'],2)],
            ['Mark value',$walletCurrency.' '.number_format($summary['mark_value'],2)],
            ['P/L',($summary['profit_loss'] >= 0 ? '+' : '').$walletCurrency.' '.number_format($summary['profit_loss'],2).' · '.($summary['return_percent'] >= 0 ? '+' : '').number_format($summary['return_percent'],2).'%'],
        ] as [$label,$value])
            <div class="ui-panel p-4">
                <p class="text-[10px] font-semibold uppercase tracking-[.11em] text-muted-foreground">{{ $label }}</p>
                <p class="mt-2 text-base font-semibold tabular-nums">{{ $value }}</p>
            </div>
        @endforeach
    </section>

    <section class="ui-panel mt-5 overflow-hidden">
        <div class="border-b border-border/70 px-5 py-4">
            <p class="ui-kicker">Holdings</p>
            <h2 class="mt-1 text-[15px] font-semibold">Owned liquid exposure</h2>
        </div>
        <div class="divide-y divide-border/70">
            @forelse($holdings as $item)
                @php $instrument=$item['instrument']; $positive=$item['profit_loss']>=0; @endphp
                <div class="grid gap-4 px-5 py-4 lg:grid-cols-[1.4fr_.7fr_.8fr_.8fr_.8fr_auto] lg:items-center">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full border border-border bg-muted/20 px-2 py-1 text-[9px] font-semibold uppercase">{{ strtoupper($item['asset_class']) }}</span>
                            <span class="text-sm font-semibold">{{ $instrument->display_symbol }}</span>
                        </div>
                        <p class="mt-1 text-[10px] text-muted-foreground">{{ $instrument->name }}</p>
                    </div>
                    <div><p class="text-[9px] uppercase text-muted-foreground">Quantity</p><p class="mt-1 text-xs font-semibold tabular-nums">{{ number_format($item['quantity'],8) }}</p></div>
                    <div><p class="text-[9px] uppercase text-muted-foreground">Avg entry</p><p class="mt-1 text-xs font-semibold tabular-nums">{{ number_format($item['average_entry_price'], max(2,(int)$instrument->price_precision)) }}</p></div>
                    <div><p class="text-[9px] uppercase text-muted-foreground">Mark value</p><p class="mt-1 text-xs font-semibold tabular-nums">{{ $item['settlement_currency'] }} {{ number_format($item['mark_value'],2) }}</p></div>
                    <div><p class="text-[9px] uppercase text-muted-foreground">P/L</p><p class="mt-1 text-xs font-semibold tabular-nums {{ $positive ? 'text-emerald-600' : 'text-red-600' }}">{{ $positive ? '+' : '' }}{{ $item['settlement_currency'] }} {{ number_format($item['profit_loss'],2) }} · {{ $item['return_percent']>=0?'+':'' }}{{ number_format($item['return_percent'],2) }}%</p></div>
                    <div class="flex gap-2 lg:justify-end">
                        <a class="ui-btn ui-btn-secondary whitespace-nowrap" href="{{ route('broker.workstation',['assetClass'=>$instrument->asset_class,'symbol'=>$instrument->symbol]) }}">Trade</a>
                    </div>
                </div>
            @empty
                <div class="px-5 py-12 text-center text-sm text-muted-foreground">No liquid-market holdings yet.</div>
            @endforelse
        </div>
    </section>

    <div class="mt-5 grid gap-5 xl:grid-cols-2">
        <section class="ui-panel overflow-hidden">
            <div class="border-b border-border/70 px-5 py-4"><p class="ui-kicker">Positions</p><h2 class="mt-1 text-[15px] font-semibold">Open contracts</h2></div>
            <div class="divide-y divide-border/70">
                @forelse($positions->take(6) as $position)
                    @php $instrument=$position->marketInstrument ?? $position->stock?->marketInstrument; @endphp
                    <div class="flex items-center justify-between gap-4 px-5 py-4">
                        <div><p class="text-xs font-semibold">{{ $instrument?->display_symbol ?? $position->stock?->symbol ?? 'Instrument' }}</p><p class="mt-1 text-[10px] text-muted-foreground">{{ strtoupper($instrument?->asset_class ?? 'stock') }} · {{ number_format((float)$position->open_quantity,8) }} open units</p></div>
                        <a class="ui-btn ui-btn-secondary" href="{{ route('broker.positions') }}">Manage</a>
                    </div>
                @empty
                    <div class="px-5 py-8 text-center text-xs text-muted-foreground">No open positions.</div>
                @endforelse
            </div>
        </section>

        <section class="ui-panel overflow-hidden">
            <div class="border-b border-border/70 px-5 py-4"><p class="ui-kicker">Execution Activity</p><h2 class="mt-1 text-[15px] font-semibold">Recent fills</h2></div>
            <div class="divide-y divide-border/70">
                @forelse($recentExecutions as $execution)
                    <div class="flex items-center justify-between gap-4 px-5 py-4">
                        <div><p class="text-xs font-semibold">{{ $execution->marketInstrument?->display_symbol }} · {{ strtoupper($execution->side) }}</p><p class="mt-1 text-[10px] text-muted-foreground">{{ number_format((float)$execution->quantity,8) }} @ {{ number_format((float)$execution->price,8) }}</p></div>
                        <div class="text-right"><p class="text-xs font-semibold">{{ $execution->settlement_currency }} {{ number_format((float)($execution->settlement_amount ?? $execution->gross_value),2) }}</p><p class="mt-1 text-[9px] text-muted-foreground">{{ $execution->executed_at?->format('M j · H:i') }}</p></div>
                    </div>
                @empty
                    <div class="px-5 py-8 text-center text-xs text-muted-foreground">No executions yet.</div>
                @endforelse
            </div>
        </section>
    </div>
</div>
</x-user-layout>
