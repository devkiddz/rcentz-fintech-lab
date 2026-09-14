<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-light text-lg text-foreground leading-tight mr-4">
                    NAV Updates
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <!-- Statistics Overview -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-card border border-border p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-8 h-8 md:w-10 md:h-10 bg-tesla-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 md:w-5 md:h-5 text-tesla-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <div class="ml-2 md:ml-3">
                            <p class="text-xs font-medium text-muted-foreground dark:text-gray-300">Total Plans</p>
                            <p class="text-sm md:text-lg font-light text-foreground">{{ $stats['total_plans'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-card border border-border p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-8 h-8 md:w-10 md:h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 md:w-5 md:h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-2 md:ml-3">
                            <p class="text-xs font-medium text-muted-foreground dark:text-gray-300">Active Plans</p>
                            <p class="text-sm md:text-lg font-light text-foreground">{{ $stats['active_plans'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-card border border-border p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-8 h-8 md:w-10 md:h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 md:w-5 md:h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-2 md:ml-3">
                            <p class="text-xs font-medium text-muted-foreground dark:text-gray-300">With Holdings</p>
                            <p class="text-sm md:text-lg font-light text-foreground">{{ $stats['plans_with_holdings'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-card border border-border p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-8 h-8 md:w-10 md:h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 md:w-5 md:h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                        <div class="ml-2 md:ml-3">
                            <p class="text-xs font-medium text-muted-foreground dark:text-gray-300">Total Holdings</p>
                            <p class="text-sm md:text-lg font-light text-foreground">{{ $stats['total_holdings'] }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- NAV Updates Form -->
            <div class="bg-card border border-border overflow-hidden rounded-lg mb-6">
                <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                    <h3 class="text-base font-medium text-foreground">Update NAV Values</h3>
                    <p class="text-xs text-muted-foreground mt-1">Update NAV for investment plans and automatically recalculate all holdings</p>
                </div>
                
                <form method="POST" action="{{ route('admin.investments.nav-updates.bulk-update') }}" class="p-4">
                    @csrf
                    <div class="space-y-4">
                        @foreach($plans as $plan)
                        <div class="border border-border dark:border-gray-700 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center space-x-3">
                                    @if($plan->image)
                                        <img src="{{ asset('storage/' . $plan->image) }}" 
                                             alt="{{ $plan->name }}" 
                                             class="w-10 h-10 rounded-lg object-cover">
                                    @else
                                        <div class="w-10 h-10 bg-gradient-to-br from-tesla-500 to-purple-600 rounded-lg flex items-center justify-center">
                                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                            </svg>
                                        </div>
                                    @endif
                                    <div>
                                        <h4 class="text-sm font-medium text-foreground">{{ $plan->name }}</h4>
                                        <p class="text-xs text-muted-foreground dark:text-gray-300">{{ ucfirst($plan->type) }} • {{ $plan->category }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-muted-foreground dark:text-gray-300">Current NAV</p>
                                    <p class="text-sm font-medium text-foreground">{{ $plan->formatted_nav }}</p>
                                    @if($plan->nav_change_percentage)
                                        <p class="text-xs {{ $plan->nav_change_color }}">{{ $plan->formatted_nav_change }}</p>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground mb-2">New NAV</label>
                                    <input type="number" 
                                           name="updates[{{ $plan->id }}][new_nav]" 
                                           step="0.0001" 
                                           value="{{ $plan->nav }}"
                                           class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-black focus:border-black transition-colors">
                                    <input type="hidden" name="updates[{{ $plan->id }}][plan_id]" value="{{ $plan->id }}">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground mb-2">Update Reason</label>
                                    <input type="text" 
                                           name="updates[{{ $plan->id }}][reason]" 
                                           placeholder="e.g., Market update, rebalancing"
                                           class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-black focus:border-black transition-colors">
                                </div>
                                <div class="flex items-end">
                                    <a href="{{ route('admin.investments.nav-updates.show', $plan) }}" 
                                       class="w-full px-3 py-2 bg-muted text-muted-foreground text-xs font-medium rounded-lg hover:bg-gray-200 transition-colors text-center">
                                        View History
                                    </a>
                                </div>
                            </div>
                            
                            @if($plan->holdings_count > 0)
                            <div class="mt-3 p-3 bg-tesla-50 rounded-lg">
                                <p class="text-xs text-tesla-700">
                                    <span class="font-medium">{{ $plan->holdings_count }}</span> holdings will be updated with new NAV
                                </p>
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="submit" 
                                class="px-4 py-2 bg-black dark:bg-card text-white dark:text-foreground text-sm font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-muted transition-colors">
                            Update All NAVs
                        </button>
                    </div>
                </form>
            </div>

            <!-- Individual Plan Updates -->
            <div class="bg-card border border-border overflow-hidden rounded-lg">
                <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                    <h3 class="text-base font-medium text-foreground">Investment Plans</h3>
                    <p class="text-xs text-muted-foreground mt-1">Individual plan details and quick updates</p>
                </div>
                
                <div class="p-4">
                    <div class="space-y-4">
                        @foreach($plans as $plan)
                        <div class="border border-border dark:border-gray-700 rounded-lg p-4 hover:bg-muted/40 transition-colors">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    @if($plan->image)
                                        <img src="{{ asset('storage/' . $plan->image) }}" 
                                             alt="{{ $plan->name }}" 
                                             class="w-12 h-12 rounded-lg object-cover">
                                    @else
                                        <div class="w-12 h-12 bg-gradient-to-br from-tesla-500 to-purple-600 rounded-lg flex items-center justify-center">
                                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                            </svg>
                                        </div>
                                    @endif
                                    <div>
                                        <h4 class="text-sm font-medium text-foreground">{{ $plan->name }}</h4>
                                        <p class="text-xs text-muted-foreground dark:text-gray-300">{{ ucfirst($plan->type) }} • {{ $plan->category }}</p>
                                        <p class="text-xs text-muted-foreground dark:text-gray-300">{{ $plan->holdings_count }} holdings</p>
                                    </div>
                                </div>
                                
                                <div class="flex items-center space-x-3">
                                    <div class="text-right">
                                        <p class="text-xs text-muted-foreground dark:text-gray-300">Current NAV</p>
                                        <p class="text-sm font-medium text-foreground">{{ $plan->formatted_nav }}</p>
                                        @if($plan->nav_change_percentage)
                                            <p class="text-xs {{ $plan->nav_change_color }}">{{ $plan->formatted_nav_change }}</p>
                                        @endif
                                        @if($plan->last_nav_update)
                                            <p class="text-xs text-muted-foreground dark:text-gray-300">Updated {{ $plan->last_nav_update->diffForHumans() }}</p>
                                        @endif
                                    </div>
                                    
                                    <div class="flex space-x-2">
                                        <a href="{{ route('admin.investments.nav-updates.show', $plan) }}" 
                                           class="px-3 py-1.5 bg-muted text-muted-foreground text-xs font-medium rounded hover:bg-gray-200 transition-colors">
                                            History
                                        </a>
                                        <a href="{{ route('admin.investments.plans.show', $plan) }}" 
                                           class="px-3 py-1.5 bg-black dark:bg-card text-white dark:text-foreground text-xs font-medium rounded hover:bg-gray-800 dark:hover:bg-muted transition-colors">
                                            View Plan
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
