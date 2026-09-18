@php
    $parentBase = 'flex items-center gap-3 rounded-xl px-3 py-2.5 text-[12px] font-semibold transition-colors';
    $parentIdle = 'text-foreground bg-transparent hover:bg-muted/70';
    $parentActive = 'text-foreground bg-red-500/[.07] ring-1 ring-red-500/15 shadow-sm';

    $childBase = 'flex items-center gap-3 rounded-lg px-3 py-2 text-[11px] font-medium transition-colors';
    $childIdle = 'text-muted-foreground hover:bg-muted/55 hover:text-foreground';
    $childActive = 'bg-red-500/[.08] text-red-600 dark:text-red-400';

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
             {{ request()->routeIs('investments.*','account.investments*','admin.investments.*') ? 'open' : '' }}>
        <summary title="Investments"
                 class="{{ $parentBase }} justify-between {{ request()->routeIs('investments.*','account.investments*','admin.investments.*') ? $parentActive : $parentIdle }}">
            <span class="flex min-w-0 items-center gap-3">
                <i data-lucide="gem" class="h-4 w-4 shrink-0"></i>
                <span class="sidebar-label truncate">Investments</span>
            </span>
            <i data-lucide="chevron-down" class="sidebar-chevron h-4 w-4 transition-transform group-open:rotate-180"></i>
        </summary>

        <div class="sidebar-subnav ml-5 mt-1.5 space-y-1 border-l border-border/70 pl-4">
            <p class="px-3 pt-1 text-[8px] font-semibold uppercase tracking-[.14em] text-muted-foreground">Market</p>
            <a href="{{ route('investments.index') }}" class="{{ $childBase }} {{ request()->routeIs('investments.index','investments.show') ? $childActive : $childIdle }}"><i data-lucide="layout-grid" class="h-4 w-4"></i><span>All Investments</span></a>
            <a href="{{ route('investments.stocks') }}" class="{{ $childBase }} {{ request()->routeIs('investments.stocks') ? $childActive : $childIdle }}"><i data-lucide="chart-no-axes-combined" class="h-4 w-4"></i><span>Stocks</span></a>
            <a href="{{ route('investments.crypto') }}" class="{{ $childBase }} {{ request()->routeIs('investments.crypto') ? $childActive : $childIdle }}"><i data-lucide="coins" class="h-4 w-4"></i><span>Cryptocurrency</span></a>
            <a href="{{ route('investments.real-estate') }}" class="{{ $childBase }} {{ request()->routeIs('investments.real-estate') ? $childActive : $childIdle }}"><i data-lucide="house" class="h-4 w-4"></i><span>Real Estate</span></a>
            <a href="{{ route('investments.bonds') }}" class="{{ $childBase }} {{ request()->routeIs('investments.bonds') ? $childActive : $childIdle }}"><i data-lucide="landmark" class="h-4 w-4"></i><span>Bonds & Fixed Income</span></a>

            <div class="my-2 border-t border-border/60"></div>

            @if(auth()->user()->isAdmin())
                <p class="px-3 pt-1 text-[8px] font-semibold uppercase tracking-[.14em] text-red-500">Administration</p>
                <a href="{{ route('admin.investments.control.index') }}" class="{{ $childBase }} {{ request()->routeIs('admin.investments.*') ? $childActive : $childIdle }}"><i data-lucide="shield-check" class="h-4 w-4"></i><span>Investment Control</span></a>
                <p class="px-3 py-2 text-[9px] leading-4 text-muted-foreground">Admin can mutate customer accounts through explicit admin controls. Customer account links remain ownership-bound.</p>
            @else
                <p class="px-3 pt-1 text-[8px] font-semibold uppercase tracking-[.14em] text-muted-foreground">My Account</p>
                <a href="{{ route('account.investments') }}" class="{{ $childBase }} {{ request()->routeIs('account.investments') ? $childActive : $childIdle }}"><i data-lucide="circle-user-round" class="h-4 w-4"></i><span>My Investments</span></a>
                <a href="{{ route('account.investments.portfolio') }}" class="{{ $childBase }} {{ request()->routeIs('account.investments.portfolio') ? $childActive : $childIdle }}"><i data-lucide="pie-chart" class="h-4 w-4"></i><span>Portfolio</span></a>
                <a href="{{ route('account.investments.transactions') }}" class="{{ $childBase }} {{ request()->routeIs('account.investments.transactions') ? $childActive : $childIdle }}"><i data-lucide="receipt-text" class="h-4 w-4"></i><span>Transactions</span></a>
                <a href="{{ route('account.investments.performance') }}" class="{{ $childBase }} {{ request()->routeIs('account.investments.performance') ? $childActive : $childIdle }}"><i data-lucide="activity" class="h-4 w-4"></i><span>Performance</span></a>
                <a href="{{ route('account.investments.watchlist') }}" class="{{ $childBase }} {{ request()->routeIs('account.investments.watchlist*') ? $childActive : $childIdle }}"><i data-lucide="bookmark" class="h-4 w-4"></i><span>Watchlist</span></a>
            @endif
        </div>
    </details>

    <details class="sidebar-group group rounded-xl"
             data-nav-group="money"
             {{ request()->routeIs('money.*','wallet.*','account.history') ? 'open' : '' }}>
        <summary title="Money"
                 class="{{ $parentBase }} justify-between {{ request()->routeIs('money.*','wallet.*','account.history') ? $parentActive : $parentIdle }}">
            <span class="flex min-w-0 items-center gap-3">
                <i data-lucide="wallet-cards" class="h-4 w-4 shrink-0"></i>
                <span class="sidebar-label truncate">Money</span>
            </span>
            <i data-lucide="chevron-down" class="sidebar-chevron h-4 w-4 transition-transform group-open:rotate-180"></i>
        </summary>
        <div class="sidebar-subnav ml-5 mt-1.5 space-y-1 border-l border-border/70 pl-4">
            <a href="{{ route('money.index') }}" class="{{ $childBase }} {{ request()->routeIs('money.index') ? $childActive : $childIdle }}"><i data-lucide="wallet" class="h-4 w-4"></i><span>Overview</span></a>
            <a href="{{ route('money.add') }}" class="{{ $childBase }} {{ request()->routeIs('money.add','money.add.*') ? $childActive : $childIdle }}"><i data-lucide="circle-plus" class="h-4 w-4"></i><span>Add Money</span></a>
            <a href="{{ route('money.withdraw') }}" class="{{ $childBase }} {{ request()->routeIs('money.withdraw*') ? $childActive : $childIdle }}"><i data-lucide="circle-minus" class="h-4 w-4"></i><span>Withdraw</span></a>
            <a href="{{ route('money.send') }}" class="{{ $childBase }} {{ request()->routeIs('money.send*') ? $childActive : $childIdle }}"><i data-lucide="send" class="h-4 w-4"></i><span>Send Money</span></a>
            <a href="{{ route('money.activity') }}" class="{{ $childBase }} {{ request()->routeIs('money.activity') ? $childActive : $childIdle }}"><i data-lucide="list" class="h-4 w-4"></i><span>Activity</span></a>
            <a href="{{ route('money.connections') }}" class="{{ $childBase }} {{ request()->routeIs('money.connections*') ? $childActive : $childIdle }}"><i data-lucide="link" class="h-4 w-4"></i><span>Connections</span></a>
            <a href="{{ route('account.history') }}" class="{{ $childBase }} {{ request()->routeIs('account.history') ? $childActive : $childIdle }}"><i data-lucide="shield-check" class="h-4 w-4"></i><span>Audit History</span></a>
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

    <details class="sidebar-group group rounded-xl"
             data-nav-group="memberships"
             {{ request()->routeIs('memberships.*') ? 'open' : '' }}>
        <summary title="Membership"
                 class="{{ $parentBase }} justify-between {{ request()->routeIs('memberships.*') ? $parentActive : $parentIdle }}">
            <span class="flex min-w-0 items-center gap-3"><i data-lucide="badge" class="h-4 w-4 shrink-0"></i><span class="sidebar-label truncate">Membership</span></span>
            <i data-lucide="chevron-down" class="sidebar-chevron h-4 w-4 transition-transform group-open:rotate-180"></i>
        </summary>
        <div class="sidebar-subnav ml-5 mt-1.5 space-y-1 border-l border-border/70 pl-4">
            <a href="{{ route('memberships.index') }}" class="{{ $childBase }} {{ request()->routeIs('memberships.index') ? $childActive : $childIdle }}"><i data-lucide="layout-grid" class="h-4 w-4"></i><span>Overview</span></a>
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

    <div class="sidebar-section-label px-3 pt-4 pb-1 text-[9px] font-semibold uppercase tracking-[.16em] text-muted-foreground">Intelligence</div>

    <details class="sidebar-group group rounded-xl"
             data-nav-group="signals"
             {{ request()->routeIs('signals.*') ? 'open' : '' }}>
        <summary title="Signals"
                 class="{{ $parentBase }} justify-between {{ request()->routeIs('signals.*') ? $parentActive : $parentIdle }}">
            <span class="flex min-w-0 items-center gap-3">
                <i data-lucide="radio-tower" class="h-4 w-4 shrink-0"></i>
                <span class="sidebar-label truncate">Signals</span>
            </span>
            <i data-lucide="chevron-down" class="sidebar-chevron h-4 w-4 transition-transform group-open:rotate-180"></i>
        </summary>
        <div class="sidebar-subnav ml-5 mt-1.5 space-y-1 border-l border-border/70 pl-4">
            <a href="{{ route('signals.index') }}" class="{{ $childBase }} {{ request()->routeIs('signals.index','signals.show') ? $childActive : $childIdle }}"><i data-lucide="radio-tower" class="h-4 w-4"></i><span>Current Signals</span></a>
            <a href="{{ route('signals.history') }}" class="{{ $childBase }} {{ request()->routeIs('signals.history') ? $childActive : $childIdle }}"><i data-lucide="history" class="h-4 w-4"></i><span>History</span></a>
        </div>
    </details>
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
