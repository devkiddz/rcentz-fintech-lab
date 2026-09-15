<x-admin-layout>
    <x-slot name="header">Overview</x-slot>

    <div class="mx-auto max-w-[1600px] space-y-6">
        <section class="flex flex-col gap-4 border-b border-border pb-6 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-xs font-medium uppercase tracking-[0.18em] text-muted-foreground">Operations</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight text-foreground">Good to see you, {{ auth()->user()->name }}</h1>
                <p class="mt-2 max-w-2xl text-sm text-muted-foreground">A live view of customers, capital movement, investments, trading and marketplace operations.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.wallet-transactions.index') }}" class="ui-btn ui-btn-secondary">
                    <i data-lucide="wallet-cards" class="h-4 w-4"></i> Wallet queue
                </a>
                <a href="{{ route('admin.kyc.index') }}" class="ui-btn ui-btn-secondary">
                    <i data-lucide="shield-check" class="h-4 w-4"></i> KYC review
                </a>
                <a href="{{ route('admin.ai-bots.create') }}" class="ui-btn ui-btn-primary">
                    <i data-lucide="bot" class="h-4 w-4"></i> Create bot
                </a>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <a href="{{ route('admin.users.index') }}" class="ui-panel group p-5 transition hover:-translate-y-0.5">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-muted-foreground">Customers</p>
                        <p class="mt-2 text-3xl font-semibold tracking-tight">{{ number_format($totalUsers) }}</p>
                    </div>
                    <div class="rounded-xl border border-border bg-muted p-2.5"><i data-lucide="users" class="h-5 w-5"></i></div>
                </div>
                <p class="mt-4 text-xs text-muted-foreground">{{ $conversionRate }}% order/customer ratio</p>
            </a>

            <a href="{{ route('admin.investments.transactions.index') }}" class="ui-panel group p-5 transition hover:-translate-y-0.5">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-muted-foreground">Investment volume</p>
                        <p class="mt-2 text-3xl font-semibold tracking-tight">{{ currency_symbol() }}{{ number_format($investmentVolumeThisMonth, 0) }}</p>
                    </div>
                    <div class="rounded-xl border border-border bg-muted p-2.5"><i data-lucide="chart-no-axes-combined" class="h-5 w-5"></i></div>
                </div>
                <p class="mt-4 text-xs {{ !is_null($investmentVolumeChange) && $investmentVolumeChange < 0 ? 'text-red-600' : 'text-muted-foreground' }}">
                    {{ is_null($investmentVolumeChange) ? 'No previous-month baseline' : (($investmentVolumeChange >= 0 ? '+' : '').$investmentVolumeChange.'% vs previous month') }}
                </p>
            </a>

            <a href="{{ route('admin.stocks.transactions.index') }}" class="ui-panel group p-5 transition hover:-translate-y-0.5">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-muted-foreground">Stock volume</p>
                        <p class="mt-2 text-3xl font-semibold tracking-tight">{{ currency_symbol() }}{{ number_format($stockVolumeThisMonth, 0) }}</p>
                    </div>
                    <div class="rounded-xl border border-border bg-muted p-2.5"><i data-lucide="candlestick-chart" class="h-5 w-5"></i></div>
                </div>
                <p class="mt-4 text-xs {{ !is_null($stockVolumeChange) && $stockVolumeChange < 0 ? 'text-red-600' : 'text-muted-foreground' }}">
                    {{ is_null($stockVolumeChange) ? 'No previous-month baseline' : (($stockVolumeChange >= 0 ? '+' : '').$stockVolumeChange.'% vs previous month') }}
                </p>
            </a>

            <a href="{{ route('admin.purchases.index') }}" class="ui-panel group p-5 transition hover:-translate-y-0.5">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-muted-foreground">Combined AUM</p>
                        <p class="mt-2 text-3xl font-semibold tracking-tight">{{ currency_symbol() }}{{ number_format($totalInvestmentAum + $totalStockAum, 0) }}</p>
                    </div>
                    <div class="rounded-xl border border-border bg-muted p-2.5"><i data-lucide="landmark" class="h-5 w-5"></i></div>
                </div>
                <p class="mt-4 text-xs text-muted-foreground">{{ currency_symbol() }}{{ number_format($totalSales, 0) }} completed marketplace sales</p>
            </a>
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.35fr_.65fr]">
            <div class="space-y-6">
                <div class="ui-panel overflow-hidden">
                    <div class="flex items-center justify-between border-b border-border px-5 py-4">
                        <div>
                            <h2 class="font-semibold">Recent financial activity</h2>
                            <p class="text-xs text-muted-foreground">Latest investment and stock executions</p>
                        </div>
                        <a href="{{ route('admin.wallet-transactions.index') }}" class="text-sm font-medium text-foreground">View ledger →</a>
                    </div>

                    <div class="grid lg:grid-cols-2">
                        <div class="border-b border-border p-5 lg:border-b-0 lg:border-r">
                            <div class="mb-4 flex items-center justify-between">
                                <span class="text-sm font-medium">Investments</span>
                                <span class="text-xs text-muted-foreground">{{ $investmentTransactionsThisMonth }} this month</span>
                            </div>
                            <div class="space-y-3">
                                @forelse($recentInvestmentTransactions->take(5) as $tx)
                                    <div class="flex items-center justify-between gap-4 rounded-xl border border-border p-3">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-medium">{{ $tx->user->name }}</p>
                                            <p class="truncate text-xs text-muted-foreground">{{ $tx->investmentPlan->name }} · {{ $tx->type_label }}</p>
                                        </div>
                                        <p class="shrink-0 text-sm font-medium">{{ $tx->formatted_total_amount }}</p>
                                    </div>
                                @empty
                                    <div class="rounded-xl border border-dashed border-border p-6 text-center text-sm text-muted-foreground">No investment activity yet.</div>
                                @endforelse
                            </div>
                        </div>

                        <div class="p-5">
                            <div class="mb-4 flex items-center justify-between">
                                <span class="text-sm font-medium">Stock trading</span>
                                <span class="text-xs text-muted-foreground">{{ $stockTransactionsThisMonth }} this month</span>
                            </div>
                            <div class="space-y-3">
                                @forelse($recentStockTransactions->take(5) as $tx)
                                    <div class="flex items-center justify-between gap-4 rounded-xl border border-border p-3">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-medium">{{ $tx->user->name }}</p>
                                            <p class="truncate text-xs text-muted-foreground">{{ $tx->stock->symbol }} · {{ $tx->type_label }}</p>
                                        </div>
                                        <p class="shrink-0 text-sm font-medium">{{ $tx->formatted_total_amount }}</p>
                                    </div>
                                @empty
                                    <div class="rounded-xl border border-dashed border-border p-6 text-center text-sm text-muted-foreground">No stock activity yet.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ui-panel overflow-hidden">
                    <div class="flex items-center justify-between border-b border-border px-5 py-4">
                        <div>
                            <h2 class="font-semibold">Recent marketplace orders</h2>
                            <p class="text-xs text-muted-foreground">Vehicle commerce activity</p>
                        </div>
                        <a href="{{ route('admin.purchases.index') }}" class="text-sm font-medium">View orders →</a>
                    </div>
                    <div class="divide-y divide-border">
                        @forelse($recentPurchases->take(5) as $purchase)
                            <div class="flex items-center justify-between gap-5 px-5 py-4">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium">{{ $purchase->user->name }}</p>
                                    <p class="truncate text-xs text-muted-foreground">{{ optional($purchase->car)->make }} {{ optional($purchase->car)->model }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-medium">{{ currency_symbol() }}{{ number_format($purchase->amount, 2) }}</p>
                                    <p class="text-xs text-muted-foreground">{{ ucfirst($purchase->status) }}</p>
                                </div>
                            </div>
                        @empty
                            <div class="p-8 text-center text-sm text-muted-foreground">No marketplace orders yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <aside class="space-y-6">
                <div class="ui-panel p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="font-semibold">Operations</h2>
                            <p class="text-xs text-muted-foreground">Fast access to review queues</p>
                        </div>
                        <i data-lucide="activity" class="h-5 w-5 text-muted-foreground"></i>
                    </div>
                    <div class="mt-5 space-y-2">
                        <a href="{{ route('admin.copy-trading.applications') }}" class="flex items-center justify-between rounded-xl border border-border p-3 hover:bg-muted">
                            <span class="flex items-center gap-3 text-sm"><i data-lucide="clipboard-check" class="h-4 w-4"></i> Provider applications</span>
                            <i data-lucide="chevron-right" class="h-4 w-4 text-muted-foreground"></i>
                        </a>
                        <a href="{{ route('admin.ai-bots.index') }}" class="flex items-center justify-between rounded-xl border border-border p-3 hover:bg-muted">
                            <span class="flex items-center gap-3 text-sm"><i data-lucide="bot" class="h-4 w-4"></i> Bot catalog</span>
                            <i data-lucide="chevron-right" class="h-4 w-4 text-muted-foreground"></i>
                        </a>
                        <a href="{{ route('admin.kyc.index') }}" class="flex items-center justify-between rounded-xl border border-border p-3 hover:bg-muted">
                            <span class="flex items-center gap-3 text-sm"><i data-lucide="shield-check" class="h-4 w-4"></i> KYC management</span>
                            <i data-lucide="chevron-right" class="h-4 w-4 text-muted-foreground"></i>
                        </a>
                        <a href="{{ route('admin.wallet-transactions.index') }}" class="flex items-center justify-between rounded-xl border border-border p-3 hover:bg-muted">
                            <span class="flex items-center gap-3 text-sm"><i data-lucide="wallet-cards" class="h-4 w-4"></i> Wallet transactions</span>
                            <i data-lucide="chevron-right" class="h-4 w-4 text-muted-foreground"></i>
                        </a>
                    </div>
                </div>

                <div class="ui-panel p-5">
                    <h2 class="font-semibold">Market snapshot</h2>
                    <p class="mt-1 text-xs text-muted-foreground">Current catalog health</p>
                    <div class="mt-5 grid grid-cols-2 gap-3">
                        <div class="rounded-xl border border-border p-4">
                            <p class="text-xs text-muted-foreground">Active stocks</p>
                            <p class="mt-1 text-xl font-semibold">{{ $activeStocks }}</p>
                        </div>
                        <div class="rounded-xl border border-border p-4">
                            <p class="text-xs text-muted-foreground">Active plans</p>
                            <p class="mt-1 text-xl font-semibold">{{ $activeInvestmentPlans }}</p>
                        </div>
                        <div class="rounded-xl border border-border p-4">
                            <p class="text-xs text-muted-foreground">Gainers</p>
                            <p class="mt-1 text-xl font-semibold">{{ $marketGainers }}</p>
                        </div>
                        <div class="rounded-xl border border-border p-4">
                            <p class="text-xs text-muted-foreground">Losers</p>
                            <p class="mt-1 text-xl font-semibold">{{ $marketLosers }}</p>
                        </div>
                    </div>
                </div>
            </aside>
        </section>
    </div>
</x-admin-layout>
