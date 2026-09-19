<x-user-layout>
    <x-slot name="header">Overview</x-slot>

    @php
        $accountAlerts = auth()->user()->accountAlerts()->active()->latest()->limit(4)->get();
        $primarySignalDelivery = $dashboardSignals->first();
        $primarySignal = $primarySignalDelivery?->signal;
        $primaryTarget = $primarySignal?->targets?->sortBy('sequence')->first();
        $returnPositive = $totalReturn >= 0;
    @endphp

    <style>
        [data-overview-redesign] .overview-grid { display: grid; gap: 1rem; }
        [data-overview-redesign] .overview-hero-grid { display: grid; gap: 1rem; }
        [data-overview-redesign] .overview-command-grid { display: grid; gap: 1rem; }
        [data-overview-redesign] .overview-lower-grid { display: grid; gap: 1rem; }
        [data-overview-redesign] .overview-metrics { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .625rem; }
        [data-overview-redesign] .signal-metrics { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .5rem; }
        [data-overview-redesign] .signal-toggle-open { display: inline-flex; }
        [data-overview-redesign] .signal-toggle-closed { display: none; }
        [data-overview-redesign] details[open] .signal-toggle-open { display: none; }
        [data-overview-redesign] details[open] .signal-toggle-closed { display: inline-flex; }
        [data-overview-redesign] details[open] .signal-toggle-icon { transform: rotate(180deg); }
        [data-overview-redesign] .overview-scroll-safe { min-width: 0; }
        [data-overview-redesign] .overview-quick-actions { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .5rem; }
        [data-overview-redesign] .overview-quick-actions .ui-btn { width: 100%; min-width: 0; white-space: nowrap; }
        [data-overview-redesign] summary::-webkit-details-marker { display: none; }
        [data-overview-redesign] summary::marker { content: ""; }
        @media (max-width: 359px) {
            [data-overview-redesign] .overview-quick-actions { grid-template-columns: minmax(0, 1fr); }
        }
        @media (min-width: 640px) {
            [data-overview-redesign] .overview-metrics { grid-template-columns: repeat(4, minmax(0, 1fr)); }
            [data-overview-redesign] .signal-metrics { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        @media (min-width: 1024px) {
            [data-overview-redesign] .overview-hero-grid { grid-template-columns: minmax(0, 1.55fr) minmax(290px, .45fr); }
            [data-overview-redesign] .overview-command-grid { grid-template-columns: minmax(0, 1.25fr) minmax(300px, .75fr); }
            [data-overview-redesign] .overview-lower-grid { grid-template-columns: minmax(0, .8fr) minmax(0, 1.2fr); }
            [data-overview-redesign] .signal-metrics { grid-template-columns: repeat(5, minmax(0, 1fr)); }
        }
    </style>

    <div class="ui-page max-w-[1440px]" data-overview-redesign>
        <section class="ui-page-header">
            <div class="min-w-0">
                <p class="ui-kicker">Account overview</p>
                <h1 class="ui-heading">Welcome back, {{ auth()->user()->name }}</h1>
                <p class="ui-lead">A focused view of your cash, portfolio, Signals and recent account activity.</p>
            </div>

            <div class="ui-header-actions">
                <form method="POST" action="{{ route('account.preferences.currency') }}" class="flex items-center gap-2">
                    @csrf @method('PATCH')
                    <select name="currency" class="ui-input !h-9 !w-auto min-w-[100px] text-xs" onchange="this.form.submit()">
                        @foreach(['USD','NGN','EUR','GBP','CAD','AUD','CHF','JPY','CNY','INR','ZAR','SGD'] as $code)
                            <option value="{{ $code }}" @selected(auth()->user()->currency===$code)>{{ $code }}</option>
                        @endforeach
                    </select>
                </form>
                <a href="{{ route('money.activity') }}" class="ui-btn ui-btn-secondary"><i data-lucide="receipt-text" class="h-4 w-4"></i>Activity</a>
                <a href="{{ route('profile.edit') }}" class="ui-btn ui-btn-secondary"><i data-lucide="circle-user-round" class="h-4 w-4"></i>Account</a>
            </div>
        </section>

        <section class="overview-hero-grid mb-4">
            <article class="ui-panel overflow-hidden">
                <div class="p-5 sm:p-6">
                    <div class="flex flex-col gap-5">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="ui-kicker">Financial position</p>
                                <p class="mt-2 text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">{{ format_currency($totalAssets) }}</p>
                                <p class="mt-1 text-xs text-muted-foreground">Total assets across cash and current portfolio value.</p>
                            </div>
                            <div class="rounded-xl border border-border bg-muted/20 px-4 py-3 sm:text-right">
                                <p class="ui-label">Total return</p>
                                <p class="mt-1 text-lg font-semibold {{ $returnPositive ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $returnPositive ? '+' : '-' }}{{ format_currency(abs($totalReturn)) }}
                                </p>
                                <p class="text-[11px] {{ $returnPositive ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $returnPositive ? '+' : '' }}{{ number_format($returnPercentage, 2) }}%
                                </p>
                            </div>
                        </div>

                        <div class="overview-metrics">
                            <div class="rounded-xl border border-border bg-muted/15 p-3.5">
                                <p class="ui-label">Available cash</p>
                                <p class="mt-1.5 truncate text-sm font-semibold tabular-nums">{{ format_currency($availableBalance) }}</p>
                            </div>
                            <div class="rounded-xl border border-border bg-muted/15 p-3.5">
                                <p class="ui-label">Portfolio</p>
                                <p class="mt-1.5 truncate text-sm font-semibold tabular-nums">{{ format_currency($portfolioValue) }}</p>
                            </div>
                            <div class="rounded-xl border border-border bg-muted/15 p-3.5">
                                <p class="ui-label">Invested capital</p>
                                <p class="mt-1.5 truncate text-sm font-semibold tabular-nums">{{ format_currency($investedCapital) }}</p>
                            </div>
                            <div class="rounded-xl border border-border bg-muted/15 p-3.5">
                                <p class="ui-label">Reserved</p>
                                <p class="mt-1.5 truncate text-sm font-semibold tabular-nums">{{ format_currency($reservedBalance) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </article>

            <article class="ui-panel p-5 sm:p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="ui-kicker">Quick actions</p>
                        <h2 class="mt-1 text-lg font-semibold">Move money</h2>
                        <p class="mt-1 text-xs leading-5 text-muted-foreground">Common account actions without leaving your overview.</p>
                    </div>
                    <div class="ui-metric-icon"><i data-lucide="wallet-cards" class="h-5 w-5"></i></div>
                </div>
                <div class="overview-quick-actions mt-5">
                    <a href="{{ route('money.add') }}" class="ui-btn ui-btn-primary justify-center" title="Add money"><i data-lucide="plus" class="h-4 w-4"></i>Deposit</a>
                    <a href="{{ route('money.withdraw') }}" class="ui-btn ui-btn-secondary justify-center"><i data-lucide="arrow-up-right" class="h-4 w-4"></i>Withdraw</a>
                    <a href="{{ route('money.send') }}" class="ui-btn ui-btn-secondary justify-center"><i data-lucide="arrow-right-left" class="h-4 w-4"></i>Transfer</a>
                    <a href="{{ route('account.history') }}" class="ui-btn ui-btn-secondary justify-center"><i data-lucide="history" class="h-4 w-4"></i>History</a>
                </div>
                @if($pendingTransactions > 0)
                    <div class="mt-4 rounded-xl border border-amber-500/20 bg-amber-500/[.05] px-3.5 py-3 text-xs text-muted-foreground">
                        <span class="font-semibold text-foreground">{{ number_format($pendingTransactions) }}</span> transaction{{ $pendingTransactions === 1 ? '' : 's' }} awaiting completion.
                    </div>
                @endif
            </article>
        </section>

        @if($accountAlerts->isNotEmpty())
            <section class="mb-4 ui-panel overflow-hidden">
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

        <section class="overview-command-grid mb-4">
            <details class="ui-panel overflow-hidden" data-dashboard-signals open>
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 sm:px-6">
                    <div class="min-w-0">
                        <p class="ui-kicker">Signal intelligence</p>
                        <div class="mt-1 flex flex-wrap items-center gap-2">
                            <h2 class="text-lg font-semibold">Your Signals</h2>
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

            <article class="ui-panel overflow-hidden">
                <div class="border-b border-border px-5 py-4 sm:px-6">
                    <p class="ui-kicker">Membership access</p>
                    <h2 class="mt-1 text-lg font-semibold">Account coverage</h2>
                    <p class="mt-1 text-xs text-muted-foreground">Your current membership state and product access.</p>
                </div>
                <div class="p-5 sm:p-6">
                    <div class="flex items-start gap-4">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-border bg-muted/30"><i data-lucide="badge-check" class="h-5 w-5"></i></div>
                        <div class="min-w-0 flex-1">
                            @if($activeMemberships->isNotEmpty())
                                <p class="text-xl font-semibold">{{ $activeMemberships->count() }} active membership{{ $activeMemberships->count() === 1 ? '' : 's' }}</p>
                                <p class="mt-1 text-sm text-muted-foreground">{{ $activeMemberships->map(fn($membership) => $membership->plan?->type?->name)->filter()->implode(' · ') }}</p>
                            @elseif($membershipStatuses->isNotEmpty())
                                <p class="text-xl font-semibold">Membership history available</p>
                                <p class="mt-1 text-sm text-muted-foreground">Review your latest membership records and available plans.</p>
                            @else
                                <p class="text-xl font-semibold">No active membership</p>
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
        </section>

        <section class="overview-lower-grid mb-4">
            <article class="ui-panel p-5 sm:p-6">
                <div class="flex items-start justify-between gap-4">
                    <div><p class="ui-kicker">Asset allocation</p><h2 class="mt-1 text-lg font-semibold">Where your assets sit</h2></div>
                    <a href="{{ route('portfolio.index') }}" class="ui-btn ui-btn-ghost ui-btn-sm">Portfolio<i data-lucide="arrow-right" class="h-4 w-4"></i></a>
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

            <article class="ui-panel overflow-hidden">
                <div class="flex items-center justify-between gap-4 border-b border-border px-5 py-4 sm:px-6">
                    <div><p class="ui-kicker">Recent activity</p><h2 class="mt-1 text-lg font-semibold">Latest transactions</h2></div>
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
        </section>

        <section class="grid gap-4 md:grid-cols-3">
            <article class="ui-panel p-5 sm:p-6">
                <div class="flex items-center justify-between gap-4"><div><p class="ui-kicker">Cash flow</p><h2 class="mt-1 text-lg font-semibold">Credits & debits</h2></div><i data-lucide="landmark" class="h-5 w-5 text-muted-foreground"></i></div>
                <div class="mt-5 grid grid-cols-2 gap-3">
                    <div class="rounded-xl border border-border bg-muted/15 p-3"><p class="ui-label">Credits</p><p class="mt-1 text-sm font-semibold text-emerald-600 dark:text-emerald-400">+{{ format_currency($totalCredits) }}</p></div>
                    <div class="rounded-xl border border-border bg-muted/15 p-3"><p class="ui-label">Debits</p><p class="mt-1 text-sm font-semibold text-red-600 dark:text-red-400">-{{ format_currency($totalDebits) }}</p></div>
                </div>
            </article>

            <article class="ui-panel p-5 sm:p-6">
                <div class="flex items-center justify-between gap-4"><div><p class="ui-kicker">Stock portfolio</p><h2 class="mt-1 text-lg font-semibold">{{ format_currency($stockValue) }}</h2><p class="mt-1 text-xs text-muted-foreground">{{ $stockHoldings->count() }} active position{{ $stockHoldings->count() === 1 ? '' : 's' }}</p></div><i data-lucide="candlestick-chart" class="h-5 w-5 text-muted-foreground"></i></div>
                <a href="{{ route('trading.portfolio') }}" class="ui-btn ui-btn-secondary ui-btn-sm mt-5">View stock portfolio</a>
            </article>

            <article class="ui-panel p-5 sm:p-6">
                <div class="flex items-center justify-between gap-4"><div><p class="ui-kicker">Investment plans</p><h2 class="mt-1 text-lg font-semibold">{{ format_currency($investmentValue) }}</h2><p class="mt-1 text-xs text-muted-foreground">{{ $investmentHoldings->count() }} active holding{{ $investmentHoldings->count() === 1 ? '' : 's' }}</p></div><i data-lucide="pie-chart" class="h-5 w-5 text-muted-foreground"></i></div>
                <a href="{{ route('portfolio.index') }}" class="ui-btn ui-btn-secondary ui-btn-sm mt-5">View investments</a>
            </article>
        </section>
    </div>
</x-user-layout>
