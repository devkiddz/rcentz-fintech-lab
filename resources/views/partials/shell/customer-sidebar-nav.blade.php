@php
    $parentBase = 'flex items-center gap-3 rounded-xl px-3 py-2.5 text-[12px] font-semibold transition-colors';
    $parentIdle = 'text-foreground bg-muted/70 hover:bg-muted';
    $parentActive = 'text-foreground bg-muted shadow-sm';

    $childBase = 'flex items-center gap-3 rounded-lg px-3 py-2 text-[11px] font-medium transition-colors';
    $childIdle = 'text-muted-foreground hover:bg-muted/50 hover:text-foreground';
    $childActive = 'bg-muted/55 text-foreground';

    $standaloneBase = 'flex items-center gap-3 rounded-xl px-3 py-2.5 text-[12px] font-semibold transition-colors';
    $standaloneIdle = 'text-foreground bg-muted/70 hover:bg-muted';
    $standaloneActive = 'text-foreground bg-muted shadow-sm';
@endphp

<div class="px-3 pt-3 pb-1">
    <p class="sidebar-section-label px-3 text-[9px] font-semibold uppercase tracking-[.16em] text-muted-foreground">Workspace</p>
</div>

<nav class="flex-1 overflow-y-auto px-3 pb-4 space-y-1.5" data-sidebar-nav>
    <a href="{{ route('dashboard') }}" title="Overview"
       class="{{ $standaloneBase }} {{ request()->routeIs('dashboard') ? $standaloneActive : $standaloneIdle }}">
        <i data-lucide="layout-dashboard" class="h-4 w-4 shrink-0"></i>
        <span class="sidebar-label">Overview</span>
    </a>

    <details class="sidebar-group group rounded-xl"
             data-nav-group="trading"
             {{ request()->routeIs('stocks.*','trading.*') ? 'open' : '' }}>
        <summary title="Trading"
                 class="{{ $parentBase }} justify-between {{ request()->routeIs('stocks.*','trading.*') ? $parentActive : $parentIdle }}">
            <span class="flex min-w-0 items-center gap-3">
                <i data-lucide="candlestick-chart" class="h-4 w-4 shrink-0"></i>
                <span class="sidebar-label truncate">Trading</span>
            </span>
            <i data-lucide="chevron-down" class="sidebar-chevron h-4 w-4 transition-transform group-open:rotate-180"></i>
        </summary>
        <div class="sidebar-subnav ml-5 mt-1.5 space-y-1 border-l border-border/70 pl-4">
            <a href="{{ route('stocks.index') }}" class="{{ $childBase }} {{ request()->routeIs('stocks.*') ? $childActive : $childIdle }}"><i data-lucide="activity" class="h-4 w-4"></i><span>Live Markets</span></a>
            <a href="{{ route('trading.positions.index') }}" class="{{ $childBase }} {{ request()->routeIs('trading.positions.*') ? $childActive : $childIdle }}"><i data-lucide="target" class="h-4 w-4"></i><span>Trade Positions</span></a>
            <a href="{{ route('trading.portfolio') }}" class="{{ $childBase }} {{ request()->routeIs('trading.portfolio') ? $childActive : $childIdle }}"><i data-lucide="briefcase-business" class="h-4 w-4"></i><span>Stock Portfolio</span></a>
            <a href="{{ route('trading.transactions') }}" class="{{ $childBase }} {{ request()->routeIs('trading.transactions') ? $childActive : $childIdle }}"><i data-lucide="receipt-text" class="h-4 w-4"></i><span>Transactions</span></a>
            <a href="{{ route('trading.watchlist') }}" class="{{ $childBase }} {{ request()->routeIs('trading.watchlist*') ? $childActive : $childIdle }}"><i data-lucide="bookmark" class="h-4 w-4"></i><span>Watchlist</span></a>
        </div>
    </details>

    <details class="sidebar-group group rounded-xl"
             data-nav-group="copy-trading"
             {{ request()->routeIs('copy-trading.*') ? 'open' : '' }}>
        <summary title="Copy Trading"
                 class="{{ $parentBase }} justify-between {{ request()->routeIs('copy-trading.*') ? $parentActive : $parentIdle }}">
            <span class="flex min-w-0 items-center gap-3">
                <i data-lucide="users-round" class="h-4 w-4 shrink-0"></i>
                <span class="sidebar-label truncate">Copy Trading</span>
            </span>
            <i data-lucide="chevron-down" class="sidebar-chevron h-4 w-4 transition-transform group-open:rotate-180"></i>
        </summary>
        <div class="sidebar-subnav ml-5 mt-1.5 space-y-1 border-l border-border/70 pl-4">
            <a href="{{ route('copy-trading.marketplace') }}" class="{{ $childBase }} {{ request()->routeIs('copy-trading.marketplace') ? $childActive : $childIdle }}"><i data-lucide="store" class="h-4 w-4"></i><span>Strategy Marketplace</span></a>
            <a href="{{ route('copy-trading.my-copies') }}" class="{{ $childBase }} {{ request()->routeIs('copy-trading.my-copies','copy-trading.relationships.*') ? $childActive : $childIdle }}"><i data-lucide="copy-check" class="h-4 w-4"></i><span>My Copies</span></a>
            <a href="{{ route('copy-trading.executions') }}" class="{{ $childBase }} {{ request()->routeIs('copy-trading.executions*') ? $childActive : $childIdle }}"><i data-lucide="list-checks" class="h-4 w-4"></i><span>Execution History</span></a>
            <a href="{{ route('copy-trading.apply') }}" class="{{ $childBase }} {{ request()->routeIs('copy-trading.apply*') ? $childActive : $childIdle }}"><i data-lucide="badge-check" class="h-4 w-4"></i><span>Become a Provider</span></a>
            @if(auth()->user()->copyTraderProfile?->approved_at)
                <a href="{{ route('copy-trading.provider.dashboard') }}" class="{{ $childBase }} {{ request()->routeIs('copy-trading.provider.*') ? $childActive : $childIdle }}"><i data-lucide="panel-top" class="h-4 w-4"></i><span>Provider Desk</span></a>
            @endif
        </div>
    </details>

    <details class="sidebar-group group rounded-xl"
             data-nav-group="bots"
             {{ request()->routeIs('ai-bots.*') ? 'open' : '' }}>
        <summary title="AI Trading Bots"
                 class="{{ $parentBase }} justify-between {{ request()->routeIs('ai-bots.*') ? $parentActive : $parentIdle }}">
            <span class="flex min-w-0 items-center gap-3">
                <i data-lucide="bot" class="h-4 w-4 shrink-0"></i>
                <span class="sidebar-label truncate">AI Trading Bots</span>
            </span>
            <i data-lucide="chevron-down" class="sidebar-chevron h-4 w-4 transition-transform group-open:rotate-180"></i>
        </summary>
        <div class="sidebar-subnav ml-5 mt-1.5 space-y-1 border-l border-border/70 pl-4">
            <a href="{{ route('ai-bots.marketplace') }}" class="{{ $childBase }} {{ request()->routeIs('ai-bots.marketplace','ai-bots.show') ? $childActive : $childIdle }}"><i data-lucide="store" class="h-4 w-4"></i><span>Bot Marketplace</span></a>
            <a href="{{ route('ai-bots.my-bots') }}" class="{{ $childBase }} {{ request()->routeIs('ai-bots.my-bots','ai-bots.configure','ai-bots.run','ai-bots.update','ai-bots.toggle') ? $childActive : $childIdle }}"><i data-lucide="bot-message-square" class="h-4 w-4"></i><span>My Bots</span></a>
            <a href="{{ route('ai-bots.subscriptions') }}" class="{{ $childBase }} {{ request()->routeIs('ai-bots.subscriptions') ? $childActive : $childIdle }}"><i data-lucide="badge-dollar-sign" class="h-4 w-4"></i><span>Subscriptions</span></a>
            <a href="{{ route('ai-bots.performance') }}" class="{{ $childBase }} {{ request()->routeIs('ai-bots.performance','ai-bots.executions.*') ? $childActive : $childIdle }}"><i data-lucide="chart-no-axes-combined" class="h-4 w-4"></i><span>Performance</span></a>
        </div>
    </details>

    <details class="sidebar-group group rounded-xl"
             data-nav-group="investments"
             {{ request()->routeIs('investments.*','investment.*','portfolio.*','watchlist.*','automatic-investments.*') ? 'open' : '' }}>
        <summary title="Investments"
                 class="{{ $parentBase }} justify-between {{ request()->routeIs('investments.*','investment.*','portfolio.*','watchlist.*','automatic-investments.*') ? $parentActive : $parentIdle }}">
            <span class="flex min-w-0 items-center gap-3">
                <i data-lucide="gem" class="h-4 w-4 shrink-0"></i>
                <span class="sidebar-label truncate">Investments</span>
            </span>
            <i data-lucide="chevron-down" class="sidebar-chevron h-4 w-4 transition-transform group-open:rotate-180"></i>
        </summary>
        <div class="sidebar-subnav ml-5 mt-1.5 space-y-1 border-l border-border/70 pl-4">
            <a href="{{ route('investments.index') }}" class="{{ $childBase }} {{ request()->routeIs('investments.*') ? $childActive : $childIdle }}"><i data-lucide="search" class="h-4 w-4"></i><span>Browse Plans</span></a>
            <a href="{{ route('investment.dashboard') }}" class="{{ $childBase }} {{ request()->routeIs('investment.dashboard') ? $childActive : $childIdle }}"><i data-lucide="layout-dashboard" class="h-4 w-4"></i><span>Investment Dashboard</span></a>
            <a href="{{ route('portfolio.index') }}" class="{{ $childBase }} {{ request()->routeIs('portfolio.index','portfolio.holdings') ? $childActive : $childIdle }}"><i data-lucide="pie-chart" class="h-4 w-4"></i><span>Portfolio & Holdings</span></a>
            <a href="{{ route('portfolio.transactions') }}" class="{{ $childBase }} {{ request()->routeIs('portfolio.transactions','investment.transactions') ? $childActive : $childIdle }}"><i data-lucide="receipt" class="h-4 w-4"></i><span>Transactions</span></a>
            <a href="{{ route('investment.analytics') }}" class="{{ $childBase }} {{ request()->routeIs('investment.analytics','portfolio.analytics') ? $childActive : $childIdle }}"><i data-lucide="chart-spline" class="h-4 w-4"></i><span>Analytics</span></a>
            <a href="{{ route('automatic-investments.index') }}" class="{{ $childBase }} {{ request()->routeIs('automatic-investments.*') ? $childActive : $childIdle }}"><i data-lucide="repeat-2" class="h-4 w-4"></i><span>Automatic Investing</span></a>
            <a href="{{ route('watchlist.index') }}" class="{{ $childBase }} {{ request()->routeIs('watchlist.*') ? $childActive : $childIdle }}"><i data-lucide="bookmark" class="h-4 w-4"></i><span>Investment Watchlist</span></a>
        </div>
    </details>

    <details class="sidebar-group group rounded-xl"
             data-nav-group="wallet"
             {{ request()->routeIs('wallet.*','account.history') ? 'open' : '' }}>
        <summary title="Wallet & Money"
                 class="{{ $parentBase }} justify-between {{ request()->routeIs('wallet.*','account.history') ? $parentActive : $parentIdle }}">
            <span class="flex min-w-0 items-center gap-3">
                <i data-lucide="wallet-cards" class="h-4 w-4 shrink-0"></i>
                <span class="sidebar-label truncate">Wallet & Money</span>
            </span>
            <i data-lucide="chevron-down" class="sidebar-chevron h-4 w-4 transition-transform group-open:rotate-180"></i>
        </summary>
        <div class="sidebar-subnav ml-5 mt-1.5 space-y-1 border-l border-border/70 pl-4">
            <a href="{{ route('wallet.index') }}" class="{{ $childBase }} {{ request()->routeIs('wallet.index') ? $childActive : $childIdle }}"><i data-lucide="wallet" class="h-4 w-4"></i><span>Wallet Overview</span></a>
            <a href="{{ route('wallet.deposit') }}" class="{{ $childBase }} {{ request()->routeIs('wallet.deposit','wallet.process-deposit','wallet.crypto-payment*') ? $childActive : $childIdle }}"><i data-lucide="circle-plus" class="h-4 w-4"></i><span>Deposit Funds</span></a>
            <a href="{{ route('wallet.withdraw') }}" class="{{ $childBase }} {{ request()->routeIs('wallet.withdraw','wallet.process-withdrawal') ? $childActive : $childIdle }}"><i data-lucide="circle-minus" class="h-4 w-4"></i><span>Withdraw Funds</span></a>
            <a href="{{ route('wallet.transfer') }}" class="{{ $childBase }} {{ request()->routeIs('wallet.transfer','wallet.process-transfer') ? $childActive : $childIdle }}"><i data-lucide="send" class="h-4 w-4"></i><span>Send Money</span></a>
            <a href="{{ route('wallet.transactions') }}" class="{{ $childBase }} {{ request()->routeIs('wallet.transactions') ? $childActive : $childIdle }}"><i data-lucide="list" class="h-4 w-4"></i><span>Wallet Transactions</span></a>
            <a href="{{ route('wallet.connections') }}" class="{{ $childBase }} {{ request()->routeIs('wallet.connections*') ? $childActive : $childIdle }}"><i data-lucide="link" class="h-4 w-4"></i><span>Connected Wallets</span></a>
            <a href="{{ route('account.history') }}" class="{{ $childBase }} {{ request()->routeIs('account.history') ? $childActive : $childIdle }}"><i data-lucide="history" class="h-4 w-4"></i><span>Financial History</span></a>
        </div>
    </details>

    <details class="sidebar-group group rounded-xl"
             data-nav-group="commerce"
             {{ request()->routeIs('cars.*','dashboard.history') ? 'open' : '' }}>
        <summary title="Commerce"
                 class="{{ $parentBase }} justify-between {{ request()->routeIs('cars.*','dashboard.history') ? $parentActive : $parentIdle }}">
            <span class="flex min-w-0 items-center gap-3">
                <i data-lucide="shopping-bag" class="h-4 w-4 shrink-0"></i>
                <span class="sidebar-label truncate">Commerce</span>
            </span>
            <i data-lucide="chevron-down" class="sidebar-chevron h-4 w-4 transition-transform group-open:rotate-180"></i>
        </summary>
        <div class="sidebar-subnav ml-5 mt-1.5 space-y-1 border-l border-border/70 pl-4">
            <a href="{{ route('cars.browse') }}" class="{{ $childBase }} {{ request()->routeIs('cars.*') ? $childActive : $childIdle }}"><i data-lucide="car" class="h-4 w-4"></i><span>Browse Cars</span></a>
            <a href="{{ route('dashboard.history') }}" class="{{ $childBase }} {{ request()->routeIs('dashboard.history') ? $childActive : $childIdle }}"><i data-lucide="package-check" class="h-4 w-4"></i><span>Orders & Invoices</span></a>
        </div>
    </details>

    <div class="sidebar-section-label px-3 pt-4 pb-1 text-[9px] font-semibold uppercase tracking-[.16em] text-muted-foreground">Account & Trust</div>

    <details class="sidebar-group group rounded-xl"
             data-nav-group="account"
             {{ request()->routeIs('notifications.*','profile.*','support.*') ? 'open' : '' }}>
        <summary title="Account"
                 class="{{ $parentBase }} justify-between {{ request()->routeIs('notifications.*','profile.*','support.*') ? $parentActive : $parentIdle }}">
            <span class="flex min-w-0 items-center gap-3">
                <i data-lucide="circle-user-round" class="h-4 w-4 shrink-0"></i>
                <span class="sidebar-label truncate">Account</span>
            </span>
            <i data-lucide="chevron-down" class="sidebar-chevron h-4 w-4 transition-transform group-open:rotate-180"></i>
        </summary>
        <div class="sidebar-subnav ml-5 mt-1.5 space-y-1 border-l border-border/70 pl-4">
            <a href="{{ route('notifications.index') }}" class="{{ $childBase }} {{ request()->routeIs('notifications.*') ? $childActive : $childIdle }}"><i data-lucide="bell" class="h-4 w-4"></i><span>Notifications</span></a>
            <a href="{{ route('profile.edit') }}" class="{{ $childBase }} {{ request()->routeIs('profile.edit','profile.update') ? $childActive : $childIdle }}"><i data-lucide="user-round-cog" class="h-4 w-4"></i><span>Profile & Security</span></a>
            <a href="{{ route('profile.kyc') }}" class="{{ $childBase }} {{ request()->routeIs('profile.kyc*') ? $childActive : $childIdle }}"><i data-lucide="shield-check" class="h-4 w-4"></i><span>Identity Verification</span></a>
            <a href="{{ route('support.index') }}" class="{{ $childBase }} {{ request()->routeIs('support.*') ? $childActive : $childIdle }}"><i data-lucide="headphones" class="h-4 w-4"></i><span>Support Center</span></a>
        </div>
    </details>

    <div class="sidebar-section-label px-3 pt-4 pb-1 text-[9px] font-semibold uppercase tracking-[.16em] text-muted-foreground">Intelligence Roadmap</div>

    @if(Route::has('signals.index'))
        <a href="{{ route('signals.index') }}"
           class="{{ $standaloneBase }} {{ request()->routeIs('signals.*') ? $standaloneActive : $standaloneIdle }}">
            <i data-lucide="radio-tower" class="h-4 w-4 shrink-0"></i><span class="sidebar-label">Signal Center</span>
        </a>
    @else
        <div class="{{ $standaloneBase }} cursor-not-allowed opacity-45">
            <i data-lucide="radio-tower" class="h-4 w-4 shrink-0"></i><span class="sidebar-label">Signal Center · Planned</span>
        </div>
    @endif
</nav>

<div class="border-t border-border p-3 space-y-1">
    <button type="button" onclick="toggleWorkspaceTheme()"
            class="sidebar-utility {{ $standaloneBase }} w-full {{ $standaloneIdle }}">
        <i data-lucide="sun-moon" class="h-4 w-4 shrink-0"></i>
        <span class="sidebar-label">Toggle theme</span>
    </button>
    <button type="button" onclick="toggleDesktopSidebar()"
            class="sidebar-utility hidden lg:flex {{ $standaloneBase }} w-full {{ $standaloneIdle }}">
        <i data-lucide="panel-left-close" class="sidebar-collapse-icon h-4 w-4 shrink-0"></i>
        <span class="sidebar-label">Collapse sidebar</span>
    </button>
</div>
