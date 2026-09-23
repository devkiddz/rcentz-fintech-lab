@php
    $parentBase = 'flex items-center gap-3 rounded-xl px-3 py-2.5 text-[12px] font-semibold transition-colors';
    $parentIdle = 'text-foreground bg-transparent hover:bg-muted/70';
    $parentActive = 'text-foreground bg-red-500/[.07] ring-1 ring-red-500/15 shadow-sm';

    $childBase = 'flex items-center gap-3 rounded-lg px-3 py-2 text-[11px] font-medium transition-colors';
    $childIdle = 'text-muted-foreground hover:bg-muted/55 hover:text-foreground';
    $childActive = 'bg-red-500/[.08] text-red-600 dark:text-red-400';

    $standaloneBase = 'flex items-center gap-3 rounded-xl px-3 py-2.5 text-[12px] font-semibold transition-colors';
    $standaloneIdle = 'text-foreground bg-transparent hover:bg-muted/70';
    $standaloneActive = 'text-foreground bg-red-500/[.07] ring-1 ring-red-500/15 shadow-sm';
@endphp

<div class="px-3 pt-3 pb-1">
    <p class="sidebar-section-label px-3 text-[9px] font-semibold uppercase tracking-[.16em] text-muted-foreground">{{ localize('ui.r2e.admin.home', 'Home') }}</p>
</div>

