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
    <p class="sidebar-section-label px-3 text-[9px] font-semibold uppercase tracking-[.16em] text-muted-foreground">Operations</p>
</div>

<nav class="flex-1 overflow-y-auto px-3 pb-4 space-y-1.5" data-sidebar-nav>
    <a href="{{ route('admin.dashboard') }}"
       title="Overview"
       class="{{ $standaloneBase }} {{ request()->routeIs('admin.dashboard') ? $standaloneActive : $standaloneIdle }}">
        <i data-lucide="layout-dashboard" class="h-4 w-4 shrink-0"></i>
        <span class="sidebar-label">Overview</span>
    </a>

    <details class="sidebar-group group rounded-xl"
             data-nav-group="admin-trading"
             {{ request()->routeIs('admin.trading.*','admin.stocks.*') ? 'open' : '' }}>
        <summary title="Trading Command"
                 class="{{ $parentBase }} justify-between {{ request()->routeIs('admin.trading.*','admin.stocks.*') ? $parentActive : $parentIdle }}">
            <span class="flex min-w-0 items-center gap-3">
                <i data-lucide="candlestick-chart" class="h-4 w-4 shrink-0"></i>
                <span class="sidebar-label truncate">Trading Command</span>
            </span>
            <i data-lucide="chevron-down" class="sidebar-chevron h-4 w-4 transition-transform group-open:rotate-180"></i>
        </summary>
        <div class="sidebar-subnav ml-5 mt-1.5 space-y-1 border-l border-border/70 pl-4">
            <a href="{{ route('admin.trading.index') }}" class="{{ $childBase }} {{ request()->routeIs('admin.trading.index') ? $childActive : $childIdle }}"><i data-lucide="gauge" class="h-4 w-4"></i><span>Trading Overview</span></a>
            <a href="{{ route('admin.trading.manual') }}" class="{{ $childBase }} {{ request()->routeIs('admin.trading.manual') ? $childActive : $childIdle }}"><i data-lucide="candlestick-chart" class="h-4 w-4"></i><span>Trading Desk</span></a>
            <a href="{{ route('admin.stocks.index') }}" class="{{ $childBase }} {{ request()->routeIs('admin.stocks.index','admin.stocks.show','admin.stocks.trade*') ? $childActive : $childIdle }}"><i data-lucide="line-chart" class="h-4 w-4"></i><span>Markets & Stocks</span></a>
            <a href="{{ route('admin.trading.positions') }}" class="{{ $childBase }} {{ request()->routeIs('admin.trading.positions') ? $childActive : $childIdle }}"><i data-lucide="target" class="h-4 w-4"></i><span>Open Positions</span></a>
            <a href="{{ route('admin.stocks.holdings.index') }}" class="{{ $childBase }} {{ request()->routeIs('admin.stocks.holdings.*') ? $childActive : $childIdle }}"><i data-lucide="briefcase-business" class="h-4 w-4"></i><span>Stock Holdings</span></a>
            <a href="{{ route('admin.stocks.transactions.index') }}" class="{{ $childBase }} {{ request()->routeIs('admin.stocks.transactions.*') ? $childActive : $childIdle }}"><i data-lucide="receipt-text" class="h-4 w-4"></i><span>Transactions</span></a>
            <a href="{{ route('admin.trading.history') }}" class="{{ $childBase }} {{ request()->routeIs('admin.trading.history') ? $childActive : $childIdle }}"><i data-lucide="history" class="h-4 w-4"></i><span>Trade History</span></a>
        </div>
    </details>

    <details class="sidebar-group group rounded-xl"
             data-nav-group="admin-copy"
             {{ request()->routeIs('admin.copy-trading.*') ? 'open' : '' }}>
        <summary title="Copy Trading"
                 class="{{ $parentBase }} justify-between {{ request()->routeIs('admin.copy-trading.*') ? $parentActive : $parentIdle }}">
            <span class="flex min-w-0 items-center gap-3">
                <i data-lucide="users-round" class="h-4 w-4 shrink-0"></i>
                <span class="sidebar-label truncate">Copy Trading</span>
            </span>
            <i data-lucide="chevron-down" class="sidebar-chevron h-4 w-4 transition-transform group-open:rotate-180"></i>
        </summary>
        <div class="sidebar-subnav ml-5 mt-1.5 space-y-1 border-l border-border/70 pl-4">
            <a href="{{ route('admin.copy-trading.applications') }}" class="{{ $childBase }} {{ request()->routeIs('admin.copy-trading.applications*') ? $childActive : $childIdle }}"><i data-lucide="clipboard-check" class="h-4 w-4"></i><span>Provider Applications</span></a>
            <a href="{{ route('admin.copy-trading.providers') }}" class="{{ $childBase }} {{ request()->routeIs('admin.copy-trading.providers*') ? $childActive : $childIdle }}"><i data-lucide="badge-check" class="h-4 w-4"></i><span>Providers</span></a>
            <a href="{{ route('admin.copy-trading.strategies') }}" class="{{ $childBase }} {{ request()->routeIs('admin.copy-trading.strategies*') ? $childActive : $childIdle }}"><i data-lucide="workflow" class="h-4 w-4"></i><span>Strategies</span></a>
        </div>
    </details>

    <details class="sidebar-group group rounded-xl"
             data-nav-group="admin-bots"
             {{ request()->routeIs('admin.ai-bots.*') ? 'open' : '' }}>
        <summary title="AI Bot Operations"
                 class="{{ $parentBase }} justify-between {{ request()->routeIs('admin.ai-bots.*') ? $parentActive : $parentIdle }}">
            <span class="flex min-w-0 items-center gap-3">
                <i data-lucide="bot" class="h-4 w-4 shrink-0"></i>
                <span class="sidebar-label truncate">AI Bot Operations</span>
            </span>
            <i data-lucide="chevron-down" class="sidebar-chevron h-4 w-4 transition-transform group-open:rotate-180"></i>
        </summary>
        <div class="sidebar-subnav ml-5 mt-1.5 space-y-1 border-l border-border/70 pl-4">
            <a href="{{ route('admin.ai-bots.index') }}" class="{{ $childBase }} {{ request()->routeIs('admin.ai-bots.index','admin.ai-bots.edit') ? $childActive : $childIdle }}"><i data-lucide="store" class="h-4 w-4"></i><span>Bot Catalog</span></a>
            <a href="{{ route('admin.ai-bots.create') }}" class="{{ $childBase }} {{ request()->routeIs('admin.ai-bots.create') ? $childActive : $childIdle }}"><i data-lucide="circle-plus" class="h-4 w-4"></i><span>Create Bot</span></a>
            <a href="{{ route('admin.ai-bots.subscriptions') }}" class="{{ $childBase }} {{ request()->routeIs('admin.ai-bots.subscriptions') ? $childActive : $childIdle }}"><i data-lucide="badge-dollar-sign" class="h-4 w-4"></i><span>Subscriptions</span></a>
            <a href="{{ route('admin.ai-bots.executions') }}" class="{{ $childBase }} {{ request()->routeIs('admin.ai-bots.executions*') ? $childActive : $childIdle }}"><i data-lucide="activity" class="h-4 w-4"></i><span>Executions</span></a>
        </div>
    </details>

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
            <a href="{{ route('admin.investments.control.index') }}"
               class="{{ $childBase }} {{ request()->routeIs('admin.investments.control.*') ? $childActive : $childIdle }}">
                <i data-lucide="layout-dashboard" class="h-4 w-4"></i>
                <span>Investment Control</span>
            </a>

            <a href="{{ route('investments.index') }}" class="{{ $childBase }}">
                <i data-lucide="eye" class="h-4 w-4"></i>
                <span>Customer Market</span>
            </a>

            <details class="group/legacy rounded-lg">
                <summary class="{{ $childBase }} cursor-pointer justify-between text-muted-foreground hover:text-foreground">
                    <span class="flex items-center gap-3">
                        <i data-lucide="flask-conical" class="h-4 w-4"></i>
                        <span>Legacy Lab</span>
                    </span>
                    <i data-lucide="chevron-down" class="h-3.5 w-3.5 transition-transform group-open/legacy:rotate-180"></i>
                </summary>

                <div class="mt-1 space-y-1 pl-5">
                    <a href="{{ route('admin.investments.holdings.index') }}"
                       class="{{ $childBase }} {{ request()->routeIs('admin.investments.holdings.*') ? $childActive : $childIdle }}">
                        <i data-lucide="archive" class="h-3.5 w-3.5"></i><span>Old Holdings</span>
                    </a>
                    <a href="{{ route('admin.investments.transactions.index') }}"
                       class="{{ $childBase }} {{ request()->routeIs('admin.investments.transactions.*') ? $childActive : $childIdle }}">
                        <i data-lucide="receipt" class="h-3.5 w-3.5"></i><span>Old Transactions</span>
                    </a>
                    <a href="{{ route('admin.investments.plans.index') }}"
                       class="{{ $childBase }} {{ request()->routeIs('admin.investments.plans.*','admin.investments.nav-updates.*','admin.investments.automatic-nav-updates.*') ? $childActive : $childIdle }}">
                        <i data-lucide="layers-3" class="h-3.5 w-3.5"></i><span>NAV Plans</span>
                    </a>
                </div>
            </details>
        </div>
    </details>

    <div class="sidebar-section-label px-3 pt-4 pb-1 text-[9px] font-semibold uppercase tracking-[.16em] text-muted-foreground">Customers & Finance</div>

    <details class="sidebar-group group rounded-xl"
             data-nav-group="admin-transactions"
             {{ request()->routeIs('admin.wallet-transactions.*','admin.withdrawal-token-requests.*') ? 'open' : '' }}>
        <summary title="Transactions"
                 class="{{ $parentBase }} justify-between {{ request()->routeIs('admin.wallet-transactions.*','admin.withdrawal-token-requests.*') ? $parentActive : $parentIdle }}">
            <span class="flex min-w-0 items-center gap-3">
                <i data-lucide="wallet-cards" class="h-4 w-4 shrink-0"></i>
                <span class="sidebar-label truncate">Transactions</span>
            </span>
            <i data-lucide="chevron-down" class="sidebar-chevron h-4 w-4 transition-transform group-open:rotate-180"></i>
        </summary>
        <div class="sidebar-subnav ml-5 mt-1.5 space-y-1 border-l border-border/70 pl-4">
            <a href="{{ route('admin.wallet-transactions.index') }}"
               class="{{ $childBase }} {{ request()->routeIs('admin.wallet-transactions.*') && !request()->filled('type') ? $childActive : $childIdle }}">
                <i data-lucide="list" class="h-4 w-4"></i><span>All Transactions</span>
            </a>
            <a href="{{ route('admin.wallet-transactions.index', ['type' => 'deposit']) }}"
               class="{{ $childBase }} {{ request()->routeIs('admin.wallet-transactions.index') && request()->query('type') === 'deposit' ? $childActive : $childIdle }}">
                <i data-lucide="arrow-down-to-line" class="h-4 w-4"></i><span>Deposits</span>
            </a>
            <a href="{{ route('admin.wallet-transactions.index', ['type' => 'withdrawal']) }}"
               class="{{ $childBase }} {{ request()->routeIs('admin.wallet-transactions.index') && request()->query('type') === 'withdrawal' ? $childActive : $childIdle }}">
                <i data-lucide="arrow-up-from-line" class="h-4 w-4"></i><span>Withdrawals</span>
            </a>
            <a href="{{ route('admin.withdrawal-token-requests.index') }}"
               class="{{ $childBase }} {{ request()->routeIs('admin.withdrawal-token-requests.*') ? $childActive : $childIdle }}">
                <i data-lucide="shield-check" class="h-4 w-4"></i><span>Withdrawal Requests</span>
            </a>
        </div>
    </details>
    <details class="sidebar-group group rounded-xl"
             data-nav-group="admin-vip"
             {{ request()->routeIs('admin.vip.*') ? 'open' : '' }}>
        <summary title="VIP Membership"
                 class="{{ $parentBase }} justify-between {{ request()->routeIs('admin.vip.*') ? $parentActive : $parentIdle }}">
            <span class="flex min-w-0 items-center gap-3">
                <i data-lucide="crown" class="h-4 w-4 shrink-0"></i>
                <span class="sidebar-label truncate">VIP Membership</span>
            </span>
            <i data-lucide="chevron-down" class="sidebar-chevron h-4 w-4 transition-transform group-open:rotate-180"></i>
        </summary>
        <div class="sidebar-subnav ml-5 mt-1.5 space-y-1 border-l border-border/70 pl-4">
            <a href="{{ route('admin.vip.index') }}" class="{{ $childBase }} {{ request()->routeIs('admin.vip.index','admin.vip.plans.*','admin.vip.entitlements.*') ? $childActive : $childIdle }}"><i data-lucide="layers-3" class="h-4 w-4"></i><span>Plans & Entitlements</span></a>
            <a href="{{ route('admin.vip.memberships') }}" class="{{ $childBase }} {{ request()->routeIs('admin.vip.memberships*') ? $childActive : $childIdle }}"><i data-lucide="badge-check" class="h-4 w-4"></i><span>Memberships</span></a>
        </div>
    </details>
    <a href="{{ route('admin.users.index') }}" title="Customers"
       class="{{ $standaloneBase }} {{ request()->routeIs('admin.users.*') ? $standaloneActive : $standaloneIdle }}">
        <i data-lucide="users" class="h-4 w-4 shrink-0"></i><span class="sidebar-label">Customers</span>
    </a>
    <a href="{{ route('admin.kyc.index') }}" title="KYC & Compliance"
       class="{{ $standaloneBase }} {{ request()->routeIs('admin.kyc.*') ? $standaloneActive : $standaloneIdle }}">
        <i data-lucide="shield-check" class="h-4 w-4 shrink-0"></i><span class="sidebar-label">KYC & Compliance</span>
    </a>
    <a href="{{ route('admin.payment_methods.index') }}" title="Payment Methods"
       class="{{ $standaloneBase }} {{ request()->routeIs('admin.payment_methods.*') ? $standaloneActive : $standaloneIdle }}">
        <i data-lucide="credit-card" class="h-4 w-4 shrink-0"></i><span class="sidebar-label">Payment Methods</span>
    </a>
    <a href="{{ route('admin.purchases.index') }}" title="Orders"
       class="{{ $standaloneBase }} {{ request()->routeIs('admin.purchases.*') ? $standaloneActive : $standaloneIdle }}">
        <i data-lucide="package-check" class="h-4 w-4 shrink-0"></i><span class="sidebar-label">Orders</span>
    </a>

    <details class="sidebar-group group rounded-xl"
             data-nav-group="admin-commerce"
             {{ request()->routeIs('admin.cars.*') ? 'open' : '' }}>
        <summary title="Commerce Catalog"
                 class="{{ $parentBase }} justify-between {{ request()->routeIs('admin.cars.*') ? $parentActive : $parentIdle }}">
            <span class="flex min-w-0 items-center gap-3">
                <i data-lucide="shopping-bag" class="h-4 w-4 shrink-0"></i>
                <span class="sidebar-label truncate">Commerce Catalog</span>
            </span>
            <i data-lucide="chevron-down" class="sidebar-chevron h-4 w-4 transition-transform group-open:rotate-180"></i>
        </summary>
        <div class="sidebar-subnav ml-5 mt-1.5 space-y-1 border-l border-border/70 pl-4">
            <a href="{{ route('admin.cars.index') }}" class="{{ $childBase }} {{ request()->routeIs('admin.cars.index','admin.cars.show') ? $childActive : $childIdle }}"><i data-lucide="car" class="h-4 w-4"></i><span>Car Catalog</span></a>
            <a href="{{ route('admin.cars.create') }}" class="{{ $childBase }} {{ request()->routeIs('admin.cars.create') ? $childActive : $childIdle }}"><i data-lucide="circle-plus" class="h-4 w-4"></i><span>Add Car</span></a>
        </div>
    </details>

    <div class="sidebar-section-label px-3 pt-4 pb-1 text-[9px] font-semibold uppercase tracking-[.16em] text-muted-foreground">Communication & Platform</div>

    <details class="sidebar-group group rounded-xl"
             data-nav-group="admin-email"
             {{ request()->routeIs('admin.emails.*') ? 'open' : '' }}>
        <summary title="Email Center"
                 class="{{ $parentBase }} justify-between {{ request()->routeIs('admin.emails.*') ? $parentActive : $parentIdle }}">
            <span class="flex min-w-0 items-center gap-3">
                <i data-lucide="mail" class="h-4 w-4 shrink-0"></i>
                <span class="sidebar-label truncate">Email Center</span>
            </span>
            <i data-lucide="chevron-down" class="sidebar-chevron h-4 w-4 transition-transform group-open:rotate-180"></i>
        </summary>
        <div class="sidebar-subnav ml-5 mt-1.5 space-y-1 border-l border-border/70 pl-4">
            <a href="{{ route('admin.emails.index') }}" class="{{ $childBase }} {{ request()->routeIs('admin.emails.index','admin.emails.templates.*') ? $childActive : $childIdle }}"><i data-lucide="files" class="h-4 w-4"></i><span>Templates</span></a>
            <a href="{{ route('admin.emails.compose') }}" class="{{ $childBase }} {{ request()->routeIs('admin.emails.compose') ? $childActive : $childIdle }}"><i data-lucide="send" class="h-4 w-4"></i><span>Compose Email</span></a>
        </div>
    </details>

    <details class="sidebar-group group rounded-xl"
             data-nav-group="admin-settings"
             {{ request()->routeIs('admin.settings.*') ? 'open' : '' }}>
        <summary title="System Settings"
                 class="{{ $parentBase }} justify-between {{ request()->routeIs('admin.settings.*') ? $parentActive : $parentIdle }}">
            <span class="flex min-w-0 items-center gap-3">
                <i data-lucide="settings-2" class="h-4 w-4 shrink-0"></i>
                <span class="sidebar-label truncate">System Settings</span>
            </span>
            <i data-lucide="chevron-down" class="sidebar-chevron h-4 w-4 transition-transform group-open:rotate-180"></i>
        </summary>
        <div class="sidebar-subnav ml-5 mt-1.5 space-y-1 border-l border-border/70 pl-4">
            <a href="{{ route('admin.settings.index', ['section' => 'overview']) }}" class="{{ $childBase }} {{ request()->routeIs('admin.settings.*') && request()->query('section', 'overview') === 'overview' ? $childActive : $childIdle }}"><i data-lucide="layout-dashboard" class="h-4 w-4"></i><span>Overview</span></a>
            <a href="{{ route('admin.settings.index', ['section' => 'general']) }}" class="{{ $childBase }} {{ request()->routeIs('admin.settings.*') && request()->query('section') === 'general' ? $childActive : $childIdle }}"><i data-lucide="settings-2" class="h-4 w-4"></i><span>General</span></a>
            <a href="{{ route('admin.settings.index', ['section' => 'appearance']) }}" class="{{ $childBase }} {{ request()->routeIs('admin.settings.*') && request()->query('section') === 'appearance' ? $childActive : $childIdle }}"><i data-lucide="palette" class="h-4 w-4"></i><span>Appearance</span></a>
            <a href="{{ route('admin.settings.index', ['section' => 'market']) }}" class="{{ $childBase }} {{ request()->routeIs('admin.settings.*') && request()->query('section') === 'market' ? $childActive : $childIdle }}"><i data-lucide="chart-candlestick" class="h-4 w-4"></i><span>Market</span></a>
            <a href="{{ route('admin.settings.index', ['section' => 'trading']) }}" class="{{ $childBase }} {{ request()->routeIs('admin.settings.*') && request()->query('section') === 'trading' ? $childActive : $childIdle }}"><i data-lucide="chart-no-axes-combined" class="h-4 w-4"></i><span>Trading</span></a>
            <a href="{{ route('admin.settings.index', ['section' => 'security']) }}" class="{{ $childBase }} {{ request()->routeIs('admin.settings.*') && request()->query('section') === 'security' ? $childActive : $childIdle }}"><i data-lucide="shield-check" class="h-4 w-4"></i><span>Security</span></a>
            <a href="{{ route('admin.settings.index', ['section' => 'mail']) }}" class="{{ $childBase }} {{ request()->routeIs('admin.settings.*') && request()->query('section') === 'mail' ? $childActive : $childIdle }}"><i data-lucide="mail" class="h-4 w-4"></i><span>Mail & Notifications</span></a>
            <a href="{{ route('admin.settings.index', ['section' => 'integrations']) }}" class="{{ $childBase }} {{ request()->routeIs('admin.settings.*') && request()->query('section') === 'integrations' ? $childActive : $childIdle }}"><i data-lucide="plug-zap" class="h-4 w-4"></i><span>Integrations</span></a>
            <a href="{{ route('admin.settings.index', ['section' => 'system']) }}" class="{{ $childBase }} {{ request()->routeIs('admin.settings.*') && request()->query('section') === 'system' ? $childActive : $childIdle }}"><i data-lucide="server-cog" class="h-4 w-4"></i><span>System</span></a>
        </div>
    </details>
    <a href="{{ route('cron.setup') }}" title="Scheduler & Cron"
       class="{{ $standaloneBase }} {{ request()->routeIs('cron.setup') ? $standaloneActive : $standaloneIdle }}">
        <i data-lucide="clock-3" class="h-4 w-4 shrink-0"></i><span class="sidebar-label">Scheduler & Cron</span>
    </a>
    <a href="{{ route('admin.profile.edit') }}" title="Admin Profile"
       class="{{ $standaloneBase }} {{ request()->routeIs('admin.profile.*','admin.password.*','admin.preferences.*') ? $standaloneActive : $standaloneIdle }}">
        <i data-lucide="user-cog" class="h-4 w-4 shrink-0"></i><span class="sidebar-label">Admin Profile</span>
    </a>
    <a href="{{ route('admin.about') }}" title="About Platform"
       class="{{ $standaloneBase }} {{ request()->routeIs('admin.about') ? $standaloneActive : $standaloneIdle }}">
        <i data-lucide="info" class="h-4 w-4 shrink-0"></i><span class="sidebar-label">About Platform</span>
    </a>

    <div class="sidebar-section-label px-3 pt-4 pb-1 text-[9px] font-semibold uppercase tracking-[.16em] text-muted-foreground">Intelligence Roadmap</div>

    @if(Route::has('admin.signals.index'))
        <a href="{{ route('admin.signals.index') }}" class="{{ $standaloneBase }} {{ request()->routeIs('admin.signals.*') ? $standaloneActive : $standaloneIdle }}">
            <i data-lucide="radio-tower" class="h-4 w-4 shrink-0"></i><span class="sidebar-label">Signal Engine</span>
        </a>
    @else
        <div class="{{ $standaloneBase }} cursor-not-allowed opacity-45">
            <i data-lucide="radio-tower" class="h-4 w-4 shrink-0"></i><span class="sidebar-label">Signal Engine · Planned</span>
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
