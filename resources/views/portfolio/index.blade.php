<x-user-layout>
    <x-slot name="header">
        Portfolio Overview
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
                        <div class="flex items-center mb-1">
                            <h1 class="text-xl font-light">Portfolio Overview</h1>
                            <div class="ml-3 real-time-indicator">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-900 text-green-300">
                                    <i data-lucide="radio" class="w-3 h-3 mr-1"></i>
                                    Live
                                </span>
                            </div>
                        </div>
                        <p class="text-tesla-100 dark:text-gray-300 text-sm">Track your investment performance and holdings</p>
                        <p class="text-xs text-gray-400 mt-1">Last updated: <span data-last-updated>{{ now()->format('H:i:s') }}</span></p>
                    </div>
                    
                    <!-- Enhanced Stats Card -->
                    <div class="bg-white bg-opacity-15 backdrop-blur-xl rounded-xl p-4 border border-white border-opacity-20 shadow-xl lg:w-80">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <p class="text-xs text-tesla-100 dark:text-gray-300 mb-1">Total Value</p>
                                <p class="text-lg font-light" data-portfolio-total-value="{{ currency_symbol() }}{{ number_format($totalCurrentValue, 2) }}">{{ currency_symbol() }}{{ number_format($totalCurrentValue, 2) }}</p>
                            </div>
                            <div class="w-10 h-10 flex items-center justify-center">
                                <i data-lucide="trending-up" class="w-5 h-5 text-white"></i>
                            </div>
                        </div>
                        <div class="flex space-x-4 text-xs">
                            <div class="flex-1 text-center">
                                <p class="text-tesla-100 dark:text-gray-300">Invested</p>
                                <p class="text-white font-medium" data-portfolio-total-cost="{{ currency_symbol() }}{{ number_format($totalInvested, 2) }}">{{ currency_symbol() }}{{ number_format($totalInvested, 2) }}</p>
                            </div>
                            <div class="flex-1 text-center">
                                <p class="text-tesla-100 dark:text-gray-300">Gain/Loss</p>
                                <p class="text-white font-medium {{ $totalGainLoss >= 0 ? 'text-green-400' : 'text-red-400' }}" data-portfolio-total-gain-loss="{{ currency_symbol() }}{{ number_format($totalGainLoss, 2) }}">
                                    {{ $totalGainLoss >= 0 ? '+' : '' }}{{ currency_symbol() }}{{ number_format($totalGainLoss, 2) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Portfolio Navigation -->
        <div class="bg-card rounded-xl p-4 shadow-sm border border-gray-100 mb-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-light text-black dark:text-white mb-1">Portfolio Tools</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-300">Access detailed portfolio analysis and management</p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <a href="{{ route('portfolio.holdings') }}" class="group bg-muted rounded-lg p-4 hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-300">
                    <div class="flex items-center justify-between mb-2">
                        <div class="w-8 h-8 flex items-center justify-center">
                            <i data-lucide="pie-chart" class="w-4 h-4 text-tesla-600"></i>
                        </div>
                        <i data-lucide="arrow-right" class="w-4 h-4 text-gray-400 group-hover:text-gray-600 transition-colors duration-200"></i>
                    </div>
                    <h4 class="text-sm font-medium text-black dark:text-white mb-1">Holdings</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-300">Detailed holdings breakdown</p>
                </a>

                <a href="{{ route('portfolio.analytics') }}" class="group bg-muted rounded-lg p-4 hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-300">
                    <div class="flex items-center justify-between mb-2">
                        <div class="w-8 h-8 flex items-center justify-center">
                            <i data-lucide="bar-chart-3" class="w-4 h-4 text-green-600"></i>
                        </div>
                        <i data-lucide="arrow-right" class="w-4 h-4 text-gray-400 group-hover:text-gray-600 transition-colors duration-200"></i>
                    </div>
                    <h4 class="text-sm font-medium text-black dark:text-white mb-1">Analytics</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-300">Performance charts & analysis</p>
                </a>

                <a href="{{ route('portfolio.transactions') }}" class="group bg-muted rounded-lg p-4 hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-300">
                    <div class="flex items-center justify-between mb-2">
                        <div class="w-8 h-8 flex items-center justify-center">
                            <i data-lucide="activity" class="w-4 h-4 text-purple-600"></i>
                        </div>
                        <i data-lucide="arrow-right" class="w-4 h-4 text-gray-400 group-hover:text-gray-600 transition-colors duration-200"></i>
                    </div>
                    <h4 class="text-sm font-medium text-black dark:text-white mb-1">Transactions</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-300">Complete transaction history</p>
                </a>

                <a href="{{ route('investment.dashboard') }}" class="group bg-muted rounded-lg p-4 hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-300">
                    <div class="flex items-center justify-between mb-2">
                        <div class="w-8 h-8 flex items-center justify-center">
                            <i data-lucide="layout-dashboard" class="w-4 h-4 text-orange-600"></i>
                        </div>
                        <i data-lucide="arrow-right" class="w-4 h-4 text-gray-400 group-hover:text-gray-600 transition-colors duration-200"></i>
                    </div>
                    <h4 class="text-sm font-medium text-black dark:text-white mb-1">Dashboard</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-300">Investment dashboard overview</p>
                </a>
            </div>
        </div>

        <!-- Portfolio Performance -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <!-- Total Invested -->
            <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-300 mb-1">Total Invested</p>
                        <p class="text-lg font-light text-black">{{ currency_symbol() }}{{ number_format($totalInvested, 2) }}</p>
                    </div>
                    <div class="w-8 h-8 flex items-center justify-center">
                        <i data-lucide="dollar-sign" class="w-4 h-4 text-tesla-600"></i>
                    </div>
                </div>
            </div>

            <!-- Current Value -->
            <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-300 mb-1">Current Value</p>
                        <p class="text-lg font-light text-black">{{ currency_symbol() }}{{ number_format($totalCurrentValue, 2) }}</p>
                    </div>
                    <div class="w-8 h-8 flex items-center justify-center">
                        <i data-lucide="trending-up" class="w-4 h-4 text-green-600"></i>
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
                        <p class="text-xs {{ $totalGainLoss >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $totalGainLossPercentage >= 0 ? '+' : '' }}{{ number_format($totalGainLossPercentage, 2) }}%
                        </p>
                    </div>
                    <div class="w-8 h-8 flex items-center justify-center">
                        <i data-lucide="{{ $totalGainLoss >= 0 ? 'arrow-up' : 'arrow-down' }}" class="w-4 h-4 {{ $totalGainLoss >= 0 ? 'text-green-600' : 'text-red-600' }}"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Holdings Section -->
        <div class="bg-card rounded-xl p-6 shadow-sm border border-gray-100 mb-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-light text-black dark:text-white mb-1">Your Holdings</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-300">{{ $holdings->count() }} investment plans</p>
                </div>
                <a href="{{ route('portfolio.holdings') }}" class="text-xs text-tesla-600 hover:text-tesla-800 transition-colors duration-200 font-medium">
                    Browse More
                    <i data-lucide="arrow-right" class="w-3 h-3 inline ml-1"></i>
                </a>
            </div>
            
            @if($holdings->count() > 0)
                <div class="space-y-3">
                    @foreach($holdings as $holding)
                    <div class="group bg-muted rounded-lg p-4 hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-300">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex-1">
                                <h4 class="font-medium text-black dark:text-white text-sm mb-1">{{ $holding->investmentPlan->name }}</h4>
                                <p class="text-xs text-gray-600 dark:text-gray-300">{{ $holding->investmentPlan->category }}</p>
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $holding->investmentPlan->risk_level_badge }}">
                                {{ ucfirst($holding->investmentPlan->risk_level) }}
                            </span>
                        </div>
                        
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-3">
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-300">Units</p>
                                <p class="text-xs font-medium text-black">{{ number_format($holding->units, 4) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-300">Value</p>
                                <p class="text-xs font-medium text-black">{{ currency_symbol() }}{{ number_format($holding->current_value, 2) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-300">Gain/Loss</p>
                                <p class="text-xs font-medium {{ $holding->unrealized_gain_loss >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $holding->unrealized_gain_loss >= 0 ? '+' : '' }}{{ currency_symbol() }}{{ number_format($holding->unrealized_gain_loss, 2) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-300">Return</p>
                                <p class="text-xs font-medium {{ $holding->unrealized_gain_loss_percentage >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $holding->unrealized_gain_loss_percentage >= 0 ? '+' : '' }}{{ number_format($holding->unrealized_gain_loss_percentage, 2) }}%
                                </p>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <a href="{{ route('investments.show', $holding->investmentPlan) }}" class="text-xs text-tesla-600 hover:text-tesla-800 transition-colors duration-200">
                                View Details
                            </a>
                            <div class="flex space-x-2">
                                <a href="{{ route('investments.buy', $holding->investmentPlan) }}" class="px-2 py-1 bg-black dark:bg-white text-white dark:text-gray-900 text-xs font-medium rounded transition-colors duration-200 hover:bg-gray-800">
                                    Buy More
                                </a>
                                @if($holding->units > 0)
                                <a href="{{ route('investments.sell', $holding->investmentPlan) }}" class="px-2 py-1 border border-gray-300 text-black text-xs font-medium rounded transition-colors duration-200 hover:bg-gray-50">
                                    Sell
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <div class="w-12 h-12 bg-muted rounded-full flex items-center justify-center mx-auto mb-3">
                        <i data-lucide="trending-up" class="w-6 h-6 text-gray-400 dark:text-gray-300"></i>
                    </div>
                    <h3 class="text-base font-light text-black dark:text-white mb-1">No holdings yet</h3>
                    <p class="text-gray-600 text-xs mb-3">Start building your portfolio by investing in our plans</p>
                    <a href="{{ route('investments.index') }}" class="inline-flex items-center px-4 py-2 bg-black dark:bg-white text-white dark:text-gray-900 text-xs font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-gray-100 transition-colors duration-200">
                        <i data-lucide="plus" class="w-3 h-3 mr-1"></i>
                        Start Investing
                    </a>
                </div>
            @endif
        </div>

        <!-- Recent Transactions -->
        <div class="bg-card rounded-xl p-6 shadow-sm border border-border">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-light text-black dark:text-white mb-1">Recent Transactions</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-300">Your latest investment activity</p>
                </div>
                <a href="{{ route('wallet.transactions') }}" class="text-xs text-tesla-600 hover:text-tesla-800 transition-colors duration-200 font-medium">
                    View All
                    <i data-lucide="arrow-right" class="w-3 h-3 inline ml-1"></i>
                </a>
            </div>
            
            @if($recentTransactions->count() > 0)
                <div class="space-y-3">
                    @foreach($recentTransactions as $transaction)
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
                            <p class="text-xs text-gray-500 dark:text-gray-300">{{ $transaction->type_label }} • {{ $transaction->formatted_executed_at }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-medium text-black">{{ currency_symbol() }}{{ number_format($transaction->total_amount, 2) }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-300">{{ number_format($transaction->units, 4) }} units</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-6">
                    <div class="w-8 h-8 bg-muted rounded-full flex items-center justify-center mx-auto mb-2">
                        <i data-lucide="activity" class="w-4 h-4 text-gray-400 dark:text-gray-300"></i>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-300">No recent transactions</p>
                </div>
            @endif
        </div>
    </div>
</x-user-layout> 
