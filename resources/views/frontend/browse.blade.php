@extends('layouts.main')

@section('title', 'Shop Vehicles')

@section('content')
<div class="min-h-screen bg-white dark:bg-dark-bg">
    <!-- Hero Header (revamped) -->
    <header class="relative overflow-hidden bg-gradient-to-br from-black via-gray-900 to-black text-white">
        <svg class="absolute inset-0 opacity-[0.06] z-0 pointer-events-none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none" aria-hidden="true" focusable="false">
            <defs>
                <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                    <path d="M 40 0 L 0 0 0 40" fill="none" stroke="white" stroke-width="1" />
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#grid)" />
        </svg>

        <div class="absolute inset-0 z-0 pointer-events-none" aria-hidden="true">
            <img src="{{ asset('images/tesla-model-y.jpg') }}" alt="" class="h-full w-full object-cover opacity-20" loading="eager" decoding="async" />
            <div class="absolute inset-0 bg-gradient-to-b from-black/80 via-black/70 to-black/90"></div>
        </div>

        <div class="relative z-10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-14 lg:py-20 text-center">
                <h1 class="text-4xl font-semibold tracking-tight sm:text-5xl">Browse Inventory</h1>
                <p class="mt-4 max-w-3xl mx-auto text-base text-tesla-100 dark:text-gray-300 sm:text-lg">
                    Explore premium electric vehicles ready for immediate delivery.
                </p>
            </div>
        </div>

        <div class="absolute bottom-0 left-0 right-0 translate-y-[1px]" aria-hidden="true">
            <svg viewBox="0 0 1440 80" xmlns="http://www.w3.org/2000/svg"><path fill="#ffffff" fill-opacity="1" d="M0,64L48,69.3C96,75,192,85,288,85.3C384,85,480,75,576,53.3C672,32,768,0,864,0C960,0,1056,32,1152,53.3C1248,75,1344,85,1392,90.7L1440,96L1440,160L1392,160C1344,160,1248,160,1152,160C1050,160,960,160,864,160C768,160,672,160,576,160C480,160,384,160,288,160C192,160,96,160,48,160L0,160Z"></path></svg>
        </div>
    </header>

    <!-- Filter and Results Section -->
    <section class="py-12 lg:py-16">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Mobile Filter Toggle -->
            <div class="lg:hidden mb-6">
                <button id="mobile-filter-toggle" 
                        class="w-full flex items-center justify-between bg-tesla-50 border border-tesla-200 rounded-lg px-4 py-3 text-tesla-900 font-medium">
                    <span class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.414A1 1 0 013 6.707V4z"/>
                        </svg>
                        Filters & Sort
                    </span>
                    <svg class="w-5 h-5 transform transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
            </div>

            <div class="flex flex-col lg:flex-row gap-8">
                <!-- Filter Sidebar -->
                <div class="lg:w-1/4">
                    <div id="filter-sidebar" class="bg-card border border-border rounded-2xl p-6 shadow lg:sticky lg:top-24 hidden lg:block">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-xl font-medium text-black dark:text-white">Filters</h2>
                            <button id="close-mobile-filters" class="lg:hidden p-2 text-tesla-500 hover:text-tesla-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        
                        <form action="{{ route('cars.browse') }}" method="GET" class="space-y-6" id="filter-form">
                            <!-- Quick Filters -->
                            <div>
                                <h3 class="text-sm font-medium text-gray-700 dark:text-dark-text mb-3">Quick Filters</h3>
                                <div class="space-y-2">
                                    <label class="flex items-center group cursor-pointer">
                                        <input type="checkbox" name="available" value="true" 
                                               class="w-4 h-4 text-black dark:text-dark-accent border-gray-300 dark:border-gray-600 rounded focus:ring-black focus:ring-2" 
                                               {{ request('available') == 'true' ? 'checked' : '' }}>
                                        <span class="ml-3 text-sm text-gray-600 dark:text-dark-text group-hover:text-black dark:group-hover:text-white transition-colors duration-200">Available Only</span>
                                    </label>
                                    <label class="flex items-center group cursor-pointer">
                                        <input type="checkbox" name="featured" value="true" 
                                               class="w-4 h-4 text-black dark:text-dark-accent border-gray-300 dark:border-gray-600 rounded focus:ring-black focus:ring-2" 
                                               {{ request('featured') == 'true' ? 'checked' : '' }}>
                                        <span class="ml-3 text-sm text-gray-600 dark:text-dark-text group-hover:text-black dark:group-hover:text-white transition-colors duration-200">Featured Vehicles</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Make Filter -->
                            <div>
                                <h3 class="text-sm font-medium text-gray-700 dark:text-dark-text mb-3">Make</h3>
                                <select name="make" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-dark-card dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-black focus:border-black transition-colors duration-200">
                                    <option value="">All Makes</option>
                                    @foreach($makes as $make)
                                        <option value="{{ $make }}" {{ request('make') == $make ? 'selected' : '' }}>
                                            {{ $make }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Model Filter -->
                            <div>
                                <h3 class="text-sm font-medium text-gray-700 dark:text-dark-text mb-3">Model</h3>
                                <select name="model" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-dark-card dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-black focus:border-black transition-colors duration-200">
                                    <option value="">All Models</option>
                                    @foreach($models as $model)
                                        <option value="{{ $model }}" {{ request('model') == $model ? 'selected' : '' }}>
                                            {{ $model }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Year Filter -->
                            <div>
                                <h3 class="text-sm font-medium text-gray-700 dark:text-dark-text mb-3">Year</h3>
                                <select name="year" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-dark-card dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-black focus:border-black transition-colors duration-200">
                                    <option value="">All Years</option>
                                    @foreach($years as $year)
                                        <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>
                                            {{ $year }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Price Range -->
                            <div>
                                <h3 class="text-sm font-medium text-gray-700 dark:text-dark-text mb-3">Price Range</h3>
                                <select name="price_range" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-dark-card dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-black focus:border-black transition-colors duration-200">
                                    <option value="">All Prices</option>
                                    <option value="0-50000" {{ request('price_range') == '0-50000' ? 'selected' : '' }}>Under {{ currency_symbol() }}50,000</option>
                                    <option value="50000-75000" {{ request('price_range') == '50000-75000' ? 'selected' : '' }}>{{ currency_symbol() }}50,000 - {{ currency_symbol() }}75,000</option>
                                    <option value="75000-100000" {{ request('price_range') == '75000-100000' ? 'selected' : '' }}>{{ currency_symbol() }}75,000 - {{ currency_symbol() }}100,000</option>
                                    <option value="100000-150000" {{ request('price_range') == '100000-150000' ? 'selected' : '' }}>{{ currency_symbol() }}100,000 - {{ currency_symbol() }}150,000</option>
                                    <option value="150000+" {{ request('price_range') == '150000+' ? 'selected' : '' }}>{{ currency_symbol() }}150,000+</option>
                                </select>
                            </div>

                            <!-- Sort By -->
                            <div>
                                <h3 class="text-sm font-medium text-gray-700 dark:text-dark-text mb-3">Sort By</h3>
                                <select name="sort" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-dark-card dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-black focus:border-black transition-colors duration-200">
                                    <option value="newest" {{ $sort == 'newest' ? 'selected' : '' }}>Newest First</option>
                                    <option value="oldest" {{ $sort == 'oldest' ? 'selected' : '' }}>Oldest First</option>
                                    <option value="price_low" {{ $sort == 'price_low' ? 'selected' : '' }}>Price: Low to High</option>
                                    <option value="price_high" {{ $sort == 'price_high' ? 'selected' : '' }}>Price: High to Low</option>
                                </select>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex flex-col space-y-3 pt-6 border-t border-tesla-200">
                                <button type="submit" class="w-full text-white py-3 px-4 rounded-lg font-medium transition-all duration-200" style="background-color: #C8102E;" onmouseover="this.style.backgroundColor='#A00D25'" onmouseout="this.style.backgroundColor='#C8102E'">
                                    Apply Filters
                                </button>
                                <a href="{{ route('cars.browse') }}" class="w-full border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-dark-text hover:bg-gray-50 dark:hover:bg-gray-700 py-3 px-4 rounded-lg font-medium text-center transition-all duration-200">
                                    Reset All
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Results Section -->
                <div class="lg:w-3/4">
                    <!-- Results Header -->
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8">
                        <div>
                            <h2 class="text-2xl font-medium text-tesla-900 dark:text-white mb-2">Available Vehicles</h2>
                            <p class="text-tesla-600 dark:text-gray-300">
                                Showing {{ $cars->firstItem() ?? 0 }} - {{ $cars->lastItem() ?? 0 }} of {{ $cars->total() }} vehicles
                            </p>
                        </div>
                        
                        <!-- View Toggle -->
                        <div class="flex items-center space-x-2 mt-4 sm:mt-0">
                            <span class="text-sm text-tesla-600">View:</span>
                            <button id="grid-view" class="p-2 text-tesla-500 hover:text-tesla-900 hover:bg-tesla-100 rounded-lg transition-colors duration-200 view-toggle active">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                                </svg>
                            </button>
                            <button id="list-view" class="p-2 text-tesla-500 hover:text-tesla-900 hover:bg-tesla-100 rounded-lg transition-colors duration-200 view-toggle">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    @if($cars->count() > 0)
                        <!-- Cars Grid (revamped cards) -->
                        <div id="cars-container" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 lg:gap-8">
                            @foreach($cars as $car)
                                <article class="group rounded-xl border border-border bg-card shadow-sm transition hover:shadow-md focus-within:ring-2 focus-within:ring-black/40">
                                    <a href="{{ route('cars.show', $car->id) }}" class="block overflow-hidden rounded-t-xl">
                                        <div class="relative aspect-[16/9]">
                                            @if($car->first_image)
                                                <img
                                                    src="{{ strpos($car->first_image, 'http') === 0 ? $car->first_image : asset('storage/' . $car->first_image) }}"
                                                    alt="{{ $car->title }}"
                                                    class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                                    loading="lazy"
                                                    decoding="async"
                                                />
                                            @else
                                                <div class="flex h-full w-full items-center justify-center bg-gray-100">
                                                    <div class="text-center">
                                                        <svg class="mx-auto mb-2 h-12 w-12 text-gray-400 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                                        </svg>
                                                        <p class="text-sm text-gray-500 dark:text-gray-300">{{ $car->title }}</p>
                                                    </div>
                                                </div>
                                            @endif

                                            @if(!$car->is_available)
                                                <div class="absolute right-3 top-3 rounded-full bg-red-600 px-2.5 py-1 text-[11px] font-medium text-white">Sold Out</div>
                                            @endif
                                        </div>
                                    </a>

                                    <div class="p-4">
                                        <h3 class="mb-1 text-base font-medium text-black dark:text-white group-hover:text-black/80 dark:group-hover:text-tesla-100 sm:text-lg">
                                            <a href="{{ route('cars.show', $car->id) }}" class="focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-black/40">
                                                {{ $car->title }}
                                            </a>
                                        </h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ $car->year }} {{ $car->make }} {{ $car->model }}</p>

                                        <div class="mt-3 mb-3 flex items-center gap-6 text-xs text-gray-600 dark:text-gray-300">
                                            <div>
                                                <span class="font-medium">{{ $car->engine_range ?? '410' }}mi</span>
                                                <span class="block text-[10px]">Range</span>
                                            </div>
                                            <div>
                                                <span class="font-medium">{{ $car->acceleration ?? '3.1' }}s</span>
                                                <span class="block text-[10px]">0-60 mph</span>
                                            </div>
                                            <div>
                                                <span class="font-medium">{{ $car->top_speed ?? '130' }}mph</span>
                                                <span class="block text-[10px]">Top Speed</span>
                                            </div>
                                        </div>

                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-sm font-medium text-black dark:text-white">Starting at {{ $car->formatted_price }}*</p>
                                                <p class="text-xs text-gray-500 dark:text-dark-text">After Est. Gas Savings</p>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <a href="{{ route('cars.show', $car->id) }}" class="rounded border border-gray-300 dark:border-gray-600 px-3 py-1.5 text-xs font-medium text-black dark:text-white transition hover:bg-gray-50 dark:hover:bg-gray-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-black/40">Learn</a>
                                                @if($car->is_available)
                                                    <a href="{{ route('cars.show', $car->id) }}" class="rounded px-3 py-1.5 text-xs font-medium text-white transition focus-visible:outline-none focus-visible:ring-2" style="background-color: #C8102E;" onmouseover="this.style.backgroundColor='#A00D25'" onmouseout="this.style.backgroundColor='#C8102E'">Order</a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        <div class="mt-12 flex justify-center">
                            <div class="bg-card border border-tesla-200 dark:border-gray-700 rounded-lg p-2">
                                {{ $cars->withQueryString()->links('pagination::tailwind') }}
                            </div>
                        </div>
                    @else
                        <!-- Empty State -->
                        <div class="bg-tesla-50 border border-tesla-200 rounded-xl p-12 text-center">
                            <svg class="w-20 h-20 text-tesla-400 mx-auto mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                            <h3 class="text-xl font-medium text-tesla-900 mb-3">No vehicles found</h3>
                            <p class="text-tesla-600 mb-6 max-w-md mx-auto">
                                We couldn't find any vehicles matching your current filters. Try adjusting your search criteria or browse all available vehicles.
                            </p>
                            <div class="flex flex-col sm:flex-row justify-center space-y-3 sm:space-y-0 sm:space-x-4">
                                <a href="{{ route('cars.browse') }}" class="bg-tesla-blue hover:bg-tesla-600 text-white px-6 py-3 rounded-lg font-medium transition-all duration-300 transform hover:scale-105">
                                    View All Vehicles
                                </a>
                                <button onclick="document.getElementById('filter-form').reset(); document.getElementById('filter-form').submit();" 
                                        class="border border-tesla-300 text-tesla-700 hover:bg-tesla-50 px-6 py-3 rounded-lg font-medium transition-all duration-300">
                                    Reset Filters
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mobile filter toggle
    const mobileFilterToggle = document.getElementById('mobile-filter-toggle');
    const filterSidebar = document.getElementById('filter-sidebar');
    const closeMobileFilters = document.getElementById('close-mobile-filters');

    if (mobileFilterToggle && filterSidebar) {
        mobileFilterToggle.addEventListener('click', function() {
            filterSidebar.classList.toggle('hidden');
            const icon = this.querySelector('svg:last-child');
            icon.classList.toggle('rotate-180');
        });
    }

    if (closeMobileFilters) {
        closeMobileFilters.addEventListener('click', function() {
            filterSidebar.classList.add('hidden');
            const icon = mobileFilterToggle.querySelector('svg:last-child');
            icon.classList.remove('rotate-180');
        });
    }

    // View toggle functionality
    const gridView = document.getElementById('grid-view');
    const listView = document.getElementById('list-view');
    const carsContainer = document.getElementById('cars-container');
    const viewToggles = document.querySelectorAll('.view-toggle');

    function setActiveView(activeButton) {
        viewToggles.forEach(btn => {
            btn.classList.remove('active', 'text-tesla-blue', 'bg-tesla-100');
            btn.classList.add('text-tesla-500');
        });
        activeButton.classList.add('active', 'text-tesla-blue', 'bg-tesla-100');
        activeButton.classList.remove('text-tesla-500');
    }

    if (gridView && listView && carsContainer) {
        gridView.addEventListener('click', function() {
            carsContainer.className = 'grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 lg:gap-8';
            setActiveView(this);
            localStorage.setItem('vehicleView', 'grid');
        });

        listView.addEventListener('click', function() {
            carsContainer.className = 'space-y-6';
            setActiveView(this);
            localStorage.setItem('vehicleView', 'list');
            
            // Update card layout for list view
            const cards = carsContainer.querySelectorAll('.group');
            cards.forEach(card => {
                card.classList.add('flex', 'flex-col', 'sm:flex-row');
                const img = card.querySelector('div:first-child');
                const content = card.querySelector('.p-6');
                if (img && content) {
                    img.classList.add('sm:w-1/3');
                    content.classList.add('sm:w-2/3');
                }
            });
        });

        // Restore saved view preference
        const savedView = localStorage.getItem('vehicleView');
        if (savedView === 'list') {
            listView.click();
        }
    }

    // Auto-submit form on filter change
    const filterForm = document.getElementById('filter-form');
    const filterInputs = filterForm.querySelectorAll('select, input[type="checkbox"]');
    
    filterInputs.forEach(input => {
        input.addEventListener('change', function() {
            // Add a small delay to allow for multiple quick changes
            clearTimeout(window.filterTimeout);
            window.filterTimeout = setTimeout(() => {
                filterForm.submit();
            }, 300);
        });
    });

    // Smooth scroll to results after filter
    if (window.location.search) {
        setTimeout(() => {
            document.querySelector('#cars-container')?.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'start' 
            });
        }, 100);
    }

    // Intersection Observer for card animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fade-in');
            }
        });
    }, observerOptions);

    // Observe all car cards
    document.querySelectorAll('.group').forEach(card => {
        observer.observe(card);
    });
});
</script>
@endsection
