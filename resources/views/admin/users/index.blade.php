<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-6">
                <h2 class="font-light text-lg text-foreground leading-tight mr-4">
                    {{ __('Users') }}
                </h2>
                <a href="{{ route('admin.about') }}" class="inline-flex items-center px-3 py-1 bg-muted text-muted-foreground text-xs font-medium rounded hover:bg-muted transition-all duration-200">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 20h.01"></path>
                    </svg>
                    About Hyipcoders
                </a>
            </div>
            <a href="{{ route('admin.users.create') }}" 
               class="inline-flex items-center px-4 py-2 bg-black dark:bg-card text-white dark:text-foreground text-sm font-medium rounded-lg hover:opacity-90 transition-all duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Add New
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            @if($users->count() > 0)
            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                <div class="bg-card border border-border p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-tesla-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-tesla-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-xs font-medium text-muted-foreground dark:text-gray-300">Total Users</p>
                            <p class="text-lg font-light text-foreground">{{ $users->total() }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-card border border-border p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-xs font-medium text-muted-foreground dark:text-gray-300">Admins</p>
                            <p class="text-lg font-light text-foreground">{{ $users->where('is_admin', true)->count() }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-card border border-border p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-xs font-medium text-muted-foreground dark:text-gray-300">Total Revenue</p>
                            <p class="text-lg font-light text-foreground">${{ number_format($totalRevenue, 0) }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-card border border-border p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-tesla-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-tesla-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-xs font-medium text-muted-foreground dark:text-gray-300">Wallet Balance</p>
                            <p class="text-lg font-light text-foreground">${{ number_format($totalWalletBalance, 0) }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-card border border-border p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-xs font-medium text-muted-foreground dark:text-gray-300">Investments</p>
                            <p class="text-lg font-light text-foreground">${{ number_format($totalInvestmentValue, 0) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users Grid -->
            <div class="bg-card border border-border overflow-hidden rounded-lg">
                <div class="px-4 py-3 border-b border-border dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-medium text-foreground">Customer Directory</h3>
                        <div class="flex items-center space-x-2">
                            <span class="text-xs text-muted-foreground dark:text-gray-300">{{ $users->count() }} of {{ $users->total() }} users</span>
                        </div>
                    </div>
                </div>
                
                <div class="p-4">
                    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4">
                        @foreach($users as $user)
                        <div class="group bg-muted/40 rounded-lg p-4 hover:bg-card hover:shadow-lg transition-all duration-200 border border-transparent hover:border-border dark:border-gray-700">
                            <!-- User Avatar and Info -->
                            <div class="flex items-start space-x-3 mb-3">
                                @if($user->profile_image)
                                    <img src="{{ asset('storage/' . $user->profile_image) }}" 
                                         alt="{{ $user->name }}" 
                                         class="w-12 h-12 rounded-lg object-cover flex-shrink-0">
                                @else
                                    <div class="w-12 h-12 bg-gradient-to-br from-tesla-500 to-purple-600 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <span class="text-white font-bold text-sm">{{ strtoupper(substr($user->name, 0, 2)) }}</span>
                                    </div>
                                @endif
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-1">
                                        <h4 class="text-sm font-medium text-foreground truncate">{{ $user->name }}</h4>
                                        @if($user->is_admin)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-purple-100 text-purple-800">
                                                Admin
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-muted-foreground truncate">{{ $user->email }}</p>
                                    <p class="text-xs text-gray-400 mt-1">Member since {{ $user->created_at->format('M d, Y') }}</p>
                                </div>
                            </div>

                            <!-- User Stats -->
                            <div class="grid grid-cols-2 gap-3 mb-3">
                                <div class="text-center">
                                    <p class="text-xs text-muted-foreground dark:text-gray-300">Car Purchases</p>
                                    <p class="text-sm font-medium text-foreground">{{ $user->purchases->count() }}</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs text-muted-foreground dark:text-gray-300">Total Spent</p>
                                    <p class="text-sm font-medium text-green-600">${{ number_format($user->purchases->where('status', 'completed')->sum('amount'), 0) }}</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs text-muted-foreground dark:text-gray-300">Wallet Balance</p>
                                    <p class="text-sm font-medium text-tesla-600">${{ number_format($user->wallet ? $user->wallet->balance : 0, 0) }}</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs text-muted-foreground dark:text-gray-300">Investments</p>
                                    <p class="text-sm font-medium text-orange-600">${{ number_format($user->investmentHoldings->sum('current_value'), 0) }}</p>
                                </div>
                            </div>

                            <!-- Recent Activity -->
                            <div class="mb-3">
                                <p class="text-xs text-muted-foreground dark:text-gray-300 mb-2">Recent Activity</p>
                                <div class="space-y-1">
                                    @if($user->purchases->count() > 0)
                                        <div class="flex items-center space-x-2">
                                            <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                            <span class="text-xs text-muted-foreground dark:text-gray-300">Latest car purchase: {{ $user->purchases->first()->purchased_at->format('M d') }}</span>
                                        </div>
                                    @endif
                                    @if($user->investmentHoldings->count() > 0)
                                        <div class="flex items-center space-x-2">
                                            <div class="w-2 h-2 bg-orange-500 rounded-full"></div>
                                            <span class="text-xs text-muted-foreground dark:text-gray-300">{{ $user->investmentHoldings->count() }} investment holdings</span>
                                        </div>
                                    @endif
                                    @if($user->stockHoldings->count() > 0)
                                        <div class="flex items-center space-x-2">
                                            <div class="w-2 h-2 bg-purple-500 rounded-full"></div>
                                            <span class="text-xs text-muted-foreground dark:text-gray-300">{{ $user->stockHoldings->count() }} stock holdings</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex space-x-2">
                                <a href="{{ route('admin.users.show', $user) }}" class="flex-1 text-center px-3 py-2 bg-muted text-muted-foreground text-xs font-medium rounded hover:bg-muted transition-colors">
                                    View
                                </a>
                                <a href="{{ route('admin.users.edit', $user) }}" class="flex-1 text-center px-3 py-2 bg-tesla-100 text-tesla-700 text-xs font-medium rounded hover:bg-tesla-200 transition-colors">
                                    Edit
                                </a>
                                @if(!$user->is_admin)
                                    <a href="{{ route('impersonate', $user->id) }}" class="flex-1 text-center px-3 py-2 bg-green-100 text-green-700 text-xs font-medium rounded hover:bg-green-200 transition-colors">
                                        Login as User
                                    </a>
                                    @if($user->purchases->count() === 0)
                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="flex-1">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')" class="w-full text-center px-3 py-2 bg-red-100 text-red-700 text-xs font-medium rounded hover:bg-red-200 transition-colors">
                                                Delete
                                            </button>
                                        </form>
                                    @endif
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                
                <!-- Pagination -->
                @if($users->hasPages())
                <div class="px-4 py-3 border-t border-border dark:border-gray-700">
                    {{ $users->links() }}
                </div>
                @endif
            </div>
            @else
            <!-- Empty State -->
            <div class="bg-card border border-border p-8 rounded-lg text-center">
                <div class="w-16 h-16 bg-muted rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-foreground dark:text-white mb-2">No users found</h3>
                <p class="text-xs text-muted-foreground dark:text-gray-300 mb-6">No users have been registered yet.</p>
                <a href="{{ route('admin.users.create') }}" 
                   class="inline-flex items-center px-4 py-2 bg-black dark:bg-card text-white dark:text-foreground text-sm font-medium rounded-lg hover:opacity-90 transition-all duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Add First User
                </a>
            </div>
            @endif
        </div>
    </div>
</x-admin-layout> 