<nav class="flex-1 overflow-y-auto px-3 pb-4 space-y-1.5" data-sidebar-nav>
    <a href="{{ route('admin.dashboard') }}"
       title="{{ localize('ui.r2e.admin.overview', 'Overview') }}"
       class="{{ $standaloneBase }} {{ request()->routeIs('admin.dashboard') ? $standaloneActive : $standaloneIdle }}">
        <i data-lucide="layout-dashboard" class="h-4 w-4 shrink-0"></i>
        <span class="sidebar-label">{{ localize('ui.r2e.admin.overview', 'Overview') }}</span>
    </a>

    <div class="sidebar-section-label px-3 pt-4 pb-1 text-[9px] font-semibold uppercase tracking-[.16em] text-muted-foreground">{{ localize('ui.r2e.admin.markets_intelligence', 'Markets & Intelligence') }}</div>

    <details class="sidebar-group group rounded-xl"
             data-nav-group="admin-markets"
             {{ request()->routeIs('admin.instruments.*','admin.stocks.*') ? 'open' : '' }}>
        <summary title="Markets"
                 class="{{ $parentBase }} justify-between {{ request()->routeIs('admin.instruments.*','admin.stocks.*') ? $parentActive : $parentIdle }}">
            <span class="flex min-w-0 items-center gap-3">
                <i data-lucide="chart-candlestick" class="h-4 w-4 shrink-0"></i>
                <span class="sidebar-label truncate">Markets</span>
            </span>
            <i data-lucide="chevron-down" class="sidebar-chevron h-4 w-4 transition-transform group-open:rotate-180"></i>
        </summary>
        <div class="sidebar-subnav ml-5 mt-1.5 space-y-1 border-l border-border/70 pl-4">
            <p class="px-3 pt-1 text-[8px] font-semibold uppercase tracking-[.14em] text-muted-foreground">Instruments</p>
            <a href="{{ route('admin.instruments.index') }}" class="{{ $childBase }} {{ request()->routeIs('admin.instruments.index') ? $childActive : $childIdle }}"><i data-lucide="layout-grid" class="h-4 w-4"></i><span>All Instruments</span></a>
            <a href="{{ route('admin.instruments.stocks') }}" class="{{ $childBase }} {{ request()->routeIs('admin.instruments.stocks','admin.instruments.stocks.*','admin.stocks.*') ? $childActive : $childIdle }}"><i data-lucide="chart-no-axes-combined" class="h-4 w-4"></i><span>Stocks</span></a>
            <a href="{{ route('admin.instruments.forex') }}" class="{{ $childBase }} {{ request()->routeIs('admin.instruments.forex','admin.instruments.forex.*') ? $childActive : $childIdle }}"><i data-lucide="arrow-left-right" class="h-4 w-4"></i><span>Forex</span></a>
            <a href="{{ route('admin.instruments.crypto') }}" class="{{ $childBase }} {{ request()->routeIs('admin.instruments.crypto','admin.instruments.crypto.*') ? $childActive : $childIdle }}"><i data-lucide="bitcoin" class="h-4 w-4"></i><span>Crypto</span></a>
            <a href="{{ route('admin.instruments.commodities') }}" class="{{ $childBase }} {{ request()->routeIs('admin.instruments.commodities','admin.instruments.commodities.*') ? $childActive : $childIdle }}"><i data-lucide="gem" class="h-4 w-4"></i><span>Commodities</span></a>
        </div>
    </details>

    <details class="sidebar-group group rounded-xl"
             data-nav-group="admin-signals"
             {{ request()->routeIs('admin.signals.*') ? 'open' : '' }}>
        <summary title="{{ localize('ui.r2e.admin.signal_engine', 'Signal Engine') }}"
                 class="{{ $parentBase }} justify-between {{ request()->routeIs('admin.signals.*') ? $parentActive : $parentIdle }}">
            <span class="flex min-w-0 items-center gap-3"><i data-lucide="radio-tower" class="h-4 w-4 shrink-0"></i><span class="sidebar-label truncate">{{ localize('ui.r2e.admin.signal_engine', 'Signal Engine') }}</span></span>
            <i data-lucide="chevron-down" class="sidebar-chevron h-4 w-4 transition-transform group-open:rotate-180"></i>
        </summary>
        <div class="sidebar-subnav ml-5 mt-1.5 space-y-1 border-l border-border/70 pl-4">
            <a href="{{ route('admin.signals.index') }}" class="{{ $childBase }} {{ request()->routeIs('admin.signals.index','admin.signals.show') ? $childActive : $childIdle }}"><i data-lucide="layout-dashboard" class="h-4 w-4"></i><span>{{ localize('ui.r2e.admin.overview', 'Overview') }}</span></a>
            <a href="{{ route('admin.signals.candidates') }}" class="{{ $childBase }} {{ request()->routeIs('admin.signals.candidates') ? $childActive : $childIdle }}"><i data-lucide="sparkles" class="h-4 w-4"></i><span>{{ localize('ui.r2e.admin.candidates_ready', 'Candidates & Ready') }}</span></a>
            <a href="{{ route('admin.signals.live') }}" class="{{ $childBase }} {{ request()->routeIs('admin.signals.live') ? $childActive : $childIdle }}"><i data-lucide="radio-tower" class="h-4 w-4"></i><span>{{ localize('ui.r2e.admin.live_signals', 'Live Signals') }}</span></a>
            <a href="{{ route('admin.signals.recipients') }}" class="{{ $childBase }} {{ request()->routeIs('admin.signals.recipients') ? $childActive : $childIdle }}"><i data-lucide="users-round" class="h-4 w-4"></i><span>{{ localize('ui.r2e.admin.recipients', 'Recipients') }}</span></a>
            <a href="{{ route('admin.signals.activity') }}" class="{{ $childBase }} {{ request()->routeIs('admin.signals.activity') ? $childActive : $childIdle }}"><i data-lucide="activity" class="h-4 w-4"></i><span>{{ localize('ui.r2e.admin.engine_activity', 'Engine Activity') }}</span></a>
            <a href="{{ route('admin.signals.history') }}" class="{{ $childBase }} {{ request()->routeIs('admin.signals.history') ? $childActive : $childIdle }}"><i data-lucide="history" class="h-4 w-4"></i><span>History</span></a>
        </div>
    </details>

    <div class="sidebar-section-label px-3 pt-4 pb-1 text-[9px] font-semibold uppercase tracking-[.16em] text-muted-foreground">{{ localize('ui.r2e.admin.trading_automation', 'Trading & Automation') }}</div>

    <details class="sidebar-group group rounded-xl"
             data-nav-group="admin-trading"
             {{ request()->routeIs('admin.trading.*','admin.stocks.*') ? 'open' : '' }}>
        <summary title="Trading"
                 class="{{ $parentBase }} justify-between {{ request()->routeIs('admin.trading.*','admin.stocks.*') ? $parentActive : $parentIdle }}">
            <span class="flex min-w-0 items-center gap-3">
                <i data-lucide="candlestick-chart" class="h-4 w-4 shrink-0"></i>
                <span class="sidebar-label truncate">Trading</span>
            </span>
            <i data-lucide="chevron-down" class="sidebar-chevron h-4 w-4 transition-transform group-open:rotate-180"></i>
        </summary>
        <div class="sidebar-subnav ml-5 mt-1.5 space-y-1 border-l border-border/70 pl-4">
            <p class="px-3 pt-1 text-[8px] font-semibold uppercase tracking-[.14em] text-muted-foreground">Instruments</p>
            <a href="{{ route('admin.trading.instruments.index') }}" class="{{ $childBase }} {{ request()->routeIs('admin.trading.instruments.*') ? $childActive : $childIdle }}"><i data-lucide="list-tree" class="h-4 w-4"></i><span>Tradable Instruments</span></a>
            <p class="px-3 pt-2 text-[8px] font-semibold uppercase tracking-[.14em] text-muted-foreground">Execution</p>
            <a href="{{ route('admin.trading.index') }}" class="{{ $childBase }} {{ request()->routeIs('admin.trading.index') ? $childActive : $childIdle }}"><i data-lucide="gauge" class="h-4 w-4"></i><span>Trading Overview</span></a>
            <a href="{{ route('admin.trading.manual') }}" class="{{ $childBase }} {{ request()->routeIs('admin.trading.manual') ? $childActive : $childIdle }}"><i data-lucide="candlestick-chart" class="h-4 w-4"></i><span>Trading Desk</span></a>
            <a href="{{ route('admin.trading.positions') }}" class="{{ $childBase }} {{ request()->routeIs('admin.trading.positions','admin.trading.positions.show') ? $childActive : $childIdle }}"><i data-lucide="target" class="h-4 w-4"></i><span>Open Positions</span></a>
            <a href="{{ route('admin.stocks.holdings.index') }}" class="{{ $childBase }} {{ request()->routeIs('admin.stocks.holdings.*') ? $childActive : $childIdle }}"><i data-lucide="briefcase-business" class="h-4 w-4"></i><span>Holdings</span></a>
            <a href="{{ route('admin.stocks.transactions.index') }}" class="{{ $childBase }} {{ request()->routeIs('admin.stocks.transactions.*') ? $childActive : $childIdle }}"><i data-lucide="receipt-text" class="h-4 w-4"></i><span>Transactions</span></a>
            <a href="{{ route('admin.trading.history') }}" class="{{ $childBase }} {{ request()->routeIs('admin.trading.history') ? $childActive : $childIdle }}"><i data-lucide="history" class="h-4 w-4"></i><span>Trade History</span></a>
        </div>
    </details>

    <details class="sidebar-group group rounded-xl"
             data-nav-group="admin-copy"
             {{ request()->routeIs('admin.copy-trading.*') ? 'open' : '' }}>
        <summary title="{{ localize('ui.r2d.common.copy_trading', 'Copy Trading') }}"
                 class="{{ $parentBase }} justify-between {{ request()->routeIs('admin.copy-trading.*') ? $parentActive : $parentIdle }}">
            <span class="flex min-w-0 items-center gap-3">
                <i data-lucide="users-round" class="h-4 w-4 shrink-0"></i>
                <span class="sidebar-label truncate">{{ localize('ui.r2d.common.copy_trading', 'Copy Trading') }}</span>
            </span>
            <i data-lucide="chevron-down" class="sidebar-chevron h-4 w-4 transition-transform group-open:rotate-180"></i>
        </summary>
        <div class="sidebar-subnav ml-5 mt-1.5 space-y-1 border-l border-border/70 pl-4">
            <a href="{{ route('admin.copy-trading.applications') }}" class="{{ $childBase }} {{ request()->routeIs('admin.copy-trading.applications*') ? $childActive : $childIdle }}"><i data-lucide="clipboard-check" class="h-4 w-4"></i><span>{{ localize('ui.r2e.admin.provider_applications', 'Provider Applications') }}</span></a>
            <a href="{{ route('admin.copy-trading.providers') }}" class="{{ $childBase }} {{ request()->routeIs('admin.copy-trading.providers*') ? $childActive : $childIdle }}"><i data-lucide="badge-check" class="h-4 w-4"></i><span>{{ localize('ui.r2e.admin.providers', 'Providers') }}</span></a>
            <a href="{{ route('admin.copy-trading.strategies') }}" class="{{ $childBase }} {{ request()->routeIs('admin.copy-trading.strategies*') ? $childActive : $childIdle }}"><i data-lucide="workflow" class="h-4 w-4"></i><span>{{ localize('ui.r2e.admin.strategies', 'Strategies') }}</span></a>
        </div>
    </details>

    <details class="sidebar-group group rounded-xl"
             data-nav-group="admin-bots"
             {{ request()->routeIs('admin.ai-bots.*') ? 'open' : '' }}>
        <summary title="{{ localize('ui.r2e.admin.ai_bot_operations', 'AI Bot Operations') }}"
                 class="{{ $parentBase }} justify-between {{ request()->routeIs('admin.ai-bots.*') ? $parentActive : $parentIdle }}">
            <span class="flex min-w-0 items-center gap-3">
                <i data-lucide="bot" class="h-4 w-4 shrink-0"></i>
                <span class="sidebar-label truncate">{{ localize('ui.r2e.admin.ai_bot_operations', 'AI Bot Operations') }}</span>
            </span>
            <i data-lucide="chevron-down" class="sidebar-chevron h-4 w-4 transition-transform group-open:rotate-180"></i>
        </summary>
        <div class="sidebar-subnav ml-5 mt-1.5 space-y-1 border-l border-border/70 pl-4">
            <a href="{{ route('admin.ai-bots.index') }}" class="{{ $childBase }} {{ request()->routeIs('admin.ai-bots.index','admin.ai-bots.edit') ? $childActive : $childIdle }}"><i data-lucide="store" class="h-4 w-4"></i><span>{{ localize('ui.r2e.admin.bot_catalog', 'Bot Catalog') }}</span></a>
            <a href="{{ route('admin.ai-bots.create') }}" class="{{ $childBase }} {{ request()->routeIs('admin.ai-bots.create') ? $childActive : $childIdle }}"><i data-lucide="circle-plus" class="h-4 w-4"></i><span>{{ localize('ui.r2e.admin.create_bot', 'Create Bot') }}</span></a>
            <a href="{{ route('admin.ai-bots.subscriptions') }}" class="{{ $childBase }} {{ request()->routeIs('admin.ai-bots.subscriptions') ? $childActive : $childIdle }}"><i data-lucide="badge-dollar-sign" class="h-4 w-4"></i><span>{{ localize('ui.r2e.admin.subscriptions', 'Subscriptions') }}</span></a>
            <a href="{{ route('admin.ai-bots.executions') }}" class="{{ $childBase }} {{ request()->routeIs('admin.ai-bots.executions*') ? $childActive : $childIdle }}"><i data-lucide="activity" class="h-4 w-4"></i><span>{{ localize('ui.r2d.common.executions', 'Executions') }}</span></a>
        </div>
    </details>

    <div class="sidebar-section-label px-3 pt-4 pb-1 text-[9px] font-semibold uppercase tracking-[.16em] text-muted-foreground">{{ localize('ui.r2d.common.investments', 'Investments') }}</div>

    <details class="sidebar-group group rounded-xl"
             data-nav-group="admin-investments"
             {{ request()->routeIs('admin.investments.*') ? 'open' : '' }}>
        <summary title="Investments"
                 class="{{ $parentBase }} justify-between {{ request()->routeIs('admin.investments.*') ? $parentActive : $parentIdle }}">
            <span class="flex min-w-0 items-center gap-3">
                <i data-lucide="gem" class="h-4 w-4 shrink-0"></i>
                <span class="sidebar-label truncate">Investments</span>
            </span>
            <i data-lucide="chevron-down" class="sidebar-chevron h-4 w-4 transition-transform group-open:rotate-180"></i>
        </summary>
        <div class="sidebar-subnav ml-5 mt-1.5 space-y-1 border-l border-border/70 pl-4">
            <details class="group/investment-instruments rounded-lg" {{ request()->routeIs('admin.investments.control.*','admin.investments.instruments.*') ? 'open' : '' }}>
                <summary class="{{ $childBase }} cursor-pointer justify-between {{ request()->routeIs('admin.investments.control.*','admin.investments.instruments.*') ? $childActive : $childIdle }}">
                    <span class="flex items-center gap-3"><i data-lucide="boxes" class="h-4 w-4"></i><span>Instruments</span></span>
                    <i data-lucide="chevron-down" class="h-3.5 w-3.5 transition-transform group-open/investment-instruments:rotate-180"></i>
                </summary>
                <div class="mt-1 space-y-1 pl-5">
                    <a href="{{ route('admin.investments.control.index') }}" class="{{ $childBase }} {{ request()->routeIs('admin.investments.control.*') ? $childActive : $childIdle }}"><i data-lucide="layers-3" class="h-3.5 w-3.5"></i><span>Investment Products</span></a>
                    <a href="{{ route('admin.investments.instruments.base-assets.index') }}" class="{{ $childBase }} {{ request()->routeIs('admin.investments.instruments.base-assets.*') ? $childActive : $childIdle }}"><i data-lucide="database-zap" class="h-3.5 w-3.5"></i><span>Base Assets / References</span></a>
                </div>
            </details>
            <a href="{{ route('investments.index') }}" class="{{ $childBase }}"><i data-lucide="eye" class="h-4 w-4"></i><span>Customer Market</span></a>
            <p class="px-3 pt-2 text-[8px] font-semibold uppercase tracking-[.14em] text-muted-foreground">Product Views</p>
            <a href="{{ route('admin.investments.control.index', ['category' => 'stock_market']) }}" class="{{ $childBase }} {{ request()->routeIs('admin.investments.control.index') && request()->query('category') === 'stock_market' ? $childActive : $childIdle }}"><i data-lucide="chart-no-axes-combined" class="h-4 w-4"></i><span>Stocks</span></a>
            <a href="{{ route('admin.investments.control.index', ['category' => 'forex']) }}" class="{{ $childBase }} {{ request()->routeIs('admin.investments.control.index') && request()->query('category') === 'forex' ? $childActive : $childIdle }}"><i data-lucide="landmark" class="h-4 w-4"></i><span>Forex</span></a>
            <a href="{{ route('admin.investments.control.index', ['category' => 'cryptocurrency']) }}" class="{{ $childBase }} {{ request()->routeIs('admin.investments.control.index') && request()->query('category') === 'cryptocurrency' ? $childActive : $childIdle }}"><i data-lucide="bitcoin" class="h-4 w-4"></i><span>Crypto</span></a>
            <a href="{{ route('admin.investments.control.index', ['category' => 'real_estate']) }}" class="{{ $childBase }} {{ request()->routeIs('admin.investments.control.index') && request()->query('category') === 'real_estate' ? $childActive : $childIdle }}"><i data-lucide="house" class="h-4 w-4"></i><span>Real Estate</span></a>
            <a href="{{ route('admin.investments.control.index', ['category' => 'bonds']) }}" class="{{ $childBase }} {{ request()->routeIs('admin.investments.control.index') && request()->query('category') === 'bonds' ? $childActive : $childIdle }}"><i data-lucide="landmark" class="h-4 w-4"></i><span>Bonds & Fixed Income</span></a>
            <details class="group/legacy rounded-lg">
                <summary class="{{ $childBase }} cursor-pointer justify-between text-muted-foreground hover:text-foreground">
                    <span class="flex items-center gap-3"><i data-lucide="flask-conical" class="h-4 w-4"></i><span>Legacy Records</span></span>
                    <i data-lucide="chevron-down" class="h-3.5 w-3.5 transition-transform group-open/legacy:rotate-180"></i>
                </summary>
                <div class="mt-1 space-y-1 pl-5">
                    <a href="{{ route('admin.investments.holdings.index') }}" class="{{ $childBase }} {{ request()->routeIs('admin.investments.holdings.*') ? $childActive : $childIdle }}"><i data-lucide="archive" class="h-3.5 w-3.5"></i><span>Old Holdings</span></a>
                    <a href="{{ route('admin.investments.transactions.index') }}" class="{{ $childBase }} {{ request()->routeIs('admin.investments.transactions.*') ? $childActive : $childIdle }}"><i data-lucide="receipt" class="h-3.5 w-3.5"></i><span>Old Transactions</span></a>
                    <a href="{{ route('admin.investments.plans.index') }}" class="{{ $childBase }} {{ request()->routeIs('admin.investments.plans.*','admin.investments.nav-updates.*','admin.investments.automatic-nav-updates.*') ? $childActive : $childIdle }}"><i data-lucide="layers-3" class="h-3.5 w-3.5"></i><span>NAV Plans</span></a>
                </div>
            </details>
        </div>
    </details>

    <div class="sidebar-section-label px-3 pt-4 pb-1 text-[9px] font-semibold uppercase tracking-[.16em] text-muted-foreground">{{ localize('ui.r2e.admin.customers_finance', 'Customers & Finance') }}</div>

    <details class="sidebar-group group rounded-xl"
             data-nav-group="admin-transactions"
             {{ request()->routeIs('admin.wallet-transactions.*','admin.withdrawal-token-requests.*') ? 'open' : '' }}>
        <summary title="{{ localize('ui.r2d.common.transactions', 'Transactions') }}"
                 class="{{ $parentBase }} justify-between {{ request()->routeIs('admin.wallet-transactions.*','admin.withdrawal-token-requests.*') ? $parentActive : $parentIdle }}">
            <span class="flex min-w-0 items-center gap-3">
                <i data-lucide="wallet-cards" class="h-4 w-4 shrink-0"></i>
                <span class="sidebar-label truncate">{{ localize('ui.r2d.common.transactions', 'Transactions') }}</span>
            </span>
            <i data-lucide="chevron-down" class="sidebar-chevron h-4 w-4 transition-transform group-open:rotate-180"></i>
        </summary>
        <div class="sidebar-subnav ml-5 mt-1.5 space-y-1 border-l border-border/70 pl-4">
            <a href="{{ route('admin.wallet-transactions.index') }}"
               class="{{ $childBase }} {{ request()->routeIs('admin.wallet-transactions.*') && !request()->filled('type') ? $childActive : $childIdle }}">
                <i data-lucide="list" class="h-4 w-4"></i><span>{{ localize('ui.r2e.admin.all_transactions', 'All Transactions') }}</span>
            </a>
            <a href="{{ route('admin.wallet-transactions.index', ['type' => 'deposit']) }}"
               class="{{ $childBase }} {{ request()->routeIs('admin.wallet-transactions.index') && request()->query('type') === 'deposit' ? $childActive : $childIdle }}">
                <i data-lucide="arrow-down-to-line" class="h-4 w-4"></i><span>{{ localize('ui.r2e.admin.deposits', 'Deposits') }}</span>
            </a>
            <a href="{{ route('admin.wallet-transactions.index', ['type' => 'withdrawal']) }}"
               class="{{ $childBase }} {{ request()->routeIs('admin.wallet-transactions.index') && request()->query('type') === 'withdrawal' ? $childActive : $childIdle }}">
                <i data-lucide="arrow-up-from-line" class="h-4 w-4"></i><span>{{ localize('ui.r2e.admin.withdrawals', 'Withdrawals') }}</span>
            </a>
            <a href="{{ route('admin.withdrawal-token-requests.index') }}"
               class="{{ $childBase }} {{ request()->routeIs('admin.withdrawal-token-requests.*') ? $childActive : $childIdle }}">
                <i data-lucide="shield-check" class="h-4 w-4"></i><span>{{ localize('ui.r2e.admin.withdrawal_requests', 'Withdrawal Requests') }}</span>
            </a>
        </div>
    </details>
    <details class="sidebar-group group rounded-xl"
             data-nav-group="admin-memberships"
             {{ request()->routeIs('admin.memberships.*') ? 'open' : '' }}>
        <summary title="{{ localize('ui.r2e.common.memberships', 'Memberships') }}"
                 class="{{ $parentBase }} justify-between {{ request()->routeIs('admin.memberships.*') ? $parentActive : $parentIdle }}">
            <span class="flex min-w-0 items-center gap-3"><i data-lucide="badge" class="h-4 w-4 shrink-0"></i><span class="sidebar-label truncate">{{ localize('ui.r2e.common.memberships', 'Memberships') }}</span></span>
            <i data-lucide="chevron-down" class="sidebar-chevron h-4 w-4 transition-transform group-open:rotate-180"></i>
        </summary>
        <div class="sidebar-subnav ml-5 mt-1.5 space-y-1 border-l border-border/70 pl-4">
            <a href="{{ route('admin.memberships.index') }}" class="{{ $childBase }} {{ request()->routeIs('admin.memberships.index') ? $childActive : $childIdle }}"><i data-lucide="layout-grid" class="h-4 w-4"></i><span>{{ localize('ui.r2e.admin.overview', 'Overview') }}</span></a>
            @foreach($membershipNavTypes ?? collect() as $membershipType)
                @php $membershipTypeActive = request()->route('type')?->is($membershipType) ?? false; @endphp
                <details class="group rounded-lg" data-nav-group="admin-memberships-{{ $membershipType->slug }}" {{ $membershipTypeActive ? 'open' : '' }}>
                    <summary class="{{ $childBase }} justify-between {{ $membershipTypeActive ? $childActive : $childIdle }}">
                        <span class="flex items-center gap-3"><i data-lucide="{{ $membershipType->icon ?: 'badge-check' }}" class="h-4 w-4"></i><span>{{ $membershipType->name }}</span></span>
                        <i data-lucide="chevron-down" class="h-3.5 w-3.5 transition-transform group-open:rotate-180"></i>
                    </summary>
                    <div class="ml-4 mt-1 space-y-1 border-l border-border/60 pl-3">
                        <a href="{{ route('admin.memberships.show', $membershipType) }}" class="{{ $childBase }} {{ $membershipTypeActive && !request()->routeIs('admin.memberships.registry*') ? $childActive : $childIdle }}"><i data-lucide="layers-3" class="h-4 w-4"></i><span>{{ localize('ui.r2e.admin.plans_entitlements', 'Plans & Entitlements') }}</span></a>
                        <a href="{{ route('admin.memberships.registry', $membershipType) }}" class="{{ $childBase }} {{ $membershipTypeActive && request()->routeIs('admin.memberships.registry*') ? $childActive : $childIdle }}"><i data-lucide="badge-check" class="h-4 w-4"></i><span>{{ localize('ui.r2e.common.memberships', 'Memberships') }}</span></a>
                    </div>
                </details>
            @endforeach
        </div>
    </details>
    <a href="{{ route('admin.rewards.index') }}" title="{{ localize('ui.r2e.admin.rewards_bonuses', 'Rewards & Bonuses') }}"
       class="{{ $standaloneBase }} {{ request()->routeIs('admin.rewards.*') ? $standaloneActive : $standaloneIdle }}">
        <i data-lucide="gift" class="h-4 w-4 shrink-0"></i><span class="sidebar-label">{{ localize('ui.r2e.admin.rewards_bonuses', 'Rewards & Bonuses') }}</span>
    </a>
    <a href="{{ route('admin.users.index') }}" title="{{ localize('ui.r2e.admin.customers', 'Customers') }}"
       class="{{ $standaloneBase }} {{ request()->routeIs('admin.users.*') ? $standaloneActive : $standaloneIdle }}">
        <i data-lucide="users" class="h-4 w-4 shrink-0"></i><span class="sidebar-label">{{ localize('ui.r2e.admin.customers', 'Customers') }}</span>
    </a>
    <a href="{{ route('admin.kyc.index') }}" title="{{ localize('ui.r2e.admin.kyc_compliance', 'KYC & Compliance') }}"
       class="{{ $standaloneBase }} {{ request()->routeIs('admin.kyc.*') ? $standaloneActive : $standaloneIdle }}">
        <i data-lucide="shield-check" class="h-4 w-4 shrink-0"></i><span class="sidebar-label">{{ localize('ui.r2e.admin.kyc_compliance', 'KYC & Compliance') }}</span>
    </a>
    <a href="{{ route('admin.payment_methods.index') }}" title="{{ localize('ui.r2e.admin.payment_methods', 'Payment Methods') }}"
       class="{{ $standaloneBase }} {{ request()->routeIs('admin.payment_methods.*') ? $standaloneActive : $standaloneIdle }}">
        <i data-lucide="credit-card" class="h-4 w-4 shrink-0"></i><span class="sidebar-label">{{ localize('ui.r2e.admin.payment_methods', 'Payment Methods') }}</span>
    </a>
    <a href="{{ route('admin.purchases.index') }}" title="{{ localize('ui.r2e.admin.orders', 'Orders') }}"
       class="{{ $standaloneBase }} {{ request()->routeIs('admin.purchases.*') ? $standaloneActive : $standaloneIdle }}">
        <i data-lucide="package-check" class="h-4 w-4 shrink-0"></i><span class="sidebar-label">{{ localize('ui.r2e.admin.orders', 'Orders') }}</span>
    </a>

    <details class="sidebar-group group rounded-xl"
             data-nav-group="admin-commerce"
             {{ request()->routeIs('admin.cars.*') ? 'open' : '' }}>
        <summary title="{{ localize('ui.r2e.admin.commerce_catalog', 'Commerce Catalog') }}"
                 class="{{ $parentBase }} justify-between {{ request()->routeIs('admin.cars.*') ? $parentActive : $parentIdle }}">
            <span class="flex min-w-0 items-center gap-3">
                <i data-lucide="shopping-bag" class="h-4 w-4 shrink-0"></i>
                <span class="sidebar-label truncate">{{ localize('ui.r2e.admin.commerce_catalog', 'Commerce Catalog') }}</span>
            </span>
            <i data-lucide="chevron-down" class="sidebar-chevron h-4 w-4 transition-transform group-open:rotate-180"></i>
        </summary>
        <div class="sidebar-subnav ml-5 mt-1.5 space-y-1 border-l border-border/70 pl-4">
            <a href="{{ route('admin.cars.index') }}" class="{{ $childBase }} {{ request()->routeIs('admin.cars.index','admin.cars.show') ? $childActive : $childIdle }}"><i data-lucide="car" class="h-4 w-4"></i><span>{{ localize('ui.r2e.admin.car_catalog', 'Car Catalog') }}</span></a>
            <a href="{{ route('admin.cars.create') }}" class="{{ $childBase }} {{ request()->routeIs('admin.cars.create') ? $childActive : $childIdle }}"><i data-lucide="circle-plus" class="h-4 w-4"></i><span>{{ localize('ui.r2e.admin.add_car', 'Add Car') }}</span></a>
        </div>
    </details>

    <div class="sidebar-section-label px-3 pt-4 pb-1 text-[9px] font-semibold uppercase tracking-[.16em] text-muted-foreground">{{ localize('ui.r2e.admin.communication_platform', 'Communication & Platform') }}</div>

    <a href="{{ route('admin.messages.index') }}" title="{{ localize('ui.r2e.admin.messages', 'Messages') }}"
       class="{{ $standaloneBase }} {{ request()->routeIs('admin.messages.*') ? $standaloneActive : $standaloneIdle }}">
        <i data-lucide="messages-square" class="h-4 w-4 shrink-0"></i><span class="sidebar-label">{{ localize('ui.r2e.admin.messages', 'Messages') }}</span>
    </a>
    <a href="{{ route('admin.support.index') }}" title="{{ localize('ui.r2e.admin.support_tickets', 'Support Tickets') }}"
       class="{{ $standaloneBase }} {{ request()->routeIs('admin.support.*') ? $standaloneActive : $standaloneIdle }}">
        <i data-lucide="life-buoy" class="h-4 w-4 shrink-0"></i><span class="sidebar-label">{{ localize('ui.r2e.admin.support_tickets', 'Support Tickets') }}</span>
    </a>

    <details class="sidebar-group group rounded-xl"
             data-nav-group="admin-email"
             {{ request()->routeIs('admin.emails.*') ? 'open' : '' }}>
        <summary title="{{ localize('ui.r2e.admin.email_center', 'Email Center') }}"
                 class="{{ $parentBase }} justify-between {{ request()->routeIs('admin.emails.*') ? $parentActive : $parentIdle }}">
            <span class="flex min-w-0 items-center gap-3">
                <i data-lucide="mail" class="h-4 w-4 shrink-0"></i>
                <span class="sidebar-label truncate">{{ localize('ui.r2e.admin.email_center', 'Email Center') }}</span>
            </span>
            <i data-lucide="chevron-down" class="sidebar-chevron h-4 w-4 transition-transform group-open:rotate-180"></i>
        </summary>
        <div class="sidebar-subnav ml-5 mt-1.5 space-y-1 border-l border-border/70 pl-4">
            <a href="{{ route('admin.emails.index') }}" class="{{ $childBase }} {{ request()->routeIs('admin.emails.index','admin.emails.templates.*') ? $childActive : $childIdle }}"><i data-lucide="files" class="h-4 w-4"></i><span>{{ localize('ui.r2e.admin.templates', 'Templates') }}</span></a>
            <a href="{{ route('admin.emails.compose') }}" class="{{ $childBase }} {{ request()->routeIs('admin.emails.compose') ? $childActive : $childIdle }}"><i data-lucide="send" class="h-4 w-4"></i><span>{{ localize('ui.r2e.admin.compose_email', 'Compose Email') }}</span></a>
        </div>
    </details>

    <details class="sidebar-group group rounded-xl"
             data-nav-group="admin-settings"
             {{ request()->routeIs('admin.settings.*') ? 'open' : '' }}>
        <summary title="{{ localize('ui.r2e.admin.system_settings', 'System Settings') }}"
                 class="{{ $parentBase }} justify-between {{ request()->routeIs('admin.settings.*') ? $parentActive : $parentIdle }}">
            <span class="flex min-w-0 items-center gap-3">
                <i data-lucide="settings-2" class="h-4 w-4 shrink-0"></i>
                <span class="sidebar-label truncate">{{ localize('ui.r2e.admin.system_settings', 'System Settings') }}</span>
            </span>
            <i data-lucide="chevron-down" class="sidebar-chevron h-4 w-4 transition-transform group-open:rotate-180"></i>
        </summary>
        <div class="sidebar-subnav ml-5 mt-1.5 space-y-1 border-l border-border/70 pl-4">
            <a href="{{ route('admin.settings.index', ['section' => 'overview']) }}" class="{{ $childBase }} {{ request()->routeIs('admin.settings.*') && request()->query('section', 'overview') === 'overview' ? $childActive : $childIdle }}"><i data-lucide="layout-dashboard" class="h-4 w-4"></i><span>{{ localize('ui.r2e.admin.overview', 'Overview') }}</span></a>
            <a href="{{ route('admin.settings.index', ['section' => 'general']) }}" class="{{ $childBase }} {{ request()->routeIs('admin.settings.*') && request()->query('section') === 'general' ? $childActive : $childIdle }}"><i data-lucide="settings-2" class="h-4 w-4"></i><span>{{ localize('ui.r2e.admin.general', 'General') }}</span></a>
            <a href="{{ route('admin.settings.index', ['section' => 'appearance']) }}" class="{{ $childBase }} {{ request()->routeIs('admin.settings.*') && request()->query('section') === 'appearance' ? $childActive : $childIdle }}"><i data-lucide="palette" class="h-4 w-4"></i><span>{{ localize('ui.r2e.admin.appearance', 'Appearance') }}</span></a>
            <a href="{{ route('admin.settings.index', ['section' => 'localization']) }}" class="{{ $childBase }} {{ request()->routeIs('admin.settings.*') && request()->query('section') === 'localization' ? $childActive : $childIdle }}"><i data-lucide="languages" class="h-4 w-4"></i><span>{{ localize('ui.r2e.admin.localization', 'Localization') }}</span></a>
            <a href="{{ route('admin.settings.index', ['section' => 'market']) }}" class="{{ $childBase }} {{ request()->routeIs('admin.settings.*') && request()->query('section') === 'market' ? $childActive : $childIdle }}"><i data-lucide="chart-candlestick" class="h-4 w-4"></i><span>{{ localize('ui.r2e.admin.market', 'Market') }}</span></a>
            <a href="{{ route('admin.settings.index', ['section' => 'trading']) }}" class="{{ $childBase }} {{ request()->routeIs('admin.settings.*') && request()->query('section') === 'trading' ? $childActive : $childIdle }}"><i data-lucide="chart-no-axes-combined" class="h-4 w-4"></i><span>{{ localize('ui.r2d.common.trading', 'Trading') }}</span></a>
            <a href="{{ route('admin.settings.index', ['section' => 'security']) }}" class="{{ $childBase }} {{ request()->routeIs('admin.settings.*') && request()->query('section') === 'security' ? $childActive : $childIdle }}"><i data-lucide="shield-check" class="h-4 w-4"></i><span>{{ localize('ui.r2e.common.security', 'Security') }}</span></a>
            <a href="{{ route('admin.settings.index', ['section' => 'mail']) }}" class="{{ $childBase }} {{ request()->routeIs('admin.settings.*') && request()->query('section') === 'mail' ? $childActive : $childIdle }}"><i data-lucide="mail" class="h-4 w-4"></i><span>{{ localize('ui.r2e.admin.mail_notifications', 'Mail & Notifications') }}</span></a>
            <a href="{{ route('admin.settings.index', ['section' => 'integrations']) }}" class="{{ $childBase }} {{ request()->routeIs('admin.settings.*') && request()->query('section') === 'integrations' ? $childActive : $childIdle }}"><i data-lucide="plug-zap" class="h-4 w-4"></i><span>{{ localize('ui.r2e.admin.integrations', 'Integrations') }}</span></a>
            <a href="{{ route('admin.settings.index', ['section' => 'system']) }}" class="{{ $childBase }} {{ request()->routeIs('admin.settings.*') && request()->query('section') === 'system' ? $childActive : $childIdle }}"><i data-lucide="server-cog" class="h-4 w-4"></i><span>{{ localize('ui.r2e.admin.system', 'System') }}</span></a>
        </div>
    </details>
    <a href="{{ route('cron.setup') }}" title="{{ localize('ui.r2e.admin.scheduler_cron', 'Scheduler & Cron') }}"
       class="{{ $standaloneBase }} {{ request()->routeIs('cron.setup') ? $standaloneActive : $standaloneIdle }}">
        <i data-lucide="clock-3" class="h-4 w-4 shrink-0"></i><span class="sidebar-label">{{ localize('ui.r2e.admin.scheduler_cron', 'Scheduler & Cron') }}</span>
    </a>
    <a href="{{ route('admin.profile.edit') }}" title="{{ localize('ui.r2e.admin.admin_profile', 'Admin Profile') }}"
       class="{{ $standaloneBase }} {{ request()->routeIs('admin.profile.*','admin.password.*','admin.preferences.*') ? $standaloneActive : $standaloneIdle }}">
        <i data-lucide="user-cog" class="h-4 w-4 shrink-0"></i><span class="sidebar-label">{{ localize('ui.r2e.admin.admin_profile', 'Admin Profile') }}</span>
    </a>
    <a href="{{ route('admin.about') }}" title="{{ localize('ui.r2e.admin.about_platform', 'About Platform') }}"
       class="{{ $standaloneBase }} {{ request()->routeIs('admin.about') ? $standaloneActive : $standaloneIdle }}">
        <i data-lucide="info" class="h-4 w-4 shrink-0"></i><span class="sidebar-label">{{ localize('ui.r2e.admin.about_platform', 'About Platform') }}</span>
    </a>

</nav>

<div class="border-t border-border p-3 space-y-1">
    <button type="button" onclick="toggleWorkspaceTheme()"
            class="sidebar-utility {{ $standaloneBase }} w-full {{ $standaloneIdle }}">
        <i data-lucide="sun-moon" class="h-4 w-4 shrink-0"></i>
        <span class="sidebar-label">{{ localize('ui.r2e.admin.toggle_theme', 'Toggle theme') }}</span>
    </button>
    <button type="button" onclick="toggleDesktopSidebar()"
            class="sidebar-utility hidden lg:flex {{ $standaloneBase }} w-full {{ $standaloneIdle }}">
        <i data-lucide="panel-left-close" class="sidebar-collapse-icon h-4 w-4 shrink-0"></i>
        <span class="sidebar-label">{{ localize('ui.r2e.admin.collapse_sidebar', 'Collapse sidebar') }}</span>
    </button>
</div>
