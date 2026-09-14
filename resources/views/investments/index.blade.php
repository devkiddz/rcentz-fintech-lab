<x-user-layout>
    <x-slot name="header">
        Investment Plans
    </x-slot>

    <div class="max-w-7xl mx-auto">
        <!-- Enhanced Header -->
        <div class="bg-gradient-to-br from-tesla-600 via-tesla-700 to-tesla-800 dark:from-tesla-700 dark:via-tesla-800 dark:to-tesla-900 rounded-2xl p-6 mb-6 text-white relative overflow-hidden">
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-0 right-0 w-48 h-48 bg-white rounded-full -translate-y-24 translate-x-24"></div>
                <div class="absolute bottom-0 left-0 w-24 h-24 bg-white rounded-full translate-y-12 -translate-x-12"></div>
            </div>
            
            <div class="relative z-10">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                    <div class="mb-4 lg:mb-0 lg:flex-1">
                        <h1 class="text-xl font-light mb-1">Investment Plans</h1>
                        <p class="text-tesla-100 dark:text-gray-300 text-sm">Discover and invest in diversified portfolios designed for growth</p>
                    </div>
                    
                    <!-- Enhanced Stats Card -->
                    <div class="bg-white bg-opacity-15 backdrop-blur-xl rounded-xl p-4 border border-white border-opacity-20 shadow-xl lg:w-80">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <p class="text-xs text-tesla-100 dark:text-gray-300 mb-1">Available Plans</p>
                                <p class="text-lg font-light">{{ $plans->total() }}</p>
                            </div>
                            <div class="w-10 h-10 flex items-center justify-center">
                                <i data-lucide="trending-up" class="w-5 h-5 text-white"></i>
                            </div>
                        </div>
                        <div class="flex space-x-4 text-xs">
                            <div class="flex-1 text-center">
                                <p class="text-tesla-100 dark:text-gray-300">Categories</p>
                                <p class="text-white font-medium">{{ $categories->count() }}</p>
                            </div>
                            <div class="flex-1 text-center">
                                <p class="text-tesla-100 dark:text-gray-300">Featured</p>
                                <p class="text-white font-medium">{{ $featuredPlans->total() }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Collapsible Search and Filters -->
        <div class="bg-tesla-50 dark:bg-dark-card rounded-xl shadow-sm border border-gray-100 mb-6">
            <div class="p-4">
                <button type="button" 
                        onclick="toggleFilters()" 
                        class="flex items-center justify-between w-full text-left">
                    <div class="flex items-center">
                        <i data-lucide="filter" class="w-4 h-4 text-gray-600 mr-2"></i>
                        <span class="text-sm font-medium text-black">Search & Filters</span>
                    </div>
                    <i data-lucide="chevron-down" id="filterIcon" class="w-4 h-4 text-gray-600 transition-transform duration-200"></i>
                </button>
            </div>
            
            <div id="filterSection" class="hidden border-t border-border">
                <form method="GET" class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Search -->
                        <div class="md:col-span-2">
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Search Plans</label>
                            <div class="relative">
                                <i data-lucide="search" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400 dark:text-gray-300"></i>
                                <input type="text" 
                                       id="search" 
                                       name="search" 
                                       value="{{ request('search') }}"
                                       class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition-colors duration-200"
                                       placeholder="Search by name, category, or description">
                            </div>
                        </div>

                        <!-- Category Filter -->
                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                            <select id="category" name="category" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition-colors duration-200">
                                <option value="">All Categories</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category }}" {{ request('category') == $category ? 'selected' : '' }}>{{ $category }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Risk Level Filter -->
                        <div>
                            <label for="risk_level" class="block text-sm font-medium text-gray-700 mb-2">Risk Level</label>
                            <select id="risk_level" name="risk_level" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition-colors duration-200">
                                <option value="">All Risk Levels</option>
                                <option value="conservative" {{ request('risk_level') == 'conservative' ? 'selected' : '' }}>Conservative</option>
                                <option value="moderate" {{ request('risk_level') == 'moderate' ? 'selected' : '' }}>Moderate</option>
                                <option value="aggressive" {{ request('risk_level') == 'aggressive' ? 'selected' : '' }}>Aggressive</option>
                            </select>
                        </div>

                        <!-- Sort By -->
                        <div>
                            <label for="sort" class="block text-sm font-medium text-gray-700 mb-2">Sort By</label>
                            <select id="sort" name="sort" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition-colors duration-200">
                                <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>Name A-Z</option>
                                <option value="performance" {{ request('sort') == 'performance' ? 'selected' : '' }}>Best Performance</option>
                                <option value="nav" {{ request('sort') == 'nav' ? 'selected' : '' }}>Lowest NAV</option>
                                <option value="risk" {{ request('sort') == 'risk' ? 'selected' : '' }}>Risk Level</option>
                            </select>
                        </div>

                        <!-- Filter Buttons -->
                        <div class="md:col-span-4 flex space-x-3">
                            <button type="submit" class="px-4 py-2 bg-black dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-gray-100 transition-colors duration-200 flex items-center">
                                <i data-lucide="filter" class="w-4 h-4 mr-2"></i>
                                Apply Filters
                            </button>
                            <a href="{{ route('investments.index') }}" class="px-4 py-2 border border-gray-300 text-black dark:text-white text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors duration-200 flex items-center">
                                <i data-lucide="refresh-cw" class="w-4 h-4 mr-2"></i>
                                Clear Filters
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Enhanced Featured Plans -->
        @if($featuredPlans->count() > 0)
        <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-light text-black">Featured Plans</h2>
                <a href="#" class="text-xs text-black hover:text-gray-600 transition-colors duration-200 font-medium">
                    View All Featured
                    <i data-lucide="arrow-right" class="w-3 h-3 inline ml-1"></i>
                </a>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($featuredPlans as $plan)
                <div class="group bg-tesla-50 dark:bg-dark-card rounded-xl p-5 shadow-sm border border-gray-100 hover:shadow-lg transition-all duration-300 hover:scale-105 relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br from-tesla-50 to-tesla-100 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <div class="relative z-10">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <h3 class="font-medium text-black text-lg mb-1">{{ $plan->name }}</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-300">{{ $plan->category }}</p>
                            </div>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $plan->risk_level_badge }}">
                                {{ ucfirst($plan->risk_level) }}
                            </span>
                        </div>
                        
                        <div class="space-y-3 mb-4">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500 dark:text-gray-300">Current NAV:</span>
                                <span class="text-sm font-medium text-black">{{ currency_symbol() }}{{ number_format($plan->nav, 4) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500 dark:text-gray-300">1Y Return:</span>
                                <span class="text-sm font-medium {{ $plan->nav_change_percentage >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $plan->nav_change_percentage >= 0 ? '+' : '' }}{{ number_format($plan->nav_change_percentage, 2) }}%
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500 dark:text-gray-300">Min Investment:</span>
                                <span class="text-sm font-medium text-black">{{ currency_symbol() }}{{ number_format($plan->minimum_investment, 2) }}</span>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <a href="{{ route('investments.show', $plan) }}" class="flex items-center text-tesla-600 text-xs font-medium hover:text-tesla-800 transition-colors duration-200">
                                <span>View Details</span>
                                <i data-lucide="arrow-right" class="w-3 h-3 ml-1 group-hover:translate-x-1 transition-transform duration-200"></i>
                            </a>
                            <a href="{{ route('investments.buy', $plan) }}" class="px-3 py-1 bg-black dark:bg-white text-white dark:text-gray-900 text-xs font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-gray-100 transition-colors duration-200">
                                Invest Now
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            
            <!-- Featured Plans Pagination -->
            @if($featuredPlans->hasPages())
            <div class="mt-6">
                {{ $featuredPlans->links() }}
            </div>
            @endif
        </div>
        @endif

        <!-- Enhanced All Plans -->
        <div class="bg-tesla-50 dark:bg-dark-card rounded-xl p-5 shadow-sm border border-border">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-light text-black dark:text-white mb-1">All Investment Plans</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-300">Showing {{ $plans->count() }} of {{ $plans->total() }} plans</p>
                </div>
            </div>
            
            @if($plans->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($plans as $plan)
                    <div class="group bg-muted rounded-lg p-4 hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-300">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex-1">
                                <h4 class="font-medium text-black dark:text-white text-sm mb-1">{{ $plan->name }}</h4>
                                <p class="text-xs text-gray-600 dark:text-gray-300">{{ $plan->category }}</p>
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $plan->risk_level_badge }}">
                                {{ ucfirst($plan->risk_level) }}
                            </span>
                        </div>
                        
                        <div class="space-y-2 mb-3">
                            <div class="flex justify-between">
                                <span class="text-xs text-gray-500 dark:text-gray-300">NAV:</span>
                                <span class="text-xs font-medium text-black">{{ currency_symbol() }}{{ number_format($plan->nav, 4) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-xs text-gray-500 dark:text-gray-300">Return:</span>
                                <span class="text-xs font-medium {{ $plan->nav_change_percentage >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $plan->nav_change_percentage >= 0 ? '+' : '' }}{{ number_format($plan->nav_change_percentage, 2) }}%
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-xs text-gray-500 dark:text-gray-300">Min:</span>
                                <span class="text-xs font-medium text-black">{{ currency_symbol() }}{{ number_format($plan->minimum_investment, 0) }}</span>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <a href="{{ route('investments.show', $plan) }}" class="text-xs text-tesla-600 hover:text-tesla-800 transition-colors duration-200">
                                View Details
                            </a>
                            <a href="{{ route('investments.buy', $plan) }}" class="px-2 py-1 bg-black dark:bg-white text-white dark:text-gray-900 text-xs font-medium rounded transition-colors duration-200 hover:bg-gray-800">
                                Invest
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $plans->links() }}
                </div>
            @else
                <div class="text-center py-8">
                    <div class="w-12 h-12 bg-muted rounded-full flex items-center justify-center mx-auto mb-3">
                        <i data-lucide="trending-up" class="w-6 h-6 text-gray-400 dark:text-gray-300"></i>
                    </div>
                    <h3 class="text-base font-light text-black dark:text-white mb-1">No investment plans found</h3>
                    <p class="text-gray-600 text-xs mb-3">Try adjusting your filters or check back later</p>
                    <a href="{{ route('investments.index') }}" class="inline-flex items-center px-4 py-2 bg-black dark:bg-white text-white dark:text-gray-900 text-xs font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-gray-100 transition-colors duration-200">
                        <i data-lucide="refresh-cw" class="w-3 h-3 mr-1"></i>
                        Clear Filters
                    </a>
                </div>
            @endif
        </div>
    </div>

    <script>
        function toggleFilters() {
            const filterSection = document.getElementById('filterSection');
            const filterIcon = document.getElementById('filterIcon');
            
            if (filterSection.classList.contains('hidden')) {
                filterSection.classList.remove('hidden');
                filterIcon.style.transform = 'rotate(180deg)';
            } else {
                filterSection.classList.add('hidden');
                filterIcon.style.transform = 'rotate(0deg)';
            }
        }
    </script>
</x-user-layout> 
