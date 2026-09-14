<x-user-layout>
    <x-slot name="header">
        Search Results
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
                        <h1 class="text-xl font-light mb-1">Search Results</h1>
                        <p class="text-tesla-100 dark:text-gray-300 text-sm">Found {{ $stocks->total() }} stocks matching your criteria</p>
                    </div>
                    
                    <!-- Enhanced Stats Card -->
                    <div class="bg-card bg-opacity-15 backdrop-blur-xl rounded-xl p-4 border border-white border-opacity-20 shadow-xl lg:w-80">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <p class="text-xs text-tesla-100 dark:text-gray-300 mb-1">Results</p>
                                <p class="text-lg font-light">{{ $stocks->total() }}</p>
                            </div>
                            <div class="w-10 h-10 flex items-center justify-center">
                                <i data-lucide="search" class="w-5 h-5 text-white"></i>
                            </div>
                        </div>
                        <div class="flex space-x-4 text-xs">
                            <div class="flex-1 text-center">
                                <p class="text-tesla-100 dark:text-gray-300">Gainers</p>
                                <p class="text-white font-medium">{{ $stocks->where('change_percentage', '>', 0)->count() }}</p>
                            </div>
                            <div class="flex-1 text-center">
                                <p class="text-tesla-100 dark:text-gray-300">Losers</p>
                                <p class="text-white font-medium">{{ $stocks->where('change_percentage', '<', 0)->count() }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Filters -->
        <div class="bg-card rounded-xl p-4 shadow-sm border border-gray-100 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <!-- Search -->
                <div>
                    <label for="search" class="block text-xs font-medium text-foreground mb-1">Search</label>
                    <input type="text" id="search" name="search" value="{{ request('search') }}" placeholder="Symbol or company name" class="w-full px-3 py-2 border border-border dark:text-white rounded-lg focus:ring-2 focus:ring-ring focus:border-transparent transition-colors duration-200">
                </div>

                <!-- Sector Filter -->
                <div>
                    <label for="sector" class="block text-xs font-medium text-foreground mb-1">Sector</label>
                    <select id="sector" name="sector" class="w-full px-3 py-2 border border-border dark:text-white rounded-lg focus:ring-2 focus:ring-ring focus:border-transparent transition-colors duration-200">
                        <option value="">All Sectors</option>
                        @foreach($sectors as $sector)
                            <option value="{{ $sector }}" {{ request('sector') == $sector ? 'selected' : '' }}>{{ $sector }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Industry Filter -->
                <div>
                    <label for="industry" class="block text-xs font-medium text-foreground mb-1">Industry</label>
                    <select id="industry" name="industry" class="w-full px-3 py-2 border border-border dark:text-white rounded-lg focus:ring-2 focus:ring-ring focus:border-transparent transition-colors duration-200">
                        <option value="">All Industries</option>
                        @foreach($industries as $industry)
                            <option value="{{ $industry }}" {{ request('industry') == $industry ? 'selected' : '' }}>{{ $industry }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Sort -->
                <div>
                    <label for="sort" class="block text-xs font-medium text-foreground mb-1">Sort By</label>
                    <select id="sort" name="sort" class="w-full px-3 py-2 border border-border dark:text-white rounded-lg focus:ring-2 focus:ring-ring focus:border-transparent transition-colors duration-200">
                        <option value="symbol" {{ request('sort') == 'symbol' ? 'selected' : '' }}>Symbol</option>
                        <option value="price" {{ request('sort') == 'price' ? 'selected' : '' }}>Price</option>
                        <option value="change" {{ request('sort') == 'change' ? 'selected' : '' }}>Change</option>
                        <option value="volume" {{ request('sort') == 'volume' ? 'selected' : '' }}>Volume</option>
                        <option value="market_cap" {{ request('sort') == 'market_cap' ? 'selected' : '' }}>Market Cap</option>
                    </select>
                </div>

                <!-- Actions -->
                <div class="flex items-end space-x-2">
                    <button type="submit" class="px-4 py-2 bg-foreground text-background text-xs font-medium rounded-lg hover:opacity-90 transition-colors duration-200">
                        <i data-lucide="search" class="w-3 h-3 mr-1"></i>
                        Search
                    </button>
                    <a href="{{ route('stocks.index') }}" class="px-4 py-2 border border-border text-foreground text-xs font-medium rounded-lg hover:bg-muted/30 dark:hover:bg-gray-800 transition-colors duration-200">
                        <i data-lucide="refresh-cw" class="w-3 h-3 mr-1"></i>
                        Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Search Results -->
        <div class="bg-card rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-border">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-light text-foreground mb-1">Search Results</h3>
                        <p class="text-xs text-muted-foreground dark:text-gray-300">{{ $stocks->total() }} stocks found</p>
                    </div>
                </div>
            </div>
            
            @if($stocks->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-muted/30">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Stock</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Change</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Volume</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Market Cap</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-card divide-y divide-gray-100 dark:divide-tesla-600">
                            @foreach($stocks as $stock)
                            <tr class="hover:bg-muted/30 transition-colors duration-200">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 w-8 h-8 rounded-lg overflow-hidden">
                                            @if($stock->logo_url)
                                                <img src="{{ $stock->logo_url }}" alt="{{ $stock->company_name }}" class="w-8 h-8 object-cover">
                                            @else
                                                <div class="w-8 h-8 bg-muted rounded-lg flex items-center justify-center">
                                                    <i data-lucide="bar-chart-3" class="w-4 h-4 text-muted-foreground"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-foreground">{{ $stock->symbol }}</div>
                                            <div class="text-xs text-muted-foreground dark:text-gray-300">{{ $stock->company_name }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-foreground">{{ $stock->formatted_current_price }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm {{ $stock->change_color }}">
                                        {{ $stock->formatted_change_amount }}
                                    </div>
                                    <div class="text-xs {{ $stock->change_color }}">
                                        {{ $stock->formatted_change_percentage }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-foreground">{{ $stock->formatted_volume }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-foreground">{{ $stock->formatted_market_cap }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex space-x-2">
                                        <a href="{{ route('stocks.show', $stock) }}" class="text-xs text-tesla-600 hover:text-tesla-800 transition-colors duration-200">
                                            View
                                        </a>
                                        <a href="{{ route('trading.buy', $stock) }}" class="text-xs text-green-600 hover:text-green-800 transition-colors duration-200">
                                            Buy
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-border">
                    {{ $stocks->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-muted rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="search" class="w-8 h-8 text-gray-400 dark:text-gray-300"></i>
                    </div>
                    <h3 class="text-lg font-light text-foreground mb-2">No stocks found</h3>
                    <p class="text-muted-foreground text-sm mb-4">Try adjusting your search criteria</p>
                    <a href="{{ route('stocks.index') }}" class="inline-flex items-center px-4 py-2 bg-foreground text-background text-sm font-medium rounded-lg hover:opacity-90 transition-colors duration-200">
                        <i data-lucide="refresh-cw" class="w-4 h-4 mr-2"></i>
                        Browse All Stocks
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-user-layout> 
