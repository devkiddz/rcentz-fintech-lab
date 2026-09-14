<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-light text-lg text-foreground leading-tight mr-4">
                    Stock Transactions
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <!-- Statistics Overview -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-card border border-border p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-8 h-8 md:w-10 md:h-10 bg-tesla-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 md:w-5 md:h-5 text-tesla-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                            </svg>
                        </div>
                        <div class="ml-2 md:ml-3">
                            <p class="text-xs font-medium text-muted-foreground dark:text-gray-300">Total Transactions</p>
                            <p class="text-sm md:text-lg font-light text-foreground">{{ $stats['total_transactions'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-card border border-border p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-8 h-8 md:w-10 md:h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 md:w-5 md:h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-2 md:ml-3">
                            <p class="text-xs font-medium text-muted-foreground dark:text-gray-300">Unique Users</p>
                            <p class="text-sm md:text-lg font-light text-foreground">{{ $stats['unique_users'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-card border border-border p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-8 h-8 md:w-10 md:h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 md:w-5 md:h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <div class="ml-2 md:ml-3">
                            <p class="text-xs font-medium text-muted-foreground dark:text-gray-300">Unique Stocks</p>
                            <p class="text-sm md:text-lg font-light text-foreground">{{ $stats['unique_stocks'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-card border border-border p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-8 h-8 md:w-10 md:h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 md:w-5 md:h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                        <div class="ml-2 md:ml-3">
                            <p class="text-xs font-medium text-muted-foreground dark:text-gray-300">Total Volume</p>
                            <p class="text-sm md:text-lg font-light text-foreground">${{ number_format($stats['total_volume'], 0) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transaction Type Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-card border border-border p-4 rounded-lg">
                    <div class="text-center">
                        <p class="text-xs font-medium text-muted-foreground mb-1">Buy Transactions</p>
                        <p class="text-lg font-light text-foreground">{{ $stats['buy_transactions'] }}</p>
                    </div>
                </div>

                <div class="bg-card border border-border p-4 rounded-lg">
                    <div class="text-center">
                        <p class="text-xs font-medium text-muted-foreground mb-1">Sell Transactions</p>
                        <p class="text-lg font-light text-foreground">{{ $stats['sell_transactions'] }}</p>
                    </div>
                </div>

                <div class="bg-card border border-border p-4 rounded-lg">
                    <div class="text-center">
                        <p class="text-xs font-medium text-muted-foreground mb-1">Completed</p>
                        <p class="text-lg font-light text-foreground">{{ $stats['completed_transactions'] }}</p>
                    </div>
                </div>

                <div class="bg-card border border-border p-4 rounded-lg">
                    <div class="text-center">
                        <p class="text-xs font-medium text-muted-foreground mb-1">Pending</p>
                        <p class="text-lg font-light text-foreground">{{ $stats['pending_transactions'] }}</p>
                    </div>
                </div>
            </div>

            <!-- Transactions List -->
            <div class="bg-card border border-border overflow-hidden rounded-lg">
                <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                    <h3 class="text-base font-medium text-foreground">Stock Transactions</h3>
                    <p class="text-xs text-muted-foreground mt-1">All buy/sell transactions by users</p>
                </div>
                
                <div class="p-4">
                    @if($transactions->count() > 0)
                    <div class="space-y-4">
                        @foreach($transactions as $transaction)
                        <div class="border border-border dark:border-gray-700 rounded-lg p-4 hover:bg-muted/40 transition-colors">
                            <!-- Mobile Layout -->
                            <div class="md:hidden">
                                <div class="flex items-start space-x-3 mb-3">
                                    @if($transaction->user->profile_image)
                                        <img src="{{ asset('storage/' . $transaction->user->profile_image) }}" 
                                             alt="{{ $transaction->user->name }}" 
                                             class="w-10 h-10 rounded-lg object-cover">
                                    @else
                                        <div class="w-10 h-10 bg-gradient-to-br from-tesla-500 to-purple-600 rounded-lg flex items-center justify-center">
                                            <span class="text-white font-bold text-xs">{{ strtoupper(substr($transaction->user->name, 0, 2)) }}</span>
                                        </div>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-medium text-foreground truncate">{{ $transaction->user->name }}</h4>
                                        <p class="text-xs text-muted-foreground truncate">{{ $transaction->stock->name }} ({{ $transaction->stock->symbol }})</p>
                                        <p class="text-xs text-muted-foreground mt-1">{{ ucfirst($transaction->type) }} • {{ number_format($transaction->quantity, 2) }} shares</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-medium text-foreground">${{ number_format($transaction->total_amount, 2) }}</p>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $transaction->status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                            {{ ucfirst($transaction->status) }}
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Mobile Actions -->
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('admin.stocks.transactions.show', $transaction) }}" 
                                       class="flex-1 px-3 py-2 bg-muted text-muted-foreground text-xs font-medium rounded text-center hover:bg-muted transition-colors">
                                       View Details
                                    </a>
                                    <a href="{{ route('admin.users.show', $transaction->user) }}" 
                                       class="flex-1 px-3 py-2 bg-black dark:bg-card text-white dark:text-foreground text-xs font-medium rounded text-center hover:opacity-90 transition-colors">
                                       View User
                                    </a>
                                </div>
                            </div>

                            <!-- Desktop Layout -->
                            <div class="hidden md:flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    @if($transaction->user->profile_image)
                                        <img src="{{ asset('storage/' . $transaction->user->profile_image) }}" 
                                             alt="{{ $transaction->user->name }}" 
                                             class="w-12 h-12 rounded-lg object-cover">
                                    @else
                                        <div class="w-12 h-12 bg-gradient-to-br from-tesla-500 to-purple-600 rounded-lg flex items-center justify-center">
                                            <span class="text-white font-bold text-sm">{{ strtoupper(substr($transaction->user->name, 0, 2)) }}</span>
                                        </div>
                                    @endif
                                    <div>
                                        <h4 class="text-sm font-medium text-foreground">{{ $transaction->user->name }}</h4>
                                        <p class="text-xs text-muted-foreground dark:text-gray-300">{{ $transaction->stock->name }} ({{ $transaction->stock->symbol }})</p>
                                        <p class="text-xs text-muted-foreground mt-1">{{ ucfirst($transaction->type) }} • {{ number_format($transaction->quantity, 2) }} shares</p>
                                    </div>
                                </div>
                                
                                <div class="flex items-center space-x-3">
                                    <div class="text-right">
                                        <p class="text-sm font-medium text-foreground">${{ number_format($transaction->total_amount, 2) }}</p>
                                        <p class="text-xs text-muted-foreground dark:text-gray-300">${{ number_format($transaction->price_per_share, 2) }} per share</p>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $transaction->status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                            {{ ucfirst($transaction->status) }}
                                        </span>
                                    </div>
                                    
                                    <div class="flex space-x-2">
                                        <a href="{{ route('admin.stocks.transactions.show', $transaction) }}" 
                                           class="px-3 py-1.5 bg-muted text-muted-foreground text-xs font-medium rounded hover:bg-muted transition-colors">
                                           View
                                        </a>
                                        <a href="{{ route('admin.users.show', $transaction->user) }}" 
                                           class="px-3 py-1.5 bg-black dark:bg-card text-white dark:text-foreground text-xs font-medium rounded hover:opacity-90 transition-colors">
                                           User
                                        </a>
                                    </div>
                                </div>
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
                        <div class="w-16 h-16 bg-muted rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-foreground dark:text-white mb-2">No stock transactions found</h3>
                        <p class="text-xs text-muted-foreground dark:text-gray-300">There are no stock transactions at the moment.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
