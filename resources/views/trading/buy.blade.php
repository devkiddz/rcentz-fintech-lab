<x-user-layout>
    <x-slot name="header">
        Buy {{ $stock->symbol }}
    </x-slot>

    <div class="max-w-4xl mx-auto">
        <!-- Enhanced Header -->
        <div class="bg-gradient-to-br from-tesla-600 via-tesla-700 to-tesla-800 dark:from-tesla-700 dark:via-tesla-800 dark:to-tesla-900 rounded-2xl p-6 mb-6 text-white relative overflow-hidden">
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-0 right-0 w-32 h-32 bg-card rounded-full -translate-y-16 translate-x-16"></div>
                <div class="absolute bottom-0 left-0 w-16 h-16 bg-card rounded-full translate-y-8 -translate-x-8"></div>
            </div>
            
            <div class="relative z-10">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                    <div class="mb-4 lg:mb-0 lg:flex-1">
                        <h1 class="text-xl font-light mb-1">Buy {{ $stock->symbol }}</h1>
                        <p class="text-tesla-100 dark:text-gray-300 text-sm">{{ $stock->company_name }} • {{ $stock->sector }}</p>
                    </div>
                    
                    <!-- Enhanced Stock Stats Card -->
                    <div class="bg-card bg-opacity-15 backdrop-blur-xl rounded-xl p-4 border border-white border-opacity-20 shadow-xl lg:w-64">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-tesla-100 dark:text-gray-300 mb-1">Current Price</p>
                                <p class="text-lg font-light">{{ currency_symbol() }}{{ number_format($stock->current_price, 2) }}</p>
                                <p class="text-xs {{ $stock->change_percentage >= 0 ? 'text-green-400' : 'text-red-400' }}">
                                    {{ $stock->change_percentage >= 0 ? '+' : '' }}{{ number_format($stock->change_percentage, 2) }}%
                                </p>
                            </div>
                            <div class="w-10 h-10 flex items-center justify-center">
                                @if($stock->logo_url)
                                    <img src="{{ $stock->logo_url }}" alt="{{ $stock->symbol }}" class="w-8 h-8 rounded">
                                @else
                                    <i data-lucide="trending-up" class="w-5 h-5 text-white"></i>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stock Chart Widget -->
        <div class="bg-card rounded-xl p-6 shadow-sm border border-gray-100 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">{{ $stock->symbol }} Price Chart</h3>
                <div class="flex space-x-2">
                    <button type="button" class="chart-period-btn px-3 py-1 text-xs rounded-lg border border-border bg-foreground text-background" data-period="1m">1M</button>
                </div>
            </div>
            <div class="relative">
                <canvas id="stockChart" class="w-full h-64"></canvas>
            </div>
        </div>

        <!-- Enhanced Buy Form -->
        <div class="bg-card rounded-xl p-6 shadow-sm border border-border">
            <form action="{{ route('trading.execute-buy', $stock) }}" method="POST" class="space-y-6">
                @csrf
                
                <!-- Quantity Input -->
                <div>
                    <label for="quantity" class="block text-sm font-medium text-foreground mb-2">Number of Shares</label>
                    <div class="relative">
                        <input type="number" 
                               id="quantity" 
                               name="quantity" 
                               step="1" 
                               min="1" 
                               max="10000"
                               value="{{ old('quantity') }}"
                               class="w-full px-4 py-3 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-transparent transition-colors duration-200"
                               placeholder="0"
                               required>
                    </div>
                    <p class="text-xs text-muted-foreground mt-1">Maximum shares: 10,000</p>
                    @error('quantity')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Stock Information -->
                <div class="bg-gradient-to-br from-tesla-50 to-tesla-100 rounded-lg p-4 border border-tesla-200">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-tesla-800">Stock Information</span>
                        <div class="w-8 h-8 flex items-center justify-center">
                            @if($stock->logo_url)
                                <img src="{{ $stock->logo_url }}" alt="{{ $stock->symbol }}" class="w-6 h-6 rounded">
                            @else
                                <i data-lucide="info" class="w-4 h-4 text-tesla-600"></i>
                            @endif
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-tesla-700 mb-1">Current Price</p>
                            <p class="text-sm font-medium text-tesla-800">{{ currency_symbol() }}{{ number_format($stock->current_price, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-tesla-700 mb-1">Today's Change</p>
                            <p class="text-sm font-medium {{ $stock->change_percentage >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $stock->change_percentage >= 0 ? '+' : '' }}{{ number_format($stock->change_percentage, 2) }}%
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-tesla-700 mb-1">Volume</p>
                            <p class="text-sm font-medium text-tesla-800">{{ number_format($stock->volume) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-tesla-700 mb-1">Market Cap</p>
                            <p class="text-sm font-medium text-tesla-800">{{ $stock->market_cap ? '$' . number_format($stock->market_cap) : 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Analyst Recommendations -->
                @php
                    $latestQuote = App\Models\StockQuote::getLatestQuote($stock->symbol);
                @endphp
                @if($latestQuote && $latestQuote->total_recommendations > 0)
                <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-4 border border-purple-200">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-purple-800">Analyst Recommendations</span>
                        <div class="w-8 h-8 flex items-center justify-center">
                            <i data-lucide="users" class="w-4 h-4 text-purple-600"></i>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-purple-700">Consensus</span>
                            <span class="text-sm font-medium {{ $latestQuote->recommendation_color }}">
                                {{ $latestQuote->recommendation_label }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-purple-700">Buy Rating</span>
                            <span class="text-sm font-medium text-purple-800">{{ $latestQuote->recommendation_percentage }}%</span>
                        </div>
                        <div class="w-full bg-purple-200 rounded-full h-2">
                            <div class="bg-purple-600 h-2 rounded-full" style="width: {{ $latestQuote->recommendation_percentage }}%"></div>
                        </div>
                        <div class="grid grid-cols-5 gap-2 text-xs text-purple-700">
                            <div class="text-center">
                                <div class="font-medium">Strong Buy</div>
                                <div>{{ $latestQuote->strong_buy }}</div>
                            </div>
                            <div class="text-center">
                                <div class="font-medium">Buy</div>
                                <div>{{ $latestQuote->buy }}</div>
                            </div>
                            <div class="text-center">
                                <div class="font-medium">Hold</div>
                                <div>{{ $latestQuote->hold }}</div>
                            </div>
                            <div class="text-center">
                                <div class="font-medium">Sell</div>
                                <div>{{ $latestQuote->sell }}</div>
                            </div>
                            <div class="text-center">
                                <div class="font-medium">Strong Sell</div>
                                <div>{{ $latestQuote->strong_sell }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Enhanced Calculation Breakdown -->
                <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4 border border-green-200">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-green-800">Purchase Breakdown</span>
                        <div class="w-8 h-8 flex items-center justify-center">
                            <i data-lucide="calculator" class="w-4 h-4 text-green-600"></i>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-sm text-green-700">Price per Share</span>
                            <span class="text-sm font-medium text-green-800">{{ currency_symbol() }}{{ number_format($stock->current_price, 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-green-700">Number of Shares</span>
                            <span class="text-sm font-medium text-green-800" id="shares-display">0</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-green-700">Subtotal</span>
                            <span class="text-sm font-medium text-green-800" id="subtotal-display">{{ currency_symbol() }}0.00</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-green-700">Trading Fee</span>
                            <span class="text-sm font-medium text-green-800">{{ currency_symbol() }}0.00</span>
                        </div>
                        <hr class="border-green-300">
                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-green-800">Total Cost</span>
                            <span class="text-sm font-bold text-green-800" id="total-display">{{ currency_symbol() }}0.00</span>
                        </div>
                    </div>
                </div>

                <!-- Wallet Balance -->
                <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-lg p-4 border border-border">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-foreground">Wallet Balance</span>
                        <div class="w-8 h-8 flex items-center justify-center">
                            <i data-lucide="wallet" class="w-4 h-4 text-muted-foreground"></i>
                        </div>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-foreground">Available Funds</span>
                        <span class="text-sm font-medium text-foreground">{{ currency_symbol() }}{{ number_format($wallet->balance, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center mt-1">
                        <span class="text-sm text-foreground">After Purchase</span>
                        <span class="text-sm font-medium text-foreground" id="remaining-display">{{ currency_symbol() }}{{ number_format($wallet->balance, 2) }}</span>
                    </div>
                </div>

                <!-- Current Holdings -->
                @if($userHolding)
                <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-lg p-4 border border-orange-200">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-orange-800">Current Holdings</span>
                        <div class="w-8 h-8 flex items-center justify-center">
                            <i data-lucide="pie-chart" class="w-4 h-4 text-orange-600"></i>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-orange-700 mb-1">Shares Owned</p>
                            <p class="text-sm font-medium text-orange-800">{{ number_format($userHolding->quantity) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-orange-700 mb-1">Average Price</p>
                            <p class="text-sm font-medium text-orange-800">{{ currency_symbol() }}{{ number_format($userHolding->average_buy_price, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-orange-700 mb-1">Total Value</p>
                            <p class="text-sm font-medium text-orange-800">{{ currency_symbol() }}{{ number_format($userHolding->current_value, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-orange-700 mb-1">Gain/Loss</p>
                            <p class="text-sm font-medium {{ $userHolding->unrealized_gain_loss >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $userHolding->unrealized_gain_loss >= 0 ? '+' : '' }}{{ currency_symbol() }}{{ number_format($userHolding->unrealized_gain_loss, 2) }}
                            </p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Submit Button -->
                <button type="submit" 
                        class="w-full bg-foreground text-background py-3 px-6 rounded-lg font-medium hover:opacity-90 transition-colors duration-200 flex items-center justify-center">
                    <i data-lucide="shopping-cart" class="w-4 h-4 mr-2"></i>
                    Buy Shares
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize stock chart
            const chartData = @json($chartData ?? []);
            const ctx = document.getElementById('stockChart').getContext('2d');
            let stockChart = new Chart(ctx, {
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
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: 'white',
                            bodyColor: 'white',
                            borderColor: 'rgba(255, 255, 255, 0.1)',
                            borderWidth: 1,
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
                                color: '#6B7280',
                                font: {
                                    size: 10
                                }
                            }
                        },
                        y: {
                            display: true,
                            position: 'right',
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                color: '#6B7280',
                                font: {
                                    size: 10
                                },
                                callback: function(value) {
                                    return '$' + value.toFixed(2);
                                }
                            }
                        }
                    },
                    elements: {
                        point: {
                            radius: 0,
                            hoverRadius: 4
                        },
                        line: {
                            borderWidth: 2,
                            tension: 0.4
                        }
                    }
                }
            });

            // Chart period button (only 1M available)
            const periodButton = document.querySelector('.chart-period-btn');
            if (periodButton) {
                periodButton.addEventListener('click', function() {
                    // Fetch chart data for 1M period
                    fetchChartData('1m');
                });
            }

            // Function to fetch chart data
            function fetchChartData(period) {
                const symbol = '{{ $stock->symbol }}';
                
                // Show loading state
                const canvas = document.getElementById('stockChart');
                const ctx = canvas.getContext('2d');
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                
                // Fetch data from server
                fetch(`/api/stocks/${symbol}/chart-data?period=${period}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.chartData) {
                            // Update chart with new data
                            stockChart.data = data.chartData;
                            stockChart.update();
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching chart data:', error);
                    });
            }

            // Original calculation logic
            const quantityInput = document.getElementById('quantity');
            const sharesDisplay = document.getElementById('shares-display');
            const subtotalDisplay = document.getElementById('subtotal-display');
            const totalDisplay = document.getElementById('total-display');
            const remainingDisplay = document.getElementById('remaining-display');
            
            const currentPrice = {{ $stock->current_price }};
            const walletBalance = {{ $wallet->balance }};
            
            function updateCalculations() {
                const quantity = parseInt(quantityInput.value) || 0;
                const subtotal = quantity * currentPrice;
                const fee = 0; // No trading fee for now
                const total = subtotal + fee;
                const remaining = walletBalance - total;
                
                sharesDisplay.textContent = quantity.toLocaleString();
                subtotalDisplay.textContent = '$' + subtotal.toFixed(2);
                totalDisplay.textContent = '$' + total.toFixed(2);
                remainingDisplay.textContent = '$' + remaining.toFixed(2);
                
                // Update button state
                const submitButton = document.querySelector('button[type="submit"]');
                if (quantity <= 0 || total > walletBalance) {
                    submitButton.disabled = true;
                    submitButton.classList.add('opacity-50', 'cursor-not-allowed');
                } else {
                    submitButton.disabled = false;
                    submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            }
            
            quantityInput.addEventListener('input', updateCalculations);
            updateCalculations();
        });
    </script>
</x-user-layout>
