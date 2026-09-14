<x-user-layout>
    <x-slot name="header">
        {{ $plan->name }}
    </x-slot>

    <div class="max-w-7xl mx-auto">
        <!-- Enhanced Header -->
        <div class="bg-gradient-to-br from-tesla-600 via-tesla-700 to-tesla-800 dark:from-tesla-700 dark:via-tesla-800 dark:to-tesla-900 rounded-2xl p-6 mb-6 text-white relative overflow-hidden">
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-0 right-0 w-48 h-48 bg-card rounded-full -translate-y-24 translate-x-24"></div>
                <div class="absolute bottom-0 left-0 w-24 h-24 bg-card rounded-full translate-y-12 -translate-x-12"></div>
            </div>
            
            <div class="relative z-10">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                    <div class="mb-4 lg:mb-0 lg:flex-1">
                        <h1 class="text-xl font-light mb-1">{{ $plan->name }}</h1>
                        <p class="text-tesla-100 dark:text-gray-300 text-sm">{{ $plan->category }} • {{ ucfirst($plan->risk_level) }} Risk</p>
                    </div>
                    
                    <!-- Enhanced Stats Card -->
                    <div class="bg-card bg-opacity-15 backdrop-blur-xl rounded-xl p-4 border border-white border-opacity-20 shadow-xl lg:w-80">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <p class="text-xs text-tesla-100 dark:text-gray-300 mb-1">Current NAV</p>
                                <p class="text-lg font-light">{{ currency_symbol() }}{{ number_format($plan->nav, 4) }}</p>
                            </div>
                            <div class="w-10 h-10 flex items-center justify-center">
                                <i data-lucide="trending-up" class="w-5 h-5 text-white"></i>
                            </div>
                        </div>
                        <div class="flex space-x-4 text-xs">
                            <div class="flex-1 text-center">
                                <p class="text-tesla-100 dark:text-gray-300">Change</p>
                                <p class="text-white font-medium {{ $plan->nav_change_percentage >= 0 ? 'text-green-400' : 'text-red-400' }}">
                                    {{ $plan->nav_change_percentage >= 0 ? '+' : '' }}{{ number_format($plan->nav_change_percentage, 2) }}%
                                </p>
                            </div>
                            <div class="flex-1 text-center">
                                <p class="text-tesla-100 dark:text-gray-300">Min Investment</p>
                                <p class="text-white font-medium">{{ currency_symbol() }}{{ number_format($plan->minimum_investment, 0) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Plan Overview -->
                <div class="bg-card rounded-xl p-6 shadow-sm border border-border">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-light text-foreground">Plan Overview</h2>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $plan->risk_level_badge }}">
                            {{ ucfirst($plan->risk_level) }} Risk
                        </span>
                    </div>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div class="text-center">
                            <p class="text-xs text-muted-foreground dark:text-gray-300 mb-1">NAV</p>
                            <p class="text-lg font-medium text-foreground">{{ currency_symbol() }}{{ number_format($plan->nav, 4) }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-xs text-muted-foreground dark:text-gray-300 mb-1">1Y Return</p>
                            <p class="text-lg font-medium {{ $plan->nav_change_percentage >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $plan->nav_change_percentage >= 0 ? '+' : '' }}{{ number_format($plan->nav_change_percentage, 2) }}%
                            </p>
                        </div>
                        <div class="text-center">
                            <p class="text-xs text-muted-foreground dark:text-gray-300 mb-1">Min Investment</p>
                            <p class="text-lg font-medium text-foreground">{{ currency_symbol() }}{{ number_format($plan->minimum_investment, 2) }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-xs text-muted-foreground dark:text-gray-300 mb-1">Management Fee</p>
                            <p class="text-lg font-medium text-foreground">{{ number_format($plan->management_fee * 100, 2) }}%</p>
                        </div>
                    </div>
                    
                    <div class="prose prose-sm max-w-none">
                        <p class="text-muted-foreground">{{ $plan->description }}</p>
                    </div>
                </div>

                <!-- Performance Chart -->
                <div class="bg-card rounded-xl p-6 shadow-sm border border-border">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-light text-foreground">Performance</h2>
                        <div class="flex space-x-2">
                            <button data-period="1y" class="px-3 py-1 text-xs font-medium rounded bg-foreground text-background">1Y</button>
                            <button data-period="3y" class="px-3 py-1 text-xs font-medium rounded bg-muted text-muted-foreground hover:bg-muted">3Y</button>
                            <button data-period="5y" class="px-3 py-1 text-xs font-medium rounded bg-muted text-muted-foreground hover:bg-muted">5Y</button>
                        </div>
                    </div>
                    
                    <!-- Investment Performance Chart -->
                    <div class="relative">
                        <canvas id="investmentChart" class="w-full h-64"></canvas>
                    </div>
                </div>

                <!-- Holdings Breakdown -->
                <div class="bg-card rounded-xl p-6 shadow-sm border border-border">
                    <h2 class="text-lg font-light text-foreground mb-4">Holdings Breakdown</h2>
                    
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 bg-muted rounded-lg">
                            <div class="flex items-center">
                                <div class="w-8 h-8 flex items-center justify-center mr-3">
                                    <i data-lucide="building" class="w-4 h-4 text-tesla-500"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-foreground">Technology</p>
                                    <p class="text-xs text-muted-foreground dark:text-gray-300">Tesla, Apple, Microsoft</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-foreground">45.2%</p>
                                <p class="text-xs text-muted-foreground dark:text-gray-300">{{ currency_symbol() }}2.3M</p>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between p-3 bg-muted rounded-lg">
                            <div class="flex items-center">
                                <div class="w-8 h-8 flex items-center justify-center mr-3">
                                    <i data-lucide="zap" class="w-4 h-4 text-green-500"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-foreground">Energy</p>
                                    <p class="text-xs text-muted-foreground dark:text-gray-300">Renewable energy companies</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-foreground">28.7%</p>
                                <p class="text-xs text-muted-foreground dark:text-gray-300">{{ currency_symbol() }}1.5M</p>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between p-3 bg-muted rounded-lg">
                            <div class="flex items-center">
                                <div class="w-8 h-8 flex items-center justify-center mr-3">
                                    <i data-lucide="car" class="w-4 h-4 text-purple-500"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-foreground">Automotive</p>
                                    <p class="text-xs text-muted-foreground dark:text-gray-300">EV manufacturers</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-foreground">26.1%</p>
                                <p class="text-xs text-muted-foreground dark:text-gray-300">{{ currency_symbol() }}1.3M</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Investment Actions -->
                <div class="bg-card rounded-xl p-6 shadow-sm border border-border">
                    <h3 class="text-lg font-light text-foreground mb-4">Invest Now</h3>
                    
                    @if($userHolding)
                        <div class="mb-4 p-3 bg-tesla-50 rounded-lg border border-tesla-200">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-medium text-tesla-800">Your Holdings</span>
                                <div class="w-6 h-6 flex items-center justify-center">
                                    <i data-lucide="pie-chart" class="w-4 h-4 text-tesla-600"></i>
                                </div>
                            </div>
                            <div class="space-y-1">
                                <div class="flex justify-between">
                                    <span class="text-xs text-tesla-700">Units:</span>
                                    <span class="text-xs font-medium text-tesla-800">{{ number_format($userHolding->units, 4) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-xs text-tesla-700">Value:</span>
                                    <span class="text-xs font-medium text-tesla-800">{{ currency_symbol() }}{{ number_format($userHolding->units * $plan->nav, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    <div class="space-y-3">
                        <a href="{{ route('investments.buy', $plan) }}" class="w-full px-4 py-3 bg-foreground text-background text-sm font-medium rounded-lg hover:opacity-90 transition-colors duration-200 flex items-center justify-center">
                            <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                            Buy More
                        </a>
                        
                        @if($userHolding && $userHolding->units > 0)
                            <a href="{{ route('investments.sell', $plan) }}" class="w-full px-4 py-3 border border-border text-foreground text-sm font-medium rounded-lg hover:bg-muted/30 transition-colors duration-200 flex items-center justify-center">
                                <i data-lucide="minus" class="w-4 h-4 mr-2"></i>
                                Sell Units
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Plan Details -->
                <div class="bg-card rounded-xl p-6 shadow-sm border border-border">
                    <h3 class="text-lg font-light text-foreground mb-4">Plan Details</h3>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-xs text-muted-foreground dark:text-gray-300">Type:</span>
                            <span class="text-xs font-medium text-foreground">{{ ucfirst($plan->type) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-xs text-muted-foreground dark:text-gray-300">Category:</span>
                                                            <span class="text-xs font-medium text-foreground">{{ $plan->category }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-xs text-muted-foreground dark:text-gray-300">Risk Level:</span>
                            <span class="text-xs font-medium text-foreground">{{ ucfirst($plan->risk_level) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-xs text-muted-foreground dark:text-gray-300">Expense Ratio:</span>
                            <span class="text-xs font-medium text-foreground">{{ number_format($plan->expense_ratio * 100, 2) }}%</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-xs text-muted-foreground dark:text-gray-300">Total Assets:</span>
                            <span class="text-xs font-medium text-foreground">{{ $plan->total_assets ? '$' . number_format($plan->total_assets, 0) : 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-xs text-muted-foreground dark:text-gray-300">Inception:</span>
                            <span class="text-xs font-medium text-foreground">{{ $plan->inception_date ? $plan->inception_date->format('M Y') : 'N/A' }}</span>
                        </div>
                        @if($plan->dividend_yield)
                        <div class="flex justify-between">
                            <span class="text-xs text-muted-foreground dark:text-gray-300">Dividend Yield:</span>
                            <span class="text-xs font-medium text-foreground">{{ number_format($plan->dividend_yield, 2) }}%</span>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="bg-card rounded-xl p-6 shadow-sm border border-border">
                    <h3 class="text-lg font-light text-foreground mb-4">Recent Activity</h3>
                    
                    @if($recentTransactions->count() > 0)
                        <div class="space-y-3">
                            @foreach($recentTransactions->take(5) as $transaction)
                            <div class="flex items-center space-x-3 p-2 bg-muted rounded-lg">
                                <div class="w-8 h-8 flex items-center justify-center">
                                    @if($transaction->type === 'buy')
                                        <i data-lucide="plus" class="w-4 h-4 text-green-500"></i>
                                    @else
                                        <i data-lucide="minus" class="w-4 h-4 text-red-500"></i>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-medium text-foreground">{{ ucfirst($transaction->type) }}</p>
                                    <p class="text-xs text-muted-foreground dark:text-gray-300">{{ $transaction->created_at->format('M d, Y') }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs font-medium text-foreground">{{ currency_symbol() }}{{ number_format($transaction->total_amount, 2) }}</p>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <div class="w-8 h-8 bg-muted rounded-full flex items-center justify-center mx-auto mb-2">
                                <i data-lucide="activity" class="w-4 h-4 text-gray-400 dark:text-gray-300"></i>
                            </div>
                            <p class="text-xs text-muted-foreground dark:text-gray-300">No recent activity</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        // Initialize investment performance chart
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('investmentChart');
            if (ctx) {
                // Generate sample investment data for demonstration
                const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                const nav = {{ $plan->nav ?? 10.00 }};
                
                const investmentData = [
                    nav * 0.95, // Start with slightly lower NAV
                    nav * 0.98,
                    nav * 1.02,
                    nav * 1.05,
                    nav * 1.08,
                    nav * 1.12,
                    nav * 1.15,
                    nav * 1.18,
                    nav * 1.22,
                    nav * 1.25,
                    nav * 1.28,
                    nav // End with current NAV
                ];

                const investmentChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: '{{ $plan->name }} NAV',
                            data: investmentData,
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
                                        return '$' + context.parsed.y.toFixed(2);
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
                                        return '$' + value.toFixed(2);
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
                            btn.classList.remove('bg-black', 'text-white');
                            btn.classList.add('bg-muted', 'text-muted-foreground');
                        });
                        this.classList.remove('bg-muted', 'text-muted-foreground');
                        this.classList.add('bg-black', 'text-white');
                    });
                });

                function updateChartPeriod(period) {
                    // For demo purposes, we'll just update the chart with different data ranges
                    // In a real implementation, this would fetch data from the server
                    const periods = {
                        '1y': investmentData,
                        '3y': investmentData.map(val => val * 1.1), // Simulate 3Y data
                        '5y': investmentData.map(val => val * 1.2)  // Simulate 5Y data
                    };
                    
                    const periodLabels = {
                        '1y': labels,
                        '3y': labels,
                        '5y': labels
                    };

                    investmentChart.data.labels = periodLabels[period];
                    investmentChart.data.datasets[0].data = periods[period];
                    investmentChart.update();
                }
            }
        });
    </script>
</x-user-layout> 
