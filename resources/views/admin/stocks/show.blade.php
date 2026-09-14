<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-light text-lg text-foreground leading-tight mr-4">
                    {{ $stock->name }} ({{ $stock->symbol }})
                </h2>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('admin.stocks.index') }}" 
                   class="px-3 py-2 bg-muted text-muted-foreground text-sm font-medium rounded-lg hover:bg-muted transition-colors">
                    Back to Stocks
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="xl:col-span-2 space-y-6">
                    <!-- Stock Overview -->
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Stock Overview</h3>
                        </div>
                        <div class="p-4">
                            <div class="flex items-start space-x-4 mb-4">
                                @if($stock->logo_url)
                                    <div class="w-16 h-16 rounded-lg flex items-center justify-center bg-card border border-border">
                                        <img src="{{ $stock->logo_url }}" alt="{{ $stock->symbol }} Logo" class="w-14 h-14 object-contain" loading="lazy">
                                    </div>
                                @else
                                    <div class="w-16 h-16 bg-gradient-to-br from-tesla-500 to-purple-600 rounded-lg flex items-center justify-center">
                                        <span class="text-white font-bold text-lg">{{ $stock->symbol }}</span>
                                    </div>
                                @endif
                                <div class="flex-1">
                                    <h4 class="text-lg font-medium text-foreground dark:text-white mb-2">{{ $stock->name }}</h4>
                                    <p class="text-sm text-muted-foreground mb-3">{{ $stock->description }}</p>
                                    <div class="flex flex-wrap gap-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-tesla-100 text-tesla-800">
                                            {{ $stock->sector }}
                                        </span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            {{ $stock->industry }}
                                        </span>
                                        @if($stock->is_active)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Active
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Current Price Metrics -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-card border border-border p-4 rounded-lg">
                            <div class="text-center">
                                <p class="text-xs font-medium text-muted-foreground mb-1">Current Price</p>
                                <p class="text-lg font-light text-foreground">{{ currency_symbol() }}{{ number_format($stock->current_price, 2) }}</p>
                            </div>
                        </div>

                        <div class="bg-card border border-border p-4 rounded-lg">
                            <div class="text-center">
                                <p class="text-xs font-medium text-muted-foreground mb-1">Previous Close</p>
                                <p class="text-lg font-light text-foreground">{{ currency_symbol() }}{{ number_format($stock->previous_close, 2) }}</p>
                            </div>
                        </div>

                        <div class="bg-card border border-border p-4 rounded-lg">
                            <div class="text-center">
                                <p class="text-xs font-medium text-muted-foreground mb-1">Change</p>
                                <p class="text-lg font-light {{ $stock->price_change >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $stock->price_change >= 0 ? '+' : '' }}{{ currency_symbol() }}{{ number_format($stock->price_change, 2) }}
                                </p>
                            </div>
                        </div>

                        <div class="bg-card border border-border p-4 rounded-lg">
                            <div class="text-center">
                                <p class="text-xs font-medium text-muted-foreground mb-1">Change %</p>
                                <p class="text-lg font-light {{ $stock->price_change_percentage >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $stock->price_change_percentage >= 0 ? '+' : '' }}{{ number_format($stock->price_change_percentage, 2) }}%
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Holdings -->
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Recent Holdings</h3>
                            <p class="text-xs text-muted-foreground mt-1">Latest stock holdings by users</p>
                        </div>
                        <div class="p-4">
                            @if($stock->holdings->count() > 0)
                            <div class="space-y-3">
                                @foreach($stock->holdings->take(5) as $holding)
                                <div class="border border-border dark:border-gray-700 rounded-lg p-3">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            @if($holding->user->profile_image)
                                                <img src="{{ asset('storage/' . $holding->user->profile_image) }}" 
                                                     alt="{{ $holding->user->name }}" 
                                                     class="w-8 h-8 rounded-lg object-cover">
                                            @else
                                                <div class="w-8 h-8 bg-gradient-to-br from-tesla-500 to-purple-600 rounded-lg flex items-center justify-center">
                                                    <span class="text-white font-bold text-xs">{{ strtoupper(substr($holding->user->name, 0, 2)) }}</span>
                                                </div>
                                            @endif
                                            <div>
                                                <h4 class="text-sm font-medium text-foreground">{{ $holding->user->name }}</h4>
                                                <p class="text-xs text-muted-foreground dark:text-gray-300">{{ number_format($holding->quantity, 2) }} shares</p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-medium text-foreground">{{ currency_symbol() }}{{ number_format($holding->current_value, 2) }}</p>
                                            <p class="text-xs {{ $holding->unrealized_gain_loss_percentage >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                {{ $holding->unrealized_gain_loss_percentage >= 0 ? '+' : '' }}{{ number_format($holding->unrealized_gain_loss_percentage, 2) }}%
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @else
                            <div class="text-center py-6">
                                <p class="text-sm text-muted-foreground dark:text-gray-300">No holdings found for this stock.</p>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Recent Transactions -->
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Recent Transactions</h3>
                            <p class="text-xs text-muted-foreground mt-1">Latest buy/sell transactions</p>
                        </div>
                        <div class="p-4">
                            @if($stock->transactions->count() > 0)
                            <div class="space-y-3">
                                @foreach($stock->transactions->take(5) as $transaction)
                                <div class="border border-border dark:border-gray-700 rounded-lg p-3">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            @if($transaction->user->profile_image)
                                                <img src="{{ asset('storage/' . $transaction->user->profile_image) }}" 
                                                     alt="{{ $transaction->user->name }}" 
                                                     class="w-8 h-8 rounded-lg object-cover">
                                            @else
                                                <div class="w-8 h-8 bg-gradient-to-br from-tesla-500 to-purple-600 rounded-lg flex items-center justify-center">
                                                    <span class="text-white font-bold text-xs">{{ strtoupper(substr($transaction->user->name, 0, 2)) }}</span>
                                                </div>
                                            @endif
                                            <div>
                                                <h4 class="text-sm font-medium text-foreground">{{ $transaction->user->name }}</h4>
                                                <p class="text-xs text-muted-foreground dark:text-gray-300">{{ ucfirst($transaction->type) }} • {{ number_format($transaction->quantity, 2) }} shares</p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-medium text-foreground">{{ currency_symbol() }}{{ number_format($transaction->total_amount, 2) }}</p>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $transaction->status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                {{ ucfirst($transaction->status) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @else
                            <div class="text-center py-6">
                                <p class="text-sm text-muted-foreground dark:text-gray-300">No transactions found for this stock.</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Stock Information -->
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Stock Information</h3>
                        </div>
                        <div class="p-4">
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Symbol</label>
                                    <p class="text-sm font-medium text-foreground">{{ $stock->symbol }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Sector</label>
                                    <p class="text-sm font-medium text-foreground">{{ $stock->sector }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Industry</label>
                                    <p class="text-sm font-medium text-foreground">{{ $stock->industry }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Status</label>
                                    <p class="text-sm font-medium text-foreground">
                                        @if($stock->is_active)
                                            <span class="text-green-600">Active</span>
                                        @else
                                            <span class="text-red-600">Inactive</span>
                                        @endif
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Last Updated</label>
                                    <p class="text-sm font-medium text-foreground">
                                        {{ $stock->last_updated ? $stock->last_updated->format('M d, Y h:i A') : 'Never' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Summary -->
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Statistics Summary</h3>
                        </div>
                        <div class="p-4">
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Total Holdings</label>
                                    <p class="text-sm font-medium text-foreground">{{ $stats['total_holdings'] }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Total Transactions</label>
                                    <p class="text-sm font-medium text-foreground">{{ $stats['total_transactions'] }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Total Volume</label>
                                    <p class="text-sm font-medium text-foreground">{{ currency_symbol() }}{{ number_format($stats['total_volume'], 2) }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Total Shares</label>
                                    <p class="text-sm font-medium text-foreground">{{ number_format($stats['total_shares'], 2) }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Total Value</label>
                                    <p class="text-sm font-medium text-foreground">{{ currency_symbol() }}{{ number_format($stats['total_value'], 2) }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Total Invested</label>
                                    <p class="text-sm font-medium text-foreground">{{ currency_symbol() }}{{ number_format($stats['total_invested'], 2) }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Total Gain/Loss</label>
                                    <p class="text-sm font-medium {{ $stats['total_gain_loss'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $stats['total_gain_loss'] >= 0 ? '+' : '' }}{{ currency_symbol() }}{{ number_format($stats['total_gain_loss'], 2) }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4 space-y-2">
                                <a href="{{ route('admin.stocks.holdings.index') }}?stock={{ $stock->id }}" 
                                   class="w-full inline-flex items-center justify-center px-3 py-2 bg-muted text-muted-foreground text-sm font-medium rounded-lg hover:bg-muted transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                    View All Holdings
                                </a>
                                <a href="{{ route('admin.stocks.transactions.index') }}?stock={{ $stock->id }}" 
                                   class="w-full inline-flex items-center justify-center px-3 py-2 bg-black dark:bg-card text-white dark:text-foreground text-sm font-medium rounded-lg hover:opacity-90 transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                    </svg>
                                    View All Transactions
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
