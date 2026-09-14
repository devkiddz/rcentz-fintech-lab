<x-user-layout>
    <x-slot name="header">
        Investment Dashboard
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
                        <h1 class="text-xl font-light mb-1">Investment Dashboard</h1>
                        <p class="text-tesla-100 dark:text-gray-300 text-sm">Complete overview of your investment portfolio</p>
                    </div>
                    
                    <!-- Enhanced Stats Card -->
                    <div class="bg-white bg-opacity-15 backdrop-blur-xl rounded-xl p-4 border border-white border-opacity-20 shadow-xl lg:w-80">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <p class="text-xs text-tesla-100 dark:text-gray-300 mb-1">Total Portfolio</p>
                                <p class="text-lg font-light">{{ currency_symbol() }}{{ number_format($totalPortfolioValue, 2) }}</p>
                            </div>
                            <div class="w-10 h-10 flex items-center justify-center">
                                <i data-lucide="trending-up" class="w-5 h-5 text-white"></i>
                            </div>
                        </div>
                        <div class="flex space-x-4 text-xs">
                            <div class="flex-1 text-center">
                                <p class="text-tesla-100 dark:text-gray-300">Invested</p>
                                <p class="text-white font-medium">{{ currency_symbol() }}{{ number_format($totalInvested, 0) }}</p>
                            </div>
                            <div class="flex-1 text-center">
                                <p class="text-tesla-100 dark:text-gray-300">Gain/Loss</p>
                                <p class="text-white font-medium {{ $totalGainLoss >= 0 ? 'text-green-400' : 'text-red-400' }}">
                                    {{ $totalGainLoss >= 0 ? '+' : '' }}{{ currency_symbol() }}{{ number_format($totalGainLoss, 0) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Portfolio Overview -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <!-- Total Portfolio Value -->
            <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-300 mb-1">Portfolio Value</p>
                        <p class="text-lg font-light text-black">{{ currency_symbol() }}{{ number_format($totalPortfolioValue, 2) }}</p>
                    </div>
                    <div class="w-8 h-8 flex items-center justify-center">
                        <i data-lucide="pie-chart" class="w-4 h-4 text-tesla-600"></i>
                    </div>
                </div>
            </div>

            <!-- Total Invested -->
            <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-300 mb-1">Total Invested</p>
                        <p class="text-lg font-light text-black">{{ currency_symbol() }}{{ number_format($totalInvested, 2) }}</p>
                    </div>
                    <div class="w-8 h-8 flex items-center justify-center">
                        <i data-lucide="dollar-sign" class="w-4 h-4 text-green-600"></i>
                    </div>
                </div>
            </div>

            <!-- Total Gain/Loss -->
            <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-300 mb-1">Total Gain/Loss</p>
                        <p class="text-lg font-light {{ $totalGainLoss >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $totalGainLoss >= 0 ? '+' : '' }}{{ currency_symbol() }}{{ number_format($totalGainLoss, 2) }}
                        </p>
                        <p class="text-xs {{ $totalGainLossPercentage >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $totalGainLossPercentage >= 0 ? '+' : '' }}{{ number_format($totalGainLossPercentage, 2) }}%
                        </p>
                    </div>
                    <div class="w-8 h-8 flex items-center justify-center">
                        <i data-lucide="{{ $totalGainLoss >= 0 ? 'arrow-up' : 'arrow-down' }}" class="w-4 h-4 {{ $totalGainLoss >= 0 ? 'text-green-600' : 'text-red-600' }}"></i>
                    </div>
                </div>
            </div>

            <!-- Asset Allocation -->
            <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-300 mb-1">Monthly Performance</p>
                        <p class="text-lg font-light {{ $monthlyPerformance['is_positive'] ? 'text-green-600' : 'text-red-600' }}">
                            {{ $monthlyPerformance['is_positive'] ? '+' : '' }}{{ number_format($monthlyPerformance['percentage_change'], 2) }}%
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-300">This month</p>
                    </div>
                    <div class="w-8 h-8 flex items-center justify-center">
                        <i data-lucide="{{ $monthlyPerformance['is_positive'] ? 'trending-up' : 'trending-down' }}" class="w-4 h-4 {{ $monthlyPerformance['is_positive'] ? 'text-green-600' : 'text-red-600' }}"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Asset Breakdown -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Investment Holdings -->
            <div class="bg-card rounded-xl p-6 shadow-sm border border-border">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-light text-black dark:text-white mb-1">Investment Holdings</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-300">{{ $investmentHoldings->count() }} plans</p>
                    </div>
                    <a href="{{ route('portfolio.holdings') }}" class="text-xs text-tesla-600 hover:text-tesla-800 transition-colors duration-200 font-medium">
                        View All
                        <i data-lucide="arrow-right" class="w-3 h-3 inline ml-1"></i>
                    </a>
                </div>
                
                @if($investmentHoldings->count() > 0)
                    <div class="space-y-3">
                        @foreach($investmentHoldings->take(3) as $holding)
                        <div class="flex items-center justify-between p-3 bg-muted rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-muted rounded-lg flex items-center justify-center">
                                    <i data-lucide="trending-up" class="w-4 h-4 text-gray-600 dark:text-gray-300"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-black">{{ $holding->investmentPlan->name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-300">{{ number_format($holding->units, 4) }} units</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-black">{{ currency_symbol() }}{{ number_format($holding->current_value, 2) }}</p>
                                <p class="text-xs {{ $holding->unrealized_gain_loss_percentage >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $holding->unrealized_gain_loss_percentage >= 0 ? '+' : '' }}{{ number_format($holding->unrealized_gain_loss_percentage, 2) }}%
                                </p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-6">
                        <div class="w-8 h-8 bg-muted rounded-full flex items-center justify-center mx-auto mb-2">
                            <i data-lucide="trending-up" class="w-4 h-4 text-gray-400 dark:text-gray-300"></i>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-300">No investment holdings</p>
                    </div>
                @endif
            </div>

            <!-- Stock Holdings -->
            <div class="bg-card rounded-xl p-6 shadow-sm border border-border">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-light text-black dark:text-white mb-1">Stock Holdings</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-300">{{ $stockHoldings->count() }} stocks</p>
                    </div>
                    <a href="{{ route('trading.portfolio') }}" class="text-xs text-tesla-600 hover:text-tesla-800 transition-colors duration-200 font-medium">
                        View All
                        <i data-lucide="arrow-right" class="w-3 h-3 inline ml-1"></i>
                    </a>
                </div>
                
                @if($stockHoldings->count() > 0)
                    <div class="space-y-3">
                        @foreach($stockHoldings->take(3) as $holding)
                        <div class="flex items-center justify-between p-3 bg-muted rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-muted rounded-lg flex items-center justify-center">
                                    <i data-lucide="bar-chart-3" class="w-4 h-4 text-gray-600 dark:text-gray-300"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-black">{{ $holding->stock->symbol }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-300">{{ number_format($holding->quantity, 2) }} shares</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-black">{{ currency_symbol() }}{{ number_format($holding->current_value, 2) }}</p>
                                <p class="text-xs {{ $holding->unrealized_gain_loss_percentage >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $holding->unrealized_gain_loss_percentage >= 0 ? '+' : '' }}{{ number_format($holding->unrealized_gain_loss_percentage, 2) }}%
                                </p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-6">
                        <div class="w-8 h-8 bg-muted rounded-full flex items-center justify-center mx-auto mb-2">
                            <i data-lucide="bar-chart-3" class="w-4 h-4 text-gray-400 dark:text-gray-300"></i>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-300">No stock holdings</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Recent Investment Transactions -->
            <div class="bg-card rounded-xl p-6 shadow-sm border border-border">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-light text-black dark:text-white mb-1">Recent Investment Activity</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-300">Latest investment transactions</p>
                    </div>
                    <a href="{{ route('portfolio.transactions') }}" class="text-xs text-tesla-600 hover:text-tesla-800 transition-colors duration-200 font-medium">
                        View All
                        <i data-lucide="arrow-right" class="w-3 h-3 inline ml-1"></i>
                    </a>
                </div>
                
                @if($recentInvestmentTransactions->count() > 0)
                    <div class="space-y-3">
                        @foreach($recentInvestmentTransactions as $transaction)
                        <div class="flex items-center space-x-3 p-3 bg-muted rounded-lg">
                            <div class="w-8 h-8 flex items-center justify-center">
                                @if($transaction->type === 'buy')
                                    <i data-lucide="plus" class="w-4 h-4 text-green-500"></i>
                                @else
                                    <i data-lucide="minus" class="w-4 h-4 text-red-500"></i>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-medium text-black">{{ $transaction->investmentPlan->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-300">{{ $transaction->type_label }} • {{ $transaction->created_at->format('M j, Y') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs font-medium text-black">{{ currency_symbol() }}{{ number_format($transaction->total_amount, 2) }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-6">
                        <div class="w-8 h-8 bg-muted rounded-full flex items-center justify-center mx-auto mb-2">
                            <i data-lucide="activity" class="w-4 h-4 text-gray-400 dark:text-gray-300"></i>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-300">No recent investment activity</p>
                    </div>
                @endif
            </div>

            <!-- Recent Stock Transactions -->
            <div class="bg-card rounded-xl p-6 shadow-sm border border-border">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-light text-black dark:text-white mb-1">Recent Stock Activity</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-300">Latest stock transactions</p>
                    </div>
                    <a href="{{ route('trading.transactions') }}" class="text-xs text-tesla-600 hover:text-tesla-800 transition-colors duration-200 font-medium">
                        View All
                        <i data-lucide="arrow-right" class="w-3 h-3 inline ml-1"></i>
                    </a>
                </div>
                
                @if($recentStockTransactions->count() > 0)
                    <div class="space-y-3">
                        @foreach($recentStockTransactions as $transaction)
                        <div class="flex items-center space-x-3 p-3 bg-muted rounded-lg">
                            <div class="w-8 h-8 flex items-center justify-center">
                                @if($transaction->type === 'buy')
                                    <i data-lucide="plus" class="w-4 h-4 text-green-500"></i>
                                @else
                                    <i data-lucide="minus" class="w-4 h-4 text-red-500"></i>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-medium text-black">{{ $transaction->stock->symbol }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-300">{{ $transaction->type_label }} • {{ $transaction->created_at->format('M j, Y') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs font-medium text-black">{{ currency_symbol() }}{{ number_format($transaction->total_amount, 2) }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-6">
                        <div class="w-8 h-8 bg-muted rounded-full flex items-center justify-center mx-auto mb-2">
                            <i data-lucide="activity" class="w-4 h-4 text-gray-400 dark:text-gray-300"></i>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-300">No recent stock activity</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Automatic Investment Plans -->
        <div class="bg-card rounded-xl p-6 shadow-sm border border-border">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-light text-black dark:text-white mb-1">Automatic Investment Plans</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-300">Your recurring investment schedules</p>
                </div>
                <a href="{{ route('automatic-investments.index') }}" class="text-xs text-tesla-600 hover:text-tesla-800 transition-colors duration-200 font-medium">
                    Manage Plans
                    <i data-lucide="arrow-right" class="w-3 h-3 inline ml-1"></i>
                </a>
            </div>
            
            @if($automaticPlans->count() > 0)
                <div class="space-y-3">
                    @foreach($automaticPlans as $plan)
                    <div class="flex items-center justify-between p-3 bg-muted rounded-lg">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-muted rounded-lg flex items-center justify-center">
                                <i data-lucide="repeat" class="w-4 h-4 text-gray-600 dark:text-gray-300"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-black">{{ $plan->investmentPlan->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-300">{{ currency_symbol() }}{{ number_format($plan->amount, 2) }} {{ $plan->frequency }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $plan->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $plan->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-6">
                    <div class="w-8 h-8 bg-muted rounded-full flex items-center justify-center mx-auto mb-2">
                        <i data-lucide="repeat" class="w-4 h-4 text-gray-400 dark:text-gray-300"></i>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-300">No automatic investment plans</p>
                    <a href="{{ route('automatic-investments.index') }}" class="inline-flex items-center px-3 py-1 mt-2 bg-black dark:bg-white text-white dark:text-gray-900 text-xs font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-gray-100 transition-colors duration-200">
                        <i data-lucide="plus" class="w-3 h-3 mr-1"></i>
                        Create Plan
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-user-layout> 
