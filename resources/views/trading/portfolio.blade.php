<x-user-layout>
    <x-slot name="header">
        Stock Portfolio
    </x-slot>

    <div class="max-w-7xl mx-auto">
        <!-- Portfolio Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Value -->
            <div class="bg-card rounded-xl p-6 shadow-sm border border-border">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-muted-foreground mb-1">Total Value</p>
                        <p class="text-2xl font-bold text-gray-900">{{ currency_symbol() }}{{ number_format($totalCurrentValue, 2) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-gradient-to-br from-tesla-500 to-tesla-600 rounded-lg flex items-center justify-center">
                        <i data-lucide="dollar-sign" class="w-6 h-6 text-white"></i>
                    </div>
                </div>
            </div>

            <!-- Total Invested -->
            <div class="bg-card rounded-xl p-6 shadow-sm border border-border">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-muted-foreground mb-1">Total Invested</p>
                        <p class="text-2xl font-bold text-gray-900">{{ currency_symbol() }}{{ number_format($totalInvested, 2) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-lg flex items-center justify-center">
                        <i data-lucide="trending-up" class="w-6 h-6 text-white"></i>
                    </div>
                </div>
            </div>

            <!-- Total Gain/Loss -->
            <div class="bg-card rounded-xl p-6 shadow-sm border border-border">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-muted-foreground mb-1">Total Gain/Loss</p>
                        <p class="text-2xl font-bold {{ $totalGainLoss >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $totalGainLoss >= 0 ? '+' : '' }}{{ currency_symbol() }}{{ number_format($totalGainLoss, 2) }}
                        </p>
                        <p class="text-sm {{ $totalGainLossPercentage >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $totalGainLossPercentage >= 0 ? '+' : '' }}{{ number_format($totalGainLossPercentage, 2) }}%
                        </p>
                    </div>
                    <div class="w-12 h-12 {{ $totalGainLoss >= 0 ? 'bg-gradient-to-br from-green-500 to-green-600' : 'bg-gradient-to-br from-red-500 to-red-600' }} rounded-lg flex items-center justify-center">
                        <i data-lucide="{{ $totalGainLoss >= 0 ? 'trending-up' : 'trending-down' }}" class="w-6 h-6 text-white"></i>
                    </div>
                </div>
            </div>

            <!-- Number of Holdings -->
            <div class="bg-card rounded-xl p-6 shadow-sm border border-border">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-muted-foreground mb-1">Holdings</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $holdings->count() }}</p>
                    </div>
                    <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg flex items-center justify-center">
                        <i data-lucide="pie-chart" class="w-6 h-6 text-white"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Holdings Table -->
        <div class="bg-card rounded-xl shadow-sm border border-gray-100 mb-8">
            <div class="p-6 border-b border-border">
                <h2 class="text-lg font-semibold text-gray-900">Your Holdings</h2>
                <p class="text-sm text-muted-foreground mt-1">Track your stock investments and performance</p>
            </div>
            
            @if($holdings->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-muted/30">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Stock</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Shares</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Avg Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Current Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Total Value</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Gain/Loss</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-card divide-y divide-gray-200">
                            @foreach($holdings as $holding)
                                <tr class="hover:bg-muted/30">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            @if($holding->stock->logo_url)
                                                <img src="{{ $holding->stock->logo_url }}" alt="{{ $holding->stock->symbol }}" class="w-8 h-8 rounded mr-3">
                                            @else
                                                <div class="w-8 h-8 bg-muted rounded mr-3 flex items-center justify-center">
                                                    <span class="text-xs font-medium text-muted-foreground">{{ substr($holding->stock->symbol, 0, 2) }}</span>
                                                </div>
                                            @endif
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">{{ $holding->stock->symbol }}</div>
                                                <div class="text-sm text-muted-foreground dark:text-gray-300">{{ $holding->stock->company_name }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ number_format($holding->quantity) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ currency_symbol() }}{{ number_format($holding->average_buy_price, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ currency_symbol() }}{{ number_format($holding->stock->current_price, 2) }}</div>
                                        <div class="text-xs {{ $holding->stock->change_percentage >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $holding->stock->change_percentage >= 0 ? '+' : '' }}{{ number_format($holding->stock->change_percentage, 2) }}%
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ currency_symbol() }}{{ number_format($holding->current_value, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium {{ $holding->unrealized_gain_loss >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $holding->unrealized_gain_loss >= 0 ? '+' : '' }}{{ currency_symbol() }}{{ number_format($holding->unrealized_gain_loss, 2) }}
                                        </div>
                                        <div class="text-xs {{ $holding->unrealized_gain_loss_percentage >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $holding->unrealized_gain_loss_percentage >= 0 ? '+' : '' }}{{ number_format($holding->unrealized_gain_loss_percentage, 2) }}%
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <a href="{{ route('trading.buy', $holding->stock) }}" 
                                               class="w-8 h-8 flex items-center justify-center text-tesla-600 hover:text-tesla-800 hover:bg-tesla-50 rounded-lg transition-colors duration-200"
                                               title="Buy More">
                                                <i data-lucide="plus" class="w-4 h-4"></i>
                                            </a>
                                            <a href="{{ route('trading.sell', $holding->stock) }}" 
                                               class="w-8 h-8 flex items-center justify-center text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition-colors duration-200"
                                               title="Sell">
                                                <i data-lucide="minus" class="w-4 h-4"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-8 text-center">
                    <div class="w-16 h-16 bg-muted rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="pie-chart" class="w-8 h-8 text-gray-400 dark:text-gray-300"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Holdings Yet</h3>
                    <p class="text-muted-foreground mb-6">Start building your portfolio by buying your first stock.</p>
                    <a href="{{ route('stocks.index') }}" 
                       class="inline-flex items-center px-4 py-2 bg-foreground text-background rounded-lg hover:opacity-90 transition-colors duration-200">
                        <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                        Browse Stocks
                    </a>
                </div>
            @endif
        </div>

        <!-- Recent Transactions -->
        @if($recentTransactions->count() > 0)
            <div class="bg-card rounded-xl shadow-sm border border-border">
                <div class="p-6 border-b border-border">
                    <h2 class="text-lg font-semibold text-gray-900">Recent Transactions</h2>
                    <p class="text-sm text-muted-foreground mt-1">Your latest stock trading activity</p>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-muted/30">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Stock</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Quantity</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Total</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-card divide-y divide-gray-200">
                            @foreach($recentTransactions as $transaction)
                                <tr class="hover:bg-muted/30">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $transaction->created_at->format('M j, Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            @if($transaction->stock->logo_url)
                                                <img src="{{ $transaction->stock->logo_url }}" alt="{{ $transaction->stock->symbol }}" class="w-6 h-6 rounded mr-2">
                                            @else
                                                <div class="w-6 h-6 bg-muted rounded mr-2 flex items-center justify-center">
                                                    <span class="text-xs font-medium text-muted-foreground">{{ substr($transaction->stock->symbol, 0, 2) }}</span>
                                                </div>
                                            @endif
                                            <span class="text-sm font-medium text-gray-900">{{ $transaction->stock->symbol }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $transaction->type === 'buy' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ ucfirst($transaction->type) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ number_format($transaction->quantity) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ currency_symbol() }}{{ number_format($transaction->price_per_share, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ currency_symbol() }}{{ number_format($transaction->total_amount, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            {{ ucfirst($transaction->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="p-6 border-t border-border">
                    <a href="{{ route('trading.transactions') }}" 
                       class="text-tesla-600 hover:text-tesla-900 text-sm font-medium">
                        View All Transactions →
                    </a>
                </div>
            </div>
        @endif
    </div>
</x-user-layout>
