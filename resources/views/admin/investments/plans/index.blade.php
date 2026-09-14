<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-light text-lg text-foreground leading-tight mr-4">
                    Investment Plans
                </h2>
            </div>
            <a href="{{ route('admin.investments.plans.create') }}" 
               class="px-4 py-2 bg-black dark:bg-card text-white dark:text-foreground text-sm font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-muted transition-colors">
                Add New Plan
            </a>
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
                            <p class="text-sm md:text-lg font-light text-foreground">{{ $stats['total'] }}</p>
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
                            <p class="text-sm md:text-lg font-light text-foreground">{{ $stats['active'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-card border border-border p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-8 h-8 md:w-10 md:h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 md:w-5 md:h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                            </svg>
                        </div>
                        <div class="ml-2 md:ml-3">
                            <p class="text-xs font-medium text-muted-foreground dark:text-gray-300">Featured Plans</p>
                            <p class="text-sm md:text-lg font-light text-foreground">{{ $stats['featured'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-card border border-border p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-8 h-8 md:w-10 md:h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 md:w-5 md:h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                        <div class="ml-2 md:ml-3">
                            <p class="text-xs font-medium text-muted-foreground dark:text-gray-300">Total Assets</p>
                            <p class="text-sm md:text-lg font-light text-foreground">${{ number_format($stats['total_assets'], 0) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            @if($plans->count() > 0)
            <!-- Investment Plans List -->
            <div class="bg-card border border-border overflow-hidden rounded-lg">
                <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-medium text-foreground">Investment Plans</h3>
                        <div class="flex items-center space-x-2">
                            <span class="text-xs text-muted-foreground dark:text-gray-300">{{ $plans->count() }} of {{ $plans->total() }} plans</span>
                        </div>
                    </div>
                </div>
                
                <div class="p-4">
                    <div class="space-y-4">
                        @foreach($plans as $plan)
                        <div class="bg-muted/40 dark:bg-dark-muted rounded-lg p-4 border border-border dark:border-gray-700 hover:bg-card hover:shadow-sm transition-all duration-200">
                            <!-- Mobile Layout -->
                            <div class="md:hidden">
                                <div class="flex items-start space-x-3 mb-3">
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
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-medium text-foreground truncate">{{ $plan->name }}</h4>
                                        <p class="text-xs text-muted-foreground truncate">{{ $plan->type_label }} • {{ $plan->category }}</p>
                                        <p class="text-xs text-muted-foreground mt-1">{{ $plan->formatted_nav }} • {{ $plan->formatted_minimum_investment }}</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium {{ $plan->risk_level_badge }}">
                                            {{ ucfirst($plan->risk_level) }}
                                        </span>
                                        <div class="flex items-center space-x-2 mt-1">
                                            @if($plan->is_active)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-green-100 text-green-800">
                                                    Active
                                                </span>
                                            @endif
                                            @if($plan->is_featured)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-yellow-100 text-yellow-800">
                                                    Featured
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Mobile Actions -->
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('admin.investments.plans.show', $plan) }}" 
                                       class="flex-1 px-3 py-2 bg-muted text-muted-foreground text-xs font-medium rounded text-center hover:bg-gray-200 transition-colors">
                                        View Details
                                    </a>
                                    <a href="{{ route('admin.investments.plans.edit', $plan) }}" 
                                       class="flex-1 px-3 py-2 bg-black dark:bg-card text-white dark:text-foreground text-xs font-medium rounded text-center hover:bg-gray-800 dark:hover:bg-muted transition-colors">
                                        Edit
                                    </a>
                                    <form method="POST" action="{{ route('admin.investments.plans.destroy', $plan) }}" class="flex-1" onsubmit="return confirm('Are you sure you want to delete this investment plan?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="w-full px-3 py-2 bg-red-100 text-red-700 text-xs font-medium rounded hover:bg-red-200 transition-colors">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Desktop Layout -->
                            <div class="hidden md:flex items-center justify-between">
                                <div class="flex items-center space-x-4">
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
                                        <p class="text-xs text-muted-foreground dark:text-gray-300">{{ $plan->type_label }} • {{ $plan->category }}</p>
                                        <p class="text-xs text-muted-foreground mt-1">{{ $plan->formatted_nav }} • {{ $plan->formatted_minimum_investment }}</p>
                                    </div>
                                </div>
                                
                                <div class="flex items-center space-x-3">
                                    <div class="text-right">
                                        <p class="text-xs text-muted-foreground dark:text-gray-300">{{ $plan->holdings_count }} holdings</p>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium {{ $plan->risk_level_badge }}">
                                            {{ ucfirst($plan->risk_level) }}
                                        </span>
                                    </div>
                                    
                                    <div class="flex space-x-2">
                                        <a href="{{ route('admin.investments.plans.show', $plan) }}" 
                                           class="px-3 py-1.5 bg-muted text-muted-foreground text-xs font-medium rounded hover:bg-gray-200 transition-colors">
                                            View
                                        </a>
                                        <a href="{{ route('admin.investments.plans.edit', $plan) }}" 
                                           class="px-3 py-1.5 bg-black dark:bg-card text-white dark:text-foreground text-xs font-medium rounded hover:bg-gray-800 dark:hover:bg-muted transition-colors">
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('admin.investments.plans.destroy', $plan) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this investment plan?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="px-3 py-1.5 bg-red-100 text-red-700 text-xs font-medium rounded hover:bg-red-200 transition-colors">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                
                <!-- Pagination -->
                @if($plans->hasPages())
                <div class="px-4 py-3 border-t border-border dark:border-gray-700">
                    {{ $plans->links() }}
                </div>
                @endif
            </div>
            @else
            <!-- Empty State -->
            <div class="bg-card border border-border p-8 rounded-lg text-center">
                <div class="w-16 h-16 bg-muted dark:bg-dark-muted rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-foreground dark:text-white mb-2">No investment plans found</h3>
                <p class="text-xs text-muted-foreground dark:text-gray-300 mb-4">Get started by creating your first investment plan.</p>
                <a href="{{ route('admin.investments.plans.create') }}" 
                   class="px-4 py-2 bg-black dark:bg-card text-white dark:text-foreground text-sm font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-muted transition-colors">
                    Create First Plan
                </a>
            </div>
            @endif
        </div>
    </div>
</x-admin-layout>
