<x-admin-layout>
<x-slot name="header">AI Trading Bots</x-slot>

<div class="space-y-5">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-muted-foreground">AI Trading Bots</p>
            <h1 class="mt-1.5 text-sm font-semibold tracking-tight">Bot Catalog</h1>
            <p class="mt-1 text-sm text-muted-foreground">Compact performance cards for each admin-owned bot product.</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.ai-bots.executions') }}" class="ui-btn ui-btn-secondary">
                <i data-lucide="activity" class="h-4 w-4"></i>
                Executions
            </a>
            <a href="{{ route('admin.ai-bots.subscriptions') }}" class="ui-btn ui-btn-secondary">
                <i data-lucide="badge-dollar-sign" class="h-4 w-4"></i>
                Subscriptions
            </a>
            <a href="{{ route('admin.ai-bots.create') }}" class="ui-btn ui-btn-primary">
                <i data-lucide="circle-plus" class="h-4 w-4"></i>
                Create Bot
            </a>
        </div>
    </div>

    <div class="space-y-4">
        @forelse($products as $product)
            @php
                $m = $product->performance_metrics;
                $strategyLabel = match($product->strategy) {
                    'dca' => 'DCA / Interval',
                    'price_below' => 'Price Below',
                    'price_above' => 'Price Above',
                    default => strtoupper(str_replace('_',' ',$product->strategy)),
                };
                $profitPositive = $m['profit_loss'] > 0;
                $profitNegative = $m['profit_loss'] < 0;
                $returnPositive = $m['return_percent'] > 0;
            @endphp

            <article class="ui-panel overflow-hidden border border-border/70 bg-gradient-to-br from-background via-background to-muted/10 shadow-sm">
                <div class="p-4 sm:p-5">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center gap-1 rounded-full border border-sky-500/20 bg-sky-500/10 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-sky-600">
                                    <i data-lucide="candlestick-chart" class="h-3.5 w-3.5"></i>{{ $product->stock->symbol }}
                                </span>
                                <span class="inline-flex items-center gap-1 rounded-full border border-border bg-muted/40 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-muted-foreground">
                                    <i data-lucide="cpu" class="h-3.5 w-3.5"></i>{{ $strategyLabel }}
                                </span>
                                <span class="inline-flex items-center gap-1 rounded-full border border-amber-500/20 bg-amber-500/10 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-amber-600">
                                    <i data-lucide="shield-alert" class="h-3.5 w-3.5"></i>{{ ucfirst($product->risk_level) }} risk
                                </span>
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] {{ $product->is_active ? 'bg-emerald-500/10 text-emerald-600 border border-emerald-500/20' : 'bg-muted text-muted-foreground border border-border' }}">
                                    @if($product->is_active)
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    @else
                                        <span class="h-1.5 w-1.5 rounded-full bg-muted-foreground"></span>
                                    @endif
                                    {{ $product->is_active ? 'Live' : 'Inactive' }}
                                </span>
                            </div>

                            <h2 class="mt-3 text-sm font-semibold tracking-tight">{{ $product->name }}</h2>
                            <p class="mt-1 line-clamp-2 text-[13px] leading-5 text-muted-foreground">{{ $product->description }}</p>
                        </div>

                        <details class="relative shrink-0">
                            <summary class="list-none cursor-pointer rounded-lg border border-border bg-background p-2 text-muted-foreground hover:bg-muted hover:text-foreground" aria-label="Bot options">
                                <i data-lucide="more-vertical" class="h-4 w-4"></i>
                            </summary>
                            <div class="absolute right-0 z-30 mt-2 w-44 rounded-xl border border-border bg-background p-1.5 shadow-lg">
                                <a href="{{ route('admin.ai-bots.edit',$product) }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium hover:bg-muted"><i data-lucide="pencil" class="h-3.5 w-3.5"></i>Edit Bot</a>
                                <form method="POST" action="{{ route('admin.ai-bots.toggle',$product) }}">@csrf @method('PATCH')<button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-xs font-medium hover:bg-muted"><i data-lucide="power" class="h-3.5 w-3.5"></i>{{ $product->is_active ? 'Disable Bot' : 'Enable Bot' }}</button></form>
                            </div>
                        </details>
                    </div>

                    <div class="mt-4 grid gap-4 lg:grid-cols-[0.95fr_1.05fr]">
                        <section class="rounded-2xl border border-border/70 bg-muted/15 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-muted-foreground">Performance Snapshot</p>
                                @if($m['is_manual_performance'])
                                    <span class="inline-flex items-center gap-1 rounded-full border border-violet-500/20 bg-violet-500/10 px-2 py-1 text-[10px] font-medium text-violet-600">
                                        <i data-lucide="sparkles" class="h-3 w-3"></i> Preview mode
                                    </span>
                                @endif
                            </div>

                            <div class="mt-3 grid grid-cols-2 gap-3">
                                <div class="rounded-xl border {{ $profitPositive ? 'border-emerald-500/20 bg-emerald-500/5' : ($profitNegative ? 'border-red-500/20 bg-red-500/5' : 'border-border bg-background/60') }} p-3">
                                    <div class="flex items-center gap-2 text-[11px] uppercase tracking-[0.14em] text-muted-foreground">
                                        <i data-lucide="wallet" class="h-3.5 w-3.5 {{ $profitPositive ? 'text-emerald-600' : ($profitNegative ? 'text-red-600' : '') }}"></i>
                                        {{ $m['is_manual_performance'] ? 'Preview P/L' : 'P/L' }}
                                    </div>
                                    <p class="mt-2 text-sm font-semibold {{ $profitPositive ? 'text-emerald-600' : ($profitNegative ? 'text-red-600' : '') }}">
                                        {{ $m['profit_loss'] > 0 ? '+' : '' }}{{ format_currency($m['profit_loss']) }}
                                    </p>
                                </div>
                                <div class="rounded-xl border {{ $returnPositive ? 'border-sky-500/20 bg-sky-500/5' : 'border-border bg-background/60' }} p-3">
                                    <div class="flex items-center gap-2 text-[11px] uppercase tracking-[0.14em] text-muted-foreground">
                                        <i data-lucide="trending-up" class="h-3.5 w-3.5 {{ $returnPositive ? 'text-sky-600' : '' }}"></i>
                                        {{ $m['is_manual_performance'] ? 'Preview Return' : 'Return' }}
                                    </div>
                                    <p class="mt-2 text-sm font-semibold {{ $returnPositive ? 'text-sky-600' : '' }}">
                                        {{ $m['return_percent'] > 0 ? '+' : '' }}{{ number_format($m['return_percent'],2) }}%
                                    </p>
                                </div>
                            </div>

                            <div class="mt-3 grid grid-cols-3 gap-2">
                                <div class="rounded-xl border border-border bg-background/50 p-3 text-center">
                                    <div class="mx-auto flex h-8 w-8 items-center justify-center rounded-full bg-sky-500/10 text-sky-600">
                                        <i data-lucide="activity" class="h-4 w-4"></i>
                                    </div>
                                    <p class="mt-2 text-[10px] uppercase tracking-[0.14em] text-muted-foreground">Trades</p>
                                    <p class="mt-1 text-sm font-semibold">{{ $m['completed_count'] }}</p>
                                </div>
                                <div class="rounded-xl border border-border bg-background/50 p-3 text-center">
                                    <div class="mx-auto flex h-8 w-8 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-600">
                                        <i data-lucide="target" class="h-4 w-4"></i>
                                    </div>
                                    <p class="mt-2 text-[10px] uppercase tracking-[0.14em] text-muted-foreground">Win Rate</p>
                                    <p class="mt-1 text-sm font-semibold">{{ number_format($m['win_rate'],1) }}%</p>
                                </div>
                                <div class="rounded-xl border border-border bg-background/50 p-3 text-center">
                                    <div class="mx-auto flex h-8 w-8 items-center justify-center rounded-full bg-violet-500/10 text-violet-600">
                                        <i data-lucide="bar-chart-3" class="h-4 w-4"></i>
                                    </div>
                                    <p class="mt-2 text-[10px] uppercase tracking-[0.14em] text-muted-foreground">Volume</p>
                                    <p class="mt-1 text-sm font-semibold">{{ format_currency($m['volume']) }}</p>
                                </div>
                            </div>
                        </section>

                        <section class="grid grid-cols-2 gap-2.5">
                            <div class="rounded-xl border border-border bg-background/50 p-3">
                                <div class="flex items-center gap-2 text-[10px] uppercase tracking-[0.14em] text-muted-foreground"><i data-lucide="badge-dollar-sign" class="h-3.5 w-3.5 text-emerald-600"></i>Subscription</div>
                                <p class="mt-1.5 text-sm font-semibold">{{ $product->price > 0 ? format_currency($product->price) : 'Free' }}</p>
                                <p class="text-xs text-muted-foreground">{{ ucfirst(str_replace('_',' ',$product->billing_period)) }}</p>
                            </div>

                            <div class="rounded-xl border border-border bg-background/50 p-3">
                                <div class="flex items-center gap-2 text-[10px] uppercase tracking-[0.14em] text-muted-foreground"><i data-lucide="users" class="h-3.5 w-3.5 text-sky-600"></i>Subscribers</div>
                                <p class="mt-1.5 text-sm font-semibold">{{ $product->active_subscriptions_count }} active</p>
                                <p class="text-xs text-muted-foreground">{{ $product->subscriptions_count }} total</p>
                            </div>

                            <div class="rounded-xl border border-border bg-background/50 p-3">
                                <div class="flex items-center gap-2 text-[10px] uppercase tracking-[0.14em] text-muted-foreground"><i data-lucide="coins" class="h-3.5 w-3.5 text-amber-600"></i>Default Trade</div>
                                <p class="mt-1.5 text-sm font-semibold">{{ $product->default_trade_amount ? format_currency($product->default_trade_amount) : '—' }}</p>
                            </div>

                            <div class="rounded-xl border border-border bg-background/50 p-3">
                                <div class="flex items-center gap-2 text-[10px] uppercase tracking-[0.14em] text-muted-foreground"><i data-lucide="wallet-minimal" class="h-3.5 w-3.5 text-violet-600"></i>Min Balance</div>
                                <p class="mt-1.5 text-sm font-semibold">{{ format_currency($product->minimum_balance) }}</p>
                            </div>

                            <div class="rounded-xl border border-border bg-background/50 p-3">
                                <div class="flex items-center gap-2 text-[10px] uppercase tracking-[0.14em] text-muted-foreground"><i data-lucide="shield-check" class="h-3.5 w-3.5 text-rose-600"></i>Max Allocation</div>
                                <p class="mt-1.5 text-sm font-semibold">{{ $product->max_user_allocation ? format_currency($product->max_user_allocation) : 'Open' }}</p>
                            </div>

                            <div class="rounded-xl border border-border bg-background/50 p-3">
                                <div class="flex items-center gap-2 text-[10px] uppercase tracking-[0.14em] text-muted-foreground"><i data-lucide="timer-reset" class="h-3.5 w-3.5 text-sky-600"></i>{{ $product->strategy === 'dca' ? 'Run Every' : 'Check Every' }}</div>
                                <p class="mt-1.5 text-sm font-semibold">{{ $product->default_interval_minutes }} <span class="text-xs font-normal text-muted-foreground">min</span></p>
                            </div>

                            <div class="rounded-xl border border-border bg-background/50 p-3">
                                <div class="flex items-center gap-2 text-[10px] uppercase tracking-[0.14em] text-muted-foreground"><i data-lucide="rows-4" class="h-3.5 w-3.5 text-amber-600"></i>Daily Limit</div>
                                <p class="mt-1.5 text-sm font-semibold">{{ $product->default_max_daily_trades }}</p>
                            </div>

                            <div class="rounded-xl border border-border bg-background/50 p-3">
                                <div class="flex items-center gap-2 text-[10px] uppercase tracking-[0.14em] text-muted-foreground"><i data-lucide="arrow-right-left" class="h-3.5 w-3.5 text-emerald-600"></i>Action</div>
                                <p class="mt-1.5 text-sm font-semibold">{{ ucfirst($product->action) }}</p>
                            </div>
                        </section>
                    </div>
                </div>
            </article>
        @empty
            <div class="ui-panel p-10 text-center ">
                <p class="font-medium">No bot products found.</p>
                <p class="mt-1 text-sm text-muted-foreground">Create the first admin-owned bot product.</p>
            </div>
        @endforelse
    </div>

    {{ $products->links() }}
</div>
</x-admin-layout>
