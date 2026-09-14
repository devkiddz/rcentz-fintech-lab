<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-light text-lg text-foreground leading-tight mr-4">
                    {{ $plan->name }}
                </h2>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('admin.investments.plans.edit', $plan) }}" 
                   class="px-3 py-2 bg-black dark:bg-card text-white dark:text-foreground text-sm font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-muted transition-colors">
                    Edit Plan
                </a>
                <a href="{{ route('admin.investments.plans.index') }}" 
                   class="px-3 py-2 bg-muted text-muted-foreground text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                    Back to Plans
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="xl:col-span-2 space-y-6">
                    <!-- Plan Overview -->
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Plan Overview</h3>
                        </div>
                        <div class="p-4">
                            <div class="flex items-start space-x-4 mb-4">
                                @if($plan->image)
                                    <img src="{{ asset('storage/' . $plan->image) }}" 
                                         alt="{{ $plan->name }}" 
                                         class="w-16 h-16 rounded-lg object-cover">
                                @else
                                    <div class="w-16 h-16 bg-gradient-to-br from-tesla-500 to-purple-600 rounded-lg flex items-center justify-center">
                                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                    </div>
                                @endif
                                <div class="flex-1">
                                    <h4 class="text-lg font-medium text-foreground dark:text-white mb-2">{{ $plan->name }}</h4>
                                    <p class="text-sm text-muted-foreground mb-3">{{ $plan->description }}</p>
                                    <div class="flex flex-wrap gap-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-tesla-100 text-tesla-800">
                                            {{ ucfirst($plan->type) }}
                                        </span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $plan->risk_level_badge }}">
                                            {{ ucfirst($plan->risk_level) }} Risk
                                        </span>
                                        @if($plan->is_active)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Active
                                            </span>
                                        @endif
                                        @if($plan->is_featured)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                Featured
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Metrics -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-card border border-border p-4 rounded-lg">
                            <div class="text-center">
                                <p class="text-xs font-medium text-muted-foreground mb-1">NAV</p>
                                <p class="text-lg font-light text-foreground">{{ $plan->formatted_nav }}</p>
                                @if($plan->nav_change_percentage)
                                    <p class="text-xs {{ $plan->nav_change_color }}">{{ $plan->formatted_nav_change }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="bg-card border border-border p-4 rounded-lg">
                            <div class="text-center">
                                <p class="text-xs font-medium text-muted-foreground mb-1">Min Investment</p>
                                <p class="text-lg font-light text-foreground">{{ $plan->formatted_minimum_investment }}</p>
                            </div>
                        </div>

                        <div class="bg-card border border-border p-4 rounded-lg">
                            <div class="text-center">
                                <p class="text-xs font-medium text-muted-foreground mb-1">Management Fee</p>
                                <p class="text-lg font-light text-foreground">{{ $plan->formatted_management_fee }}</p>
                            </div>
                        </div>

                        <div class="bg-card border border-border p-4 rounded-lg">
                            <div class="text-center">
                                <p class="text-xs font-medium text-muted-foreground mb-1">Expense Ratio</p>
                                <p class="text-lg font-light text-foreground">{{ $plan->formatted_expense_ratio }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Plan Details -->
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Plan Details</h3>
                        </div>
                        <div class="p-4">
                            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <dt class="text-xs font-medium text-muted-foreground dark:text-gray-300">Category</dt>
                                    <dd class="text-sm font-medium text-foreground mt-1">{{ $plan->category }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-muted-foreground dark:text-gray-300">Total Assets</dt>
                                    <dd class="text-sm font-medium text-foreground mt-1">{{ $plan->formatted_total_assets }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-muted-foreground dark:text-gray-300">Inception Date</dt>
                                    <dd class="text-sm font-medium text-foreground mt-1">{{ $plan->inception_date->format('M d, Y') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-muted-foreground dark:text-gray-300">Dividend Yield</dt>
                                    <dd class="text-sm font-medium text-foreground mt-1">{{ $plan->formatted_dividend_yield }}</dd>
                                </div>
                                @if($plan->prospectus_url)
                                <div>
                                    <dt class="text-xs font-medium text-muted-foreground dark:text-gray-300">Prospectus</dt>
                                    <dd class="text-sm font-medium text-foreground mt-1">
                                        <a href="{{ $plan->prospectus_url }}" target="_blank" class="text-tesla-600 hover:text-tesla-800">View Prospectus</a>
                                    </dd>
                                </div>
                                @endif
                                @if($plan->fact_sheet_url)
                                <div>
                                    <dt class="text-xs font-medium text-muted-foreground dark:text-gray-300">Fact Sheet</dt>
                                    <dd class="text-sm font-medium text-foreground mt-1">
                                        <a href="{{ $plan->fact_sheet_url }}" target="_blank" class="text-tesla-600 hover:text-tesla-800">View Fact Sheet</a>
                                    </dd>
                                </div>
                                @endif
                            </dl>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Statistics -->
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Statistics</h3>
                        </div>
                        <div class="p-4 space-y-4">
                            <div class="text-center">
                                <p class="text-2xl font-light text-foreground">{{ $stats['total_holdings'] }}</p>
                                <p class="text-xs text-muted-foreground dark:text-gray-300">Total Holdings</p>
                            </div>
                            <div class="text-center">
                                <p class="text-2xl font-light text-foreground">{{ $stats['total_investors'] }}</p>
                                <p class="text-xs text-muted-foreground dark:text-gray-300">Unique Investors</p>
                            </div>
                            <div class="text-center">
                                <p class="text-2xl font-light text-foreground">{{ $stats['total_transactions'] }}</p>
                                <p class="text-xs text-muted-foreground dark:text-gray-300">Total Transactions</p>
                            </div>
                            <div class="text-center">
                                <p class="text-2xl font-light text-foreground">${{ number_format($stats['total_volume'], 0) }}</p>
                                <p class="text-xs text-muted-foreground dark:text-gray-300">Total Volume</p>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Holdings -->
                    @if($plan->holdings->count() > 0)
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Recent Holdings</h3>
                        </div>
                        <div class="p-4">
                            <div class="space-y-3">
                                @foreach($plan->holdings->take(5) as $holding)
                                <div class="flex items-center justify-between p-3 bg-muted/40 dark:bg-dark-muted rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        @if($holding->user->profile_image)
                                            <img src="{{ asset('storage/' . $holding->user->profile_image) }}" 
                                                 alt="{{ $holding->user->name }}" 
                                                 class="w-8 h-8 rounded-lg object-cover">
                                        @else
                                            <div class="w-8 h-8 bg-gradient-to-br from-tesla-500 to-purple-600 rounded-lg flex items-center justify-center">
                                                <span class="text-white font-bold text-xs">{{ strtoupper(substr($holding->user->name, 0, 2)) }}</span>
                                            </div>
                                        @endif
                                        <div>
                                            <p class="text-sm font-medium text-foreground">{{ $holding->user->name }}</p>
                                            <p class="text-xs text-muted-foreground dark:text-gray-300">{{ $holding->formatted_units }} units</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-medium text-foreground">{{ $holding->formatted_current_value }}</p>
                                        <p class="text-xs {{ $holding->gain_loss_color }}">{{ $holding->unrealized_gain_loss_percentage >= 0 ? '+' : '' }}{{ number_format($holding->unrealized_gain_loss_percentage, 2) }}%</p>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @if($plan->holdings->count() > 5)
                            <div class="mt-3 text-center">
                                <p class="text-xs text-muted-foreground dark:text-gray-300">Showing latest 5 holdings out of {{ $plan->holdings->count() }} total</p>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    <!-- Recent Transactions -->
                    @if($plan->transactions->count() > 0)
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Recent Transactions</h3>
                        </div>
                        <div class="p-4">
                            <div class="space-y-3">
                                @foreach($plan->transactions->take(5) as $transaction)
                                <div class="flex items-center justify-between p-3 bg-muted/40 dark:bg-dark-muted rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        @if($transaction->user->profile_image)
                                            <img src="{{ asset('storage/' . $transaction->user->profile_image) }}" 
                                                 alt="{{ $transaction->user->name }}" 
                                                 class="w-8 h-8 rounded-lg object-cover">
                                        @else
                                            <div class="w-8 h-8 bg-gradient-to-br from-tesla-500 to-purple-600 rounded-lg flex items-center justify-center">
                                                <span class="text-white font-bold text-xs">{{ strtoupper(substr($transaction->user->name, 0, 2)) }}</span>
                                            </div>
                                        @endif
                                        <div>
                                            <p class="text-sm font-medium text-foreground">{{ $transaction->user->name }}</p>
                                            <p class="text-xs text-muted-foreground dark:text-gray-300">{{ ucfirst($transaction->type) }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-medium text-foreground">{{ $transaction->formatted_total_amount }}</p>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium {{ $transaction->status_badge }}">
                                            {{ ucfirst($transaction->status) }}
                                        </span>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @if($plan->transactions->count() > 5)
                            <div class="mt-3 text-center">
                                <p class="text-xs text-muted-foreground dark:text-gray-300">Showing latest 5 transactions out of {{ $plan->transactions->count() }} total</p>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
