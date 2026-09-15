<x-admin-layout>
<x-slot name="header">Stock Transactions</x-slot>

<div class="ui-page max-w-[1600px]">
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker text-[10px]">Trading Management</p>
            <h1 class="ui-heading !text-2xl">Stock transactions</h1>
            <p class="ui-lead !max-w-3xl !text-[13px]">Inspect customer buy and sell executions, execution value and current status.</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.stocks.index') }}" class="ui-btn ui-btn-secondary">
                <i data-lucide="candlestick-chart" class="h-4 w-4"></i>
                Stocks
            </a>
            <a href="{{ route('admin.stocks.holdings.index') }}" class="ui-btn ui-btn-secondary">
                <i data-lucide="layers-3" class="h-4 w-4"></i>
                Holdings
            </a>
        </div>
    </section>

    <section class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="ui-panel p-4">
            <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Transactions</p>
            <p class="mt-2 text-2xl font-semibold tabular-nums">{{ number_format($stats['total_transactions']) }}</p>
            <p class="mt-2 text-[9px] text-muted-foreground">{{ number_format($stats['completed_transactions']) }} completed</p>
        </div>
        <div class="ui-panel p-4">
            <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Customers</p>
            <p class="mt-2 text-2xl font-semibold tabular-nums">{{ number_format($stats['unique_users']) }}</p>
            <p class="mt-2 text-[9px] text-muted-foreground">Unique traders</p>
        </div>
        <div class="ui-panel p-4">
            <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Stocks traded</p>
            <p class="mt-2 text-2xl font-semibold tabular-nums">{{ number_format($stats['unique_stocks']) }}</p>
            <p class="mt-2 text-[9px] text-muted-foreground">{{ number_format($stats['buy_transactions']) }} buys · {{ number_format($stats['sell_transactions']) }} sells</p>
        </div>
        <div class="ui-panel p-4">
            <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Execution volume</p>
            <p class="mt-2 text-2xl font-semibold tabular-nums">{{ currency_symbol() }}{{ number_format($stats['total_volume'], 0) }}</p>
            <p class="mt-2 text-[9px] text-muted-foreground">{{ number_format($stats['pending_transactions']) }} pending</p>
        </div>
    </section>

    <section class="mt-5 flex flex-wrap items-center gap-2">
        @php
            $stockFilter = request('stock');
            $typeFilter = request('type');
        @endphp

        <a href="{{ route('admin.stocks.transactions.index', array_filter(['stock'=>$stockFilter])) }}"
           class="ui-btn {{ !$typeFilter ? 'ui-btn-primary' : 'ui-btn-secondary' }} !h-8 !px-3">
            All
        </a>
        <a href="{{ route('admin.stocks.transactions.index', array_filter(['stock'=>$stockFilter,'type'=>'buy'])) }}"
           class="ui-btn {{ $typeFilter === 'buy' ? 'ui-btn-primary' : 'ui-btn-secondary' }} !h-8 !px-3">
            <i data-lucide="arrow-down-left" class="h-3.5 w-3.5"></i>
            Buys
        </a>
        <a href="{{ route('admin.stocks.transactions.index', array_filter(['stock'=>$stockFilter,'type'=>'sell'])) }}"
           class="ui-btn {{ $typeFilter === 'sell' ? 'ui-btn-primary' : 'ui-btn-secondary' }} !h-8 !px-3">
            <i data-lucide="arrow-up-right" class="h-3.5 w-3.5"></i>
            Sells
        </a>

        @if($stockFilter)
            <a href="{{ route('admin.stocks.transactions.index', array_filter(['type'=>$typeFilter])) }}"
               class="ml-auto text-[10px] font-medium text-muted-foreground hover:text-foreground">
                Clear stock filter
            </a>
        @endif
    </section>

    <section class="ui-panel mt-4 overflow-hidden">
        <div class="border-b border-border/70 px-4 py-4">
            <div class="flex items-center gap-2">
                <i data-lucide="receipt-text" class="h-4 w-4 text-sky-500"></i>
                <h2 class="text-[13px] font-semibold">Execution ledger</h2>
            </div>
            <p class="mt-1 text-[10px] text-muted-foreground">Newest stock executions first.</p>
        </div>

        <div class="divide-y divide-border/70">
            @forelse($transactions as $transaction)
                <div class="grid gap-4 px-4 py-4 xl:grid-cols-[1.15fr_.9fr_.45fr_.55fr_.65fr_.5fr_auto] xl:items-center">
                    <div class="min-w-0">
                        <p class="truncate text-[11px] font-semibold">{{ $transaction->user->name }}</p>
                        <p class="mt-0.5 truncate text-[9px] text-muted-foreground">{{ $transaction->user->email }}</p>
                    </div>

                    <div class="min-w-0">
                        <p class="truncate text-[11px] font-semibold">{{ $transaction->stock->symbol }}</p>
                        <p class="mt-0.5 truncate text-[9px] text-muted-foreground">{{ $transaction->stock->name }}</p>
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
                        <p class="mt-0.5 text-[8px] text-muted-foreground">{{ currency_symbol() }}{{ number_format($transaction->price_per_share, 2) }} / share</p>
                    </div>

                    <div>
                        <span class="inline-flex rounded-full px-2 py-1 text-[8px] font-semibold uppercase tracking-[.11em]
                            {{ $transaction->status === 'completed' ? 'border border-emerald-500/20 bg-emerald-500/10 text-emerald-600' : 'border border-amber-500/20 bg-amber-500/10 text-amber-600' }}">
                            {{ ucfirst($transaction->status) }}
                        </span>
                        <p class="mt-1 text-[8px] text-muted-foreground">{{ $transaction->created_at->format('M d · H:i') }}</p>
                    </div>

                    <div class="flex justify-end gap-2">
                        <a href="{{ route('admin.stocks.transactions.show', $transaction) }}" class="ui-btn ui-btn-primary !h-8 !px-3">
                            View
                        </a>
                        <a href="{{ route('admin.users.show', $transaction->user) }}" class="ui-btn ui-btn-secondary !h-8 !px-3">
                            User
                        </a>
                    </div>
                </div>
            @empty
                <div class="p-10 text-center">
                    <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl border border-border bg-muted">
                        <i data-lucide="receipt-text" class="h-4 w-4 text-muted-foreground"></i>
                    </div>
                    <p class="mt-3 text-[12px] font-semibold">No stock transactions found</p>
                    <p class="mt-1 text-[10px] text-muted-foreground">No executions match the current filter.</p>
                </div>
            @endforelse
        </div>
    </section>

    <div class="mt-4">{{ $transactions->withQueryString()->links() }}</div>
</div>
</x-admin-layout>
