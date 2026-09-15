<x-user-layout>
<x-slot name="header">Trade Positions</x-slot>

<div class="ui-page max-w-[1480px]">
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker text-[10px]">Trading · Position Engine</p>
            <h1 class="ui-heading !text-2xl">Positions & exits</h1>
            <p class="ui-lead !text-[13px]">Every entry has a living position, risk rules, exit context and realized outcome.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('trading.portfolio') }}" class="ui-btn ui-btn-secondary">Portfolio</a>
            <a href="{{ route('stocks.index') }}" class="ui-btn ui-btn-primary">New Trade</a>
        </div>
    </section>

    <div class="mt-5 space-y-4">
        @forelse($positions as $position)
            @php
                $open=$position->is_open;
                $current=(float)$position->stock->current_price;
                $pl=$position->current_profit_loss;
                $ret=$position->current_return_percent;
            @endphp
            <article class="ui-panel overflow-hidden">
                <div class="grid gap-5 p-4 xl:grid-cols-[1fr_auto]">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full border border-sky-500/20 bg-sky-500/10 px-2 py-1 text-[9px] font-semibold text-sky-600">{{ $position->stock->symbol }}</span>
                            <span class="rounded-full border px-2 py-1 text-[9px] font-semibold {{ $open?'border-emerald-500/20 bg-emerald-500/10 text-emerald-600':'border-border bg-muted text-muted-foreground' }}">{{ ucfirst(str_replace('_',' ',$position->status)) }}</span>
                            <span class="rounded-full border border-border px-2 py-1 text-[9px] text-muted-foreground">{{ str_replace('_',' ',ucfirst($position->context_type)) }}</span>
                        </div>
                        <h2 class="mt-2 text-sm font-semibold">Long position #{{ $position->id }}</h2>
                        <p class="mt-1 text-[10px] text-muted-foreground">
                            Opened {{ $position->opened_at?->format('M d, Y · H:i') }} · Entry {{ currency_symbol() }}{{ number_format((float)$position->entry_price,2) }}
                        </p>

                        <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4 xl:grid-cols-7">
                            @foreach([
                                ['Open qty',number_format((float)$position->open_quantity,6)],
                                ['Current',currency_symbol().number_format($current,2)],
                                ['Stop loss',$position->stop_loss_price?currency_symbol().number_format((float)$position->stop_loss_price,2):'—'],
                                ['Take profit',$position->take_profit_price?currency_symbol().number_format((float)$position->take_profit_price,2):'—'],
                                ['Expires',$position->expires_at?->format('M d · H:i') ?? 'Open'],
                                ['P/L',($pl>=0?'+':'').currency_symbol().number_format($pl,2)],
                                ['Return',($ret>=0?'+':'').number_format($ret,2).'%'],
                            ] as [$label,$value])
                                <div class="rounded-lg border border-border bg-muted/10 p-2.5">
                                    <p class="text-[8px] uppercase tracking-[.1em] text-muted-foreground">{{ $label }}</p>
                                    <p class="mt-1 text-[10px] font-semibold">{{ $value }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex flex-wrap items-start gap-2 xl:max-w-[420px] xl:justify-end">
                        @if($open)
                            <form method="POST" action="{{ route('trading.positions.close',$position) }}">
                                @csrf
                                <button class="ui-btn ui-btn-primary !h-8 !px-3">Close now</button>
                            </form>

                            <details class="relative">
                                <summary class="ui-btn ui-btn-secondary !h-8 !px-3 cursor-pointer list-none">Partial close</summary>
                                <div class="absolute right-0 z-40 mt-2 w-64 rounded-xl border border-border bg-background p-3 shadow-xl">
                                    <form method="POST" action="{{ route('trading.positions.partial-close',$position) }}" class="space-y-2">
                                        @csrf
                                        <input name="quantity" type="number" step="0.000001" min="0.000001" max="{{ $position->open_quantity }}" class="ui-input w-full" placeholder="Quantity" required>
                                        <button class="ui-btn ui-btn-primary w-full">Sell partial</button>
                                    </form>
                                </div>
                            </details>

                            <details class="relative">
                                <summary class="ui-btn ui-btn-secondary !h-8 !px-3 cursor-pointer list-none">Risk</summary>
                                <div class="absolute right-0 z-40 mt-2 w-72 rounded-xl border border-border bg-background p-3 shadow-xl">
                                    <form method="POST" action="{{ route('trading.positions.risk',$position) }}" class="space-y-2">
                                        @csrf @method('PATCH')
                                        <input name="stop_loss_percent" type="number" step="0.01" min="0.01" max="100" value="{{ $position->stop_loss_percent }}" class="ui-input w-full" placeholder="Stop loss %">
                                        <input name="take_profit_percent" type="number" step="0.01" min="0.01" max="100" value="{{ $position->take_profit_percent }}" class="ui-input w-full" placeholder="Take profit %">
                                        <input name="duration_minutes" type="number" min="1" max="43200" value="{{ $position->duration_minutes }}" class="ui-input w-full" placeholder="Maximum holding minutes">
                                        <button class="ui-btn ui-btn-primary w-full">Update risk</button>
                                    </form>
                                </div>
                            </details>
                        @else
                            <form method="POST" action="{{ route('trading.positions.reenter',$position) }}" class="flex gap-2">
                                @csrf
                                <input name="quantity" type="number" step="0.000001" min="0.000001" class="ui-input !h-8 w-28" placeholder="Qty">
                                <button class="ui-btn ui-btn-primary !h-8 !px-3">Buy back / Re-enter</button>
                            </form>
                        @endif
                    </div>
                </div>

                <div class="border-t border-border/70 bg-muted/10 px-4 py-3">
                    <div class="flex flex-wrap gap-x-5 gap-y-2">
                        @forelse($position->events as $event)
                            <span class="text-[9px] text-muted-foreground">
                                <strong class="font-semibold text-foreground">{{ ucfirst(str_replace('_',' ',$event->event_type)) }}</strong>
                                · {{ $event->created_at->format('M d H:i') }}
                                @if($event->price) · {{ currency_symbol() }}{{ number_format((float)$event->price,2) }} @endif
                            </span>
                        @empty
                            <span class="text-[9px] text-muted-foreground">No position events.</span>
                        @endforelse
                    </div>
                </div>
            </article>
        @empty
            <div class="ui-panel p-10 text-center text-sm text-muted-foreground">No managed positions yet.</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $positions->links() }}</div>
</div>
</x-user-layout>
