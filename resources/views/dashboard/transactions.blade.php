<x-user-layout>
    <x-slot name="header">
        Transaction History
    </x-slot>

    <div class="max-w-7xl mx-auto">
        <!-- Enhanced Header -->
        <div class="bg-gradient-to-br from-tesla-600 via-tesla-700 to-tesla-800 dark:from-tesla-700 dark:via-tesla-800 dark:to-tesla-900 rounded-2xl p-6 mb-6 text-white relative overflow-hidden">
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-0 right-0 w-48 h-48 bg-white rounded-full -translate-y-24 translate-x-24"></div>
                <div class="absolute bottom-0 left-0 w-24 h-24 bg-white rounded-full translate-y-12 -translate-x-12"></div>
            </div>
            
            <div class="relative z-10">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                    <div class="mb-4 lg:mb-0 lg:flex-1">
                        <h1 class="text-xl font-light mb-1">Transaction History</h1>
                        <p class="text-tesla-100 dark:text-gray-300 text-sm">Complete transaction history for all investments</p>
                    </div>
                    
                    <!-- Enhanced Stats Card -->
                    <div class="bg-white bg-opacity-15 backdrop-blur-xl rounded-xl p-4 border border-white border-opacity-20 shadow-xl lg:w-80">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <p class="text-xs text-tesla-100 dark:text-gray-300 mb-1">Total Transactions</p>
                                <p class="text-lg font-light">{{ $investmentTransactions->total() + $stockTransactions->total() }}</p>
                            </div>
                            <div class="w-10 h-10 flex items-center justify-center">
                                <i data-lucide="activity" class="w-5 h-5 text-white"></i>
                            </div>
                        </div>
                        <div class="flex space-x-4 text-xs">
                            <div class="flex-1 text-center">
                                <p class="text-tesla-100 dark:text-gray-300">Investments</p>
                                <p class="text-white font-medium">{{ $investmentTransactions->total() }}</p>
                            </div>
                            <div class="flex-1 text-center">
                                <p class="text-tesla-100 dark:text-gray-300">Stocks</p>
                                <p class="text-white font-medium">{{ $stockTransactions->total() }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-card rounded-xl p-4 shadow-sm border border-gray-100 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <!-- Type Filter -->
                <div>
                    <label for="type" class="block text-xs font-medium text-gray-700 mb-1">Type</label>
                    <select id="type" name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition-colors duration-200">
                        <option value="">All Types</option>
                        <option value="buy" {{ request('type') == 'buy' ? 'selected' : '' }}>Buy</option>
                        <option value="sell" {{ request('type') == 'sell' ? 'selected' : '' }}>Sell</option>
                    </select>
                </div>

                <!-- Asset Type Filter -->
                <div>
                    <label for="asset_type" class="block text-xs font-medium text-gray-700 mb-1">Asset Type</label>
                    <select id="asset_type" name="asset_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition-colors duration-200">
                        <option value="">All Assets</option>
                        <option value="investment" {{ request('asset_type') == 'investment' ? 'selected' : '' }}>Investment Plans</option>
                        <option value="stock" {{ request('asset_type') == 'stock' ? 'selected' : '' }}>Stocks</option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div>
                    <label for="status" class="block text-xs font-medium text-gray-700 mb-1">Status</label>
                    <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition-colors duration-200">
                        <option value="">All Status</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>

                <!-- Date Range -->
                <div>
                    <label for="date_from" class="block text-xs font-medium text-gray-700 mb-1">From Date</label>
                    <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition-colors duration-200">
                </div>

                <!-- Actions -->
                <div class="flex items-end space-x-2">
                    <button type="submit" class="px-4 py-2 bg-black dark:bg-white text-white dark:text-gray-900 text-xs font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-gray-100 transition-colors duration-200">
                        <i data-lucide="filter" class="w-3 h-3 mr-1"></i>
                        Filter
                    </button>
                    <a href="{{ route('investment.transactions') }}" class="px-4 py-2 border border-gray-300 text-black text-xs font-medium rounded-lg hover:bg-gray-50 transition-colors duration-200">
                        <i data-lucide="refresh-cw" class="w-3 h-3 mr-1"></i>
                        Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Combined Transactions Table -->
        <div class="bg-card rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-border">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-light text-black dark:text-white mb-1">All Transactions</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-300">Investment and stock transaction history</p>
                    </div>
                </div>
            </div>
            
            @php
                $allTransactions = $investmentTransactions->concat($stockTransactions)->sortByDesc('created_at');
            @endphp
            
            @if($allTransactions->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Asset</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-card divide-y divide-gray-100 dark:divide-tesla-600">
                            @foreach($allTransactions as $transaction)
                            <tr class="hover:bg-gray-50 transition-colors duration-200">
                                <td class="px-6 py-4">
                                    <div class="text-sm text-black">{{ $transaction->created_at->format('M j, Y') }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-300">{{ $transaction->created_at->format('g:i A') }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 w-8 h-8 bg-muted rounded-lg flex items-center justify-center">
                                            @if(isset($transaction->investmentPlan))
                                                <i data-lucide="trending-up" class="w-4 h-4 text-gray-600 dark:text-gray-300"></i>
                                            @else
                                                <i data-lucide="bar-chart-3" class="w-4 h-4 text-gray-600 dark:text-gray-300"></i>
                                            @endif
                                        </div>
                                        <div class="ml-3">
                                            @if(isset($transaction->investmentPlan))
                                                <div class="text-sm font-medium text-black">{{ $transaction->investmentPlan->name }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-300">{{ $transaction->investmentPlan->category }}</div>
                                            @elseif(isset($transaction->stock))
                                                <div class="text-sm font-medium text-black">{{ $transaction->stock->symbol }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-300">{{ $transaction->stock->name }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $transaction->type === 'buy' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ ucfirst($transaction->type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-black">
                                        @if(isset($transaction->investmentPlan))
                                            {{ number_format($transaction->units, 4) }} units
                                        @else
                                            {{ number_format($transaction->shares, 2) }} shares
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-black">
                                        @if(isset($transaction->investmentPlan))
                                            {{ currency_symbol() }}{{ number_format($transaction->nav_at_transaction, 4) }}
                                        @else
                                            {{ currency_symbol() }}{{ number_format($transaction->price_per_share, 2) }}
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-black">{{ currency_symbol() }}{{ number_format($transaction->total_amount, 2) }}</div>
                                    @if($transaction->fee > 0)
                                    <div class="text-xs text-gray-500 dark:text-gray-300">Fee: {{ currency_symbol() }}{{ number_format($transaction->fee, 2) }}</div>
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
                    <!-- Note: Pagination would need to be handled differently for combined results -->
                    <div class="text-center text-xs text-gray-500 dark:text-gray-300">
                        Showing combined results from both investment and stock transactions
                    </div>
                </div>
            @else
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-muted rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="activity" class="w-8 h-8 text-gray-400 dark:text-gray-300"></i>
                    </div>
                    <h3 class="text-lg font-light text-black dark:text-white mb-2">No transactions yet</h3>
                    <p class="text-gray-600 text-sm mb-4">Start investing to see your transaction history</p>
                    <div class="flex space-x-2 justify-center">
                        <a href="{{ route('investments.index') }}" class="inline-flex items-center px-4 py-2 bg-black dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-gray-100 transition-colors duration-200">
                            <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                            Invest in Plans
                        </a>
                        <a href="{{ route('stocks.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-black dark:text-white text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors duration-200">
                            <i data-lucide="bar-chart-3" class="w-4 h-4 mr-2"></i>
                            Trade Stocks
                        </a>
                    </div>
                </div>
            @endif
        </div>

        <!-- Summary Stats -->
        @if($allTransactions->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
            <!-- Total Investment Transactions -->
            <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-300 mb-1">Investment Transactions</p>
                        <p class="text-lg font-light text-black">{{ $investmentTransactions->total() }}</p>
                    </div>
                    <div class="w-8 h-8 flex items-center justify-center">
                        <i data-lucide="trending-up" class="w-4 h-4 text-purple-600"></i>
                    </div>
                </div>
            </div>

            <!-- Total Stock Transactions -->
            <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-300 mb-1">Stock Transactions</p>
                        <p class="text-lg font-light text-black">{{ $stockTransactions->total() }}</p>
                    </div>
                    <div class="w-8 h-8 flex items-center justify-center">
                        <i data-lucide="bar-chart-3" class="w-4 h-4 text-tesla-600"></i>
                    </div>
                </div>
            </div>

            <!-- Total Amount -->
            <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-300 mb-1">Total Amount</p>
                        <p class="text-lg font-light text-black">{{ currency_symbol() }}{{ number_format($investmentTransactions->sum('total_amount') + $stockTransactions->sum('total_amount'), 2) }}</p>
                    </div>
                    <div class="w-8 h-8 flex items-center justify-center">
                        <i data-lucide="dollar-sign" class="w-4 h-4 text-green-600"></i>
                    </div>
                </div>
            </div>

            <!-- Total Fees -->
            <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-300 mb-1">Total Fees</p>
                        <p class="text-lg font-light text-black">{{ currency_symbol() }}{{ number_format($investmentTransactions->sum('fee') + $stockTransactions->sum('fee'), 2) }}</p>
                    </div>
                    <div class="w-8 h-8 flex items-center justify-center">
                        <i data-lucide="credit-card" class="w-4 h-4 text-gray-600 dark:text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</x-user-layout> 
