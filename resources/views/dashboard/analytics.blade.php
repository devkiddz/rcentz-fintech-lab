<x-user-layout>
    <x-slot name="header">
        Investment Analytics
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
                        <h1 class="text-xl font-light mb-1">Investment Analytics</h1>
                        <p class="text-tesla-100 dark:text-gray-300 text-sm">Advanced portfolio analysis and insights</p>
                    </div>
                    
                    <!-- Enhanced Stats Card -->
                    <div class="bg-white bg-opacity-15 backdrop-blur-xl rounded-xl p-4 border border-white border-opacity-20 shadow-xl lg:w-80">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <p class="text-xs text-tesla-100 dark:text-gray-300 mb-1">Total Return</p>
                                <p class="text-lg font-light {{ $totalGainLossPercentage >= 0 ? 'text-green-400' : 'text-red-400' }}">
                                    {{ $totalGainLossPercentage >= 0 ? '+' : '' }}{{ number_format($totalGainLossPercentage, 2) }}%
                                </p>
                            </div>
                            <div class="w-10 h-10 flex items-center justify-center">
                                <i data-lucide="bar-chart-3" class="w-5 h-5 text-white"></i>
                            </div>
                        </div>
                        <div class="flex space-x-4 text-xs">
                            <div class="flex-1 text-center">
                                <p class="text-tesla-100 dark:text-gray-300">Value</p>
                                <p class="text-white font-medium">{{ currency_symbol() }}{{ number_format($totalPortfolioValue, 0) }}</p>
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

        <!-- Performance Overview -->
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

        <!-- Asset Allocation -->
        <div class="bg-card rounded-xl p-6 shadow-sm border border-gray-100 mb-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-light text-black dark:text-white mb-1">Asset Allocation</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-300">Portfolio breakdown by asset type</p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Investment Allocation -->
                <div class="bg-muted rounded-lg p-4">
                    <h4 class="text-sm font-medium text-black dark:text-white mb-3">Investment Plans</h4>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-600 dark:text-gray-300">Total Value</span>
                            <span class="text-xs font-medium text-black">{{ currency_symbol() }}{{ number_format($totalInvestmentValue, 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-600 dark:text-gray-300">Percentage</span>
                            <span class="text-xs font-medium text-black">{{ $totalPortfolioValue > 0 ? number_format(($totalInvestmentValue / $totalPortfolioValue) * 100, 1) : 0 }}%</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-600 dark:text-gray-300">Holdings</span>
                            <span class="text-xs font-medium text-black">{{ $investmentHoldings->count() }}</span>
                        </div>
                    </div>
                </div>

                <!-- Stock Allocation -->
                <div class="bg-muted rounded-lg p-4">
                    <h4 class="text-sm font-medium text-black dark:text-white mb-3">Stocks</h4>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-600 dark:text-gray-300">Total Value</span>
                            <span class="text-xs font-medium text-black">{{ currency_symbol() }}{{ number_format($totalStockValue, 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-600 dark:text-gray-300">Percentage</span>
                            <span class="text-xs font-medium text-black">{{ $totalPortfolioValue > 0 ? number_format(($totalStockValue / $totalPortfolioValue) * 100, 1) : 0 }}%</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-600 dark:text-gray-300">Holdings</span>
                            <span class="text-xs font-medium text-black">{{ $stockHoldings->count() }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Chart -->
        <div class="bg-card rounded-xl p-6 shadow-sm border border-gray-100 mb-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-light text-black dark:text-white mb-1">Performance Chart</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-300">Portfolio performance over time</p>
                </div>
                <div class="flex space-x-2">
                    <button data-period="1m" class="px-3 py-1 text-xs font-medium text-black bg-muted rounded-lg">1M</button>
                    <button data-period="3m" class="px-3 py-1 text-xs font-medium text-gray-600 hover:text-black transition-colors duration-200">3M</button>
                    <button data-period="6m" class="px-3 py-1 text-xs font-medium text-gray-600 hover:text-black transition-colors duration-200">6M</button>
                    <button data-period="1y" class="px-3 py-1 text-xs font-medium text-gray-600 hover:text-black transition-colors duration-200">1Y</button>
                </div>
            </div>
            
            <!-- Dashboard Performance Chart -->
            <div class="relative">
                <canvas id="dashboardChart" class="w-full h-64"></canvas>
            </div>
        </div>

        <!-- Risk Analysis -->
        <div class="bg-card rounded-xl p-6 shadow-sm border border-gray-100 mb-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-light text-black dark:text-white mb-1">Risk Analysis</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-300">Portfolio risk assessment and metrics</p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Risk Level Distribution -->
                <div class="bg-muted rounded-lg p-4">
                    <h4 class="text-sm font-medium text-black dark:text-white mb-3">Investment Risk Levels</h4>
                    <div class="space-y-2">
                        @php
                            $riskLevels = $investmentHoldings->groupBy('investmentPlan.risk_level');
                        @endphp
                        @foreach($riskLevels as $riskLevel => $holdingsGroup)
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-600 capitalize">{{ $riskLevel }}</span>
                            <span class="text-xs font-medium text-black">{{ $holdingsGroup->count() }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Sector Allocation -->
                <div class="bg-muted rounded-lg p-4">
                    <h4 class="text-sm font-medium text-black dark:text-white mb-3">Stock Sectors</h4>
                    <div class="space-y-2">
                        @php
                            $sectors = $stockHoldings->groupBy('stock.sector');
                        @endphp
                        @foreach($sectors as $sector => $holdingsGroup)
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-600 dark:text-gray-300">{{ $sector ?: 'Unknown' }}</span>
                            <span class="text-xs font-medium text-black">{{ $holdingsGroup->count() }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Performance Summary -->
                <div class="bg-muted rounded-lg p-4">
                    <h4 class="text-sm font-medium text-black dark:text-white mb-3">Performance Summary</h4>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-600 dark:text-gray-300">Best Performer</span>
                            <span class="text-xs font-medium text-green-600">
                                @php
                                    $allHoldings = $investmentHoldings->concat($stockHoldings);
                                    $bestHolding = $allHoldings->sortByDesc('unrealized_gain_loss_percentage')->first();
                                @endphp
                                {{ $bestHolding ? number_format($bestHolding->unrealized_gain_loss_percentage, 2) . '%' : 'N/A' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-600 dark:text-gray-300">Worst Performer</span>
                            <span class="text-xs font-medium text-red-600">
                                @php
                                    $worstHolding = $allHoldings->sortBy('unrealized_gain_loss_percentage')->first();
                                @endphp
                                {{ $worstHolding ? number_format($worstHolding->unrealized_gain_loss_percentage, 2) . '%' : 'N/A' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-card rounded-xl p-6 shadow-sm border border-border">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-light text-black dark:text-white mb-1">Recent Activity</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-300">Latest portfolio changes</p>
                </div>
                <a href="{{ route('investment.transactions') }}" class="text-xs text-tesla-600 hover:text-tesla-800 transition-colors duration-200 font-medium">
                    View All
                    <i data-lucide="arrow-right" class="w-3 h-3 inline ml-1"></i>
                </a>
            </div>
            
            @php
                $allTransactions = $recentInvestmentTransactions->concat($recentStockTransactions)->sortByDesc('created_at')->take(5);
            @endphp
            
            @if($allTransactions->count() > 0)
                <div class="space-y-3">
                    @foreach($allTransactions as $transaction)
                    <div class="flex items-center space-x-3 p-3 bg-muted rounded-lg">
                        <div class="w-8 h-8 flex items-center justify-center">
                            @if($transaction->type === 'buy')
                                <i data-lucide="plus" class="w-4 h-4 text-green-500"></i>
                            @else
                                <i data-lucide="minus" class="w-4 h-4 text-red-500"></i>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium text-black">
                                @if(isset($transaction->investmentPlan))
                                    {{ $transaction->investmentPlan->name }}
                                @elseif(isset($transaction->stock))
                                    {{ $transaction->stock->symbol }}
                                @endif
                            </p>
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
                    <p class="text-xs text-gray-500 dark:text-gray-300">No recent activity</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        // Initialize dashboard performance chart
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('dashboardChart');
            if (ctx) {
                // Generate sample dashboard data for demonstration
                const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                const totalInvested = {{ $totalInvested ?? 10000 }};
                const totalCurrentValue = {{ $totalCurrentValue ?? 12500 }};
                
                const dashboardData = [
                    totalInvested, // Start with invested amount
                    totalInvested * 1.02,
                    totalInvested * 1.05,
                    totalInvested * 1.03,
                    totalInvested * 1.08,
                    totalInvested * 1.12,
                    totalInvested * 1.15,
                    totalInvested * 1.18,
                    totalInvested * 1.22,
                    totalInvested * 1.25,
                    totalInvested * 1.28,
                    totalCurrentValue // End with current value
                ];

                const dashboardChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Portfolio Value',
                            data: dashboardData,
                            borderColor: '#000000',
                            backgroundColor: 'rgba(0, 0, 0, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 0,
                            pointHoverRadius: 6,
                            pointHoverBackgroundColor: '#000000',
                            pointHoverBorderColor: '#ffffff',
                            pointHoverBorderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleColor: '#ffffff',
                                bodyColor: '#ffffff',
                                borderColor: '#000000',
                                borderWidth: 1,
                                cornerRadius: 8,
                                displayColors: false,
                                callbacks: {
                                    label: function(context) {
                                        return '$' + context.parsed.y.toLocaleString();
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                display: true,
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    color: '#6b7280',
                                    font: {
                                        size: 11
                                    }
                                }
                            },
                            y: {
                                display: true,
                                position: 'right',
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.1)',
                                    drawBorder: false
                                },
                                ticks: {
                                    color: '#6b7280',
                                    font: {
                                        size: 11
                                    },
                                    callback: function(value) {
                                        return '$' + (value / 1000).toFixed(0) + 'k';
                                    }
                                }
                            }
                        }
                    }
                });

                // Add period button event listeners
                document.querySelectorAll('[data-period]').forEach(button => {
                    button.addEventListener('click', function() {
                        const period = this.getAttribute('data-period');
                        updateChartPeriod(period);
                        
                        // Update active button
                        document.querySelectorAll('[data-period]').forEach(btn => {
                            btn.classList.remove('bg-gray-100', 'text-black');
                            btn.classList.add('text-gray-600');
                        });
                        this.classList.remove('text-gray-600');
                        this.classList.add('bg-gray-100', 'text-black');
                    });
                });

                function updateChartPeriod(period) {
                    // For demo purposes, we'll just update the chart with different data ranges
                    // In a real implementation, this would fetch data from the server
                    const periods = {
                        '1m': dashboardData.slice(-1),
                        '3m': dashboardData.slice(-3),
                        '6m': dashboardData.slice(-6),
                        '1y': dashboardData
                    };
                    
                    const periodLabels = {
                        '1m': ['Dec'],
                        '3m': ['Oct', 'Nov', 'Dec'],
                        '6m': ['Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                        '1y': labels
                    };

                    dashboardChart.data.labels = periodLabels[period];
                    dashboardChart.data.datasets[0].data = periods[period];
                    dashboardChart.update();
                }
            }
        });
    </script>
</x-user-layout> 
