<x-user-layout>
    <x-slot name="header">
        Sell {{ $stock->symbol }}
    </x-slot>

    <div class="max-w-4xl mx-auto">
        <!-- Enhanced Header -->
        <div class="bg-gradient-to-br from-tesla-600 via-tesla-700 to-tesla-800 dark:from-tesla-700 dark:via-tesla-800 dark:to-tesla-900 rounded-2xl p-6 mb-6 text-white relative overflow-hidden">
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-0 right-0 w-32 h-32 bg-white rounded-full -translate-y-16 translate-x-16"></div>
                <div class="absolute bottom-0 left-0 w-16 h-16 bg-white rounded-full translate-y-8 -translate-x-8"></div>
            </div>
            
            <div class="relative z-10">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                    <div class="mb-4 lg:mb-0 lg:flex-1">
                        <h1 class="text-xl font-light mb-1">Sell {{ $stock->symbol }}</h1>
                        <p class="text-tesla-100 dark:text-gray-300 text-sm">{{ $stock->company_name }} • {{ $stock->sector }}</p>
                    </div>
                    
                    <!-- Enhanced Stock Stats Card -->
                    <div class="bg-white bg-opacity-15 backdrop-blur-xl rounded-xl p-4 border border-white border-opacity-20 shadow-xl lg:w-64">
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
                                    <i data-lucide="trending-down" class="w-5 h-5 text-white"></i>
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
                    <button type="button" class="chart-period-btn px-3 py-1 text-xs rounded-lg border border-gray-300 text-gray-600 dark:text-gray-300" data-period="1d">1D</button>
                    <button type="button" class="chart-period-btn px-3 py-1 text-xs rounded-lg border border-gray-300 text-gray-600 dark:text-gray-300" data-period="1w">1W</button>
                    <button type="button" class="chart-period-btn px-3 py-1 text-xs rounded-lg border border-gray-300 bg-black dark:bg-white text-white dark:text-gray-900" data-period="1m">1M</button>
                  </div>
            </div>
            <div class="relative">
                <canvas id="stockChart" class="w-full h-64"></canvas>
            </div>
        </div>

        <!-- Enhanced Sell Form -->
        <div class="bg-card rounded-xl p-6 shadow-sm border border-border">
            <form action="{{ route('trading.execute-sell', $stock) }}" method="POST" class="space-y-6">
                @csrf
                
                <!-- Sell Options -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">Sell Options</label>
                    <div class="space-y-3">
                        <label class="flex items-center p-4 border border-border rounded-lg cursor-pointer hover:border-gray-300 hover:bg-gray-50 transition-all duration-200 group">
                            <input type="radio" 
                                   name="sell_type" 
                                   value="quantity" 
                                   class="w-4 h-4 text-black border-gray-300 focus:ring-black"
                                   {{ old('sell_type', 'quantity') == 'quantity' ? 'checked' : '' }}
                                   required>
                            <div class="ml-3 flex items-center flex-1">
                                <div class="w-8 h-8 flex items-center justify-center mr-3">
                                    <i data-lucide="hash" class="w-4 h-4 text-gray-500 dark:text-gray-300"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium text-black dark:text-white text-sm">Sell by Number of Shares</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-300">Specify the exact number of shares to sell</p>
                                </div>
                                <div class="w-6 h-6 flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                                    <i data-lucide="arrow-right" class="w-4 h-4 text-gray-400 group-hover:text-gray-600 dark:text-gray-300"></i>
                                </div>
                            </div>
                        </label>

                        <label class="flex items-center p-4 border border-border rounded-lg cursor-pointer hover:border-gray-300 hover:bg-gray-50 transition-all duration-200 group">
                            <input type="radio" 
                                   name="sell_type" 
                                   value="amount" 
                                   class="w-4 h-4 text-black border-gray-300 focus:ring-black"
                                   {{ old('sell_type') == 'amount' ? 'checked' : '' }}
                                   required>
                            <div class="ml-3 flex items-center flex-1">
                                <div class="w-8 h-8 flex items-center justify-center mr-3">
                                    <i data-lucide="dollar-sign" class="w-4 h-4 text-gray-500 dark:text-gray-300"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium text-black dark:text-white text-sm">Sell by Dollar Amount</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-300">Specify the dollar amount you want to receive</p>
                                </div>
                                <div class="w-6 h-6 flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                                    <i data-lucide="arrow-right" class="w-4 h-4 text-gray-400 group-hover:text-gray-600 dark:text-gray-300"></i>
                                </div>
                            </div>
                        </label>

                        <label class="flex items-center p-4 border border-border rounded-lg cursor-pointer hover:border-gray-300 hover:bg-gray-50 transition-all duration-200 group">
                            <input type="radio" 
                                   name="sell_type" 
                                   value="all" 
                                   class="w-4 h-4 text-black border-gray-300 focus:ring-black"
                                   {{ old('sell_type') == 'all' ? 'checked' : '' }}
                                   required>
                            <div class="ml-3 flex items-center flex-1">
                                <div class="w-8 h-8 flex items-center justify-center mr-3">
                                    <i data-lucide="trash-2" class="w-4 h-4 text-gray-500 dark:text-gray-300"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium text-black dark:text-white text-sm">Sell All Shares</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-300">Sell your entire position in this stock</p>
                                </div>
                                <div class="w-6 h-6 flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                                    <i data-lucide="arrow-right" class="w-4 h-4 text-gray-400 group-hover:text-gray-600 dark:text-gray-300"></i>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Quantity/Amount Input -->
                <div id="quantity-input" class="sell-input-section">
                    <label for="quantity" class="block text-sm font-medium text-gray-700 mb-2">Number of Shares</label>
                    <div class="relative">
                        <input type="number" 
                               id="quantity" 
                               name="quantity" 
                               step="1" 
                               min="1" 
                               max="{{ $holding->quantity }}"
                               value="{{ old('quantity') }}"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition-colors duration-200"
                               placeholder="0">
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Maximum shares: {{ number_format($holding->quantity) }}</p>
                    @error('quantity')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div id="amount-input" class="sell-input-section hidden">
                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">Dollar Amount</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-300">{{ currency_symbol() }}</span>
                        <input type="number" 
                               id="amount" 
                               name="amount" 
                               step="0.01" 
                               min="0.01" 
                               max="{{ $holding->current_value }}"
                               value="{{ old('amount') }}"
                               class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition-colors duration-200"
                               placeholder="0.00">
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Maximum amount: {{ currency_symbol() }}{{ number_format($holding->current_value, 2) }}</p>
                    @error('amount')
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
                <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-lg p-4 border border-red-200">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-red-800">Sale Breakdown</span>
                        <div class="w-8 h-8 flex items-center justify-center">
                            <i data-lucide="calculator" class="w-4 h-4 text-red-600"></i>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-sm text-red-700">Price per Share</span>
                            <span class="text-sm font-medium text-red-800">{{ currency_symbol() }}{{ number_format($stock->current_price, 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-red-700">Shares to Sell</span>
                            <span class="text-sm font-medium text-red-800" id="shares-display">0</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-red-700">Gross Proceeds</span>
                            <span class="text-sm font-medium text-red-800" id="gross-display">{{ currency_symbol() }}0.00</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-red-700">Trading Fee</span>
                            <span class="text-sm font-medium text-red-800">{{ currency_symbol() }}0.00</span>
                        </div>
                        <hr class="border-red-300">
                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-red-800">Net Proceeds</span>
                            <span class="text-sm font-bold text-red-800" id="net-display">{{ currency_symbol() }}0.00</span>
                        </div>
                    </div>
                </div>

                <!-- Current Holdings -->
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
                            <p class="text-sm font-medium text-orange-800">{{ number_format($holding->quantity) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-orange-700 mb-1">Average Price</p>
                            <p class="text-sm font-medium text-orange-800">{{ currency_symbol() }}{{ number_format($holding->average_buy_price, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-orange-700 mb-1">Total Value</p>
                            <p class="text-sm font-medium text-orange-800">{{ currency_symbol() }}{{ number_format($holding->current_value, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-orange-700 mb-1">Gain/Loss</p>
                            <p class="text-sm font-medium {{ $holding->unrealized_gain_loss >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $holding->unrealized_gain_loss >= 0 ? '+' : '' }}{{ currency_symbol() }}{{ number_format($holding->unrealized_gain_loss, 2) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" 
                        class="w-full bg-red-600 text-white py-3 px-6 rounded-lg font-medium hover:bg-red-700 transition-colors duration-200 flex items-center justify-center">
                    <i data-lucide="trending-down" class="w-4 h-4 mr-2"></i>
                    Sell Shares
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
            const periodButtons = document.querySelectorAll('.chart-period-btn');
            periodButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all buttons
                    periodButtons.forEach(btn => {
                        btn.classList.remove('active', 'bg-black', 'text-white');
                        btn.classList.add('text-gray-600');
                    });
                    
                    // Add active class to clicked button
                    this.classList.add('active', 'bg-black', 'text-white');
                    this.classList.remove('text-gray-600');
                    
                    // Fetch new data for the selected period
                    const period = this.dataset.period;
                    fetchChartData(period);
                });
            });

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

            // Sell type radio buttons
            const sellTypeRadios = document.querySelectorAll('input[name="sell_type"]');
            const quantityInput = document.getElementById('quantity');
            const amountInput = document.getElementById('amount');
            const sharesDisplay = document.getElementById('shares-display');
            const grossDisplay = document.getElementById('gross-display');
            const netDisplay = document.getElementById('net-display');
            
            const currentPrice = {{ $stock->current_price }};
            const totalShares = {{ $holding->quantity }};
            const totalValue = {{ $holding->current_value }};
            
            function updateCalculations() {
                const sellType = document.querySelector('input[name="sell_type"]:checked').value;
                let sharesToSell = 0;
                let grossProceeds = 0;
                
                if (sellType === 'quantity') {
                    sharesToSell = parseInt(quantityInput.value) || 0;
                    grossProceeds = sharesToSell * currentPrice;
                } else if (sellType === 'amount') {
                    const amount = parseFloat(amountInput.value) || 0;
                    sharesToSell = Math.floor(amount / currentPrice);
                    if (sharesToSell > totalShares) sharesToSell = totalShares;
                    // Keep quantity in sync for validation
                    quantityInput.value = sharesToSell > 0 ? sharesToSell : '';
                    grossProceeds = sharesToSell * currentPrice;
                } else if (sellType === 'all') {
                    sharesToSell = totalShares;
                    grossProceeds = totalValue;
                    // Auto-fill quantity with max shares
                    quantityInput.value = totalShares;
                }
                
                const fee = 0; // No trading fee for now
                const netProceeds = grossProceeds - fee;
                
                sharesDisplay.textContent = sharesToSell.toLocaleString();
                grossDisplay.textContent = '$' + grossProceeds.toFixed(2);
                netDisplay.textContent = '$' + netProceeds.toFixed(2);
                
                // Update button state
                const submitButton = document.querySelector('button[type="submit"]');
                if (sharesToSell <= 0 || sharesToSell > totalShares) {
                    submitButton.disabled = true;
                    submitButton.classList.add('opacity-50', 'cursor-not-allowed');
                } else {
                    submitButton.disabled = false;
                    submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            }
            
            // Handle sell type changes
            sellTypeRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    const sellType = this.value;
                    
                    // Toggle amount input visibility only; quantity stays visible always
                    document.getElementById('amount-input').classList.toggle('hidden', sellType !== 'amount');
                    
                    // Control editability of quantity
                    if (sellType === 'quantity') {
                        quantityInput.readOnly = false;
                        amountInput.value = '';
                    } else if (sellType === 'amount') {
                        quantityInput.readOnly = true;
                    } else if (sellType === 'all') {
                        quantityInput.readOnly = true;
                        quantityInput.value = totalShares;
                        amountInput.value = '';
                    }
                    
                    updateCalculations();
                });
            });
            
            // Handle input changes
            quantityInput.addEventListener('input', updateCalculations);
            amountInput.addEventListener('input', updateCalculations);
            
            // Initialize state based on preselected radio
            const initialType = document.querySelector('input[name="sell_type"]:checked')?.value || 'quantity';
            document.getElementById('amount-input').classList.toggle('hidden', initialType !== 'amount');
            if (initialType === 'quantity') {
                quantityInput.readOnly = false;
            } else if (initialType === 'amount') {
                quantityInput.readOnly = true;
            } else if (initialType === 'all') {
                quantityInput.readOnly = true;
                quantityInput.value = totalShares;
            }
            updateCalculations();
        });
    </script>
</x-user-layout>
