<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-light text-lg text-foreground leading-tight mr-4">
                    {{ $user->name }}
                </h2>
            </div>
            <div class="flex space-x-2">
                @if(!$user->is_admin)
                    <a href="{{ route('impersonate', $user->id) }}" 
                       class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-all duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        Login as User
                    </a>
                @endif
                <a href="{{ route('admin.users.account-operations', $user) }}"
                   class="inline-flex items-center px-4 py-2 bg-muted text-foreground text-sm font-medium rounded-lg hover:bg-muted/80 transition-all duration-200">
                    Account Operations
                </a>
                <a href="{{ route('admin.users.edit', $user) }}" 
                   class="inline-flex items-center px-4 py-2 bg-black dark:bg-card text-white dark:text-foreground text-sm font-medium rounded-lg hover:opacity-90 transition-all duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Edit User
                </a>
                <a href="{{ route('admin.users.index') }}" 
                   class="inline-flex items-center px-3 py-2 bg-muted text-muted-foreground text-sm font-medium rounded-lg hover:bg-muted transition-all duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- User Information -->
                <div class="lg:col-span-1 space-y-4">
                    <!-- Basic Info -->
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">User Information</h3>
                        </div>
                        
                        <div class="p-4 space-y-4">
                            <div class="flex items-center space-x-3">
                                @if($user->profile_image)
                                    <img src="{{ asset('storage/' . $user->profile_image) }}" 
                                         alt="{{ $user->name }}" 
                                         class="w-12 h-12 rounded-lg object-cover">
                                @else
                                    <div class="w-12 h-12 bg-gradient-to-br from-tesla-500 to-purple-600 rounded-lg flex items-center justify-center">
                                        <span class="text-white font-bold text-sm">{{ strtoupper(substr($user->name, 0, 2)) }}</span>
                                    </div>
                                @endif
                                <div>
                                    <h4 class="text-base font-medium text-foreground">{{ $user->name }}</h4>
                                    <p class="text-xs text-muted-foreground dark:text-gray-300">{{ $user->email }}</p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-muted-foreground mb-2">User Type</label>
                                @if($user->is_admin)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-purple-100 text-purple-800">
                                        Administrator
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-tesla-100 text-tesla-800">
                                        Regular User
                                    </span>
                                @endif
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-muted-foreground mb-2">Email Status</label>
                                @if($user->email_verified_at)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-green-100 text-green-800">
                                        Verified
                                    </span>
                                    <div class="text-xs text-muted-foreground mt-1">{{ $user->email_verified_at->format('M d, Y') }}</div>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-red-100 text-red-800">
                                        Not Verified
                                    </span>
                                @endif
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-muted-foreground mb-2">Member Since</label>
                                <div class="text-sm text-foreground">{{ $user->created_at->format('M d, Y') }}</div>
                                <div class="text-xs text-muted-foreground dark:text-gray-300">{{ $user->created_at->diffForHumans() }}</div>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-muted-foreground mb-2">Last Updated</label>
                                <div class="text-sm text-foreground">{{ $user->updated_at->format('M d, Y') }}</div>
                                <div class="text-xs text-muted-foreground dark:text-gray-300">{{ $user->updated_at->diffForHumans() }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Wallet Management -->
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Wallet Management</h3>
                        </div>
                        
                        <div class="p-4 space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-muted-foreground mb-2">Current Balance</label>
                                <div class="text-lg font-medium text-green-600">${{ number_format($walletBalance, 2) }}</div>
                            </div>

                            <!-- Fund Wallet -->
                            <div>
                                <label class="block text-xs font-medium text-muted-foreground mb-2">Fund Wallet</label>
                                <form method="POST" action="{{ route('admin.users.fund-wallet', $user) }}" class="space-y-2">
                                    @csrf
                                    <input type="number" name="amount" step="0.01" min="0.01" placeholder="Amount" 
                                           class="w-full px-3 py-2 text-xs border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-black">
                                    <input type="text" name="description" placeholder="Description (optional)" 
                                           class="w-full px-3 py-2 text-xs border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-black">
                                    <button type="submit" 
                                            class="w-full px-3 py-2 bg-green-600 text-white text-xs font-medium rounded hover:bg-green-700 transition-colors">
                                        Fund Wallet
                                    </button>
                                </form>
                            </div>

                            <!-- Deduct from Wallet -->
                            <div>
                                <label class="block text-xs font-medium text-muted-foreground mb-2">Deduct from Wallet</label>
                                <form method="POST" action="{{ route('admin.users.deduct-wallet', $user) }}" class="space-y-2">
                                    @csrf
                                    <input type="number" name="amount" step="0.01" min="0.01" max="{{ $walletBalance }}" placeholder="Amount" 
                                           class="w-full px-3 py-2 text-xs border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-black">
                                    <input type="text" name="description" placeholder="Description (optional)" 
                                           class="w-full px-3 py-2 text-xs border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-black">
                                    <button type="submit" 
                                            class="w-full px-3 py-2 bg-red-600 text-white text-xs font-medium rounded hover:bg-red-700 transition-colors">
                                        Deduct Funds
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-4">
                    <!-- Statistics Overview -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="bg-card border border-border p-4 rounded-lg">
                            <div class="text-center">
                                <div class="w-10 h-10 bg-tesla-100 rounded-lg flex items-center justify-center mx-auto mb-2">
                                    <svg class="w-5 h-5 text-tesla-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                    </svg>
                                </div>
                                <div class="text-lg font-light text-foreground">{{ $user->purchases->count() }}</div>
                                <div class="text-xs text-muted-foreground dark:text-gray-300">Car Purchases</div>
                            </div>
                        </div>

                        <div class="bg-card border border-border p-4 rounded-lg">
                            <div class="text-center">
                                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mx-auto mb-2">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                    </svg>
                                </div>
                                <div class="text-lg font-light text-foreground">${{ number_format($totalSpent, 0) }}</div>
                                <div class="text-xs text-muted-foreground dark:text-gray-300">Total Spent</div>
                            </div>
                        </div>

                        <div class="bg-card border border-border p-4 rounded-lg">
                            <div class="text-center">
                                <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center mx-auto mb-2">
                                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                                <div class="text-lg font-light text-foreground">${{ number_format($totalInvestmentValue, 0) }}</div>
                                <div class="text-xs text-muted-foreground dark:text-gray-300">Investments</div>
                            </div>
                        </div>

                        <div class="bg-card border border-border p-4 rounded-lg">
                            <div class="text-center">
                                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mx-auto mb-2">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                                <div class="text-lg font-light text-foreground">${{ number_format($totalStockValue, 0) }}</div>
                                <div class="text-xs text-muted-foreground dark:text-gray-300">Stocks</div>
                            </div>
                        </div>
                    </div>

                    <!-- Investment Holdings -->
                    @if($user->investmentHoldings->count() > 0)
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Investment Holdings</h3>
                        </div>
                        <div class="p-4">
                            <div class="space-y-3">
                                @foreach($user->investmentHoldings as $holding)
                                <div class="flex items-center justify-between p-3 bg-muted/40 rounded-lg border border-border dark:border-gray-700">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center">
                                            <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-foreground">{{ $holding->investmentPlan->name }}</p>
                                            <p class="text-xs text-muted-foreground dark:text-gray-300">{{ number_format($holding->units, 2) }} units</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-medium text-foreground">${{ number_format($holding->current_value, 0) }}</p>
                                        <p class="text-xs text-muted-foreground dark:text-gray-300">{{ $holding->investmentPlan->nav_per_unit }}</p>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Stock Holdings -->
                    @if($user->stockHoldings->count() > 0)
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Stock Holdings</h3>
                        </div>
                        <div class="p-4">
                            <div class="space-y-3">
                                @foreach($user->stockHoldings as $holding)
                                <div class="flex items-center justify-between p-3 bg-muted/40 rounded-lg border border-border dark:border-gray-700">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-foreground">{{ $holding->stock->symbol }}</p>
                                            <p class="text-xs text-muted-foreground dark:text-gray-300">{{ number_format($holding->shares, 2) }} shares</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-medium text-foreground">${{ number_format($holding->current_value, 0) }}</p>
                                        <p class="text-xs text-muted-foreground dark:text-gray-300">${{ $holding->stock->current_price }}</p>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Recent Purchases -->
                    @if($user->purchases->count() > 0)
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Recent Purchases</h3>
                        </div>
                        <div class="p-4">
                            <div class="space-y-3">
                                @foreach($user->purchases->take(5) as $purchase)
                                <div class="flex items-center justify-between p-3 bg-muted/40 rounded-lg border border-border dark:border-gray-700">
                                    <div class="flex items-center space-x-3">
                                        @if($purchase->car->images && count($purchase->car->images) > 0)
                                            <img src="{{ strpos($purchase->car->images[0], 'http') === 0 ? $purchase->car->images[0] : asset('storage/' . $purchase->car->images[0]) }}" 
                                                 alt="{{ $purchase->car->title }}" 
                                                 class="w-10 h-8 object-cover rounded-lg">
                                        @else
                                            <div class="w-10 h-8 bg-muted rounded-lg flex items-center justify-center">
                                                <svg class="w-4 h-4 text-gray-400 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                                </svg>
                                            </div>
                                        @endif
                                        <div>
                                            <p class="text-sm font-medium text-foreground">{{ Str::limit($purchase->car->title, 30) }}</p>
                                            <p class="text-xs text-muted-foreground dark:text-gray-300">{{ $purchase->purchased_at->format('M d, Y') }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-medium text-foreground">${{ number_format($purchase->amount, 0) }}</p>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium {{ $purchase->status_badge }}">
                                            {{ ucfirst($purchase->status) }}
                                        </span>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-admin-layout> 
