<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-light text-lg text-foreground leading-tight">
                    {{ __('Dashboard') }}
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <!-- Welcome Section -->
            <div class="bg-gradient-to-r from-gray-900 to-black text-white mb-6 rounded-xl">
                <div class="px-8 py-8">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h1 class="text-2xl font-light text-white mb-2">Welcome back, {{ auth()->user()->name }}</h1>
                            <p class="text-white/70 text-sm font-light">Here's your business overview for today</p>
                        </div>
                    </div>
                    
                    <!-- Key Metrics Row (balanced across Cars / Investments / Stocks) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Cars (Sales Revenue This Month) -->
                        <div class="bg-card/10 border border-white/20 p-6">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <p class="text-white/70 text-sm font-light">Car Sales Revenue</p>
                                    <p class="text-white text-xl font-light">{{ currency_symbol() }}{{ number_format($monthlyRevenue, 0) }}</p>
                                    @if(!is_null($monthlyRevenueChange))
                                    <p class="text-sm font-light {{ $monthlyRevenueChange >= 0 ? 'text-green-300' : 'text-red-300' }}">{{ $monthlyRevenueChange >= 0 ? '+' : '' }}{{ $monthlyRevenueChange }}% MoM</p>
                                    @endif
                                </div>
                                <div class="w-10 h-10 bg-green-500/20 flex items-center justify-center ml-3">
                                    <svg class="w-5 h-5 text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Investments (Volume This Month) -->
                        <div class="bg-card/10 border border-white/20 p-6">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <p class="text-white/70 text-sm font-light">Investment Volume</p>
                                    <p class="text-white text-xl font-light">{{ currency_symbol() }}{{ number_format($investmentVolumeThisMonth, 0) }}</p>
                                    @if(!is_null($investmentVolumeChange))
                                    <p class="text-sm font-light {{ $investmentVolumeChange >= 0 ? 'text-green-300' : 'text-red-300' }}">{{ $investmentVolumeChange >= 0 ? '+' : '' }}{{ $investmentVolumeChange }}% MoM</p>
                                    @endif
                                </div>
                                <div class="w-10 h-10 bg-tesla-500/20 flex items-center justify-center ml-3">
                                    <svg class="w-5 h-5 text-tesla-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v18h18M7 13l3 3 7-7"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Stocks (Volume This Month) -->
                        <div class="bg-card/10 border border-white/20 p-6">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <p class="text-white/70 text-sm font-light">Stock Trading Volume</p>
                                    <p class="text-white text-xl font-light">{{ currency_symbol() }}{{ number_format($stockVolumeThisMonth, 0) }}</p>
                                    @if(!is_null($stockVolumeChange))
                                    <p class="text-sm font-light {{ $stockVolumeChange >= 0 ? 'text-green-300' : 'text-red-300' }}">{{ $stockVolumeChange >= 0 ? '+' : '' }}{{ $stockVolumeChange }}% MoM</p>
                                    @endif
                                </div>
                                <div class="w-10 h-10 bg-purple-500/20 flex items-center justify-center ml-3">
                                    <svg class="w-5 h-5 text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3v18M3 13h18"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Combined AUM -->
                        <div class="bg-card/10 border border-white/20 p-6">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <p class="text-white/70 text-sm font-light">Total AUM</p>
                                    <p class="text-white text-xl font-light">{{ currency_symbol() }}{{ number_format($totalInvestmentAum + $totalStockAum, 0) }}</p>
                                    <p class="text-white/60 text-xs font-light">Inv: {{ currency_symbol() }}{{ number_format($totalInvestmentAum, 0) }} | Stk: {{ currency_symbol() }}{{ number_format($totalStockAum, 0) }}</p>
                                </div>
                                <div class="w-10 h-10 bg-orange-500/20 flex items-center justify-center ml-3">
                                    <svg class="w-5 h-5 text-orange-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h10M7 6h10"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Recent Activity Cards -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Recent Car Orders -->
                <div class="bg-card border border-border p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-medium text-foreground">Recent Car Orders</h3>
                        <span class="text-xs text-muted-foreground dark:text-gray-300">Latest 5</span>
                    </div>
                    @if($recentPurchases->count() > 0)
                    <div class="space-y-3">
                        @foreach($recentPurchases->take(5) as $purchase)
                        <div class="flex items-center justify-between text-sm border border-gray-100 p-3 rounded-lg">
                            <div class="truncate">
                                <div class="text-foreground font-medium truncate">{{ $purchase->user->name }} • {{ Str::limit($purchase->car->title, 25) }}</div>
                                <div class="text-muted-foreground text-xs">{{ $purchase->purchased_at ? $purchase->purchased_at->format('M d, Y') : 'Date N/A' }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-foreground font-medium">{{ $purchase->formatted_amount }}</div>
                                <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-medium {{ $purchase->status_badge }} rounded">{{ ucfirst($purchase->status) }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="text-xs text-muted-foreground dark:text-gray-300">No orders yet.</p>
                    @endif
                </div>

                <!-- Recent Investment Transactions -->
                <div class="bg-card border border-border p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-medium text-foreground">Recent Investments</h3>
                        <span class="text-xs text-muted-foreground dark:text-gray-300">Latest 5</span>
                    </div>
                    @if($recentInvestmentTransactions->count())
                    <div class="space-y-3">
                        @foreach($recentInvestmentTransactions->take(5) as $tx)
                        <div class="flex items-center justify-between text-sm border border-gray-100 p-3 rounded-lg">
                            <div class="truncate">
                                <div class="text-foreground font-medium truncate">{{ $tx->user->name }} • {{ Str::limit($tx->investmentPlan->name, 25) }}</div>
                                <div class="text-muted-foreground text-xs">{{ $tx->type_label }} • {{ $tx->formatted_units }} @ {{ $tx->formatted_nav_at_transaction }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-foreground font-medium">{{ $tx->formatted_total_amount }}</div>
                                <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-medium {{ $tx->status_badge }} rounded">{{ ucfirst($tx->status) }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="text-xs text-muted-foreground dark:text-gray-300">No investment activity yet.</p>
                    @endif
                </div>

                <!-- Recent Stock Trades -->
                <div class="bg-card border border-border p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-medium text-foreground">Recent Stock Trades</h3>
                        <span class="text-xs text-muted-foreground dark:text-gray-300">Latest 5</span>
                    </div>
                    @if($recentStockTransactions->count())
                    <div class="space-y-3">
                        @foreach($recentStockTransactions->take(5) as $tx)
                        <div class="flex items-center justify-between text-sm border border-gray-100 p-3 rounded-lg">
                            <div class="truncate">
                                <div class="text-foreground font-medium truncate">{{ $tx->user->name }} • {{ $tx->stock->symbol }}</div>
                                <div class="text-muted-foreground text-xs">{{ $tx->type_label }} • {{ $tx->formatted_quantity }} @ {{ $tx->formatted_price_per_share }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-foreground font-medium">{{ $tx->formatted_total_amount }}</div>
                                <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-medium {{ $tx->status_badge }} rounded">{{ ucfirst($tx->status) }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="text-xs text-muted-foreground dark:text-gray-300">No stock activity yet.</p>
                    @endif
                </div>
            </div>

            <!-- Investments & Stocks Overview -->
            <div id="investments" class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
                <!-- Investments Overview -->
                <div class="bg-card border border-border p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-medium text-foreground">Investments</h3>
                        <span class="text-xs text-muted-foreground dark:text-gray-300">This month</span>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <div class="text-center p-3 bg-muted/40 border border-border dark:border-gray-700 rounded-lg">
                            <div class="text-xs text-muted-foreground dark:text-gray-300">Active Plans</div>
                            <div class="text-lg font-light text-foreground">{{ $activeInvestmentPlans }}</div>
                        </div>
                        <div class="text-center p-3 bg-muted/40 border border-border dark:border-gray-700 rounded-lg">
                            <div class="text-xs text-muted-foreground dark:text-gray-300">Tx Count</div>
                            <div class="text-lg font-light text-foreground">{{ $investmentTransactionsThisMonth }}</div>
                        </div>
                        <div class="text-center p-3 bg-muted/40 border border-border dark:border-gray-700 rounded-lg">
                            <div class="text-xs text-muted-foreground dark:text-gray-300">Volume</div>
                            <div class="text-lg font-light text-foreground">{{ currency_symbol() }}{{ number_format($investmentVolumeThisMonth, 0) }}</div>
                            @if(!is_null($investmentVolumeChange))
                            <div class="text-xs {{ $investmentVolumeChange >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $investmentVolumeChange >= 0 ? '+' : '' }}{{ $investmentVolumeChange }}%</div>
                            @endif
                        </div>
                        <div class="text-center p-3 bg-muted/40 border border-border dark:border-gray-700 rounded-lg">
                            <div class="text-xs text-muted-foreground dark:text-gray-300">AUM</div>
                            <div class="text-lg font-light text-foreground">{{ currency_symbol() }}{{ number_format($totalInvestmentAum, 0) }}</div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h4 class="text-sm font-medium text-foreground dark:text-white mb-3">Recent Transactions</h4>
                        @if($recentInvestmentTransactions->count())
                        <div class="space-y-2">
                            @foreach($recentInvestmentTransactions->take(3) as $tx)
                            <div class="flex items-center justify-between text-sm border border-gray-100 p-2 rounded-lg">
                                <div class="truncate">
                                    <div class="text-foreground font-medium truncate text-xs">{{ $tx->user->name }} • {{ Str::limit($tx->investmentPlan->name, 20) }}</div>
                                    <div class="text-muted-foreground text-xs">{{ $tx->type_label }} • {{ $tx->formatted_units }} @ {{ $tx->formatted_nav_at_transaction }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-foreground font-medium text-xs">{{ $tx->formatted_total_amount }}</div>
                                    <div class="text-muted-foreground text-xs">{{ $tx->formatted_executed_at }}</div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <p class="text-xs text-muted-foreground dark:text-gray-300">No investment activity yet.</p>
                        @endif
                    </div>
                </div>

                <!-- Stocks Overview -->
                <div id="stocks" class="bg-card border border-border p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-medium text-foreground">Stocks</h3>
                        <span class="text-xs text-muted-foreground dark:text-gray-300">This month</span>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <div class="text-center p-3 bg-muted/40 border border-border dark:border-gray-700 rounded-lg">
                            <div class="text-xs text-muted-foreground dark:text-gray-300">Active</div>
                            <div class="text-lg font-light text-foreground">{{ $activeStocks }}</div>
                        </div>
                        <div class="text-center p-3 bg-muted/40 border border-border dark:border-gray-700 rounded-lg">
                            <div class="text-xs text-muted-foreground dark:text-gray-300">Tx Count</div>
                            <div class="text-lg font-light text-foreground">{{ $stockTransactionsThisMonth }}</div>
                        </div>
                        <div class="text-center p-3 bg-muted/40 border border-border dark:border-gray-700 rounded-lg">
                            <div class="text-xs text-muted-foreground dark:text-gray-300">Volume</div>
                            <div class="text-lg font-light text-foreground">{{ currency_symbol() }}{{ number_format($stockVolumeThisMonth, 0) }}</div>
                            @if(!is_null($stockVolumeChange))
                            <div class="text-xs {{ $stockVolumeChange >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $stockVolumeChange >= 0 ? '+' : '' }}{{ $stockVolumeChange }}%</div>
                            @endif
                        </div>
                        <div class="text-center p-3 bg-muted/40 border border-border dark:border-gray-700 rounded-lg">
                            <div class="text-xs text-muted-foreground dark:text-gray-300">AUM</div>
                            <div class="text-lg font-light text-foreground">{{ currency_symbol() }}{{ number_format($totalStockAum, 0) }}</div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h4 class="text-sm font-medium text-foreground dark:text-white mb-3">Recent Trades</h4>
                        @if($recentStockTransactions->count())
                        <div class="space-y-2">
                            @foreach($recentStockTransactions->take(3) as $tx)
                            <div class="flex items-center justify-between text-sm border border-gray-100 p-2 rounded-lg">
                                <div class="truncate">
                                    <div class="text-foreground font-medium truncate text-xs">{{ $tx->user->name }} • {{ $tx->stock->symbol }}</div>
                                    <div class="text-muted-foreground text-xs">{{ $tx->type_label }} • {{ $tx->formatted_quantity }} @ {{ $tx->formatted_price_per_share }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-foreground font-medium text-xs">{{ $tx->formatted_total_amount }}</div>
                                    <div class="text-muted-foreground text-xs">{{ $tx->formatted_executed_at }}</div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <p class="text-xs text-muted-foreground dark:text-gray-300">No stock activity yet.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout> 
