<x-admin-layout>
    <x-slot name="header">Overview</x-slot>

    <div class="ui-page max-w-[1600px]">
        <section class="ui-page-header">
            <div>
                <p class="ui-kicker text-[10px]">Admin command center</p>
                <h1 class="ui-heading !text-2xl">Operations overview</h1>
                <p class="ui-lead !max-w-3xl !text-[13px]">
                    Monitor customer activity, capital movement, trading operations, investment products and review queues from one workspace.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.wallet-transactions.index') }}" class="ui-btn ui-btn-secondary">
                    <i data-lucide="wallet-cards" class="h-4 w-4"></i>
                    Wallet queue
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
               class="ui-panel group relative overflow-hidden p-4 transition hover:-translate-y-0.5">
                <div class="absolute right-0 top-0 h-20 w-20 rounded-bl-[2.5rem] bg-sky-500/5"></div>
                <div class="relative flex items-start justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 text-[9px] font-semibold uppercase tracking-[.14em] text-muted-foreground">
                            <i data-lucide="users" class="h-3.5 w-3.5 text-sky-500"></i>
                            Customers
                        </div>
                        <p class="mt-2 text-2xl font-semibold tabular-nums">{{ number_format($totalUsers) }}</p>
                    </div>
                    <i data-lucide="arrow-up-right" class="h-4 w-4 text-muted-foreground transition group-hover:text-foreground"></i>
                </div>
                <div class="relative mt-4 border-t border-border/70 pt-3">
                    <p class="text-[10px] text-muted-foreground">{{ $conversionRate }}% order/customer ratio</p>
                </div>
            </a>

            <a href="{{ route('admin.investments.transactions.index') }}"
               class="ui-panel group relative overflow-hidden p-4 transition hover:-translate-y-0.5">
                <div class="absolute right-0 top-0 h-20 w-20 rounded-bl-[2.5rem] bg-violet-500/5"></div>
                <div class="relative flex items-start justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 text-[9px] font-semibold uppercase tracking-[.14em] text-muted-foreground">
                            <i data-lucide="chart-no-axes-combined" class="h-3.5 w-3.5 text-violet-500"></i>
                            Investment volume
                        </div>
                        <p class="mt-2 text-2xl font-semibold tabular-nums">{{ currency_symbol() }}{{ number_format($investmentVolumeThisMonth, 0) }}</p>
                    </div>
                    <i data-lucide="arrow-up-right" class="h-4 w-4 text-muted-foreground transition group-hover:text-foreground"></i>
                </div>
                <div class="relative mt-4 border-t border-border/70 pt-3">
                    <p class="text-[10px] {{ !is_null($investmentVolumeChange) && $investmentVolumeChange < 0 ? 'text-red-600' : 'text-muted-foreground' }}">
                        {{ is_null($investmentVolumeChange) ? 'No previous-month baseline' : (($investmentVolumeChange >= 0 ? '+' : '').$investmentVolumeChange.'% vs previous month') }}
                    </p>
                </div>
            </a>

            <a href="{{ route('admin.stocks.transactions.index') }}"
               class="ui-panel group relative overflow-hidden p-4 transition hover:-translate-y-0.5">
                <div class="absolute right-0 top-0 h-20 w-20 rounded-bl-[2.5rem] bg-emerald-500/5"></div>
                <div class="relative flex items-start justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 text-[9px] font-semibold uppercase tracking-[.14em] text-muted-foreground">
                            <i data-lucide="candlestick-chart" class="h-3.5 w-3.5 text-emerald-500"></i>
                            Stock volume
                        </div>
                        <p class="mt-2 text-2xl font-semibold tabular-nums">{{ currency_symbol() }}{{ number_format($stockVolumeThisMonth, 0) }}</p>
                    </div>
                    <i data-lucide="arrow-up-right" class="h-4 w-4 text-muted-foreground transition group-hover:text-foreground"></i>
                </div>
                <div class="relative mt-4 border-t border-border/70 pt-3">
                    <p class="text-[10px] {{ !is_null($stockVolumeChange) && $stockVolumeChange < 0 ? 'text-red-600' : 'text-muted-foreground' }}">
                        {{ is_null($stockVolumeChange) ? 'No previous-month baseline' : (($stockVolumeChange >= 0 ? '+' : '').$stockVolumeChange.'% vs previous month') }}
                    </p>
                </div>
            </a>

            <a href="{{ route('admin.purchases.index') }}"
               class="ui-panel group relative overflow-hidden p-4 transition hover:-translate-y-0.5">
                <div class="absolute right-0 top-0 h-20 w-20 rounded-bl-[2.5rem] bg-amber-500/5"></div>
                <div class="relative flex items-start justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 text-[9px] font-semibold uppercase tracking-[.14em] text-muted-foreground">
                            <i data-lucide="landmark" class="h-3.5 w-3.5 text-amber-500"></i>
                            Combined AUM
                        </div>
                        <p class="mt-2 text-2xl font-semibold tabular-nums">{{ currency_symbol() }}{{ number_format($totalInvestmentAum + $totalStockAum, 0) }}</p>
                    </div>
                    <i data-lucide="arrow-up-right" class="h-4 w-4 text-muted-foreground transition group-hover:text-foreground"></i>
                </div>
                <div class="relative mt-4 border-t border-border/70 pt-3">
                    <p class="text-[10px] text-muted-foreground">{{ currency_symbol() }}{{ number_format($totalSales, 0) }} completed marketplace sales</p>
                </div>
            </a>
        </section>

        <section class="mt-5 grid gap-5 xl:grid-cols-[1.45fr_.55fr]">
            <div class="space-y-5">
                <div class="ui-panel overflow-hidden">
                    <div class="flex flex-col gap-3 border-b border-border/70 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <i data-lucide="activity" class="h-4 w-4 text-sky-500"></i>
                                <h2 class="text-[13px] font-semibold">Financial activity</h2>
                            </div>
                            <p class="mt-1 text-[10px] text-muted-foreground">Recent investment and stock executions across the platform.</p>
                        </div>
                        <a href="{{ route('admin.wallet-transactions.index') }}" class="text-[11px] font-medium text-foreground hover:underline">
                            Open wallet ledger
                        </a>
                    </div>

                    <div class="grid lg:grid-cols-2">
                        <div class="border-b border-border/70 p-4 lg:border-b-0 lg:border-r">
                            <div class="mb-3 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full bg-violet-500"></span>
                                    <span class="text-[11px] font-semibold">Investments</span>
                                </div>
                                <span class="text-[9px] uppercase tracking-[.12em] text-muted-foreground">{{ $investmentTransactionsThisMonth }} this month</span>
                            </div>

                            <div class="space-y-2">
                                @forelse($recentInvestmentTransactions->take(5) as $tx)
                                    <div class="flex items-center justify-between gap-4 rounded-lg border border-border/70 bg-background/40 px-3 py-2.5">
                                        <div class="min-w-0">
                                            <p class="truncate text-[11px] font-semibold">{{ $tx->user->name }}</p>
                                            <p class="mt-0.5 truncate text-[9px] text-muted-foreground">{{ $tx->investmentPlan->name }} · {{ $tx->type_label }}</p>
                                        </div>
                                        <p class="shrink-0 text-[11px] font-semibold tabular-nums">{{ $tx->formatted_total_amount }}</p>
                                    </div>
                                @empty
                                    <div class="rounded-lg border border-dashed border-border p-6 text-center text-[10px] text-muted-foreground">
                                        No investment activity yet.
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <div class="p-4">
                            <div class="mb-3 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                    <span class="text-[11px] font-semibold">Stock trading</span>
                                </div>
                                <span class="text-[9px] uppercase tracking-[.12em] text-muted-foreground">{{ $stockTransactionsThisMonth }} this month</span>
                            </div>

                            <div class="space-y-2">
                                @forelse($recentStockTransactions->take(5) as $tx)
                                    <div class="flex items-center justify-between gap-4 rounded-lg border border-border/70 bg-background/40 px-3 py-2.5">
                                        <div class="min-w-0">
                                            <p class="truncate text-[11px] font-semibold">{{ $tx->user->name }}</p>
                                            <p class="mt-0.5 truncate text-[9px] text-muted-foreground">{{ $tx->stock->symbol }} · {{ $tx->type_label }}</p>
                                        </div>
                                        <p class="shrink-0 text-[11px] font-semibold tabular-nums">{{ $tx->formatted_total_amount }}</p>
                                    </div>
                                @empty
                                    <div class="rounded-lg border border-dashed border-border p-6 text-center text-[10px] text-muted-foreground">
                                        No stock activity yet.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ui-panel overflow-hidden">
                    <div class="flex items-center justify-between border-b border-border/70 px-4 py-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <i data-lucide="shopping-bag" class="h-4 w-4 text-amber-500"></i>
                                <h2 class="text-[13px] font-semibold">Marketplace orders</h2>
                            </div>
                            <p class="mt-1 text-[10px] text-muted-foreground">Latest vehicle commerce activity.</p>
                        </div>
                        <a href="{{ route('admin.purchases.index') }}" class="text-[11px] font-medium hover:underline">View orders</a>
                    </div>

                    <div class="divide-y divide-border/70">
                        @forelse($recentPurchases->take(5) as $purchase)
                            <div class="flex items-center justify-between gap-5 px-4 py-3">
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
                            <div class="p-8 text-center text-[10px] text-muted-foreground">No marketplace orders yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <aside class="space-y-5">
                <div class="ui-panel overflow-hidden">
                    <div class="border-b border-border/70 px-4 py-4">
                        <div class="flex items-center gap-2">
                            <i data-lucide="list-checks" class="h-4 w-4 text-sky-500"></i>
                            <h2 class="text-[13px] font-semibold">Review queues</h2>
                        </div>
                        <p class="mt-1 text-[10px] text-muted-foreground">Operational work that may need attention.</p>
                    </div>

                    <div class="p-2">
                        @foreach([
                            [route('admin.copy-trading.applications'),'clipboard-check','Provider applications'],
                            [route('admin.ai-bots.index'),'bot','Bot catalog'],
                            [route('admin.kyc.index'),'shield-check','KYC management'],
                            [route('admin.wallet-transactions.index'),'wallet-cards','Wallet transactions'],
                        ] as [$url,$icon,$label])
                            <a href="{{ $url }}" class="group flex items-center justify-between rounded-lg px-3 py-2.5 hover:bg-muted/70">
                                <span class="flex items-center gap-2.5 text-[11px] font-medium">
                                    <i data-lucide="{{ $icon }}" class="h-3.5 w-3.5 text-muted-foreground"></i>
                                    {{ $label }}
                                </span>
                                <i data-lucide="chevron-right" class="h-3.5 w-3.5 text-muted-foreground transition group-hover:translate-x-0.5"></i>
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="ui-panel overflow-hidden">
                    <div class="border-b border-border/70 px-4 py-4">
                        <div class="flex items-center gap-2">
                            <i data-lucide="radar" class="h-4 w-4 text-emerald-500"></i>
                            <h2 class="text-[13px] font-semibold">Market health</h2>
                        </div>
                        <p class="mt-1 text-[10px] text-muted-foreground">Current catalog and market-state snapshot.</p>
                    </div>

                    <div class="grid grid-cols-2 gap-px bg-border/70">
                        <div class="bg-background p-4">
                            <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Active stocks</p>
                            <p class="mt-1.5 text-lg font-semibold tabular-nums">{{ $activeStocks }}</p>
                        </div>
                        <div class="bg-background p-4">
                            <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Active plans</p>
                            <p class="mt-1.5 text-lg font-semibold tabular-nums">{{ $activeInvestmentPlans }}</p>
                        </div>
                        <div class="bg-background p-4">
                            <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Gainers</p>
                            <p class="mt-1.5 text-lg font-semibold text-emerald-600 tabular-nums">{{ $marketGainers }}</p>
                        </div>
                        <div class="bg-background p-4">
                            <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Losers</p>
                            <p class="mt-1.5 text-lg font-semibold text-red-600 tabular-nums">{{ $marketLosers }}</p>
                        </div>
                    </div>
                </div>

                <div class="ui-panel p-4">
                    <div class="flex items-start gap-3">
                        <div class="rounded-lg border border-border bg-muted/50 p-2">
                            <i data-lucide="shield-check" class="h-4 w-4"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold">Operational principle</p>
                            <p class="mt-1 text-[10px] leading-5 text-muted-foreground">
                                Review queues should surface exceptions and approvals. Product configuration belongs inside its dedicated management area.
                            </p>
                        </div>
                    </div>
                </div>
            </aside>
        </section>
    </div>
</x-admin-layout>
