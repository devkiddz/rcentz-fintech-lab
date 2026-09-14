<x-user-layout>
    <x-slot name="header">
        {{ $stock->symbol }} - {{ $stock->company_name }}
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
                        <h1 class="text-xl font-light mb-1">{{ $stock->symbol }}</h1>
                        <p class="text-tesla-100 dark:text-gray-300 text-sm">{{ $stock->company_name }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ $stock->sector }} • {{ $stock->industry }}</p>
                    </div>
                    
                    <!-- Enhanced Stats Card -->
                    <div class="bg-card bg-opacity-15 backdrop-blur-xl rounded-xl p-4 border border-white border-opacity-20 shadow-xl lg:w-80">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <p class="text-xs text-tesla-100 dark:text-gray-300 mb-1">Current Price</p>
                                <p class="text-lg font-light">{{ $stock->formatted_current_price }}</p>
                            </div>
                            <div class="w-10 h-10 flex items-center justify-center">
                                <i data-lucide="bar-chart-3" class="w-5 h-5 text-white"></i>
                            </div>
                        </div>
                        <div class="flex space-x-4 text-xs">
                            <div class="flex-1 text-center">
                                <p class="text-tesla-100 dark:text-gray-300">Change</p>
                                <p class="text-white font-medium {{ $stock->change_color }}">
                                    {{ $stock->formatted_change_amount }}
                                </p>
                            </div>
                            <div class="flex-1 text-center">
                                <p class="text-tesla-100 dark:text-gray-300">Volume</p>
                                <p class="text-white font-medium">{{ $stock->formatted_volume }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stock Overview -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <!-- Current Price -->
            <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <p class="text-xs text-muted-foreground dark:text-gray-300 mb-1">Current Price</p>
                        <p class="text-lg font-light text-foreground">{{ $stock->formatted_current_price }}</p>
                    </div>
                    <div class="w-8 h-8 flex items-center justify-center">
                        <i data-lucide="dollar-sign" class="w-4 h-4 text-tesla-600"></i>
                    </div>
                </div>
            </div>

            <!-- Change -->
            <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <p class="text-xs text-muted-foreground dark:text-gray-300 mb-1">Change</p>
                        <p class="text-lg font-light {{ $stock->change_color }}">
                            {{ $stock->formatted_change_amount }}
                        </p>
                        <p class="text-xs {{ $stock->change_color }}">
                            {{ $stock->formatted_change_percentage }}
                        </p>
                    </div>
                    <div class="w-8 h-8 flex items-center justify-center">
                        <i data-lucide="{{ $stock->change_percentage >= 0 ? 'trending-up' : 'trending-down' }}" class="w-4 h-4 {{ $stock->change_color }}"></i>
                    </div>
                </div>
            </div>

            <!-- Volume -->
            <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <p class="text-xs text-muted-foreground dark:text-gray-300 mb-1">Volume</p>
                        <p class="text-lg font-light text-foreground">{{ $stock->formatted_volume }}</p>
                    </div>
                    <div class="w-8 h-8 flex items-center justify-center">
                        <i data-lucide="activity" class="w-4 h-4 text-green-600"></i>
                    </div>
                </div>
            </div>

            <!-- Market Cap -->
            <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <p class="text-xs text-muted-foreground dark:text-gray-300 mb-1">Market Cap</p>
                        <p class="text-lg font-light text-foreground">{{ $stock->formatted_market_cap }}</p>
                    </div>
                    <div class="w-8 h-8 flex items-center justify-center">
                        <i data-lucide="pie-chart" class="w-4 h-4 text-purple-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Stock Information -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Price Chart -->
                <div class="bg-card rounded-xl p-6 shadow-sm border border-border">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-light text-foreground mb-1">Price Chart</h3>
                            <p class="text-xs text-muted-foreground dark:text-gray-300">Stock performance over time</p>
                        </div>
                        <div class="flex space-x-2">
                            <button data-period="1d" class="px-3 py-1 text-xs font-medium text-muted-foreground hover:text-foreground transition-colors duration-200">1D</button>
                            <button data-period="1w" class="px-3 py-1 text-xs font-medium text-muted-foreground hover:text-foreground transition-colors duration-200">1W</button>
                            <button data-period="1m" class="px-3 py-1 text-xs font-medium text-foreground bg-muted rounded-lg">1M</button>
                            <button data-period="1y" class="px-3 py-1 text-xs font-medium text-muted-foreground hover:text-foreground transition-colors duration-200">1Y</button>
                        </div>
                    </div>
                    
                    <!-- Stock Chart -->
                    <div class="relative">
                        <canvas id="stockChart" class="w-full h-64"></canvas>
                    </div>
                </div>

                <!-- Stock Details -->
                <div class="bg-card rounded-xl p-6 shadow-sm border border-border">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-light text-foreground mb-1">Stock Details</h3>
                            <p class="text-xs text-muted-foreground dark:text-gray-300">Comprehensive stock information</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Key Metrics -->
                        <div class="space-y-4">
                            <h4 class="text-sm font-medium text-foreground mb-3">Key Metrics</h4>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-muted-foreground">Previous Close</span>
                                    <span class="text-xs font-medium text-foreground">{{ $stock->formatted_previous_close }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-muted-foreground">P/E Ratio</span>
                                    <span class="text-xs font-medium text-foreground">{{ $stock->formatted_pe_ratio }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-muted-foreground">Dividend Yield</span>
                                    <span class="text-xs font-medium text-foreground">{{ $stock->formatted_dividend_yield }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-muted-foreground">52 Week High</span>
                                    <span class="text-xs font-medium text-foreground">{{ $stock->formatted_fifty_two_week_high }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-muted-foreground">52 Week Low</span>
                                    <span class="text-xs font-medium text-foreground">{{ $stock->formatted_fifty_two_week_low }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Company Info -->
                        <div class="space-y-4">
                            <h4 class="text-sm font-medium text-foreground mb-3">Company Information</h4>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-muted-foreground">Symbol</span>
                                    <span class="text-xs font-medium text-foreground">{{ $stock->symbol }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-muted-foreground">Company</span>
                                    <span class="text-xs font-medium text-foreground">{{ $stock->company_name }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-muted-foreground">Sector</span>
                                    <span class="text-xs font-medium text-foreground">{{ $stock->sector }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-muted-foreground">Industry</span>
                                    <span class="text-xs font-medium text-foreground">{{ $stock->industry }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-muted-foreground">Last Updated</span>
                                    <span class="text-xs font-medium text-foreground">{{ $stock->formatted_last_updated }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stock News -->
                <div class="bg-card rounded-xl p-6 shadow-sm border border-border">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-light text-foreground mb-1">Latest News</h3>
                            <p class="text-xs text-muted-foreground dark:text-gray-300">Recent headlines for {{ $stock->symbol }}</p>
                        </div>
                    </div>

                    @php
                        $articles = $newsFromDb->isNotEmpty() ? $newsFromDb : $newsFromApi;
                    @endphp

                    @if($articles->isEmpty())
                        <p class="text-sm text-muted-foreground dark:text-gray-300">No recent news found.</p>
                    @else
                        <div class="space-y-4">
                            @foreach($articles as $article)
                                <a href="{{ is_array($article) ? ($article['url'] ?? '#') : ($article->url ?? '#') }}" target="_blank" rel="noopener"
                                   class="block p-4 rounded-lg border border-border hover:bg-muted/30 dark:hover:bg-gray-800 transition-colors duration-200">
                                    <div class="flex items-start space-x-3">
                                        @php
                                            $image = is_array($article) ? ($article['image'] ?? null) : ($article->image_url ?? null);
                                            $headline = is_array($article) ? ($article['headline'] ?? '') : ($article->headline ?? '');
                                            $summary = is_array($article) ? ($article['summary'] ?? '') : ($article->summary ?? '');
                                            $source = is_array($article) ? ($article['source'] ?? '') : ($article->source ?? '');
                                            $publishedAt = is_array($article)
                                                ? (isset($article['datetime']) ? \Carbon\Carbon::parse($article['datetime'])->diffForHumans() : '')
                                                : ($article->formatted_published_date ?? '');
                                            $sentimentClass = !is_array($article) ? ($article->sentiment_color ?? 'text-muted-foreground') : 'text-muted-foreground';
                                            $sentimentLabel = !is_array($article) ? ($article->sentiment_label ?? 'Neutral') : 'Neutral';
                                        @endphp
                                        @if($image)
                                            <img src="{{ $image }}" class="w-16 h-16 object-cover rounded" alt="news" />
                                        @else
                                            <div class="w-16 h-16 rounded bg-muted flex items-center justify-center text-gray-400 dark:text-gray-300">
                                                <i data-lucide="image" class="w-5 h-5"></i>
                                            </div>
                                        @endif
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-foreground line-clamp-2">{{ $headline }}</p>
                                            <p class="text-xs text-muted-foreground line-clamp-2 mt-1">{{ $summary }}</p>
                                            <div class="flex items-center justify-between mt-2">
                                                <span class="text-[11px] text-muted-foreground dark:text-gray-300">{{ $source }} • {{ $publishedAt }}</span>
                                                <span class="text-[11px] font-medium {{ $sentimentClass }}">{{ $sentimentLabel }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Trading Actions -->
            <div class="space-y-6">
                <!-- Trading Actions -->
                <div class="bg-card rounded-xl p-6 shadow-sm border border-border">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-light text-foreground mb-1">Trading Actions</h3>
                            <p class="text-xs text-muted-foreground dark:text-gray-300">Buy or sell this stock</p>
                        </div>
                    </div>
                    
                    <div class="space-y-3">
                        <a href="{{ route('trading.buy', $stock) }}" class="w-full flex items-center justify-center px-4 py-3 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors duration-200">
                            <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                            Buy {{ $stock->symbol }}
                        </a>
                        @if($userHolding)
                            <a href="{{ route('trading.sell', $stock) }}" class="w-full flex items-center justify-center px-4 py-3 border border-red-300 text-red-600 text-sm font-medium rounded-lg hover:bg-red-50 transition-colors duration-200">
                                <i data-lucide="minus" class="w-4 h-4 mr-2"></i>
                                Sell {{ $stock->symbol }}
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Watchlist Actions -->
                <div class="bg-card rounded-xl p-6 shadow-sm border border-border">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-light text-foreground mb-1">Watchlist</h3>
                            <p class="text-xs text-muted-foreground dark:text-gray-300">Track this stock and set price alerts</p>
                        </div>
                    </div>
                    
                    @if($isInWatchlist)
                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                                <div class="flex items-center">
                                    <i data-lucide="eye" class="w-4 h-4 text-green-600 mr-2"></i>
                                    <span class="text-sm font-medium text-green-800">In Watchlist</span>
                                </div>
                                <button onclick="editWatchlistAlert('{{ $stock->symbol }}', '{{ $watchlistItem->alert_price }}', '{{ $watchlistItem->alert_type }}')"
                                        class="w-8 h-8 flex items-center justify-center text-green-600 hover:text-green-800 hover:bg-green-50 rounded-lg transition-colors duration-200"
                                        title="Edit Alert">
                                    <i data-lucide="bell" class="w-4 h-4"></i>
                                </button>
                            </div>
                            <form action="{{ route('trading.watchlist.remove', $stock) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="w-full flex items-center justify-center px-4 py-3 border border-red-300 text-red-600 text-sm font-medium rounded-lg hover:bg-red-50 transition-colors duration-200"
                                        onclick="return confirm('Remove {{ $stock->symbol }} from watchlist?')">
                                    <i data-lucide="eye-off" class="w-4 h-4 mr-2"></i>
                                    Remove from Watchlist
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="space-y-3">
                            <form action="{{ route('trading.watchlist.add', $stock) }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label for="alert_price" class="block text-xs font-medium text-foreground mb-1">Alert Price (Optional)</label>
                                    <input type="number" 
                                           id="alert_price" 
                                           name="alert_price" 
                                           step="0.01" 
                                           min="0.01"
                                           class="w-full px-3 py-2 text-sm border border-border dark:text-white rounded-lg focus:ring-2 focus:ring-ring focus:border-transparent"
                                           placeholder="0.00">
                                </div>
                                <div class="mb-4">
                                    <label for="alert_type" class="block text-xs font-medium text-foreground mb-1">Alert Type (Optional)</label>
                                    <select id="alert_type" 
                                            name="alert_type" 
                                            class="w-full px-3 py-2 text-sm border border-border dark:text-white rounded-lg focus:ring-2 focus:ring-ring focus:border-transparent">
                                        <option value="">No alert</option>
                                        <option value="above">Above this price</option>
                                        <option value="below">Below this price</option>
                                    </select>
                                </div>
                                <button type="submit" 
                                        class="w-full flex items-center justify-center px-4 py-3 bg-tesla-600 text-white text-sm font-medium rounded-lg hover:bg-tesla-700 transition-colors duration-200">
                                    <i data-lucide="eye" class="w-4 h-4 mr-2"></i>
                                    Add to Watchlist
                                </button>
                            </form>
                        </div>
                    @endif
                </div>

                <!-- User Holdings -->
                @if($userHolding)
                <div class="bg-card rounded-xl p-6 shadow-sm border border-border">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-light text-foreground mb-1">Your Holdings</h3>
                            <p class="text-xs text-muted-foreground dark:text-gray-300">Your position in this stock</p>
                        </div>
                    </div>
                    
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-muted-foreground">Shares Owned</span>
                            <span class="text-xs font-medium text-foreground">{{ number_format($userHolding->quantity, 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-muted-foreground">Average Cost</span>
                            <span class="text-xs font-medium text-foreground">{{ currency_symbol() }}{{ number_format($userHolding->average_buy_price, 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-muted-foreground">Current Value</span>
                            <span class="text-xs font-medium text-foreground">{{ currency_symbol() }}{{ number_format($userHolding->current_value, 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-muted-foreground">Gain/Loss</span>
                            <span class="text-xs font-medium {{ $userHolding->unrealized_gain_loss >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $userHolding->unrealized_gain_loss >= 0 ? '+' : '' }}{{ currency_symbol() }}{{ number_format($userHolding->unrealized_gain_loss, 2) }}
                            </span>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Recent Activity -->
                @if($recentTransactions->count() > 0)
                <div class="bg-card rounded-xl p-6 shadow-sm border border-border">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-light text-foreground mb-1">Recent Activity</h3>
                            <p class="text-xs text-muted-foreground dark:text-gray-300">Latest transactions</p>
                        </div>
                    </div>
                    
                    <div class="space-y-3">
                        @foreach($recentTransactions->take(5) as $transaction)
                        <div class="flex items-center space-x-3 p-3 bg-muted rounded-lg">
                            <div class="w-6 h-6 flex items-center justify-center">
                                @if($transaction->type === 'buy')
                                    <i data-lucide="plus" class="w-3 h-3 text-green-500"></i>
                                @else
                                    <i data-lucide="minus" class="w-3 h-3 text-red-500"></i>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-medium text-foreground">{{ $transaction->type_label }}</p>
                                <p class="text-xs text-muted-foreground dark:text-gray-300">{{ $transaction->created_at->format('M j, Y') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs font-medium text-foreground">{{ currency_symbol() }}{{ number_format($transaction->total_amount, 2) }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        // Initialize stock chart
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('stockChart');
            if (ctx) {
                const stockChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: [],
                        datasets: [{
                            label: '{{ $stock->symbol }} Price',
                            data: [],
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
                                display: false,
                                grid: {
                                    display: false
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
                                        size: 12
                                    },
                                    callback: function(value) {
                                        return '$' + value.toFixed(2);
                                    }
                                }
                            }
                        }
                    }
                });

                // Load initial chart data
                loadChartData('1m');

                // Add period button event listeners
                document.querySelectorAll('[data-period]').forEach(button => {
                    button.addEventListener('click', function() {
                        const period = this.getAttribute('data-period');
                        loadChartData(period);
                        
                        // Update active button
                        document.querySelectorAll('[data-period]').forEach(btn => {
                            btn.classList.remove('bg-black', 'text-white');
                            btn.classList.add('text-muted-foreground');
                        });
                        this.classList.remove('text-muted-foreground');
                        this.classList.add('bg-black', 'text-white');
                    });
                });

                function loadChartData(period) {
                    fetch(`/api/stocks/{{ $stock->symbol }}/chart-data?period=${period}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.chartData) {
                                stockChart.data.labels = data.chartData.labels;
                                stockChart.data.datasets[0].data = data.chartData.datasets[0].data;
                                stockChart.update();
                            }
                        })
                        .catch(error => {
                            console.error('Error loading chart data:', error);
                        });
                }
            }
        });

        function editWatchlistAlert(symbol, currentPrice, currentType) {
            // Create a modal for editing watchlist alert
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50';
            modal.innerHTML = `
                <div class="relative top-20 mx-auto p-5 border border-border w-96 max-w-[calc(100vw-2rem)] shadow-lg rounded-xl bg-card text-card-foreground">
                    <div class="mt-3">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Edit Price Alert</h3>
                        <form action="{{ route('trading.watchlist.update', $stock) }}" method="POST">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <input type="hidden" name="_method" value="PATCH">
                            <div class="mb-4">
                                <label for="alert_price" class="block text-sm font-medium text-foreground mb-2">Alert Price</label>
                                <input type="number" 
                                       id="alert_price" 
                                       name="alert_price" 
                                       value="${currentPrice || ''}"
                                       step="0.01" 
                                       min="0.01"
                                       class="w-full px-3 py-2 border border-border dark:text-white rounded-lg focus:ring-2 focus:ring-ring focus:border-transparent"
                                       placeholder="0.00">
                            </div>
                            <div class="mb-6">
                                <label for="alert_type" class="block text-sm font-medium text-foreground mb-2">Alert Type</label>
                                <select id="alert_type" 
                                        name="alert_type" 
                                        class="w-full px-3 py-2 border border-border dark:text-white rounded-lg focus:ring-2 focus:ring-ring focus:border-transparent">
                                    <option value="">No alert</option>
                                    <option value="above" ${currentType === 'above' ? 'selected' : ''}>Above this price</option>
                                    <option value="below" ${currentType === 'below' ? 'selected' : ''}>Below this price</option>
                                </select>
                            </div>
                            <div class="flex justify-end space-x-3">
                                <button type="button" 
                                        onclick="this.closest('.fixed').remove()"
                                        class="px-4 py-2 text-muted-foreground bg-muted rounded-lg hover:bg-muted transition-colors duration-200">
                                    Cancel
                                </button>
                                <button type="submit" 
                                        class="px-4 py-2 bg-foreground text-background rounded-lg hover:opacity-90 transition-colors duration-200">
                                    Update Alert
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            
            // Close modal when clicking outside
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.remove();
                }
            });
        }
    </script>
</x-user-layout> 
