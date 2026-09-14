<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-light text-lg text-foreground leading-tight mr-4">
                    Stock Holding Details
                </h2>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('admin.stocks.holdings.index') }}" 
                   class="px-3 py-2 bg-muted text-muted-foreground text-sm font-medium rounded-lg hover:bg-muted transition-colors">
                    Back to Holdings
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="xl:col-span-2 space-y-6">
                    <!-- Holding Overview -->
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Holding Overview</h3>
                        </div>
                        <div class="p-4">
                            <div class="flex items-start space-x-4 mb-4">
                                @if($holding->stock->logo_url)
                                    <div class="w-16 h-16 rounded-lg flex items-center justify-center bg-card border border-border">
                                        <img src="{{ $holding->stock->logo_url }}" alt="{{ $holding->stock->symbol }} Logo" class="w-14 h-14 object-contain" loading="lazy">
                                    </div>
                                @else
                                    <div class="w-16 h-16 bg-gradient-to-br from-tesla-500 to-purple-600 rounded-lg flex items-center justify-center">
                                        <span class="text-white font-bold text-lg">{{ $holding->stock->symbol }}</span>
                                    </div>
                                @endif
                                <div class="flex-1">
                                    <h4 class="text-lg font-medium text-foreground dark:text-white mb-2">{{ $holding->stock->name }}</h4>
                                    <p class="text-sm text-muted-foreground mb-3">{{ $holding->stock->description }}</p>
                                    <div class="flex flex-wrap gap-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-tesla-100 text-tesla-800">
                                            {{ $holding->stock->sector }}
                                        </span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            {{ $holding->stock->industry }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Performance Metrics -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-card border border-border p-4 rounded-lg">
                            <div class="text-center">
                                <p class="text-xs font-medium text-muted-foreground mb-1">Shares</p>
                                <p class="text-lg font-light text-foreground">{{ number_format($holding->quantity, 2) }}</p>
                            </div>
                        </div>

                        <div class="bg-card border border-border p-4 rounded-lg">
                            <div class="text-center">
                                <p class="text-xs font-medium text-muted-foreground mb-1">Total Invested</p>
                                <p class="text-lg font-light text-foreground">${{ number_format($holding->total_invested, 2) }}</p>
                            </div>
                        </div>

                        <div class="bg-card border border-border p-4 rounded-lg">
                            <div class="text-center">
                                <p class="text-xs font-medium text-muted-foreground mb-1">Current Value</p>
                                <p class="text-lg font-light text-foreground">${{ number_format($holding->current_value, 2) }}</p>
                            </div>
                        </div>

                        <div class="bg-card border border-border p-4 rounded-lg">
                            <div class="text-center">
                                <p class="text-xs font-medium text-muted-foreground mb-1">Gain/Loss</p>
                                <p class="text-lg font-light {{ $holding->unrealized_gain_loss >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $holding->unrealized_gain_loss >= 0 ? '+' : '' }}${{ number_format($holding->unrealized_gain_loss, 2) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Performance Details -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-card border border-border p-4 rounded-lg">
                            <div class="text-center">
                                <p class="text-xs font-medium text-muted-foreground mb-1">Gain/Loss %</p>
                                <p class="text-lg font-light {{ $holding->unrealized_gain_loss_percentage >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $holding->unrealized_gain_loss_percentage >= 0 ? '+' : '' }}{{ number_format($holding->unrealized_gain_loss_percentage, 2) }}%
                                </p>
                            </div>
                        </div>

                        <div class="bg-card border border-border p-4 rounded-lg">
                            <div class="text-center">
                                <p class="text-xs font-medium text-muted-foreground mb-1">Average Cost</p>
                                <p class="text-lg font-light text-foreground">${{ number_format($holding->average_cost, 2) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Stock Information -->
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Stock Information</h3>
                        </div>
                        <div class="p-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Current Price</label>
                                    <p class="text-sm font-medium text-foreground">${{ number_format($holding->stock->current_price, 2) }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Previous Close</label>
                                    <p class="text-sm font-medium text-foreground">${{ number_format($holding->stock->previous_close, 2) }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Price Change</label>
                                    <p class="text-sm font-medium {{ $holding->stock->price_change >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $holding->stock->price_change >= 0 ? '+' : '' }}${{ number_format($holding->stock->price_change, 2) }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Price Change %</label>
                                    <p class="text-sm font-medium {{ $holding->stock->price_change_percentage >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $holding->stock->price_change_percentage >= 0 ? '+' : '' }}{{ number_format($holding->stock->price_change_percentage, 2) }}%
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Investor Information -->
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Investor Information</h3>
                        </div>
                        <div class="p-4">
                            <div class="flex items-center space-x-3 mb-4">
                                @if($holding->user->profile_image)
                                    <img src="{{ asset('storage/' . $holding->user->profile_image) }}" 
                                         alt="{{ $holding->user->name }}" 
                                         class="w-12 h-12 rounded-lg object-cover">
                                @else
                                    <div class="w-12 h-12 bg-gradient-to-br from-tesla-500 to-purple-600 rounded-lg flex items-center justify-center">
                                        <span class="text-white font-bold text-sm">{{ strtoupper(substr($holding->user->name, 0, 2)) }}</span>
                                    </div>
                                @endif
                                <div>
                                    <h4 class="text-sm font-medium text-foreground">{{ $holding->user->name }}</h4>
                                    <p class="text-xs text-muted-foreground dark:text-gray-300">{{ $holding->user->email }}</p>
                                </div>
                            </div>
                            
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Member Since</label>
                                    <p class="text-sm font-medium text-foreground">{{ $holding->user->created_at->format('M d, Y') }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Email Status</label>
                                    <p class="text-sm font-medium text-foreground">
                                        @if($holding->user->email_verified_at)
                                            <span class="text-green-600">Verified</span>
                                        @else
                                            <span class="text-red-600">Unverified</span>
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4">
                                <a href="{{ route('admin.users.show', $holding->user) }}" 
                                   class="w-full inline-flex items-center justify-center px-3 py-2 bg-black dark:bg-card text-white dark:text-foreground text-sm font-medium rounded-lg hover:opacity-90 transition-colors">
                                   <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                   </svg>
                                   View User Profile
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Holding Details -->
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Holding Details</h3>
                        </div>
                        <div class="p-4">
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Created</label>
                                    <p class="text-sm font-medium text-foreground">{{ $holding->created_at->format('M d, Y h:i A') }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Last Updated</label>
                                    <p class="text-sm font-medium text-foreground">{{ $holding->updated_at->format('M d, Y h:i A') }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Stock Symbol</label>
                                    <p class="text-sm font-medium text-foreground">{{ $holding->stock->symbol }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Sector</label>
                                    <p class="text-sm font-medium text-foreground">{{ $holding->stock->sector }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Industry</label>
                                    <p class="text-sm font-medium text-foreground">{{ $holding->stock->industry }}</p>
                                </div>
                            </div>

                            <div class="mt-4">
                                <a href="{{ route('admin.stocks.show', $holding->stock) }}" 
                                   class="w-full inline-flex items-center justify-center px-3 py-2 bg-muted text-muted-foreground text-sm font-medium rounded-lg hover:bg-muted transition-colors">
                                   <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                   </svg>
                                   View Stock Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
