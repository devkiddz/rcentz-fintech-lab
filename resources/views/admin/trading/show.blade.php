<x-admin-layout>
<div class="ui-page max-w-[1500px]">
    {{-- V5.9.1 trade terminology cleanup --}}
    @php
        $exitLabel = $isOpen ? 'Current Market Price (CMP)' : 'Average Exit Price';
        $statusLabel = $isOpen ? 'Open' : ucfirst(str_replace('_',' ',$position->status));
        $openedEt = $position->opened_at?->copy()->setTimezone(\App\Services\MarketSessionService::TIMEZONE);
        $closedEt = $position->closed_at?->copy()->setTimezone(\App\Services\MarketSessionService::TIMEZONE);
        $expiresEt = $position->expires_at?->copy()->setTimezone(\App\Services\MarketSessionService::TIMEZONE);
    @endphp

    <section class="ui-page-header">
        <div>
            <p class="ui-kicker">Trading Command · Trade Record</p>
            <div class="mt-1 flex flex-wrap items-center gap-2">
                <h1 class="ui-heading">{{ $position->stock?->symbol ?? 'Trade' }} · Position #{{ $position->id }}</h1>
                <span class="rounded-full border px-2 py-1 text-[9px] font-semibold
                    {{ $isOpen ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-600' : 'border-border bg-muted text-muted-foreground' }}">
                    {{ $statusLabel }}
                </span>
            </div>
            <p class="ui-lead">
                {{ $position->user?->name ?? 'Unknown customer' }} ·
                Opened {{ $openedEt?->format('M d, Y · H:i') ?? '—' }} ET
                @if($closedEt) · Closed {{ $closedEt->format('M d, Y · H:i') }} ET @endif
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.trading.history') }}" class="ui-btn ui-btn-secondary">
                <i data-lucide="arrow-left" class="h-4 w-4"></i> Trade History
            </a>
            @if($position->stock)
                <a href="{{ route('admin.stocks.show',$position->stock) }}" class="ui-btn ui-btn-secondary">
                    <i data-lucide="candlestick-chart" class="h-4 w-4"></i> Instrument
                </a>
            @endif
            @if($position->user)
                <a href="{{ route('admin.users.show',$position->user) }}" class="ui-btn ui-btn-primary">
                    <i data-lucide="user-round" class="h-4 w-4"></i> Customer
                </a>
            @endif
        </div>
    </section>

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
        @foreach([
            ['Entry Market Price', currency_symbol().number_format((float)$position->entry_price,2), 'log-in'],
            [$exitLabel, currency_symbol().number_format($displayExitPrice,2), $isOpen ? 'activity' : 'log-out'],
            ['Profit / Loss', ($profitLoss >= 0 ? '+' : '-').currency_symbol().number_format(abs($profitLoss),2), 'badge-dollar-sign'],
            ['Return', ($returnPercent >= 0 ? '+' : '').number_format($returnPercent,2).'%', 'percent'],
            ['Initial Quantity', number_format((float)$position->initial_quantity,6), 'layers-3'],
            ['Open Quantity', number_format((float)$position->open_quantity,6), 'target'],
        ] as [$label,$value,$icon])
            <div class="ui-panel p-4">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-[8px] uppercase tracking-[.11em] text-muted-foreground">{{ $label }}</p>
                    <i data-lucide="{{ $icon }}" class="h-4 w-4 text-muted-foreground"></i>
                </div>
                <p class="mt-3 text-lg font-semibold tabular-nums
                    {{ $label === 'Profit / Loss' ? ($profitLoss >= 0 ? 'text-emerald-600' : 'text-red-600') : '' }}">
                    {{ $value }}
                </p>
            </div>
        @endforeach
    </section>

    <section class="mt-4 grid gap-4 xl:grid-cols-[minmax(0,1.45fr)_minmax(360px,.55fr)]">
        <div class="min-w-0">
            @if($position->stock)
                @include('trading.partials.analysis-chart', [
                    'stock' => $position->stock,
                    'analysis' => $analysis,
                    'chartHeight' => 'h-[380px] md:h-[460px]',
                ])
            @endif
        </div>

        <aside class="ui-panel overflow-hidden">
            <div class="border-b border-border px-4 py-3">
                <p class="ui-kicker">Contract overview</p>
                <h2 class="mt-1 text-sm font-semibold">Entry → exit lifecycle</h2>
            </div>

            <div class="grid grid-cols-2 gap-px bg-border">
                @foreach([
                    ['Direction',ucfirst($position->direction)],
                    ['Status',$statusLabel],
                    ['Stop loss',$position->stop_loss_price ? currency_symbol().number_format((float)$position->stop_loss_price,2) : '—'],
                    ['Take profit',$position->take_profit_price ? currency_symbol().number_format((float)$position->take_profit_price,2) : '—'],
                    ['Opened',$openedEt ? $openedEt->format('M d · H:i').' ET' : '—'],
                    [$isOpen ? 'Effective end' : 'Closed',$isOpen ? ($expiresEt ? $expiresEt->format('M d · H:i').' ET' : '—') : ($closedEt ? $closedEt->format('M d · H:i').' ET' : '—')],
                    ['Exit reason',$position->exit_reason ? ucfirst(str_replace('_',' ',$position->exit_reason)) : '—'],
                    ['Contract','Position #'.$position->id],
                ] as [$label,$value])
                    <div class="bg-background p-3.5">
                        <p class="text-[8px] uppercase tracking-[.1em] text-muted-foreground">{{ $label }}</p>
                        <p class="mt-1.5 text-[10px] font-semibold">{{ $value ?: '—' }}</p>
                    </div>
                @endforeach
            </div>

            <div class="border-t border-border p-4">
                <div class="flex items-center justify-between gap-3 rounded-xl border border-border bg-muted/10 p-3">
                    <div>
                        <p class="text-[8px] uppercase tracking-[.11em] text-muted-foreground">Entry transaction</p>
                        <p class="mt-1 text-[10px] font-semibold">{{ $position->entryTransaction ? '#'.$position->entryTransaction->id : '—' }}</p>
                    </div>
                    @if($position->entryTransaction)
                        <a href="{{ route('admin.stocks.transactions.show',$position->entryTransaction) }}" class="ui-btn ui-btn-secondary !h-8 !px-3">Open</a>
                    @endif
                </div>

                <div class="mt-2 flex items-center justify-between gap-3 rounded-xl border border-border bg-muted/10 p-3">
                    <div>
                        <p class="text-[8px] uppercase tracking-[.11em] text-muted-foreground">Last exit transaction</p>
                        <p class="mt-1 text-[10px] font-semibold">{{ $position->lastExitTransaction ? '#'.$position->lastExitTransaction->id : '—' }}</p>
                    </div>
                    @if($position->lastExitTransaction)
                        <a href="{{ route('admin.stocks.transactions.show',$position->lastExitTransaction) }}" class="ui-btn ui-btn-secondary !h-8 !px-3">Open</a>
                    @endif
                </div>
            </div>
        </aside>
    </section>

    <section class="ui-panel mt-4 overflow-hidden">
        <div class="border-b border-border px-4 py-3">
            <p class="ui-kicker">Trade timeline</p>
            <h2 class="mt-1 text-sm font-semibold">Position history</h2>
            <p class="mt-1 text-[9px] text-muted-foreground">Entry, risk changes, partial exits and final settlement remain attached to this contract.</p>
        </div>

        <div class="divide-y divide-border">
            @forelse($position->events as $event)
                @php
                    $eventEt = $event->created_at?->copy()->setTimezone(\App\Services\MarketSessionService::TIMEZONE);
                    $eventPnl = (float)($event->profit_loss ?? 0);
                @endphp
                <div class="grid gap-3 px-4 py-3 md:grid-cols-[180px_minmax(0,1fr)_130px_130px_150px] md:items-center">
                    <div>
                        <p class="text-[10px] font-semibold">{{ ucfirst(str_replace('_',' ',$event->event_type)) }}</p>
                        <p class="mt-1 text-[9px] text-muted-foreground">{{ $eventEt?->format('M d, Y · H:i:s') }} ET</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-muted-foreground">{{ $event->note ?: 'Position event recorded.' }}</p>
                        <p class="mt-1 text-[9px] text-muted-foreground">Actor: {{ $event->actor?->name ?? ucfirst((string)$event->actor_type) }}</p>
                    </div>
                    <div>
                        <p class="text-[8px] uppercase tracking-[.1em] text-muted-foreground">Quantity</p>
                        <p class="mt-1 text-[10px] font-semibold tabular-nums">{{ $event->quantity !== null ? number_format((float)$event->quantity,6) : '—' }}</p>
                    </div>
                    <div>
                        <p class="text-[8px] uppercase tracking-[.1em] text-muted-foreground">Price</p>
                        <p class="mt-1 text-[10px] font-semibold tabular-nums">{{ $event->price !== null ? currency_symbol().number_format((float)$event->price,2) : '—' }}</p>
                    </div>
                    <div>
                        <p class="text-[8px] uppercase tracking-[.1em] text-muted-foreground">Event P/L</p>
                        <p class="mt-1 text-[10px] font-semibold tabular-nums {{ $eventPnl > 0 ? 'text-emerald-600' : ($eventPnl < 0 ? 'text-red-600' : '') }}">
                            {{ $event->profit_loss !== null ? (($eventPnl >= 0 ? '+' : '-').currency_symbol().number_format(abs($eventPnl),2)) : '—' }}
                        </p>
                    </div>
                </div>
            @empty
                <div class="px-4 py-10 text-center text-xs text-muted-foreground">No position events recorded.</div>
            @endforelse
        </div>
    </section>
</div>
</x-admin-layout>
