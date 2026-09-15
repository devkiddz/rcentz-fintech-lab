<x-admin-layout>
<x-slot name="header">{{ $stock->name }} ({{ $stock->symbol }})</x-slot>

<div class="ui-page max-w-[1600px]">
    <section class="ui-page-header">
        <div class="flex min-w-0 items-start gap-4">
            @if($stock->logo_url)
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-border bg-background">
                    <img src="{{ $stock->logo_url }}" alt="{{ $stock->symbol }} logo" class="h-8 w-8 object-contain" loading="lazy">
                </div>
            @else
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-border bg-muted text-[11px] font-semibold">
                    {{ $stock->symbol }}
                </div>
            @endif

            <div class="min-w-0">
                <p class="ui-kicker text-[10px]">Trading Management · Stock</p>
                <div class="mt-1 flex flex-wrap items-center gap-2">
                    <h1 class="ui-heading !text-2xl">{{ $stock->name }}</h1>
                    <span class="rounded-full border border-border bg-muted px-2 py-0.5 text-[8px] font-semibold uppercase tracking-[.11em] text-muted-foreground">
                        {{ $stock->symbol }}
                    </span>
                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[8px] font-semibold uppercase tracking-[.11em]
                        {{ $stock->is_active ? 'border border-emerald-500/20 bg-emerald-500/10 text-emerald-600' : 'border border-border bg-muted text-muted-foreground' }}">
                        @if($stock->is_active)<span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>@endif
                        {{ $stock->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                <p class="ui-lead !mt-2 !max-w-3xl !text-[13px]">{{ $stock->description ?: 'No stock description available.' }}</p>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.stocks.index') }}" class="ui-btn ui-btn-secondary">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                Stocks
            </a>
            <a href="{{ route('admin.stocks.holdings.index') }}?stock={{ $stock->id }}" class="ui-btn ui-btn-secondary">
                <i data-lucide="layers-3" class="h-4 w-4"></i>
                Holdings
            </a>
            <a href="{{ route('admin.stocks.transactions.index') }}?stock={{ $stock->id }}" class="ui-btn ui-btn-secondary">
                <i data-lucide="receipt-text" class="h-4 w-4"></i>
                Transactions
            </a>
            <a href="{{ route('admin.stocks.trade', $stock) }}" class="ui-btn ui-btn-primary">
                <i data-lucide="badge-dollar-sign" class="h-4 w-4"></i>
                Trading Desk
            </a>
        </div>
    </section>

    <section class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="ui-panel p-4">
            <p class="text-[8px] font-semibold uppercase tracking-[.13em] text-muted-foreground">Current price</p>
            <p class="mt-2 text-2xl font-semibold tabular-nums">{{ currency_symbol() }}{{ number_format($stock->current_price, 2) }}</p>
            <p class="mt-2 text-[9px] text-muted-foreground">Previous close · {{ currency_symbol() }}{{ number_format($stock->previous_close, 2) }}</p>
        </div>

        <div class="ui-panel p-4">
            <p class="text-[8px] font-semibold uppercase tracking-[.13em] text-muted-foreground">Price move</p>
            <p class="mt-2 text-2xl font-semibold tabular-nums {{ $stock->price_change >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                {{ $stock->price_change >= 0 ? '+' : '' }}{{ currency_symbol() }}{{ number_format($stock->price_change, 2) }}
            </p>
            <p class="mt-2 text-[9px] font-semibold {{ $stock->price_change_percentage >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                {{ $stock->price_change_percentage >= 0 ? '+' : '' }}{{ number_format($stock->price_change_percentage, 2) }}%
            </p>
        </div>

        <div class="ui-panel p-4">
            <p class="text-[8px] font-semibold uppercase tracking-[.13em] text-muted-foreground">Customer exposure</p>
            <p class="mt-2 text-2xl font-semibold tabular-nums">{{ number_format($stats['total_holdings']) }}</p>
            <p class="mt-2 text-[9px] text-muted-foreground">{{ number_format($stats['total_shares'], 2) }} shares held</p>
        </div>

        <div class="ui-panel p-4">
            <p class="text-[8px] font-semibold uppercase tracking-[.13em] text-muted-foreground">Trading volume</p>
            <p class="mt-2 text-2xl font-semibold tabular-nums">{{ currency_symbol() }}{{ number_format($stats['total_volume'], 0) }}</p>
            <p class="mt-2 text-[9px] text-muted-foreground">{{ number_format($stats['total_transactions']) }} executions</p>
        </div>
    </section>


    <section class="mt-5">
        @include('trading.partials.analysis-chart', [
            'stock' => $stock,
            'analysis' => $analysis,
            'chartHeight' => 'h-[320px] md:h-[420px]',
        ])
    </section>

    <section class="mt-5 grid gap-5 xl:grid-cols-[1.45fr_.55fr]">
        <div class="space-y-5">
            <div class="ui-panel overflow-hidden">
                <div class="flex items-center justify-between border-b border-border/70 px-4 py-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <i data-lucide="wallet-cards" class="h-4 w-4 text-violet-500"></i>
                            <h2 class="text-[13px] font-semibold">Recent holdings</h2>
                        </div>
                        <p class="mt-1 text-[10px] text-muted-foreground">Latest customer positions in {{ $stock->symbol }}.</p>
                    </div>
                    <a href="{{ route('admin.stocks.holdings.index') }}?stock={{ $stock->id }}" class="text-[11px] font-medium hover:underline">View all</a>
                </div>

                <div class="divide-y divide-border/70">
                    @forelse($stock->holdings->take(5) as $holding)
                        <div class="grid gap-3 px-4 py-4 sm:grid-cols-[1.1fr_.6fr_.7fr_.6fr] sm:items-center">
                            <div class="min-w-0">
                                <p class="truncate text-[11px] font-semibold">{{ $holding->user->name }}</p>
                                <p class="mt-0.5 truncate text-[9px] text-muted-foreground">{{ $holding->user->email }}</p>
                            </div>
                            <div>
                                <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Shares</p>
                                <p class="mt-1 text-[11px] font-semibold tabular-nums">{{ number_format($holding->quantity, 2) }}</p>
                            </div>
                            <div>
                                <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Current value</p>
                                <p class="mt-1 text-[11px] font-semibold tabular-nums">{{ currency_symbol() }}{{ number_format($holding->current_value, 2) }}</p>
                            </div>
                            <div>
                                <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Unrealized</p>
                                <p class="mt-1 text-[10px] font-semibold {{ $holding->unrealized_gain_loss_percentage >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                    {{ $holding->unrealized_gain_loss_percentage >= 0 ? '+' : '' }}{{ number_format($holding->unrealized_gain_loss_percentage, 2) }}%
                                </p>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-[10px] text-muted-foreground">No holdings found for this stock.</div>
                    @endforelse
                </div>
            </div>

            <div class="ui-panel overflow-hidden">
                <div class="flex items-center justify-between border-b border-border/70 px-4 py-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <i data-lucide="arrow-left-right" class="h-4 w-4 text-sky-500"></i>
                            <h2 class="text-[13px] font-semibold">Recent transactions</h2>
                        </div>
                        <p class="mt-1 text-[10px] text-muted-foreground">Latest buy and sell executions for {{ $stock->symbol }}.</p>
                    </div>
                    <a href="{{ route('admin.stocks.transactions.index') }}?stock={{ $stock->id }}" class="text-[11px] font-medium hover:underline">View all</a>
                </div>

                <div class="divide-y divide-border/70">
                    @forelse($stock->transactions->take(5) as $transaction)
                        <a href="{{ route('admin.stocks.transactions.show', $transaction) }}"
                           class="grid gap-3 px-4 py-4 transition hover:bg-muted/20 sm:grid-cols-[1.1fr_.45fr_.6fr_.6fr_auto] sm:items-center">
                            <div class="min-w-0">
                                <p class="truncate text-[11px] font-semibold">{{ $transaction->user->name }}</p>
                                <p class="mt-0.5 text-[9px] text-muted-foreground">{{ $transaction->created_at->format('M d, Y · H:i') }}</p>
                            </div>
                            <div>
                                <span class="inline-flex rounded-full px-2 py-1 text-[8px] font-semibold uppercase tracking-[.11em]
                                    {{ $transaction->type === 'buy' ? 'border border-emerald-500/20 bg-emerald-500/10 text-emerald-600' : 'border border-red-500/20 bg-red-500/10 text-red-600' }}">
                                    {{ ucfirst($transaction->type) }}
                                </span>
                            </div>
                            <div>
                                <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Shares</p>
                                <p class="mt-1 text-[10px] font-semibold tabular-nums">{{ number_format($transaction->quantity, 2) }}</p>
                            </div>
                            <div>
                                <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Amount</p>
                                <p class="mt-1 text-[10px] font-semibold tabular-nums">{{ currency_symbol() }}{{ number_format($transaction->total_amount, 2) }}</p>
                            </div>
                            <i data-lucide="chevron-right" class="h-3.5 w-3.5 text-muted-foreground"></i>
                        </a>
                    @empty
                        <div class="p-8 text-center text-[10px] text-muted-foreground">No transactions found for this stock.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <aside class="space-y-5">
            <div class="ui-panel overflow-hidden">
                <div class="border-b border-border/70 px-4 py-4">
                    <h2 class="text-[13px] font-semibold">Market profile</h2>
                    <p class="mt-1 text-[10px] text-muted-foreground">Catalog and quote metadata.</p>
                </div>
                <div class="divide-y divide-border/70">
                    @foreach([
                        ['Symbol',$stock->symbol],
                        ['Sector',$stock->sector ?: '—'],
                        ['Industry',$stock->industry ?: '—'],
                        ['Status',$stock->is_active ? 'Active' : 'Inactive'],
                        ['Last updated',$stock->last_updated ? $stock->last_updated->format('M d, Y · H:i') : 'Never'],
                    ] as [$label,$value])
                        <div class="flex items-center justify-between gap-4 px-4 py-3">
                            <span class="text-[9px] text-muted-foreground">{{ $label }}</span>
                            <span class="text-right text-[10px] font-semibold">{{ $value }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="ui-panel overflow-hidden">
                <div class="border-b border-border/70 px-4 py-4">
                    <h2 class="text-[13px] font-semibold">Position economics</h2>
                    <p class="mt-1 text-[10px] text-muted-foreground">Aggregate customer exposure.</p>
                </div>

                <div class="grid grid-cols-2 gap-px bg-border/70">
                    <div class="bg-background p-4">
                        <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Value</p>
                        <p class="mt-1.5 text-[12px] font-semibold tabular-nums">{{ currency_symbol() }}{{ number_format($stats['total_value'], 2) }}</p>
                    </div>
                    <div class="bg-background p-4">
                        <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Invested</p>
                        <p class="mt-1.5 text-[12px] font-semibold tabular-nums">{{ currency_symbol() }}{{ number_format($stats['total_invested'], 2) }}</p>
                    </div>
                    <div class="col-span-2 bg-background p-4">
                        <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Unrealized gain / loss</p>
                        <p class="mt-1.5 text-lg font-semibold tabular-nums {{ $stats['total_gain_loss'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                            {{ $stats['total_gain_loss'] >= 0 ? '+' : '' }}{{ currency_symbol() }}{{ number_format($stats['total_gain_loss'], 2) }}
                        </p>
                    </div>
                </div>
            </div>
        </aside>
    </section>
</div>
</x-admin-layout>
