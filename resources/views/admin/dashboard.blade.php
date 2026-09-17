<x-admin-layout>
    <x-slot name="header">Overview</x-slot>

    <div class="ui-page max-w-[1600px]">
        <section class="flex flex-col gap-5 border-b border-border/70 pb-5 xl:flex-row xl:items-end xl:justify-between">
            <div class="max-w-3xl">
                <div class="mb-2 flex items-center gap-2">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-border bg-muted/40">
                        <i data-lucide="layout-dashboard" class="h-3.5 w-3.5"></i>
                    </span>
                    <p class="ui-kicker text-[10px]">Admin command center</p>
                </div>

                <h1 class="ui-heading !text-3xl">Operations overview</h1>
                <p class="ui-lead !mt-2 !max-w-3xl !text-[13px]">
                    Monitor customers, money movement, trading activity, investment products and operational review queues.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.wallet-transactions.index') }}" class="ui-btn ui-btn-secondary">
                    <i data-lucide="wallet-cards" class="h-4 w-4"></i>
                    Transactions
                </a>
                <a href="{{ route('admin.kyc.index') }}" class="ui-btn ui-btn-secondary">
                    <i data-lucide="shield-check" class="h-4 w-4"></i>
                    KYC review
                </a>
                <a href="{{ route('admin.ai-bots.create') }}" class="ui-btn ui-btn-primary">
                    <i data-lucide="bot" class="h-4 w-4"></i>
                    Create bot
                </a>
            </div>
        </section>

        <section class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <a href="{{ route('admin.users.index') }}"
               class="group relative overflow-hidden rounded-2xl border border-border bg-card p-5 transition hover:-translate-y-0.5 hover:border-foreground/15">
                <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-sky-500/10 blur-2xl"></div>
                <div class="relative">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-500/10 text-sky-500">
                            <i data-lucide="users" class="h-4.5 w-4.5"></i>
                        </div>
                        <i data-lucide="arrow-up-right" class="h-4 w-4 text-muted-foreground transition group-hover:text-foreground"></i>
                    </div>
                    <div class="mt-5">
                        <p class="text-[9px] font-semibold uppercase tracking-[.16em] text-muted-foreground">Customers</p>
                        <p class="mt-1 text-3xl font-semibold tabular-nums">{{ number_format($totalUsers) }}</p>
                        <p class="mt-2 text-[10px] text-muted-foreground">{{ $conversionRate }}% order/customer ratio</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('admin.investments.transactions.index') }}"
               class="group relative overflow-hidden rounded-2xl border border-border bg-card p-5 transition hover:-translate-y-0.5 hover:border-foreground/15">
                <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-violet-500/10 blur-2xl"></div>
                <div class="relative">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-500/10 text-violet-500">
                            <i data-lucide="chart-no-axes-combined" class="h-4.5 w-4.5"></i>
                        </div>
                        <i data-lucide="arrow-up-right" class="h-4 w-4 text-muted-foreground transition group-hover:text-foreground"></i>
                    </div>
                    <div class="mt-5">
                        <p class="text-[9px] font-semibold uppercase tracking-[.16em] text-muted-foreground">Investment volume</p>
                        <p class="mt-1 text-3xl font-semibold tabular-nums">{{ currency_symbol() }}{{ number_format($investmentVolumeThisMonth, 0) }}</p>
                        <p class="mt-2 text-[10px] {{ !is_null($investmentVolumeChange) && $investmentVolumeChange < 0 ? 'text-red-500' : 'text-muted-foreground' }}">
                            {{ is_null($investmentVolumeChange) ? 'No previous-month baseline' : (($investmentVolumeChange >= 0 ? '+' : '').$investmentVolumeChange.'% vs previous month') }}
                        </p>
                    </div>
                </div>
            </a>

            <a href="{{ route('admin.stocks.transactions.index') }}"
               class="group relative overflow-hidden rounded-2xl border border-border bg-card p-5 transition hover:-translate-y-0.5 hover:border-foreground/15">
                <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-emerald-500/10 blur-2xl"></div>
                <div class="relative">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-500">
                            <i data-lucide="candlestick-chart" class="h-4.5 w-4.5"></i>
                        </div>
                        <i data-lucide="arrow-up-right" class="h-4 w-4 text-muted-foreground transition group-hover:text-foreground"></i>
                    </div>
                    <div class="mt-5">
                        <p class="text-[9px] font-semibold uppercase tracking-[.16em] text-muted-foreground">Trading volume</p>
                        <p class="mt-1 text-3xl font-semibold tabular-nums">{{ currency_symbol() }}{{ number_format($stockVolumeThisMonth, 0) }}</p>
                        <p class="mt-2 text-[10px] {{ !is_null($stockVolumeChange) && $stockVolumeChange < 0 ? 'text-red-500' : 'text-muted-foreground' }}">
                            {{ is_null($stockVolumeChange) ? 'No previous-month baseline' : (($stockVolumeChange >= 0 ? '+' : '').$stockVolumeChange.'% vs previous month') }}
                        </p>
                    </div>
                </div>
            </a>

            <a href="{{ route('admin.purchases.index') }}"
               class="group relative overflow-hidden rounded-2xl border border-border bg-card p-5 transition hover:-translate-y-0.5 hover:border-foreground/15">
                <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-amber-500/10 blur-2xl"></div>
                <div class="relative">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500/10 text-amber-500">
                            <i data-lucide="landmark" class="h-4.5 w-4.5"></i>
                        </div>
                        <i data-lucide="arrow-up-right" class="h-4 w-4 text-muted-foreground transition group-hover:text-foreground"></i>
                    </div>
                    <div class="mt-5">
                        <p class="text-[9px] font-semibold uppercase tracking-[.16em] text-muted-foreground">Combined AUM</p>
                        <p class="mt-1 text-3xl font-semibold tabular-nums">{{ currency_symbol() }}{{ number_format($totalInvestmentAum + $totalStockAum, 0) }}</p>
                        <p class="mt-2 text-[10px] text-muted-foreground">{{ currency_symbol() }}{{ number_format($totalSales, 0) }} completed marketplace sales</p>
                    </div>
                </div>
            </a>
        </section>

        <section class="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <a href="{{ route('admin.stocks.index') }}" class="group flex items-center gap-4 rounded-2xl border border-border bg-card/70 p-4 transition hover:bg-card">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-500">
                    <i data-lucide="activity" class="h-4 w-4"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-[9px] font-semibold uppercase tracking-[.14em] text-muted-foreground">Active stocks</p>
                    <div class="mt-1 flex items-end justify-between gap-3">
                        <p class="text-xl font-semibold tabular-nums">{{ number_format($activeStocks) }}</p>
                        <span class="text-[9px] text-muted-foreground">live instruments</span>
                    </div>
                </div>
            </a>

            <a href="{{ route('admin.investments.plans.index') }}" class="group flex items-center gap-4 rounded-2xl border border-border bg-card/70 p-4 transition hover:bg-card">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-violet-500/10 text-violet-500">
                    <i data-lucide="gem" class="h-4 w-4"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-[9px] font-semibold uppercase tracking-[.14em] text-muted-foreground">Active plans</p>
                    <div class="mt-1 flex items-end justify-between gap-3">
                        <p class="text-xl font-semibold tabular-nums">{{ number_format($activeInvestmentPlans) }}</p>
                        <span class="text-[9px] text-muted-foreground">investment products</span>
                    </div>
                </div>
            </a>

            <a href="{{ route('admin.investments.transactions.index') }}" class="group flex items-center gap-4 rounded-2xl border border-border bg-card/70 p-4 transition hover:bg-card">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-sky-500/10 text-sky-500">
                    <i data-lucide="repeat-2" class="h-4 w-4"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-[9px] font-semibold uppercase tracking-[.14em] text-muted-foreground">Investment activity</p>
                    <div class="mt-1 flex items-end justify-between gap-3">
                        <p class="text-xl font-semibold tabular-nums">{{ number_format($investmentTransactionsThisMonth) }}</p>
                        <span class="text-[9px] text-muted-foreground">this month</span>
                    </div>
                </div>
            </a>

            <a href="{{ route('admin.stocks.transactions.index') }}" class="group flex items-center gap-4 rounded-2xl border border-border bg-card/70 p-4 transition hover:bg-card">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-500/10 text-amber-500">
                    <i data-lucide="candlestick-chart" class="h-4 w-4"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-[9px] font-semibold uppercase tracking-[.14em] text-muted-foreground">Trading activity</p>
                    <div class="mt-1 flex items-end justify-between gap-3">
                        <p class="text-xl font-semibold tabular-nums">{{ number_format($stockTransactionsThisMonth) }}</p>
                        <span class="text-[9px] text-muted-foreground">this month</span>
                    </div>
                </div>
            </a>
        </section>

        <section class="mt-5 grid gap-5 xl:grid-cols-[1.45fr_.55fr]">
            <div class="space-y-5">
                <section class="overflow-hidden rounded-2xl border border-border bg-card">
                    <div class="flex flex-col gap-3 border-b border-border/70 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-sky-500/10 text-sky-500">
                                    <i data-lucide="activity" class="h-4 w-4"></i>
                                </div>
                                <div>
                                    <h2 class="text-sm font-semibold">Financial activity</h2>
                                    <p class="mt-0.5 text-[10px] text-muted-foreground">Recent investment and trading executions.</p>
                                </div>
                            </div>
                        </div>
                        <a href="{{ route('admin.wallet-transactions.index') }}" class="ui-btn ui-btn-secondary ui-btn-sm">
                            Open transactions
                        </a>
                    </div>

                    <div class="grid lg:grid-cols-2">
                        <div class="border-b border-border/70 p-4 lg:border-b-0 lg:border-r">
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full bg-violet-500"></span>
                                    <span class="text-[11px] font-semibold">Investments</span>
                                </div>
                                <span class="text-[9px] uppercase tracking-[.12em] text-muted-foreground">{{ $investmentTransactionsThisMonth }} this month</span>
                            </div>

                            <div class="space-y-2">
                                @forelse($recentInvestmentTransactions->take(5) as $tx)
                                    <div class="flex items-center justify-between gap-4 rounded-xl border border-border/70 bg-background/50 px-3 py-3">
                                        <div class="min-w-0">
                                            <p class="truncate text-[11px] font-semibold">{{ $tx->user->name }}</p>
                                            <p class="mt-0.5 truncate text-[9px] text-muted-foreground">{{ $tx->investmentPlan->name }} · {{ $tx->type_label }}</p>
                                        </div>
                                        <p class="shrink-0 text-[11px] font-semibold tabular-nums">{{ $tx->formatted_total_amount }}</p>
                                    </div>
                                @empty
                                    <div class="rounded-xl border border-dashed border-border p-8 text-center text-[10px] text-muted-foreground">No investment activity yet.</div>
                                @endforelse
                            </div>
                        </div>

                        <div class="p-4">
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                    <span class="text-[11px] font-semibold">Trading</span>
                                </div>
                                <span class="text-[9px] uppercase tracking-[.12em] text-muted-foreground">{{ $stockTransactionsThisMonth }} this month</span>
                            </div>

                            <div class="space-y-2">
                                @forelse($recentStockTransactions->take(5) as $tx)
                                    <div class="flex items-center justify-between gap-4 rounded-xl border border-border/70 bg-background/50 px-3 py-3">
                                        <div class="min-w-0">
                                            <p class="truncate text-[11px] font-semibold">{{ $tx->user->name }}</p>
                                            <p class="mt-0.5 truncate text-[9px] text-muted-foreground">{{ $tx->stock->symbol }} · {{ $tx->type_label }}</p>
                                        </div>
                                        <p class="shrink-0 text-[11px] font-semibold tabular-nums">{{ $tx->formatted_total_amount }}</p>
                                    </div>
                                @empty
                                    <div class="rounded-xl border border-dashed border-border p-8 text-center text-[10px] text-muted-foreground">No trading activity yet.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </section>

                <section class="overflow-hidden rounded-2xl border border-border bg-card">
                    <div class="flex items-center justify-between gap-4 border-b border-border/70 px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-500/10 text-amber-500">
                                <i data-lucide="shopping-bag" class="h-4 w-4"></i>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold">Marketplace orders</h2>
                                <p class="mt-0.5 text-[10px] text-muted-foreground">Latest vehicle commerce activity.</p>
                            </div>
                        </div>
                        <a href="{{ route('admin.purchases.index') }}" class="ui-btn ui-btn-secondary ui-btn-sm">View orders</a>
                    </div>

                    <div class="divide-y divide-border/70">
                        @forelse($recentPurchases->take(5) as $purchase)
                            <div class="flex items-center justify-between gap-5 px-5 py-3.5">
                                <div class="min-w-0">
                                    <p class="truncate text-[11px] font-semibold">{{ $purchase->user->name }}</p>
                                    <p class="mt-0.5 truncate text-[9px] text-muted-foreground">{{ optional($purchase->car)->make }} {{ optional($purchase->car)->model }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-[11px] font-semibold tabular-nums">{{ currency_symbol() }}{{ number_format($purchase->amount, 2) }}</p>
                                    <p class="mt-0.5 text-[9px] text-muted-foreground">{{ ucfirst($purchase->status) }}</p>
                                </div>
                            </div>
                        @empty
                            <div class="p-10 text-center text-[10px] text-muted-foreground">No marketplace orders yet.</div>
                        @endforelse
                    </div>
                </section>
            </div>

            <aside class="space-y-5">
                <section class="overflow-hidden rounded-2xl border border-border bg-card">
                    <div class="border-b border-border/70 px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-sky-500/10 text-sky-500">
                                <i data-lucide="list-checks" class="h-4 w-4"></i>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold">Review queues</h2>
                                <p class="mt-0.5 text-[10px] text-muted-foreground">Operational areas that may need attention.</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-2">
                        @foreach([
                            [route('admin.copy-trading.applications'),'clipboard-check','Provider applications'],
                            [route('admin.ai-bots.index'),'bot','Bot catalog'],
                            [route('admin.kyc.index'),'shield-check','KYC management'],
                            [route('admin.wallet-transactions.index'),'wallet-cards','Transactions'],
                        ] as [$url,$icon,$label])
                            <a href="{{ $url }}" class="group flex items-center justify-between rounded-xl px-3 py-3 hover:bg-muted/70">
                                <span class="flex items-center gap-2.5 text-[11px] font-medium">
                                    <i data-lucide="{{ $icon }}" class="h-3.5 w-3.5 text-muted-foreground"></i>
                                    {{ $label }}
                                </span>
                                <i data-lucide="chevron-right" class="h-3.5 w-3.5 text-muted-foreground transition group-hover:translate-x-0.5"></i>
                            </a>
                        @endforeach
                    </div>
                </section>

                <section class="overflow-hidden rounded-2xl border border-border bg-card">
                    <div class="border-b border-border/70 px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-500">
                                <i data-lucide="radar" class="h-4 w-4"></i>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold">Market health</h2>
                                <p class="mt-0.5 text-[10px] text-muted-foreground">Current catalog and market-state snapshot.</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-px bg-border/70">
                        <div class="bg-card p-4">
                            <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Active stocks</p>
                            <p class="mt-1.5 text-xl font-semibold tabular-nums">{{ $activeStocks }}</p>
                        </div>
                        <div class="bg-card p-4">
                            <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Active plans</p>
                            <p class="mt-1.5 text-xl font-semibold tabular-nums">{{ $activeInvestmentPlans }}</p>
                        </div>
                        <div class="bg-card p-4">
                            <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Gainers</p>
                            <p class="mt-1.5 text-xl font-semibold text-emerald-500 tabular-nums">{{ $marketGainers }}</p>
                        </div>
                        <div class="bg-card p-4">
                            <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Losers</p>
                            <p class="mt-1.5 text-xl font-semibold text-red-500 tabular-nums">{{ $marketLosers }}</p>
                        </div>
                    </div>
                </section>
            </aside>
        </section>
    </div>
</x-admin-layout>
