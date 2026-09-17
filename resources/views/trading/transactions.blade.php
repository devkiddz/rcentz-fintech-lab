<x-user-layout>
    <x-slot name="header">
        Stock Transactions
    </x-slot>

    <div class="ui-page max-w-7xl">
        <section class="ui-page-header">
            <div>
                <p class="ui-kicker">Trading · Activity</p>
                <h1 class="ui-heading">Trade activity</h1>
                <p class="ui-lead">Review your stock trades, filter the ledger, and move back into the market when you need to.</p>
            </div>
            <div class="ui-header-actions">
                <a href="{{ route('trading.positions.index') }}" class="ui-btn ui-btn-secondary">
                    <i data-lucide="briefcase-business" class="h-4 w-4"></i>
                    Positions
                </a>
                <a href="{{ route('stocks.index') }}" class="ui-btn ui-btn-primary">
                    <i data-lucide="chart-no-axes-combined" class="h-4 w-4"></i>
                    Trade stocks
                </a>
            </div>
        </section>

        <section class="ui-metric-grid mb-5 sm:grid-cols-3">
            <article class="ui-metric-card">
                <p class="ui-label">Total records</p>
                <p class="mt-2 text-xl font-semibold text-foreground">{{ number_format($transactions->total()) }}</p>
            </article>
            <article class="ui-metric-card">
                <p class="ui-label">Buys on this page</p>
                <p class="mt-2 text-xl font-semibold text-foreground">{{ $transactions->where('type', 'buy')->count() }}</p>
            </article>
            <article class="ui-metric-card">
                <p class="ui-label">Sells on this page</p>
                <p class="mt-2 text-xl font-semibold text-foreground">{{ $transactions->where('type', 'sell')->count() }}</p>
            </article>
        </section>

        <!-- Filters -->
        <div class="ui-filter-panel mb-5">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Type Filter -->
                <div>
                    <label for="type" class="block text-xs font-medium text-foreground mb-1">Type</label>
                    <select id="type" name="type" class="ui-input">
                        <option value="">All Types</option>
                        <option value="buy" {{ request('type') == 'buy' ? 'selected' : '' }}>Buy</option>
                        <option value="sell" {{ request('type') == 'sell' ? 'selected' : '' }}>Sell</option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div>
                    <label for="status" class="block text-xs font-medium text-foreground mb-1">Status</label>
                    <select id="status" name="status" class="ui-input">
                        <option value="">All Status</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>

                <!-- Date Range -->
                <div>
                    <label for="date_from" class="block text-xs font-medium text-foreground mb-1">From Date</label>
                    <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}" class="ui-input">
                </div>

                <!-- Actions -->
                <div class="flex items-end space-x-2">
                    <button type="submit" class="ui-btn ui-btn-primary">
                        <i data-lucide="filter" class="w-3 h-3 mr-1"></i>
                        Filter
                    </button>
                    <a href="{{ route('trading.transactions') }}" class="ui-btn ui-btn-secondary">
                        <i data-lucide="refresh-cw" class="w-3 h-3 mr-1"></i>
                        Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Transactions Table -->
        <div class="ui-table-shell overflow-hidden">
            <div class="px-6 py-4 border-b border-border">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-foreground mb-1">Transactions</h3>
                        <p class="text-xs text-muted-foreground ">Detailed history of your stock trading activity</p>
                    </div>
                </div>
            </div>
            
            @if($transactions->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-muted/30">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Stock</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Shares</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @foreach($transactions as $transaction)
                            <tr class="hover:bg-muted/30 transition-colors duration-200">
                                <td class="px-6 py-4">
                                    <div class="text-sm text-foreground">{{ $transaction->created_at->format('M j, Y') }}</div>
                                    <div class="text-xs text-muted-foreground ">{{ $transaction->created_at->format('g:i A') }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 w-8 h-8 rounded-lg overflow-hidden">
                                            @if($transaction->stock->logo_url)
                                                <img src="{{ $transaction->stock->logo_url }}" alt="{{ $transaction->stock->company_name }}" class="w-8 h-8 object-cover">
                                            @else
                                                <div class="w-8 h-8 bg-muted rounded-lg flex items-center justify-center">
                                                    <i data-lucide="bar-chart-3" class="w-4 h-4 text-muted-foreground"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-foreground">{{ $transaction->stock->symbol }}</div>
                                            <div class="text-xs text-muted-foreground ">{{ $transaction->stock->company_name }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $transaction->type === 'buy' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ ucfirst($transaction->type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-foreground">{{ number_format($transaction->shares, 2) }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-foreground">{{ currency_symbol() }}{{ number_format($transaction->price_per_share, 2) }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-foreground">{{ currency_symbol() }}{{ number_format($transaction->total_amount, 2) }}</div>
                                    @if($transaction->fee > 0)
                                    <div class="text-xs text-muted-foreground ">Fee: {{ currency_symbol() }}{{ number_format($transaction->fee, 2) }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $transaction->status_badge }}">
                                        {{ ucfirst($transaction->status) }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-border">
                    {{ $transactions->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-muted rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="activity" class="w-8 h-8 text-muted-foreground "></i>
                    </div>
                    <h3 class="text-lg font-semibold text-foreground mb-2">No stock transactions yet</h3>
                    <p class="text-muted-foreground text-sm mb-4">Start trading stocks to see your transaction history</p>
                    <a href="{{ route('stocks.index') }}" class="inline-flex items-center px-4 py-2 bg-foreground text-background text-sm font-medium rounded-lg hover:opacity-90 transition-colors duration-200">
                        <i data-lucide="bar-chart-3" class="w-4 h-4 mr-2"></i>
                        Start Trading
                    </a>
                </div>
            @endif
        </div>

        <!-- Summary Stats -->
        @if($transactions->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
            <!-- Total Buys -->
            <div class="ui-panel p-4">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <p class="text-xs text-muted-foreground  mb-1">Total Buys</p>
                        <p class="text-lg font-semibold text-foreground">{{ $transactions->where('type', 'buy')->count() }}</p>
                    </div>
                    <div class="w-8 h-8 flex items-center justify-center">
                        <i data-lucide="plus" class="w-4 h-4 text-green-600"></i>
                    </div>
                </div>
            </div>

            <!-- Total Sells -->
            <div class="ui-panel p-4">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <p class="text-xs text-muted-foreground  mb-1">Total Sells</p>
                        <p class="text-lg font-semibold text-foreground">{{ $transactions->where('type', 'sell')->count() }}</p>
                    </div>
                    <div class="w-8 h-8 flex items-center justify-center">
                        <i data-lucide="minus" class="w-4 h-4 text-red-600"></i>
                    </div>
                </div>
            </div>

            <!-- Total Amount -->
            <div class="ui-panel p-4">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <p class="text-xs text-muted-foreground  mb-1">Total Amount</p>
                        <p class="text-lg font-semibold text-foreground">{{ currency_symbol() }}{{ number_format($transactions->sum('total_amount'), 2) }}</p>
                    </div>
                    <div class="w-8 h-8 flex items-center justify-center">
                        <i data-lucide="dollar-sign" class="w-4 h-4 text-foreground"></i>
                    </div>
                </div>
            </div>

            <!-- Total Fees -->
            <div class="ui-panel p-4">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <p class="text-xs text-muted-foreground  mb-1">Total Fees</p>
                        <p class="text-lg font-semibold text-foreground">{{ currency_symbol() }}{{ number_format($transactions->sum('fee'), 2) }}</p>
                    </div>
                    <div class="w-8 h-8 flex items-center justify-center">
                        <i data-lucide="credit-card" class="w-4 h-4 text-muted-foreground"></i>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</x-user-layout> 
