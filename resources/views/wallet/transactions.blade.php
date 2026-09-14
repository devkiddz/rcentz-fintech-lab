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
                        <p class="text-tesla-100 dark:text-gray-300 text-sm">View all your wallet transactions and activity</p>
                    </div>
                    
                    <!-- Enhanced Stats Card -->
                    <div class="bg-white bg-opacity-15 backdrop-blur-xl rounded-xl p-4 border border-white border-opacity-20 shadow-xl lg:w-80">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <p class="text-xs text-tesla-100 dark:text-gray-300 mb-1">Total Transactions</p>
                                <p class="text-lg font-light">{{ $transactions->total() }}</p>
                            </div>
                            <div class="w-10 h-10 flex items-center justify-center">
                                <i data-lucide="list" class="w-5 h-5 text-white"></i>
                            </div>
                        </div>
                        <div class="flex space-x-4 text-xs">
                            <div class="flex-1 text-center">
                                <p class="text-tesla-100 dark:text-gray-300">Deposits</p>
                                <p class="text-white font-medium">{{ format_currency($totalDeposits) }}</p>
                            </div>
                            <div class="flex-1 text-center">
                                <p class="text-tesla-100 dark:text-gray-300">Withdrawals</p>
                                <p class="text-white font-medium">{{ format_currency($totalWithdrawals) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Filters -->
        <div class="bg-card rounded-xl p-5 shadow-sm border border-gray-100 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Type Filter -->
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                    <select id="type" name="type" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition-colors duration-200">
                        <option value="">All Types</option>
                        <option value="deposit" {{ request('type') == 'deposit' ? 'selected' : '' }}>Deposits</option>
                        <option value="withdrawal" {{ request('type') == 'withdrawal' ? 'selected' : '' }}>Withdrawals</option>
                        <option value="investment" {{ request('type') == 'investment' ? 'selected' : '' }}>Investments</option>
                        <option value="stock" {{ request('type') == 'stock' ? 'selected' : '' }}>Stock Trading</option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select id="status" name="status" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition-colors duration-200">
                        <option value="">All Statuses</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>

                <!-- Date Range -->
                <div>
                    <label for="date_from" class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
                    <input type="date" 
                           id="date_from" 
                           name="date_from" 
                           value="{{ request('date_from') }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition-colors duration-200">
                </div>

                <div>
                    <label for="date_to" class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
                    <input type="date" 
                           id="date_to" 
                           name="date_to" 
                           value="{{ request('date_to') }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition-colors duration-200">
                </div>

                <!-- Filter Buttons -->
                <div class="md:col-span-4 flex space-x-3">
                    <button type="submit" class="px-4 py-2 bg-black dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-gray-100 transition-colors duration-200 flex items-center">
                        <i data-lucide="filter" class="w-4 h-4 mr-2"></i>
                        Apply Filters
                    </button>
                    <a href="{{ route('wallet.transactions') }}" class="px-4 py-2 border border-gray-300 text-black dark:text-white text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors duration-200 flex items-center">
                        <i data-lucide="refresh-cw" class="w-4 h-4 mr-2"></i>
                        Clear Filters
                    </a>
                </div>
            </form>
        </div>

        <!-- Enhanced Transaction Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <!-- Total Deposits -->
            <div class="bg-card rounded-xl p-4 shadow-sm border border-gray-100 hover:shadow-lg transition-all duration-300 group">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-300 mb-1">Total Deposits</p>
                        <p class="text-lg font-light text-black dark:text-white mb-1">{{ format_currency($totalDeposits) }}</p>
                        <p class="text-xs text-green-600">This period</p>
                    </div>
                    <div class="flex flex-col items-end space-y-1">
                        <div class="w-10 h-10 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                            <i data-lucide="trending-up" class="w-5 h-5 text-green-500"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Withdrawals -->
            <div class="bg-card rounded-xl p-4 shadow-sm border border-gray-100 hover:shadow-lg transition-all duration-300 group">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-300 mb-1">Total Withdrawals</p>
                        <p class="text-lg font-light text-black dark:text-white mb-1">{{ format_currency($totalWithdrawals) }}</p>
                        <p class="text-xs text-red-600">This period</p>
                    </div>
                    <div class="flex flex-col items-end space-y-1">
                        <div class="w-10 h-10 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                            <i data-lucide="trending-down" class="w-5 h-5 text-red-500"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Fees -->
            <div class="bg-card rounded-xl p-4 shadow-sm border border-gray-100 hover:shadow-lg transition-all duration-300 group">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-300 mb-1">Total Fees</p>
                        <p class="text-lg font-light text-black dark:text-white mb-1">{{ format_currency($totalFees) }}</p>
                        <p class="text-xs text-orange-600">Processing costs</p>
                    </div>
                    <div class="flex flex-col items-end space-y-1">
                        <div class="w-10 h-10 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                            <i data-lucide="calculator" class="w-5 h-5 text-orange-500"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Transactions List -->
        <div class="bg-card rounded-xl p-5 shadow-sm border border-border">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-light text-black dark:text-white mb-1">All Transactions</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-300">Showing {{ $transactions->count() }} of {{ $transactions->total() }} transactions</p>
                </div>
            </div>
            
            @if($transactions->count() > 0)
                <div class="space-y-3">
                    @foreach($transactions as $transaction)
                    <div class="flex items-center space-x-3 p-4 bg-muted rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200 group">
                        <div class="w-10 h-10 flex items-center justify-center">
                            @if($transaction->type === 'deposit')
                                <i data-lucide="plus" class="w-5 h-5 text-green-500"></i>
                            @elseif($transaction->type === 'withdrawal')
                                <i data-lucide="minus" class="w-5 h-5 text-red-500"></i>
                            @elseif($transaction->type === 'investment')
                                <i data-lucide="trending-up" class="w-5 h-5 text-tesla-500"></i>
                            @else
                                <i data-lucide="arrow-right-left" class="w-5 h-5 text-purple-500"></i>
                            @endif
                        </div>
                        
                        <div class="flex-1 min-w-0">
                            <h4 class="font-medium text-black dark:text-white text-sm truncate">{{ ucfirst($transaction->type) }}</h4>
                            <p class="text-xs text-gray-600 dark:text-gray-300">{{ $transaction->paymentMethod->name ?? 'Direct Transfer' }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-300">{{ $transaction->created_at->format('M d, Y H:i') }}</p>
                        </div>
                        
                        <div class="text-right">
                            <p class="font-medium text-black dark:text-white text-sm {{ $transaction->type === 'deposit' ? 'text-green-600' : ($transaction->type === 'withdrawal' ? 'text-red-600' : 'text-tesla-600') }}">
                                {{ $transaction->type === 'deposit' ? '+' : ($transaction->type === 'withdrawal' ? '-' : '') }}{{ format_currency($transaction->amount) }}
                            </p>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $transaction->status_badge }}">
                                {{ ucfirst($transaction->status) }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $transactions->links() }}
                </div>
            @else
                <div class="text-center py-8">
                    <div class="w-12 h-12 bg-muted rounded-full flex items-center justify-center mx-auto mb-3">
                        <i data-lucide="list" class="w-6 h-6 text-gray-400 dark:text-gray-300"></i>
                    </div>
                    <h3 class="text-base font-light text-black dark:text-white mb-1">No transactions found</h3>
                    <p class="text-gray-600 text-xs mb-3">Try adjusting your filters or make your first transaction</p>
                    <a href="{{ route('wallet.deposit') }}" class="inline-flex items-center px-4 py-2 bg-black dark:bg-white text-white dark:text-gray-900 text-xs font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-gray-100 transition-colors duration-200">
                        <i data-lucide="plus" class="w-3 h-3 mr-1"></i>
                        Make a Deposit
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-user-layout> 
