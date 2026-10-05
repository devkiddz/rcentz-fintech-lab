<x-user-layout>
    <x-slot name="header">Overview</x-slot>
<div data-account-async-feedback role="status" aria-live="polite" hidden class="mx-4 my-3 rounded-xl border border-border p-3 text-sm"></div>
<div data-account-async="user/dashboard">

    @php
        $accountAlerts = auth()->user()->accountAlerts()->active()->latest()->limit(4)->get();
        $primarySignalDelivery = $dashboardSignals->first();
        $primarySignal = $primarySignalDelivery?->signal;
        $primaryTarget = $primarySignal?->targets?->sortBy('sequence')->first();
        $returnPositive = $totalReturn >= 0;
    @endphp

    <style>
        [data-customer-overview] .customer-action-strip,
        [data-customer-overview] .customer-metric-strip {
            display: flex; overflow-x: auto; overflow-y: hidden;
            -webkit-overflow-scrolling: touch; overscroll-behavior-inline: contain;
            scrollbar-width: thin; scroll-snap-type: x proximity;
        }
        [data-customer-overview] .customer-action-strip > * { flex: 0 0 15rem; scroll-snap-align: start; }
        [data-customer-overview] .customer-metric-strip { gap: .75rem; }
        [data-customer-overview] .customer-metric-strip > * { flex: 0 0 17rem; scroll-snap-align: start; }
        [data-customer-overview] .customer-content-grid { display: grid; gap: 1.25rem; margin-top: 1.25rem; }
        [data-customer-overview] .customer-column { min-width: 0; display: flex; flex-direction: column; gap: 1.25rem; }
        [data-customer-overview] .customer-column > * { min-width: 0; }
        [data-customer-overview] .signal-metrics { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: .5rem; }
        [data-customer-overview] .signal-toggle-open { display: inline-flex; }
        [data-customer-overview] .signal-toggle-closed { display: none; }
        [data-customer-overview] details[open] .signal-toggle-open { display: none; }
        [data-customer-overview] details[open] .signal-toggle-closed { display: inline-flex; }
        [data-customer-overview] details[open] .signal-toggle-icon { transform: rotate(180deg); }
        [data-customer-overview] summary::-webkit-details-marker { display: none; }
        [data-customer-overview] summary::marker { content: ''; }
        @media (min-width:640px) {
            [data-customer-overview] .signal-metrics { grid-template-columns: repeat(3,minmax(0,1fr)); }
        }
        @media (min-width:1024px) {
            [data-customer-overview] .customer-action-strip,
            [data-customer-overview] .customer-metric-strip {
                display: grid; grid-template-columns: repeat(4,minmax(0,1fr)); overflow: visible;
            }
            [data-customer-overview] .customer-action-strip > *,
            [data-customer-overview] .customer-metric-strip > * { min-width: 0; }
        }
        @media (min-width:1280px) {
            [data-customer-overview] .customer-content-grid { grid-template-columns: minmax(0,1.45fr) minmax(0,.55fr); }
            [data-customer-overview] .signal-metrics { grid-template-columns: repeat(5,minmax(0,1fr)); }
        }
        @media (prefers-reduced-motion:reduce) {
            [data-customer-overview] .customer-metric-strip > * { transition: none; transform: none; }
        }
    </style>

    <div class="ui-page max-w-[1600px]" data-customer-overview>
        <section class="flex flex-col gap-4 border-b border-border/60 pb-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="min-w-0">
                <h1 class="text-2xl font-semibold tracking-tight text-foreground">Welcome back, {{ auth()->user()->name }}</h1>
                <p class="mt-1.5 text-xs leading-5 text-muted-foreground">Start a trade, manage open positions, and track your money from one place.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <form method="POST" action="{{ route('account.preferences.currency') }}" class="flex items-center gap-2">
                    @csrf @method('PATCH')
                    <select name="currency" class="ui-input !h-9 !w-auto min-w-[100px] text-xs" onchange="this.form.submit()">
                        @foreach(['USD','NGN','EUR','GBP','CAD','AUD','CHF','JPY','CNY','INR','ZAR','SGD'] as $code)
                            <option value="{{ $code }}" @selected(auth()->user()->currency===$code)>{{ $code }}</option>
                        @endforeach
                    </select>
                </form>
                <a href="{{ route('instruments.index') }}" class="ui-btn ui-btn-primary ui-btn-sm"><i data-lucide="plus" class="h-4 w-4"></i>New trade</a>
                <a href="{{ route('money.activity') }}" class="ui-btn ui-btn-secondary ui-btn-sm"><i data-lucide="receipt-text" class="h-4 w-4"></i>Money activity</a>
                <a href="{{ route('profile.edit') }}" class="ui-btn ui-btn-secondary ui-btn-sm"><i data-lucide="circle-user-round" class="h-4 w-4"></i>Account</a>
            </div>
        </section>

        <section class="mt-5 overflow-hidden rounded-2xl border border-border bg-card" aria-label="Trading desk">
            <div class="flex flex-col gap-3 border-b border-border/70 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"><i data-lucide="candlestick-chart" class="h-5 w-5"></i></span><div><h2 class="text-sm font-semibold">Your trading desk</h2><p class="mt-1 text-[11px] text-muted-foreground">Open trades here. Manage and close them in Positions.</p></div></div>
                <div class="flex flex-wrap gap-2"><span class="rounded-full border border-border bg-muted/30 px-3 py-1 text-[10px] font-semibold">{{ $tradingDesk['marketplace']==='controlled' ? 'Market' : 'Live market' }}</span><span class="rounded-full border border-primary/25 bg-primary/10 px-3 py-1 text-[10px] font-semibold text-primary">{{ $tradingDesk['directional'] ? 'Long / short trading' : 'Asset trading' }}</span></div>
            </div>
            @if($tradingDesk['read_only'])
            <div class="border-b border-border/70 bg-muted/20 px-5 py-3 text-xs leading-5 text-muted-foreground">This account is read-only. You can review markets and history, but cannot submit trading actions.</div>
            @elseif($tradingDesk['verification_required'])
            <div class="flex flex-col gap-3 border-b border-amber-500/20 bg-amber-500/10 px-5 py-3 sm:flex-row sm:items-center sm:justify-between"><div><p class="text-xs font-semibold">Trading requires approved verification</p><p class="mt-1 text-[11px] text-muted-foreground">Your current verification status: {{ ucfirst($tradingDesk['verification_status']) }}.</p></div><a href="{{ route('profile.kyc') }}" class="ui-btn ui-btn-secondary ui-btn-sm">Review verification<i data-lucide="arrow-right" class="h-3.5 w-3.5"></i></a></div>
            @endif
            <nav class="grid divide-y divide-border/70 sm:grid-cols-2 sm:divide-y-0 lg:grid-cols-4" aria-label="Trading destinations">
                @foreach([
                    ['instruments.index','New trade',$tradingDesk['directional'] ? 'Choose a market, then open Long or Short.' : 'Choose an asset to buy or sell existing holdings.','plus'],
                    ['broker.positions','Open positions','Manage risk, reduce exposure or close a trade.','layers'],
                    ['broker.orders','Order history','Check completed, pending and failed orders.','receipt-text'],
                    ['broker.portfolio','Trading account','Review balances, funds and market exposure.','wallet-cards'],
                ] as [$destination,$title,$description,$icon])
                <a href="{{ route($destination) }}" class="group min-w-0 p-5 transition hover:bg-muted/25 focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary focus-visible:-outline-offset-2"><div class="flex items-center justify-between gap-3"><i data-lucide="{{ $icon }}" class="h-5 w-5 text-primary"></i><i data-lucide="arrow-up-right" class="h-4 w-4 text-muted-foreground"></i></div><p class="mt-3 text-xs font-semibold">{{ $title }}</p><p class="mt-1 text-[11px] leading-5 text-muted-foreground">{{ $description }}</p></a>
                @endforeach
            </nav>
            <div class="border-t border-border/70 px-5 py-3 text-[11px] leading-5 text-muted-foreground">
                @if($tradingDesk['directional'])
                <span class="font-semibold text-foreground">Buy = open Long. Sell = open Short.</span> To exit an existing trade, use Close in Positions.
                @else
                <span class="font-semibold text-foreground">Sell currently requires an owned holding.</span> Opening short positions is not available in this trading mode.
                @endif
                <p class="mt-1">{{ $tradingDesk['marketplace']==='live' ? 'Live execution follows market hours and requires an executable quote.' : 'Market execution uses configured instrument prices.' }}</p>
            </div>
        </section>

        <section class="mt-5 grid items-start gap-5 xl:grid-cols-2" aria-label="Trading activity">
            <div class="overflow-hidden rounded-2xl border border-border bg-card">
                <div class="flex items-center justify-between gap-3 border-b border-border/70 px-5 py-4"><div><h2 class="text-sm font-semibold">Open positions</h2><p class="mt-1 text-[10px] text-muted-foreground">{{ number_format($tradingDesk['open_count']) }} in the selected market</p></div><a href="{{ route('broker.positions') }}" class="ui-btn ui-btn-ghost ui-btn-sm">Manage</a></div>
                <div class="divide-y divide-border/70">
                    @forelse($tradingDesk['positions'] as $position)
                    @php $instrument=$position->marketInstrument ?? $position->stock?->marketInstrument; @endphp
                    <div class="flex items-center justify-between gap-3 px-5 py-3"><div class="min-w-0"><p class="text-xs font-semibold">{{ $instrument?->display_symbol ?? $position->stock?->symbol ?? 'Instrument' }} <span class="ml-1 text-[10px] text-muted-foreground">{{ ucfirst($position->direction ?: 'long') }}</span></p><p class="mt-1 text-[10px] text-muted-foreground">{{ number_format((float)$position->open_quantity,8) }} open units · {{ ucfirst(str_replace('_',' ',$position->status)) }}</p></div><a href="{{ route('broker.positions') }}" class="ui-btn ui-btn-secondary ui-btn-sm">Manage</a></div>
                    @empty
                    <div class="px-5 py-6"><p class="text-xs font-medium">No open positions in this market.</p><p class="mt-1 text-[11px] leading-5 text-muted-foreground">Only filled trades create exposure. Failed orders remain in order history.</p></div>
                    @endforelse
                </div>
            </div>
            <div class="overflow-hidden rounded-2xl border border-border bg-card">
                <div class="flex items-center justify-between gap-3 border-b border-border/70 px-5 py-4"><div><h2 class="text-sm font-semibold">Recent orders</h2><p class="mt-1 text-[10px] text-muted-foreground">Results and receipts, across both markets</p></div><a href="{{ route('broker.orders') }}" class="ui-btn ui-btn-ghost ui-btn-sm">View all</a></div>
                <div class="divide-y divide-border/70">
                    @forelse($tradingDesk['orders'] as $order)
                    @php
                        $intent=$order->execution?->metadata['order_intent'] ?? null;
                        $direction=$order->execution?->metadata['direction'] ?? null;
                        $failed=in_array($order->status,['failed','rejected'],true);
                    @endphp
                    <a href="{{ route('broker.orders.show',['publicId'=>$order->public_id]) }}" class="block px-5 py-3 transition hover:bg-muted/20"><div class="flex flex-wrap items-center justify-between gap-2"><p class="text-xs font-semibold">{{ $order->marketInstrument?->display_symbol ?? 'Instrument' }} · {{ $intent ? ucfirst($intent).' '.ucfirst($direction ?: '') : strtoupper($order->side) }}</p><span class="rounded-full border px-2 py-1 text-[9px] font-semibold {{ $failed ? 'border-red-500/25 bg-red-500/10 text-red-600' : ($order->status==='filled' ? 'border-emerald-500/25 bg-emerald-500/10 text-emerald-600' : 'border-border text-muted-foreground') }}">{{ ucfirst($order->status) }}</span></div><p class="mt-1 text-[10px] text-muted-foreground">{{ ucfirst($order->marketplace) }} · {{ $order->created_at?->format('M j · H:i') }} · {{ $failed ? 'Order did not fill. View receipt.' : 'View order receipt' }}</p></a>
                    @empty
                    <div class="px-5 py-6"><p class="text-xs font-medium">No orders yet.</p><p class="mt-1 text-[11px] text-muted-foreground">Your submitted orders and their results will appear here.</p></div>
                    @endforelse
                </div>
            </div>
        </section>

        <div class="mt-6 border-t border-border/70 pt-4"><h2 class="text-sm font-semibold">Assets &amp; money</h2><p class="mt-1 text-[11px] text-muted-foreground">Your wider account summary, deposits, withdrawals and transfers.</p></div>
        <section class="mt-5 overflow-hidden rounded-2xl border border-border bg-card">
            <div class="flex flex-col gap-3 border-b border-border/70 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3"><span class="flex h-9 w-9 items-center justify-center rounded-xl bg-red-500/10 text-red-500"><i data-lucide="wallet-cards" class="h-4 w-4"></i></span><div><h2 class="text-sm font-semibold">Account actions</h2><p class="mt-0.5 text-[10px] text-muted-foreground">Manage your money and review account activity.</p></div></div>
                @if($pendingTransactions > 0)<span class="rounded-full border border-amber-500/20 bg-amber-500/10 px-3 py-1 text-[10px] font-semibold">{{ number_format($pendingTransactions) }} pending transaction{{ $pendingTransactions === 1 ? '' : 's' }}</span>@endif
            </div>
            <nav class="customer-action-strip gap-px bg-border" aria-label="Account actions">
                @foreach([
                    ['money.add','Deposit','Add funds to your account','plus'],
                    ['money.withdraw','Withdraw','Request a withdrawal','arrow-up-right'],
                    ['money.send','Transfer','Send money securely','arrow-right-left'],
                    ['account.history','Money history','Review deposits, withdrawals and transfers','history'],
                ] as [$destination,$title,$description,$icon])
                <a href="{{ route($destination) }}" class="flex items-center gap-3 bg-card px-5 py-4 hover:bg-muted/25 focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary focus-visible:-outline-offset-2"><i data-lucide="{{ $icon }}" class="h-4 w-4 shrink-0 text-primary"></i><span class="min-w-0"><span class="block text-xs font-semibold">{{ $title }}</span><span class="mt-1 block text-[10px] text-muted-foreground">{{ $description }}</span></span><i data-lucide="chevron-right" class="ml-auto h-3.5 w-3.5 shrink-0 text-muted-foreground"></i></a>
                @endforeach
            </nav>
        </section>
        <section class="customer-metric-strip mt-5" aria-label="Account financial summary">
            @foreach([
                ['Total assets',format_currency($totalAssets),'Cash and current portfolio value','wallet','portfolio.index'],
                ['Available cash',format_currency($availableBalance),'Available after reserved funds','wallet-cards','money.activity'],
                ['Portfolio',format_currency($portfolioValue),'Current value of your holdings','chart-no-axes-combined','portfolio.index'],
                ['Total return',($returnPositive ? '+' : '-').format_currency(abs($totalReturn)),($returnPositive ? '+' : '').number_format($returnPercentage,2).'% return','trending-up','portfolio.index'],
            ] as [$label,$value,$caption,$icon,$destination])
                <a href="{{ route($destination) }}" class="group relative overflow-hidden rounded-2xl border border-border bg-card p-5 transition hover:-translate-y-0.5 hover:border-foreground/15">
                    <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-primary/10 blur-2xl"></div>
                    <div class="relative"><div class="flex items-start justify-between gap-4"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary"><i data-lucide="{{ $icon }}" class="h-4 w-4"></i></span><i data-lucide="arrow-up-right" class="h-4 w-4 text-muted-foreground"></i></div><div class="mt-5"><p class="text-[9px] font-semibold uppercase tracking-widest text-muted-foreground">{{ $label }}</p><p class="mt-1 break-words text-2xl font-semibold tabular-nums {{ $label==='Total return' ? ($returnPositive ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400') : '' }}">{{ $value }}</p><p class="mt-2 text-[10px] text-muted-foreground">{{ $caption }}</p></div></div>
                </a>
            @endforeach
        </section>
        <section class="customer-metric-strip mt-3" aria-label="Capital and holdings">
            @foreach([
                ['Invested capital',format_currency($investedCapital),'Capital allocated to your portfolio','briefcase-business','portfolio.index'],
                ['Reserved funds',format_currency($reservedBalance),'Included in your account balance','lock-keyhole','money.activity'],
                ['Stock holdings',format_currency($stockValue),$stockHoldings->count().' holdings','candlestick-chart','broker.portfolio'],
                ['Investment plans',format_currency($investmentValue),$investmentHoldings->count().' active holdings','pie-chart','portfolio.index'],
            ] as [$label,$value,$caption,$icon,$destination])
                <a href="{{ route($destination) }}" class="rounded-2xl border border-border bg-card p-4 transition hover:border-foreground/15"><div class="flex items-center justify-between gap-3"><p class="text-[9px] font-semibold uppercase tracking-widest text-muted-foreground">{{ $label }}</p><i data-lucide="{{ $icon }}" class="h-4 w-4 text-muted-foreground"></i></div><p class="mt-3 break-words text-lg font-semibold tabular-nums">{{ $value }}</p><p class="mt-1 text-[10px] text-muted-foreground">{{ $caption }}</p></a>
            @endforeach
        </section>
        <div class="customer-content-grid">
            <div class="customer-column">
        @if($accountAlerts->isNotEmpty())
            <section class="ui-panel overflow-hidden">
                <div class="flex items-center justify-between gap-4 border-b border-border px-5 py-3.5 sm:px-6">
                    <div><p class="ui-kicker">Account alerts</p><h2 class="mt-1 text-sm font-semibold">For your attention</h2></div>
                    <span class="rounded-full border border-border bg-muted/30 px-2.5 py-1 text-[9px] font-semibold uppercase">{{ $accountAlerts->count() }}</span>
                </div>
                <div class="divide-y divide-border">
                    @foreach($accountAlerts as $alert)
                        <article class="flex flex-col gap-3 px-5 py-3.5 sm:flex-row sm:items-center sm:justify-between sm:px-6 {{ $alert->priority === 'urgent' ? 'bg-red-500/[.035]' : ($alert->priority === 'important' ? 'bg-amber-500/[.035]' : '') }}">
                            <div class="flex min-w-0 items-start gap-3">
                                <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-border bg-card"><i data-lucide="{{ $alert->type === 'withdrawal_token' ? 'key-round' : 'megaphone' }}" class="h-4 w-4"></i></div>
                                <div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><p class="truncate text-sm font-semibold">{{ $alert->title }}</p><span class="rounded-full bg-muted px-2 py-0.5 text-[8px] font-semibold uppercase">{{ $alert->priority }}</span></div><p class="mt-1 text-xs leading-5 text-muted-foreground">{{ $alert->message }}</p></div>
                            </div>
                            <div class="flex shrink-0 flex-wrap gap-2">
                                @if($alert->action_url)<a href="{{ $alert->action_url }}" class="ui-btn ui-btn-secondary ui-btn-sm">{{ $alert->action_label ?: 'Open' }}</a>@endif
                                @if(!$alert->read_at)<form method="POST" action="{{ route('account-alerts.read',$alert) }}">@csrf<button class="ui-btn ui-btn-ghost ui-btn-sm">Mark read</button></form>@endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

            @if($dashboardSignals->isNotEmpty())
            <details class="ui-panel overflow-hidden" data-dashboard-signals open>
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 sm:px-6">
                    <div class="min-w-0">
                        <p class="ui-kicker">Signal intelligence</p>
                        <div class="mt-1 flex flex-wrap items-center gap-2">
                            <h2 class="text-sm font-semibold">Your Signals</h2>
                            @if(($signalSummary['current'] ?? 0) > 0)
                                <span class="rounded-full bg-primary/10 px-2 py-0.5 text-[9px] font-semibold uppercase tracking-[.1em] text-primary">{{ $signalSummary['current'] }} current</span>
                            @endif
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">Current Signals delivered specifically to your account.</p>
                    </div>
                    <span class="inline-flex shrink-0 items-center gap-2 text-xs font-medium text-muted-foreground">
                        <span class="signal-toggle-open">Expand</span><span class="signal-toggle-closed">Collapse</span><i data-lucide="chevron-down" class="signal-toggle-icon h-4 w-4 transition-transform"></i>
                    </span>
                </summary>

                <div class="border-t border-border">
                    @if($dashboardSignals->isEmpty())
                        <div class="px-5 py-5 sm:px-6">
                            <div class="flex items-center gap-3 rounded-xl border border-border bg-muted/15 p-4"><div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-border bg-background"><i data-lucide="radio-tower" class="h-4 w-4 text-muted-foreground"></i></div><div><p class="text-sm font-medium">No current Signals</p><p class="mt-0.5 text-xs text-muted-foreground">New deliveries will appear here automatically.</p></div></div>
                        </div>
                    @else
                        <div class="space-y-3 p-3 sm:p-4">
                            @foreach($dashboardSignals as $delivery)
                                @php
                                    $signal = $delivery->signal;
                                    $firstTarget = $signal?->targets?->sortBy('sequence')->first();
                                    $precision = $signal?->price_precision ?? 2;
                                    $deliveredLabel = optional($delivery->delivered_at)->format('M d · H:i');
                                @endphp
                                <article class="overview-scroll-safe rounded-xl border border-border bg-background p-4">
                                    <div class="flex flex-col gap-4">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="text-base font-semibold">{{ $signal?->instrument_symbol ?? '—' }}</span>
                                                    <span class="text-[10px] font-bold {{ strtoupper((string)($signal?->direction))==='BUY' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">{{ strtoupper((string)($signal?->direction)) }}</span>
                                                    <span class="rounded-full border border-border px-2 py-0.5 text-[8px] font-semibold uppercase">{{ strtoupper((string)($signal?->status)) }}</span>
                                                    @if(!$delivery->read_at)<span class="h-2 w-2 rounded-full bg-primary" title="New Signal"></span>@endif
                                                </div>
                                                <p class="mt-1 text-[11px] text-muted-foreground">{{ strtoupper((string)($signal?->marketplace)) }} · {{ strtoupper((string)($signal?->timeframe)) }} · Delivered {{ $deliveredLabel }}</p>
                                            </div>
                                            <a href="{{ route('signals.show',$signal) }}" class="ui-btn ui-btn-secondary ui-btn-sm shrink-0">Open<i data-lucide="arrow-up-right" class="h-3.5 w-3.5"></i></a>
                                        </div>

                                        <div class="signal-metrics">
                                            <div class="rounded-lg border border-border bg-muted/15 p-3"><p class="ui-label">Entry</p><p class="mt-1 truncate text-xs font-semibold tabular-nums">{{ number_format((float)$signal->entry_min,$precision) }} – {{ number_format((float)$signal->entry_max,$precision) }}</p></div>
                                            <div class="rounded-lg border border-border bg-muted/15 p-3"><p class="ui-label">Stop</p><p class="mt-1 truncate text-xs font-semibold tabular-nums">{{ number_format((float)$signal->stop_loss,$precision) }}</p></div>
                                            <div class="rounded-lg border border-border bg-muted/15 p-3"><p class="ui-label">Strength</p><p class="mt-1 truncate text-xs font-semibold">{{ str_replace('_',' ',strtoupper((string)$signal->strength)) }}</p></div>
                                            <div class="rounded-lg border border-border bg-muted/15 p-3"><p class="ui-label">TP1</p><p class="mt-1 truncate text-xs font-semibold tabular-nums">{{ $firstTarget ? number_format((float)$firstTarget->price,$precision) : '—' }}</p></div>
                                            <div class="rounded-lg border border-border bg-muted/15 p-3"><p class="ui-label">Confluence</p><p class="mt-1 truncate text-xs font-semibold tabular-nums">{{ $signal->confluence_score !== null ? number_format((float)$signal->confluence_score,2).'%' : '—' }}</p></div>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                    <div class="flex justify-end border-t border-border px-5 py-3 sm:px-6"><a href="{{ route('signals.index') }}" class="ui-btn ui-btn-ghost ui-btn-sm">View all Signals<i data-lucide="arrow-right" class="h-4 w-4"></i></a></div>
                </div>
            </details>
            @endif

            <article class="ui-panel overflow-hidden">
                <div class="flex items-center justify-between gap-4 border-b border-border px-5 py-4 sm:px-6">
                    <div><p class="ui-kicker">Recent activity</p><h2 class="mt-1 text-sm font-semibold">Latest transactions</h2></div>
                    <a href="{{ route('money.activity') }}" class="ui-btn ui-btn-ghost ui-btn-sm">View all</a>
                </div>
                @if($recentTransactions->isEmpty())
                    <div class="ui-empty-state"><div class="ui-empty-icon"><i data-lucide="receipt-text" class="h-5 w-5"></i></div><h3 class="font-medium">No transactions yet</h3><p class="mt-1 text-sm text-muted-foreground">Your account activity will appear here.</p></div>
                @else
                    <div class="divide-y divide-border">
                        @foreach($recentTransactions->take(6) as $transaction)
                            <div class="flex items-center gap-3 px-5 py-3.5 sm:px-6">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-border bg-muted/30"><i data-lucide="{{ $transaction->is_credit ? 'arrow-down-left' : 'arrow-up-right' }}" class="h-4 w-4 {{ $transaction->is_credit ? 'text-emerald-500' : 'text-red-500' }}"></i></div>
                                <div class="min-w-0 flex-1"><p class="truncate text-sm font-medium">{{ $transaction->activity_label }}</p><div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-[10px] text-muted-foreground"><span>{{ $transaction->created_at->format('M d, Y · h:i A') }}</span><span>•</span><span class="capitalize">{{ $transaction->status }}</span></div></div>
                                <div class="shrink-0 text-right"><p class="text-sm font-semibold {{ $transaction->is_credit ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">{{ $transaction->signed_formatted_amount }}</p><p class="mt-1 text-[10px] text-muted-foreground">{{ $transaction->direction_label }}</p></div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </article>

            </div>
            <aside class="customer-column" aria-label="Account coverage and allocation">
            <article class="ui-panel overflow-hidden">
                <div class="border-b border-border px-5 py-4 sm:px-6">
                    <p class="ui-kicker">Membership access</p>
                    <h2 class="mt-1 text-sm font-semibold">Account coverage</h2>
                    <p class="mt-1 text-xs text-muted-foreground">Your current membership state and product access.</p>
                </div>
                <div class="p-5 sm:p-6">
                    <div class="flex items-start gap-4">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-border bg-muted/30"><i data-lucide="badge-check" class="h-5 w-5"></i></div>
                        <div class="min-w-0 flex-1">
                            @if($activeMemberships->isNotEmpty())
                                <p class="text-base font-semibold">{{ $activeMemberships->count() }} active membership{{ $activeMemberships->count() === 1 ? '' : 's' }}</p>
                                <p class="mt-1 text-sm text-muted-foreground">{{ $activeMemberships->map(fn($membership) => $membership->plan?->type?->name)->filter()->implode(' · ') }}</p>
                            @elseif($membershipStatuses->isNotEmpty())
                                <p class="text-base font-semibold">Membership history available</p>
                                <p class="mt-1 text-sm text-muted-foreground">Review your latest membership records and available plans.</p>
                            @else
                                <p class="text-base font-semibold">No active membership</p>
                                <p class="mt-1 text-sm text-muted-foreground">Explore available access plans when you are ready.</p>
                            @endif
                        </div>
                    </div>
                    <div class="mt-5 grid grid-cols-2 gap-2">
                        <a href="{{ route('memberships.index') }}" class="ui-btn ui-btn-secondary justify-center">Memberships</a>
                        <a href="{{ route('signals.index') }}" class="ui-btn ui-btn-secondary justify-center">Signals</a>
                    </div>
                </div>
            </article>
            @if(collect($allocation)->sum('value') > 0)
            <article class="ui-panel p-5 sm:p-6">
                <div class="flex items-start justify-between gap-4">
                    <div><p class="ui-kicker">Asset allocation</p><h2 class="mt-1 text-sm font-semibold">Where your assets sit</h2></div>
                    <a href="{{ route('portfolio.index') }}" class="ui-btn ui-btn-ghost ui-btn-sm">Investments<i data-lucide="arrow-right" class="h-4 w-4"></i></a>
                </div>
                <div class="mt-5 space-y-4">
                    @foreach($allocation as $item)
                        <div>
                            <div class="mb-2 flex items-center justify-between gap-4">
                                <div class="min-w-0"><p class="text-sm font-medium">{{ $item['label'] }}</p><p class="truncate text-xs text-muted-foreground">{{ format_currency($item['value']) }}</p></div>
                                <span class="text-sm font-semibold tabular-nums">{{ number_format($item['percentage'], 1) }}%</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-muted"><div class="h-full rounded-full bg-foreground/70" style="width: {{ min(100, max(0, $item['percentage'])) }}%"></div></div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-6 grid grid-cols-2 gap-3">
                    <div class="rounded-xl border border-border bg-muted/15 p-3.5"><p class="ui-label">Stocks</p><p class="mt-1 text-sm font-semibold">{{ format_currency($stockValue) }}</p><p class="mt-1 text-[10px] text-muted-foreground">{{ $stockHoldings->count() }} active position{{ $stockHoldings->count() === 1 ? '' : 's' }}</p></div>
                    <div class="rounded-xl border border-border bg-muted/15 p-3.5"><p class="ui-label">Investments</p><p class="mt-1 text-sm font-semibold">{{ format_currency($investmentValue) }}</p><p class="mt-1 text-[10px] text-muted-foreground">{{ $investmentHoldings->count() }} active holding{{ $investmentHoldings->count() === 1 ? '' : 's' }}</p></div>
                </div>
            </article>
            @endif

            @if((float)$totalCredits > 0 || (float)$totalDebits > 0)
            <article class="ui-panel p-5 sm:p-6">
                <div class="flex items-center justify-between gap-4"><div><p class="ui-kicker">Cash flow</p><h2 class="mt-1 text-sm font-semibold">Credits & debits</h2></div><i data-lucide="landmark" class="h-5 w-5 text-muted-foreground"></i></div>
                <div class="mt-5 grid grid-cols-2 gap-3">
                    <div class="rounded-xl border border-border bg-muted/15 p-3"><p class="ui-label">Credits</p><p class="mt-1 text-sm font-semibold text-emerald-600 dark:text-emerald-400">+{{ format_currency($totalCredits) }}</p></div>
                    <div class="rounded-xl border border-border bg-muted/15 p-3"><p class="ui-label">Debits</p><p class="mt-1 text-sm font-semibold text-red-600 dark:text-red-400">-{{ format_currency($totalDebits) }}</p></div>
                </div>
            </article>
            @endif

            </aside>
        </div>
    </div>
</div>
</x-user-layout>
