<x-user-layout>
    <x-slot name="header">Overview</x-slot>

    <div class="ui-page max-w-[1440px]">
        <section class="ui-page-header">
            <div>
                <p class="ui-kicker">Financial overview</p>
                <h1 class="ui-heading">Welcome back, {{ auth()->user()->name }}</h1>
                <p class="ui-lead">Your cash, holdings and account activity in one place.</p>
            </div>

            <div class="ui-header-actions">
                <form method="POST" action="{{ route('account.preferences.currency') }}" class="flex items-center gap-2">
                    @csrf @method('PATCH')
                    <select name="currency" class="ui-input !h-9 !w-auto min-w-[110px] text-xs" onchange="this.form.submit()">
                        @foreach(['USD','NGN','EUR','GBP','CAD','AUD','CHF','JPY','CNY','INR','ZAR','SGD'] as $code)<option value="{{ $code }}" @selected(auth()->user()->currency===$code)>{{ $code }}</option>@endforeach
                    </select>
                </form>
                <a href="{{ route('account.history') }}" class="ui-btn ui-btn-secondary"><i data-lucide="history" class="h-4 w-4"></i>History</a>
                <a href="{{ route('profile.edit') }}" class="ui-btn ui-btn-secondary">
                    <i data-lucide="circle-user-round" class="h-4 w-4"></i>
                    Account
                </a>
                <a href="{{ route('money.activity') }}" class="ui-btn ui-btn-secondary">
                    <i data-lucide="receipt-text" class="h-4 w-4"></i>
                    Transactions
                </a>
                <a href="{{ route('money.add') }}" class="ui-btn ui-btn-primary">
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    Add money
                </a>
            </div>
        </section>

        @php
            $vipDisplayStatus = null;
            if ($vipMembership) {
                $vipDisplayStatus = $vipMembership->status === 'active' && ! $vipMembership->is_active
                    ? 'expired'
                    : $vipMembership->status;
            }
        @endphp
        <section class="mb-4 ui-panel overflow-hidden">
            <div class="flex flex-col gap-4 p-5 sm:p-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex min-w-0 items-start gap-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-amber-500/20 bg-amber-500/10 text-amber-600 dark:text-amber-400"><i data-lucide="crown" class="h-5 w-5"></i></div>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="ui-kicker">VIP Membership</p>
                            @if($vipMembership)
                                <span class="rounded-full px-2 py-0.5 text-[9px] font-semibold uppercase {{ $vipDisplayStatus === 'active' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : ($vipDisplayStatus === 'pending' ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400' : 'bg-muted text-muted-foreground') }}">{{ $vipDisplayStatus }}</span>
                            @endif
                        </div>
                        @if($vipMembership)
                            <h2 class="mt-1 text-lg font-semibold text-foreground">{{ $vipMembership->plan?->name ?? 'VIP Membership' }}</h2>
                            <p class="mt-1 text-sm text-muted-foreground">{{ $vipMembership->is_active ? ($vipMembership->ends_at ? 'Active until '.$vipMembership->ends_at->format('M j, Y') : 'Active with no fixed expiry') : 'Membership record available in your VIP workspace.' }}</p>
                        @else
                            <h2 class="mt-1 text-lg font-semibold text-foreground">Unlock premium platform access</h2>
                            <p class="mt-1 text-sm text-muted-foreground">You do not currently have a VIP membership.</p>
                        @endif
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    @if($vipMembership?->is_active)
                        <span class="ui-btn ui-btn-ghost ui-btn-sm pointer-events-none"><i data-lucide="badge-check" class="h-4 w-4"></i>{{ $vipEntitlements->count() }} entitlements</span>
                    @endif
                    <a href="{{ route('memberships.vip.index') }}" class="ui-btn ui-btn-secondary"><i data-lucide="arrow-right" class="h-4 w-4"></i>Manage VIP</a>
                </div>
            </div>
        </section>

        @php
            $accountAlerts = auth()->user()->accountAlerts()->active()->latest()->limit(5)->get();
        @endphp
        @if($accountAlerts->isNotEmpty())
        <section class="mb-4 ui-panel overflow-hidden">
            <div class="flex items-center justify-between border-b border-border px-5 py-4">
                <div><p class="ui-kicker">Private account alerts</p><h2 class="text-lg font-semibold">For your attention</h2></div>
                <span class="rounded-full bg-foreground px-2.5 py-1 text-[10px] font-semibold text-background">{{ $accountAlerts->count() }}</span>
            </div>
            <div class="divide-y divide-border">
                @foreach($accountAlerts as $alert)
                    <article class="px-5 py-4 {{ $alert->priority === 'urgent' ? 'bg-red-500/[.04]' : ($alert->priority === 'important' ? 'bg-amber-500/[.04]' : '') }}">
                        <div class="flex items-start gap-3">
                            <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-border bg-card"><i data-lucide="{{ $alert->type === 'withdrawal_token' ? 'key-round' : 'megaphone' }}" class="h-4 w-4"></i></div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2"><p class="font-semibold">{{ $alert->title }}</p><span class="rounded-full bg-muted px-2 py-0.5 text-[9px] font-semibold uppercase">{{ $alert->priority }}</span></div>
                                <p class="mt-1 text-sm text-muted-foreground">{{ $alert->message }}</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @if($alert->action_url)<a href="{{ $alert->action_url }}" class="ui-btn ui-btn-primary ui-btn-sm">{{ $alert->action_label ?: 'Open' }}</a>@endif
                                    @if(!$alert->read_at)<form method="POST" action="{{ route('account-alerts.read',$alert) }}">@csrf<button class="ui-btn ui-btn-secondary ui-btn-sm">Mark Read</button></form>@endif
                                    <form method="POST" action="{{ route('account-alerts.dismiss',$alert) }}">@csrf<button class="ui-btn ui-btn-ghost ui-btn-sm">Dismiss</button></form>
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
        @endif

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <a href="{{ route('money.index') }}" class="ui-metric-card group cursor-pointer transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-ring/30">
                <div class="ui-metric-icon"><i data-lucide="wallet" class="h-5 w-5"></i></div>
                <div class="min-w-0 flex-1">
                    <p class="ui-label">Available balance</p>
                    <p class="mt-2 text-2xl font-semibold tracking-tight text-foreground">{{ format_currency($availableBalance) }}</p>
                    <p class="mt-1 text-xs leading-5 text-muted-foreground">{{ $reservedBalance > 0 ? format_currency($reservedBalance) . ' reserved.' : 'Cash available to spend or transfer.' }}</p>
                </div>
            </a>

            <a href="{{ route('money.activity') }}" class="ui-metric-card group cursor-pointer transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-ring/30">
                <div class="ui-metric-icon"><i data-lucide="landmark" class="h-5 w-5"></i></div>
                <div class="min-w-0 flex-1">
                    <p class="ui-label">Total assets</p>
                    <p class="mt-2 text-2xl font-semibold tracking-tight text-foreground">{{ format_currency($totalAssets) }}</p>
                    <p class="mt-1 text-xs leading-5 text-muted-foreground">Cash plus current portfolio value.</p>
                </div>
            </a>

            <a href="{{ route('portfolio.index') }}" class="ui-metric-card group cursor-pointer transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-ring/30">
                <div class="ui-metric-icon"><i data-lucide="briefcase-business" class="h-5 w-5"></i></div>
                <div class="min-w-0 flex-1">
                    <p class="ui-label">Portfolio value</p>
                    <p class="mt-2 text-2xl font-semibold tracking-tight text-foreground">{{ format_currency($portfolioValue) }}</p>
                    <p class="mt-1 text-xs leading-5 text-muted-foreground">{{ format_currency($investedCapital) }} currently invested.</p>
                </div>
            </a>

            <a href="{{ route('portfolio.analytics') }}" class="ui-metric-card group cursor-pointer transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-ring/30">
                <div class="ui-metric-icon"><i data-lucide="{{ $totalReturn >= 0 ? 'trending-up' : 'trending-down' }}" class="h-5 w-5"></i></div>
                <div class="min-w-0 flex-1">
                    <p class="ui-label">Total return</p>
                    <p class="mt-2 text-2xl font-semibold tracking-tight {{ $totalReturn >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ $totalReturn >= 0 ? '+' : '-' }}{{ format_currency(abs($totalReturn)) }}
                    </p>
                    <p class="mt-1 text-xs {{ $totalReturn >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ $totalReturn >= 0 ? '+' : '' }}{{ number_format($returnPercentage, 2) }}%
                    </p>
                </div>
            </a>
        </section>

        <section class="mt-4 grid gap-4 lg:grid-cols-[1.15fr_.85fr]">
            <article class="ui-panel p-5 sm:p-6">
                <div class="ui-section-heading">
                    <div>
                        <p class="ui-kicker">Account movement</p>
                        <h2 class="text-lg font-semibold text-foreground">Credits & debits</h2>
                    </div>
                    <a href="{{ route('money.activity') }}" class="ui-btn ui-btn-ghost ui-btn-sm">
                        View ledger
                        <i data-lucide="arrow-right" class="h-4 w-4"></i>
                    </a>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-xl border border-border bg-muted/30 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm text-muted-foreground">Credits</span>
                            <i data-lucide="arrow-down-left" class="h-4 w-4 text-emerald-500"></i>
                        </div>
                        <p class="mt-2 text-xl font-semibold text-emerald-600 dark:text-emerald-400">+{{ format_currency($totalCredits) }}</p>
                        <p class="mt-1 text-xs text-muted-foreground">Completed incoming funds.</p>
                    </div>

                    <div class="rounded-xl border border-border bg-muted/30 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm text-muted-foreground">Debits</span>
                            <i data-lucide="arrow-up-right" class="h-4 w-4 text-red-500"></i>
                        </div>
                        <p class="mt-2 text-xl font-semibold text-red-600 dark:text-red-400">-{{ format_currency($totalDebits) }}</p>
                        <p class="mt-1 text-xs text-muted-foreground">Completed outgoing funds.</p>
                    </div>

                    <div class="rounded-xl border border-border bg-muted/30 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm text-muted-foreground">Pending</span>
                            <i data-lucide="clock-3" class="h-4 w-4"></i>
                        </div>
                        <p class="mt-2 text-xl font-semibold text-foreground">{{ number_format($pendingTransactions) }}</p>
                        <p class="mt-1 text-xs text-muted-foreground">Transactions awaiting completion.</p>
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap gap-2">
                    <a href="{{ route('money.add') }}" class="ui-btn ui-btn-secondary ui-btn-sm">
                        <i data-lucide="circle-plus" class="h-4 w-4"></i> Deposit
                    </a>
                    <a href="{{ route('money.withdraw') }}" class="ui-btn ui-btn-secondary ui-btn-sm">
                        <i data-lucide="circle-minus" class="h-4 w-4"></i> Withdraw
                    </a>
                    <a href="{{ route('money.send') }}" class="ui-btn ui-btn-secondary ui-btn-sm">
                        <i data-lucide="arrow-right-left" class="h-4 w-4"></i> Transfer
                    </a>
                </div>
            </article>

            <article class="ui-panel p-5 sm:p-6">
                <div class="ui-section-heading">
                    <div>
                        <p class="ui-kicker">Asset allocation</p>
                        <h2 class="text-lg font-semibold text-foreground">Where your assets sit</h2>
                    </div>
                </div>

                <div class="mt-5 space-y-5">
                    @foreach($allocation as $item)
                        <div>
                            <div class="mb-2 flex items-center justify-between gap-4">
                                <div>
                                    <p class="text-sm font-medium text-foreground">{{ $item['label'] }}</p>
                                    <p class="text-xs text-muted-foreground">{{ format_currency($item['value']) }}</p>
                                </div>
                                <span class="text-sm font-medium text-foreground">{{ number_format($item['percentage'], 1) }}%</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-muted">
                                <div class="h-full rounded-full bg-foreground/70" style="width: {{ min(100, max(0, $item['percentage'])) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 grid grid-cols-2 gap-3">
                    <a href="{{ route('stocks.index') }}" class="ui-btn ui-btn-secondary justify-center">Stocks</a>
                    <a href="{{ route('investments.index') }}" class="ui-btn ui-btn-secondary justify-center">Investments</a>
                </div>
            </article>
        </section>

        <section class="mt-4 ui-surface overflow-hidden">
            <div class="flex items-center justify-between gap-4 border-b border-border px-5 py-4 sm:px-6">
                <div>
                    <p class="ui-kicker">Recent activity</p>
                    <h2 class="text-lg font-semibold text-foreground">Latest transactions</h2>
                </div>
                <a href="{{ route('money.activity') }}" class="ui-btn ui-btn-ghost ui-btn-sm">View all</a>
            </div>

            @if($recentTransactions->isEmpty())
                <div class="ui-empty-state">
                    <div class="ui-empty-icon"><i data-lucide="receipt-text" class="h-5 w-5"></i></div>
                    <h3 class="font-medium text-foreground">No transactions yet</h3>
                    <p class="mt-1 text-sm text-muted-foreground">Your account activity will appear here.</p>
                </div>
            @else
                <div class="divide-y divide-border">
                    @foreach($recentTransactions as $transaction)
                        <div class="flex items-center gap-3 px-5 py-4 sm:px-6">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-border bg-muted/40">
                                <i data-lucide="{{ $transaction->is_credit ? 'arrow-down-left' : 'arrow-up-right' }}" class="h-4 w-4 {{ $transaction->is_credit ? 'text-emerald-500' : 'text-red-500' }}"></i>
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-foreground">{{ $transaction->activity_label }}</p>
                                <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-muted-foreground">
                                    <span>{{ $transaction->created_at->format('M d, Y · h:i A') }}</span>
                                    <span>•</span>
                                    <span class="capitalize">{{ $transaction->status }}</span>
                                    @if($transaction->reference_id)
                                        <span class="hidden sm:inline">• {{ $transaction->reference_id }}</span>
                                    @endif
                                </div>
                            </div>

                            <div class="text-right">
                                <p class="text-sm font-semibold {{ $transaction->is_credit ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $transaction->signed_formatted_amount }}
                                </p>
                                <p class="mt-1 text-[11px] text-muted-foreground">{{ $transaction->direction_label }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="mt-4 grid gap-4 md:grid-cols-2">
            <article class="ui-panel p-5 sm:p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="ui-kicker">Stocks</p>
                        <h2 class="text-lg font-semibold text-foreground">{{ format_currency($stockValue) }}</h2>
                        <p class="mt-1 text-sm text-muted-foreground">{{ $stockHoldings->count() }} active position{{ $stockHoldings->count() === 1 ? '' : 's' }}</p>
                    </div>
                    <i data-lucide="candlestick-chart" class="h-5 w-5 text-muted-foreground"></i>
                </div>
                <a href="{{ route('trading.portfolio') }}" class="ui-btn ui-btn-secondary ui-btn-sm mt-5">View stock portfolio</a>
            </article>

            <article class="ui-panel p-5 sm:p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="ui-kicker">Investment plans</p>
                        <h2 class="text-lg font-semibold text-foreground">{{ format_currency($investmentValue) }}</h2>
                        <p class="mt-1 text-sm text-muted-foreground">{{ $investmentHoldings->count() }} active holding{{ $investmentHoldings->count() === 1 ? '' : 's' }}</p>
                    </div>
                    <i data-lucide="pie-chart" class="h-5 w-5 text-muted-foreground"></i>
                </div>
                <a href="{{ route('portfolio.index') }}" class="ui-btn ui-btn-secondary ui-btn-sm mt-5">View investment portfolio</a>
            </article>
        </section>
    </div>
</x-user-layout>
