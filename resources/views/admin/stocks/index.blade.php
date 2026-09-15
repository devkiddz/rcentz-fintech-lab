<x-admin-layout>
<x-slot name="header">Stock Management</x-slot>

<div class="ui-page max-w-[1600px]">
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker text-[10px]">Trading Management</p>
            <h1 class="ui-heading !text-2xl">Stocks</h1>
            <p class="ui-lead !max-w-3xl !text-[13px]">
                Monitor listed assets, market activity, customer holdings and execution volume.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.stocks.transactions.index') }}" class="ui-btn ui-btn-secondary">
                <i data-lucide="receipt-text" class="h-4 w-4"></i>
                Transactions
            </a>
        </div>
    </section>

    <section class="mt-5 grid grid-cols-2 gap-3 lg:grid-cols-5">
        @foreach([
            ['bar-chart-3','Total stocks',$stats['total_stocks'],'sky'],
            ['badge-check','Active stocks',$stats['active_stocks'],'emerald'],
            ['wallet-cards','With holdings',$stats['stocks_with_holdings'],'violet'],
            ['layers-3','Total holdings',$stats['total_holdings'],'amber'],
            ['arrow-left-right','Transactions',$stats['total_transactions'],'rose'],
        ] as [$icon,$label,$value,$tone])
            <div class="ui-panel p-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[8px] font-semibold uppercase tracking-[.13em] text-muted-foreground">{{ $label }}</p>
                        <p class="mt-2 text-xl font-semibold tabular-nums">{{ number_format($value) }}</p>
                    </div>
                    <div class="rounded-lg border border-border bg-muted/40 p-2">
                        <i data-lucide="{{ $icon }}" class="h-4 w-4"></i>
                    </div>
                </div>
            </div>
        @endforeach
    </section>

    <section class="ui-panel mt-5 overflow-hidden">
        <div class="flex flex-col gap-2 border-b border-border/70 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <i data-lucide="candlestick-chart" class="h-4 w-4 text-emerald-500"></i>
                    <h2 class="text-[13px] font-semibold">Listed market</h2>
                </div>
                <p class="mt-1 text-[10px] text-muted-foreground">Current price, activity and customer exposure by stock.</p>
            </div>
            <span class="text-[9px] font-semibold uppercase tracking-[.12em] text-muted-foreground">
                {{ number_format($stats['total_stocks']) }} assets
            </span>
        </div>

        @if($stocks->count() > 0)
            <div class="divide-y divide-border/70">
                @foreach($stocks as $stock)
                    <div class="group grid gap-4 px-4 py-4 transition hover:bg-muted/20 lg:grid-cols-[minmax(0,1.2fr)_repeat(3,minmax(100px,.42fr))_auto] lg:items-center">
                        <div class="flex min-w-0 items-center gap-3">
                            @if($stock->logo_url)
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-border bg-background">
                                    <img src="{{ $stock->logo_url }}" alt="{{ $stock->symbol }} logo" class="h-7 w-7 object-contain" loading="lazy">
                                </div>
                            @else
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-border bg-muted text-[10px] font-semibold">
                                    {{ $stock->symbol }}
                                </div>
                            @endif

                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="truncate text-[12px] font-semibold">{{ $stock->name }}</p>
                                    @if($stock->is_active)
                                        <span class="inline-flex items-center gap-1 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2 py-0.5 text-[8px] font-semibold uppercase tracking-[.11em] text-emerald-600">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            Active
                                        </span>
                                    @else
                                        <span class="rounded-full border border-border bg-muted px-2 py-0.5 text-[8px] font-semibold uppercase tracking-[.11em] text-muted-foreground">
                                            Inactive
                                        </span>
                                    @endif
                                </div>
                                <p class="mt-1 truncate text-[9px] text-muted-foreground">{{ $stock->symbol }} · {{ $stock->sector ?: 'Unclassified sector' }}</p>
                            </div>
                        </div>

                        <div>
                            <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Market price</p>
                            <div class="mt-1 flex items-baseline gap-2">
                                <p class="text-[12px] font-semibold tabular-nums">${{ number_format($stock->current_price, 2) }}</p>
                                @if($stock->price_change_percentage !== null)
                                    <span class="text-[9px] font-semibold {{ $stock->price_change_percentage >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                        {{ $stock->price_change_percentage >= 0 ? '+' : '' }}{{ number_format($stock->price_change_percentage, 2) }}%
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div>
                            <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Holdings</p>
                            <p class="mt-1 text-[12px] font-semibold tabular-nums">{{ number_format($stock->holdings_count) }}</p>
                        </div>

                        <div>
                            <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Transactions</p>
                            <p class="mt-1 text-[12px] font-semibold tabular-nums">{{ number_format($stock->transactions_count) }}</p>
                            @if($stock->last_updated)
                                <p class="mt-0.5 text-[8px] text-muted-foreground">{{ $stock->last_updated->diffForHumans() }}</p>
                            @endif
                        </div>

                        <div class="flex justify-end">
                            <a href="{{ route('admin.stocks.show', $stock) }}" class="ui-btn ui-btn-secondary !h-8 !px-3">
                                View
                                <i data-lucide="arrow-up-right" class="h-3.5 w-3.5"></i>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="p-10 text-center">
                <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl border border-border bg-muted">
                    <i data-lucide="candlestick-chart" class="h-4 w-4 text-muted-foreground"></i>
                </div>
                <p class="mt-3 text-[12px] font-semibold">No stocks found</p>
                <p class="mt-1 text-[10px] text-muted-foreground">There are no listed stocks available at the moment.</p>
            </div>
        @endif
    </section>
</div>
</x-admin-layout>
