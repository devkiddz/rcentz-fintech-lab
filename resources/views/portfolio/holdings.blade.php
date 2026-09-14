<x-user-layout>
    <x-slot name="header">
        Holdings
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
                        <h1 class="text-xl font-light mb-1">Holdings</h1>
                        <p class="text-tesla-100 dark:text-gray-300 text-sm">Detailed view of your investment holdings</p>
                    </div>
                    
                    <!-- Enhanced Stats Card -->
                    <div class="bg-white bg-opacity-15 backdrop-blur-xl rounded-xl p-4 border border-white border-opacity-20 shadow-xl lg:w-80">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <p class="text-xs text-tesla-100 dark:text-gray-300 mb-1">Total Holdings</p>
                                <p class="text-lg font-light">{{ $holdings->count() }}</p>
                            </div>
                            <div class="w-10 h-10 flex items-center justify-center">
                                <i data-lucide="pie-chart" class="w-5 h-5 text-white"></i>
                            </div>
                        </div>
                        <div class="flex space-x-4 text-xs">
                            <div class="flex-1 text-center">
                                <p class="text-tesla-100 dark:text-gray-300">Value</p>
                                <p class="text-white font-medium">{{ currency_symbol() }}{{ number_format($totalCurrentValue, 0) }}</p>
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

        <!-- Holdings Table -->
        <div class="bg-card rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-border">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-light text-black dark:text-white mb-1">Investment Holdings</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-300">Detailed breakdown of your investments</p>
                    </div>
                    <a href="{{ route('investments.index') }}" class="text-xs text-tesla-600 hover:text-tesla-800 transition-colors duration-200 font-medium">
                        Browse More
                        <i data-lucide="arrow-right" class="w-3 h-3 inline ml-1"></i>
                    </a>
                </div>
            </div>
            
            @if($holdings->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Investment</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Units</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Cost</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Value</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gain/Loss</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Return %</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-card divide-y divide-gray-100 dark:divide-tesla-600">
                            @foreach($holdings as $holding)
                            <tr class="hover:bg-gray-50 transition-colors duration-200">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 w-8 h-8 bg-muted rounded-lg flex items-center justify-center">
                                            <i data-lucide="trending-up" class="w-4 h-4 text-gray-600 dark:text-gray-300"></i>
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-black">{{ $holding->investmentPlan->name }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-300">{{ $holding->investmentPlan->category }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-black">{{ number_format($holding->units, 4) }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-black">{{ currency_symbol() }}{{ number_format($holding->average_cost, 4) }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-black">{{ currency_symbol() }}{{ number_format($holding->current_value, 2) }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm {{ $holding->unrealized_gain_loss >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $holding->unrealized_gain_loss >= 0 ? '+' : '' }}{{ currency_symbol() }}{{ number_format($holding->unrealized_gain_loss, 2) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm {{ $holding->unrealized_gain_loss_percentage >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $holding->unrealized_gain_loss_percentage >= 0 ? '+' : '' }}{{ number_format($holding->unrealized_gain_loss_percentage, 2) }}%
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex space-x-2">
                                        <a href="{{ route('investments.show', $holding->investmentPlan) }}" class="w-8 h-8 flex items-center justify-center text-tesla-600 hover:text-tesla-800 hover:bg-tesla-50 rounded-lg transition-colors duration-200" title="View">
                                            <i data-lucide="eye" class="w-4 h-4"></i>
                                        </a>
                                        <a href="{{ route('investments.buy', $holding->investmentPlan) }}" class="w-8 h-8 flex items-center justify-center text-black hover:text-gray-800 hover:bg-muted rounded-lg transition-colors duration-200" title="Buy">
                                            <i data-lucide="plus" class="w-4 h-4"></i>
                                        </a>
                                        @if($holding->units > 0)
                                        <a href="{{ route('investments.sell', $holding->investmentPlan) }}" class="w-8 h-8 flex items-center justify-center text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition-colors duration-200" title="Sell">
                                            <i data-lucide="minus" class="w-4 h-4"></i>
                                        </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-muted rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="trending-up" class="w-8 h-8 text-gray-400 dark:text-gray-300"></i>
                    </div>
                    <h3 class="text-lg font-light text-black dark:text-white mb-2">No holdings yet</h3>
                    <p class="text-gray-600 text-sm mb-4">Start building your portfolio by investing in our plans</p>
                    <a href="{{ route('investments.index') }}" class="inline-flex items-center px-4 py-2 bg-black dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-gray-100 transition-colors duration-200">
                        <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                        Start Investing
                    </a>
                </div>
            @endif
        </div>

        <!-- Summary Cards -->
        @if($holdings->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
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
                    </div>
                    <div class="w-8 h-8 flex items-center justify-center">
                        <i data-lucide="{{ $totalGainLoss >= 0 ? 'arrow-up' : 'arrow-down' }}" class="w-4 h-4 {{ $totalGainLoss >= 0 ? 'text-green-600' : 'text-red-600' }}"></i>
                    </div>
                </div>
            </div>

            <!-- Total Return % -->
            <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-300 mb-1">Total Return</p>
                        <p class="text-lg font-light {{ $totalGainLossPercentage >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $totalGainLossPercentage >= 0 ? '+' : '' }}{{ number_format($totalGainLossPercentage, 2) }}%
                        </p>
                    </div>
                    <div class="w-8 h-8 flex items-center justify-center">
                        <i data-lucide="percent" class="w-4 h-4 {{ $totalGainLossPercentage >= 0 ? 'text-green-600' : 'text-red-600' }}"></i>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</x-user-layout> 
