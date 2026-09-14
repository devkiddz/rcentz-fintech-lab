<x-user-layout>
    <x-slot name="header">
        Dashboard
    </x-slot>

    <div class="max-w-7xl mx-auto">
        <!-- Enhanced Welcome Section -->
        <div class="bg-gradient-to-br from-tesla-600 via-tesla-700 to-tesla-800 dark:from-tesla-700 dark:via-tesla-800 dark:to-tesla-900 rounded-2xl p-6 mb-6 text-white relative overflow-hidden transition-colors duration-200">
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-0 right-0 w-48 h-48 bg-white rounded-full -translate-y-24 translate-x-24"></div>
                <div class="absolute bottom-0 left-0 w-24 h-24 bg-white rounded-full translate-y-12 -translate-x-12"></div>
            </div>
            
            <div class="relative z-10">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                    <div class="mb-4 lg:mb-0 lg:flex-1">
                        <h1 class="text-xl font-light mb-1">Welcome back, {{ auth()->user()->name }}</h1>
                        <p class="text-tesla-100 dark:text-gray-300 text-sm">Track your investments, manage your portfolio, and explore opportunities.</p>
                    </div>
                    
                    <!-- Enhanced Wallet Card -->
                    <div class="bg-white bg-opacity-15 dark:bg-opacity-20 backdrop-blur-xl rounded-xl p-4 border border-white border-opacity-20 shadow-xl lg:w-80">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <p class="text-xs text-tesla-100 dark:text-gray-300 mb-1">Available Balance</p>
                                <p class="text-xl font-light">{{ format_currency(auth()->user()->wallet->balance ?? 0) }}</p>
                            </div>
                            <div class="w-10 h-10 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                                <i data-lucide="wallet" class="w-5 h-5"></i>
                            </div>
                        </div>
                        <div class="flex space-x-2">
                            <a href="{{ route('wallet.deposit') }}" class="flex-1 bg-white bg-opacity-20 hover:bg-opacity-30 text-white text-xs font-medium py-1.5 px-3 rounded-md transition-all duration-200">
                                <i data-lucide="plus" class="w-3 h-3 inline mr-1"></i>
                                Deposit
                            </a>
                            <a href="{{ route('wallet.withdraw') }}" class="flex-1 bg-white bg-opacity-20 hover:bg-opacity-30 text-white text-xs font-medium py-1.5 px-3 rounded-md transition-all duration-200">
                                <i data-lucide="minus" class="w-3 h-3 inline mr-1"></i>
                                Withdraw
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Portfolio Overview Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- Total Portfolio Value -->
            <div class="bg-card rounded-xl p-4 shadow-sm border border-border hover:shadow-lg transition-all duration-300 group">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-300 mb-1">Portfolio Value</p>
                        <p class="text-lg font-light text-black dark:text-white mb-1">{{ format_currency($totalPortfolioValue ?? 0) }}</p>
                        <p class="text-xs {{ $monthlyPerformance['is_positive'] ?? false ? 'text-green-600 dark:text-green-300' : 'text-red-600 dark:text-red-300' }}">
                            {{ $monthlyPerformance['is_positive'] ?? false ? '+' : '' }}{{ number_format($monthlyPerformance['percentage_change'] ?? 0, 1) }}% this month
                        </p>
                    </div>
                    <div class="flex flex-col items-end space-y-1">
                        <div class="w-10 h-10 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                            <i data-lucide="trending-up" class="w-5 h-5 text-green-500 dark:text-green-300"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Investment Holdings -->
            <div class="bg-card rounded-xl p-4 shadow-sm border border-border hover:shadow-lg transition-all duration-300 group">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-300 mb-1">Investments</p>
                        <p class="text-lg font-light text-black dark:text-white mb-1">{{ format_currency($totalInvestmentValue ?? 0) }}</p>
                        <p class="text-xs text-tesla-600 dark:text-gray-300">{{ $investmentHoldings->count() ?? 0 }} active investments</p>
                    </div>
                    <div class="flex flex-col items-end space-y-1">
                        <div class="w-10 h-10 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                            <i data-lucide="pie-chart" class="w-5 h-5 text-tesla-500 dark:text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stock Holdings -->
            <div class="bg-card rounded-xl p-4 shadow-sm border border-border hover:shadow-lg transition-all duration-300 group">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-300 mb-1">Stock Holdings</p>
                        <p class="text-lg font-light text-black dark:text-white mb-1">{{ format_currency($totalStockValue ?? 0) }}</p>
                        <p class="text-xs text-purple-600 dark:text-purple-200">{{ $stockHoldings->count() ?? 0 }} stock positions</p>
                    </div>
                    <div class="flex flex-col items-end space-y-1">
                        <div class="w-10 h-10 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                            <i data-lucide="bar-chart-3" class="w-5 h-5 text-purple-500 dark:text-purple-200"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Orders -->
            <div class="bg-card rounded-xl p-4 shadow-sm border border-border hover:shadow-lg transition-all duration-300 group">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-300 mb-1">Tesla Vehicles</p>
                        <p class="text-lg font-medium text-black dark:text-white mb-1">{{ $totalPurchases->count() ?? 0 }}</p>
                        <p class="text-xs text-red-600 dark:text-red-300">Electric fleet</p>
                    </div>
                    <div class="flex flex-col items-end space-y-1">
                        <div class="w-10 h-10 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                            <i data-lucide="car" class="w-5 h-5 text-red-500 dark:text-red-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Quick Actions Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
            <!-- Browse Cars -->
            <a href="{{ route('cars.browse') }}" class="group bg-card rounded-xl p-4 shadow-sm border border-border hover:shadow-lg transition-all duration-300 hover:scale-105 relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-tesla-50 to-tesla-100 dark:from-tesla-600/20 dark:to-tesla-500/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                <div class="relative z-10">
                    <div class="w-10 h-10 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform duration-300">
                        <i data-lucide="car" class="w-5 h-5 text-tesla-500 dark:text-gray-300"></i>
                    </div>
                    <h3 class="font-medium text-black dark:text-white text-sm mb-1">Browse Cars</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-300 mb-2">Explore our inventory</p>
                    <div class="flex items-center text-tesla-600 dark:text-gray-300 text-xs font-medium">
                        <span>View Inventory</span>
                        <i data-lucide="arrow-right" class="w-3 h-3 ml-1 group-hover:translate-x-1 transition-transform duration-200"></i>
                    </div>
                </div>
            </a>

            <!-- Investments -->
            <a href="{{ route('investments.index') }}" class="group bg-card rounded-xl p-4 shadow-sm border border-border hover:shadow-lg transition-all duration-300 hover:scale-105 relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-600/20 dark:to-green-500/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                <div class="relative z-10">
                    <div class="w-10 h-10 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform duration-300">
                        <i data-lucide="trending-up" class="w-5 h-5 text-green-500 dark:text-green-300"></i>
                    </div>
                    <h3 class="font-medium text-black dark:text-white text-sm mb-1">Investments</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-300 mb-2">Grow your wealth</p>
                    <div class="flex items-center text-green-600 dark:text-green-300 text-xs font-medium">
                        <span>Start Investing</span>
                        <i data-lucide="arrow-right" class="w-3 h-3 ml-1 group-hover:translate-x-1 transition-transform duration-200"></i>
                    </div>
                </div>
            </a>

            <!-- Stocks -->
            <a href="{{ route('stocks.index') }}" class="group bg-card rounded-xl p-4 shadow-sm border border-border hover:shadow-lg transition-all duration-300 hover:scale-105 relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-600/20 dark:to-purple-500/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                <div class="relative z-10">
                    <div class="w-10 h-10 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform duration-300">
                        <i data-lucide="bar-chart-3" class="w-5 h-5 text-purple-500 dark:text-purple-200"></i>
                    </div>
                    <h3 class="font-medium text-black dark:text-white text-sm mb-1">Stocks</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-300 mb-2">Trade individual stocks</p>
                    <div class="flex items-center text-purple-600 dark:text-purple-200 text-xs font-medium">
                        <span>Trade Stocks</span>
                        <i data-lucide="arrow-right" class="w-3 h-3 ml-1 group-hover:translate-x-1 transition-transform duration-200"></i>
                    </div>
                </div>
            </a>

            <!-- Portfolio -->
            <a href="{{ route('portfolio.index') }}" class="group bg-card rounded-xl p-4 shadow-sm border border-border hover:shadow-lg transition-all duration-300 hover:scale-105 relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-600/20 dark:to-orange-500/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                <div class="relative z-10">
                    <div class="w-10 h-10 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform duration-300">
                        <i data-lucide="pie-chart" class="w-5 h-5 text-orange-500 dark:text-orange-200"></i>
                    </div>
                    <h3 class="font-medium text-black dark:text-white text-sm mb-1">Portfolio</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-300 mb-2">View your holdings</p>
                    <div class="flex items-center text-orange-600 dark:text-orange-200 text-xs font-medium">
                        <span>View Portfolio</span>
                        <i data-lucide="arrow-right" class="w-3 h-3 ml-1 group-hover:translate-x-1 transition-transform duration-200"></i>
                    </div>
                </div>
            </a>
        </div>

        <!-- Enhanced Activity Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
            <!-- Recent Orders -->
            <div class="bg-card rounded-xl p-5 shadow-sm border border-border lg:col-span-2">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-light text-black dark:text-white mb-1">Recent Orders</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-300">Your latest Tesla purchases</p>
                    </div>
                    <a href="{{ route('dashboard.history') }}" class="text-xs text-black dark:text-gray-300 hover:text-gray-600 dark:hover:text-white transition-colors duration-200 font-medium">
                        View All
                        <i data-lucide="arrow-right" class="w-3 h-3 inline ml-1"></i>
                    </a>
                </div>
                
                @if($recentPurchases->count() > 0)
                    <div class="space-y-3">
                        @foreach($recentPurchases->take(3) as $purchase)
                        <div class="flex items-center space-x-3 p-3 bg-muted rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200 group">
                            <div class="w-10 h-10 bg-gray-200 dark:bg-tesla-500 rounded-lg overflow-hidden flex-shrink-0">
                                @if($purchase->car->first_image)
                                    <img src="{{ strpos($purchase->car->first_image, 'http') === 0 ? $purchase->car->first_image : asset('storage/' . $purchase->car->first_image) }}" 
                                         alt="{{ $purchase->car->title }}" 
                                         class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full bg-gray-300 dark:bg-tesla-400 flex items-center justify-center">
                                        <i data-lucide="car" class="w-5 h-5 text-gray-400 dark:text-gray-300"></i>
                                    </div>
                                @endif
                            </div>
                            
                            <div class="flex-1 min-w-0">
                                <h4 class="font-medium text-black dark:text-white text-sm truncate">{{ $purchase->car->title }}</h4>
                                <p class="text-xs text-gray-600 dark:text-gray-300">{{ $purchase->car->year }} {{ $purchase->car->make }} {{ $purchase->car->model }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-300">{{ $purchase->purchased_at->format('M d, Y') }}</p>
                            </div>
                            
                            <div class="text-right">
                                <p class="font-medium text-black dark:text-white text-sm">{{ $purchase->formatted_amount }}</p>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $purchase->status_badge }}">
                                    {{ ucfirst($purchase->status) }}
                                </span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-6">
                        <div class="w-12 h-12 bg-muted rounded-full flex items-center justify-center mx-auto mb-3">
                            <i data-lucide="car" class="w-6 h-6 text-gray-400 dark:text-gray-300"></i>
                        </div>
                        <h3 class="text-base font-light text-black dark:text-white mb-1">No orders yet</h3>
                        <p class="text-gray-600 dark:text-gray-300 text-xs mb-3">Start browsing our collection of electric vehicles.</p>
                        <a href="{{ route('cars.browse') }}" class="inline-flex items-center px-4 py-2 bg-black dark:bg-white text-white dark:text-gray-900 text-xs font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-gray-100 transition-colors duration-200">
                            <i data-lucide="car" class="w-3 h-3 mr-1"></i>
                            Browse Inventory
                        </a>
                    </div>
                @endif
            </div>

            <!-- Market Overview -->
            <div class="bg-card rounded-xl p-5 shadow-sm border border-border transition-colors duration-200">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-light text-black dark:text-white mb-1">Market Overview</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-300">Live market data</p>
                    </div>
                    <a href="{{ route('stocks.index') }}" class="text-xs text-black dark:text-gray-300 hover:text-gray-600 dark:hover:text-white transition-colors duration-200 font-medium">
                        View All
                        <i data-lucide="arrow-right" class="w-3 h-3 inline ml-1"></i>
                    </a>
                </div>
                
                <div class="space-y-3">
                    @if(isset($marketOverview['gainers']) && $marketOverview['gainers']->count() > 0)
                        @foreach($marketOverview['gainers'] as $stock)
                        <div class="flex items-center justify-between p-3 bg-gradient-to-r from-green-50 to-green-100 dark:from-green-500/30 dark:to-green-600/30 rounded-lg border border-green-200 dark:border-green-500/50">
                            <div class="flex items-center space-x-2">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center overflow-hidden">
                                    @if($stock->logo_url)
                                        <img src="{{ $stock->logo_url }}" alt="{{ $stock->symbol }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full bg-green-500 dark:bg-green-400 flex items-center justify-center">
                                            <span class="text-white font-bold text-xs">{{ substr($stock->symbol, 0, 1) }}</span>
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    <h4 class="font-medium text-black dark:text-white text-xs">{{ $stock->company_name }}</h4>
                                    <p class="text-xs text-gray-600 dark:text-gray-300">{{ $stock->symbol }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-medium text-black dark:text-white text-xs">{{ format_currency($stock->current_price) }}</p>
                                <p class="text-xs text-green-600 dark:text-green-200 font-medium">+{{ number_format($stock->change_percentage, 2) }}%</p>
                            </div>
                        </div>
                        @endforeach
                    @else
                        <!-- Fallback for when no data is available -->
                        <div class="flex items-center justify-between p-3 bg-gradient-to-r from-green-50 to-green-100 dark:from-green-500/30 dark:to-green-600/30 rounded-lg border border-green-200 dark:border-green-500/50">
                            <div class="flex items-center space-x-2">
                                <div class="w-8 h-8 bg-green-500 dark:bg-green-400 rounded-lg flex items-center justify-center">
                                    <span class="text-white font-bold text-xs">T</span>
                                </div>
                                <div>
                                    <h4 class="font-medium text-black dark:text-white text-xs">Tesla Inc.</h4>
                                    <p class="text-xs text-gray-600 dark:text-gray-300">TSLA</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-medium text-black dark:text-white text-xs">{{ format_currency(245.67) }}</p>
                                <p class="text-xs text-green-600 dark:text-green-200 font-medium">+2.34%</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Enhanced Performance Chart -->
        <div class="bg-card rounded-xl p-5 shadow-sm border border-border mb-6 transition-colors duration-200">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-light text-black dark:text-white mb-1">Stock Market Performance</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-300">Top stocks over the last 30 days</p>
                </div>
            </div>
            
            <!-- Real Chart -->
            <div class="relative">
                <canvas id="stockChart" class="w-full h-64"></canvas>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Chart data from backend
        const chartData = @json($chartData ?? []);

        // Helper function to get theme-appropriate colors
        function getChartColors() {
            const isDark = document.documentElement.classList.contains('dark');
            return {
                gridColor: isDark ? 'rgba(147, 197, 253, 0.1)' : 'rgba(0, 0, 0, 0.05)',
                textColor: isDark ? 'rgba(255, 255, 255, 0.9)' : 'rgba(0, 0, 0, 0.7)',
                tooltipBg: isDark ? 'rgba(29, 78, 216, 0.95)' : 'rgba(0, 0, 0, 0.8)'
            };
        }

        // Initialize chart
        const ctx = document.getElementById('stockChart').getContext('2d');
        const colors = getChartColors();
        const stockChart = new Chart(ctx, {
            type: 'line',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            color: colors.textColor,
                            font: {
                                size: 12,
                                family: 'Inter, system-ui, sans-serif'
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: colors.tooltipBg,
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: 'rgba(255, 255, 255, 0.1)',
                        borderWidth: 1,
                        cornerRadius: 8,
                        displayColors: true,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': $' + context.parsed.y.toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        display: true,
                        grid: {
                            display: false,
                            color: colors.gridColor
                        },
                        ticks: {
                            maxTicksLimit: 8,
                            color: colors.textColor,
                            font: {
                                size: 11,
                                family: 'Inter, system-ui, sans-serif'
                            }
                        }
                    },
                    y: {
                        display: true,
                        position: 'right',
                        grid: {
                            color: colors.gridColor,
                            drawBorder: false
                        },
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toFixed(0);
                            },
                            color: colors.textColor,
                            font: {
                                size: 11,
                                family: 'Inter, system-ui, sans-serif'
                            }
                        }
                    }
                },
                elements: {
                    point: {
                        radius: 0,
                        hoverRadius: 4,
                        hoverBorderWidth: 2
                    }
                }
            }
        });

        // Update chart colors on theme change
        const updateChartTheme = () => {
            const newColors = getChartColors();
            if (stockChart && stockChart.options) {
                stockChart.options.plugins.legend.labels.color = newColors.textColor;
                stockChart.options.plugins.tooltip.backgroundColor = newColors.tooltipBg;
                stockChart.options.scales.x.ticks.color = newColors.textColor;
                stockChart.options.scales.x.grid.color = newColors.gridColor;
                stockChart.options.scales.y.ticks.color = newColors.textColor;
                stockChart.options.scales.y.grid.color = newColors.gridColor;
                stockChart.update();
            }
        };

        // Listen for theme changes
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.attributeName === 'class') {
                    updateChartTheme();
                }
            });
        });
        observer.observe(document.documentElement, { attributes: true });

        // Add some interactive functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Animate numbers on load
            const animateValue = (element, start, end, duration) => {
                let startTimestamp = null;
                const step = (timestamp) => {
                    if (!startTimestamp) startTimestamp = timestamp;
                    const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                    const current = Math.floor(progress * (end - start) + start);
                    element.textContent = '$' + current.toLocaleString();
                    if (progress < 1) {
                        window.requestAnimationFrame(step);
                    }
                };
                window.requestAnimationFrame(step);
            };

            // Animate portfolio values
            const portfolioElements = document.querySelectorAll('.text-lg.font-light');
            portfolioElements.forEach(element => {
                const text = element.textContent;
                const value = parseInt(text.replace(/[^0-9]/g, ''));
                if (value > 0) {
                    animateValue(element, 0, value, 2000);
                }
            });
        });
    </script>
</x-user-layout> 
