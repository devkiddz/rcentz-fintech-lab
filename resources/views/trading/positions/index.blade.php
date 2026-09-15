<x-user-layout>
<x-slot name="header">Trade Positions</x-slot>

<div class="ui-page max-w-[1480px]">
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker text-[10px]">Trading · Position Contracts</p>
            <h1 class="ui-heading !text-2xl">Positions & exits</h1>
            <p class="ui-lead !text-[13px]">
                EMP is fixed at entry. CMP is the closing/current market price.
                Every contract ends on a real result.
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('trading.portfolio') }}" class="ui-btn ui-btn-secondary">Portfolio</a>
            <a href="{{ route('stocks.index') }}" class="ui-btn ui-btn-primary">New Trade</a>
        </div>
    </section>

    @if(session('success'))
        <div class="mb-4 rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-xs text-emerald-700 dark:text-emerald-400">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->has('position'))
        <div class="mb-4 rounded-xl border border-red-500/20 bg-red-500/10 px-4 py-3 text-xs text-red-700 dark:text-red-400">
            {{ $errors->first('position') }}
        </div>
    @endif

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-border bg-muted/10 px-4 py-3">
        <div>
            <p class="text-[9px] uppercase tracking-[.12em] text-muted-foreground">Regular U.S. market</p>
            <p class="mt-1 text-xs font-semibold">{{ ucfirst(str_replace('_',' ',$marketStatus)) }}</p>
        </div>
        <p class="text-[10px] text-muted-foreground">
            New York · {{ $marketTime->format('M d, Y · H:i') }} ET
            · Session 09:30–16:00 ET
        </p>
    </div>

    <div class="space-y-4">
        @forelse($positions as $position)
            @php
                $isOpen=$position->is_open;
                $closed=$position->status==='closed';

                $cmp=(float)$position->stock->current_price;
                $emp=(float)$position->entry_price;
                $difference=$cmp-$emp;
                $pl=$position->current_profit_loss;
                $ret=$position->current_return_percent;

                $openedEt=$position->opened_at
                    ? $position->opened_at->copy()->setTimezone(\App\Services\MarketSessionService::TIMEZONE)
                    : null;

                $closesEt=$position->expires_at
                    ? $position->expires_at->copy()->setTimezone(\App\Services\MarketSessionService::TIMEZONE)
                    : null;

                $closedEt=$position->closed_at
                    ? $position->closed_at->copy()->setTimezone(\App\Services\MarketSessionService::TIMEZONE)
                    : null;

                $sl=(float)($position->stop_loss_price ?? 0);
                $tp=(float)($position->take_profit_price ?? 0);
                $endReason=$position->metadata['effective_end_reason'] ?? null;
            @endphp

            <article class="ui-panel overflow-hidden">
                <div class="grid gap-5 p-4 xl:grid-cols-[1fr_auto]">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full border border-sky-500/20 bg-sky-500/10 px-2 py-1 text-[9px] font-semibold text-sky-600">
                                {{ $position->stock->symbol }}
                            </span>

                            <span class="rounded-full border px-2 py-1 text-[9px] font-semibold
                                {{ $isOpen
                                    ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-600'
                                    : 'border-border bg-muted text-muted-foreground' }}">
                                {{ $isOpen ? 'Open' : ucfirst(str_replace('_',' ',$position->status)) }}
                            </span>

                            <span class="rounded-full border border-border px-2 py-1 text-[9px] text-muted-foreground">
                                {{ str_replace('_',' ',ucfirst($position->context_type)) }}
                            </span>

                            @if($closed && $position->exit_reason)
                                <span class="rounded-full border border-border px-2 py-1 text-[9px] text-muted-foreground">
                                    {{ ucfirst(str_replace('_',' ',$position->exit_reason)) }}
                                </span>
                            @endif
                        </div>

                        <h2 class="mt-2 text-sm font-semibold">Long contract #{{ $position->id }}</h2>

                        <p class="mt-1 text-[10px] text-muted-foreground">
                            Opened {{ $openedEt?->format('M d, Y · H:i') }} ET
                            @if($closedEt)
                                · Closed {{ $closedEt->format('M d, Y · H:i') }} ET
                            @elseif($closesEt)
                                · Effective end {{ $closesEt->format('M d, Y · H:i') }} ET
                            @endif
                        </p>

                        <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4 xl:grid-cols-8">
                            @foreach([
                                ['Open qty',number_format((float)$position->open_quantity,6)],
                                ['EMP',currency_symbol().number_format($emp,2)],
                                ['CMP',currency_symbol().number_format($cmp,2)],
                                ['CMP - EMP',($difference>=0?'+':'').currency_symbol().number_format($difference,2)],
                                ['Stop loss',$position->stop_loss_price?currency_symbol().number_format($sl,2):'—'],
                                ['Take profit',$position->take_profit_price?currency_symbol().number_format($tp,2):'—'],
                                ['P/L',($pl>=0?'+':'').currency_symbol().number_format($pl,2)],
                                ['Return',($ret>=0?'+':'').number_format($ret,2).'%'],
                            ] as [$label,$value])
                                <div class="rounded-lg border border-border bg-muted/10 p-2.5">
                                    <p class="text-[8px] uppercase tracking-[.1em] text-muted-foreground">{{ $label }}</p>
                                    <p class="mt-1 text-[10px] font-semibold">{{ $value }}</p>
                                </div>
                            @endforeach
                        </div>

                        @if($isOpen && $closesEt)
                            <p class="mt-3 text-[9px] text-muted-foreground">
                                Effective close:
                                <strong class="text-foreground">{{ $closesEt->format('M d · H:i') }} ET</strong>
                                · {{ $endReason==='market_close' ? 'regular market close comes first' : 'selected trade horizon comes first' }}
                            </p>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-start gap-2 xl:max-w-[420px] xl:justify-end">
                        @if($isOpen)
                            <form method="POST"
                                  action="{{ route('trading.positions.close',$position) }}"
                                  onsubmit="return confirm('Kill this trade contract? This is irreversible. The full remaining position will settle at the current CMP and the realized profit or loss becomes final.');">
                                @csrf
                                <button class="ui-btn !h-8 !px-3 border border-red-500/30 bg-red-500/10 text-red-600 hover:bg-red-500/15">
                                    Kill Trade
                                </button>
                            </form>

                            <button type="button"
                                    class="ui-btn ui-btn-secondary !h-8 !px-3"
                                    onclick="document.getElementById('manage-{{ $position->id }}').showModal()">
                                Manage
                            </button>
                        @else
                            <form method="POST" action="{{ route('trading.positions.reenter',$position) }}" class="flex gap-2">
                                @csrf
                                <input name="quantity"
                                       type="number"
                                       step="0.000001"
                                       min="0.000001"
                                       class="ui-input !h-8 w-28"
                                       placeholder="Qty">
                                <button class="ui-btn ui-btn-primary !h-8 !px-3">Re-enter</button>
                            </form>
                        @endif
                    </div>
                </div>

                <div class="border-t border-border/70 bg-muted/10 px-4 py-3">
                    <div class="flex flex-wrap gap-x-5 gap-y-2">
                        @forelse($position->events as $event)
                            @php
                                $eventEt=$event->created_at
                                    ? $event->created_at->copy()->setTimezone(\App\Services\MarketSessionService::TIMEZONE)
                                    : null;
                            @endphp

                            <span class="text-[9px] text-muted-foreground">
                                <strong class="font-semibold text-foreground">
                                    {{ ucfirst(str_replace('_',' ',$event->event_type)) }}
                                </strong>
                                · {{ $eventEt?->format('M d H:i') }} ET
                                @if($event->price)
                                    · {{ currency_symbol() }}{{ number_format((float)$event->price,2) }}
                                @endif
                            </span>
                        @empty
                            <span class="text-[9px] text-muted-foreground">No position events.</span>
                        @endforelse
                    </div>
                </div>

                @if($isOpen)
                <dialog id="manage-{{ $position->id }}"
                        class="w-[min(96vw,1120px)] rounded-2xl border border-border bg-background p-0 text-foreground shadow-2xl backdrop:bg-black/70">
                    <div class="flex items-start justify-between gap-4 border-b border-border px-5 py-4">
                        <div>
                            <p class="text-[9px] font-semibold uppercase tracking-[.14em] text-sky-500">Manage trade</p>
                            <h3 class="mt-1 text-base font-semibold">{{ $position->stock->symbol }} Position Desk</h3>
                            <p class="mt-1 text-xs text-muted-foreground">
                                Review market history, trail risk or reduce exposure. Kill Trade remains the full irreversible exit.
                            </p>
                        </div>

                        <button type="button"
                                class="rounded-lg border border-border p-2 text-muted-foreground hover:bg-muted hover:text-foreground"
                                onclick="document.getElementById('manage-{{ $position->id }}').close()">
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </button>
                    </div>

                    <div class="grid gap-4 p-5 lg:grid-cols-[minmax(0,1.45fr)_minmax(320px,.75fr)]">
                        <div>
                            @include('trading.partials.mini-analysis-card',[
                                'symbol'=>$position->stock->symbol,
                                'height'=>'h-[300px] sm:h-[360px]'
                            ])
                        </div>

                        <div class="space-y-4">
                            <section class="rounded-xl border border-border bg-muted/10 p-4">
                                <p class="text-[9px] uppercase tracking-[.12em] text-muted-foreground">Contract truth</p>
                                <h4 class="mt-1 text-sm font-semibold">EMP → CMP</h4>

                                <div class="mt-3 grid grid-cols-2 gap-2">
                                    @foreach([
                                        ['EMP',currency_symbol().number_format($emp,2)],
                                        ['CMP',currency_symbol().number_format($cmp,2)],
                                        ['Difference',($difference>=0?'+':'').currency_symbol().number_format($difference,2)],
                                        ['Current P/L',($pl>=0?'+':'').currency_symbol().number_format($pl,2)],
                                        ['Return',($ret>=0?'+':'').number_format($ret,2).'%'],
                                        ['Effective end',$closesEt ? ($closesEt->format('M d H:i').' ET') : '—'],
                                    ] as [$label,$value])
                                        <div class="rounded-lg border border-border bg-background/60 p-2.5">
                                            <p class="text-[8px] uppercase tracking-[.1em] text-muted-foreground">{{ $label }}</p>
                                            <p class="mt-1 text-[10px] font-semibold">{{ $value }}</p>
                                        </div>
                                    @endforeach
                                </div>

                                <p class="mt-3 text-[10px] leading-5 text-muted-foreground">
                                    Long-position result is calculated from
                                    <strong class="text-foreground">(CMP − EMP) × quantity</strong>.
                                    The wallet receives the sale proceeds once; realized P/L is recorded for history and performance.
                                </p>
                            </section>

                            @if($marketStatus==='open')
                            <section class="rounded-xl border border-border p-4">
                                <p class="text-[9px] uppercase tracking-[.12em] text-muted-foreground">Reduce exposure</p>
                                <h4 class="mt-1 text-sm font-semibold">Partial close</h4>

                                <form method="POST"
                                      action="{{ route('trading.positions.partial-close',$position) }}"
                                      class="mt-3 space-y-3"
                                      data-open-qty="{{ (float)$position->open_quantity }}">
                                    @csrf

                                    <div class="grid grid-cols-3 gap-2">
                                        @foreach([25,50,75] as $pct)
                                            <button type="button"
                                                    class="ui-btn ui-btn-secondary !h-8 !px-2 !text-[10px]"
                                                    onclick="const f=this.closest('form'); const q=parseFloat(f.dataset.openQty||0); f.querySelector('[name=quantity]').value=(q*{{ $pct }}/100).toFixed(6);">
                                                {{ $pct }}%
                                            </button>
                                        @endforeach
                                    </div>

                                    <div>
                                        <label class="ui-label">Quantity to close</label>
                                        <input name="quantity"
                                               type="number"
                                               step="0.000001"
                                               min="0.000001"
                                               max="{{ max(0,(float)$position->open_quantity-0.000001) }}"
                                               class="ui-input w-full"
                                               placeholder="e.g. 0.500000"
                                               required>
                                        <p class="mt-1 text-[9px] text-muted-foreground">
                                            This portion settles at CMP. The remaining quantity keeps the contract alive.
                                        </p>
                                    </div>

                                    <button class="ui-btn ui-btn-primary w-full">Execute partial close</button>
                                </form>
                            </section>
                            @endif

                            <section class="rounded-xl border border-border p-4">
                                <p class="text-[9px] uppercase tracking-[.12em] text-muted-foreground">Trail / controls</p>
                                <h4 class="mt-1 text-sm font-semibold">Manage the still-open contract</h4>

                                <form method="POST"
                                      action="{{ route('trading.positions.risk',$position) }}"
                                      class="mt-3 space-y-3">
                                    @csrf
                                    @method('PATCH')

                                    <div>
                                        <label class="ui-label">Stop loss (%)</label>
                                        <input name="stop_loss_percent"
                                               type="number"
                                               step="0.01"
                                               min="0.01"
                                               max="100"
                                               value="{{ $position->stop_loss_percent }}"
                                               class="ui-input w-full"
                                               placeholder="e.g. 1.00">
                                    </div>

                                    <div>
                                        <label class="ui-label">Take profit (%)</label>
                                        <input name="take_profit_percent"
                                               type="number"
                                               step="0.01"
                                               min="0.01"
                                               max="100"
                                               value="{{ $position->take_profit_percent }}"
                                               class="ui-input w-full"
                                               placeholder="e.g. 2.00">
                                    </div>

                                    <div>
                                        <label class="ui-label">Requested remaining duration (minutes)</label>
                                        <input name="duration_minutes"
                                               type="number"
                                               min="1"
                                               max="43200"
                                               value="{{ $position->duration_minutes }}"
                                               class="ui-input w-full"
                                               placeholder="e.g. 60">
                                        <p class="mt-1 text-[9px] text-muted-foreground">
                                            Even if you request longer, this trade still closes at 16:00 ET if market close comes first.
                                        </p>
                                    </div>

                                    <button class="ui-btn ui-btn-secondary w-full">Update management rules</button>
                                </form>
                            </section>
                        </div>
                    </div>
                </dialog>
                @endif
            </article>
        @empty
            <div class="ui-panel p-10 text-center text-sm text-muted-foreground">
                No managed positions yet.
            </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $positions->links() }}</div>
</div>
</x-user-layout>
