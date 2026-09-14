<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-light text-lg text-foreground leading-tight mr-4">
                    {{ __('Fleet Management') }}
                </h2>
            </div>
            <a href="{{ route('admin.cars.create') }}" class="inline-flex items-center px-4 py-2 bg-black dark:bg-card text-white dark:text-foreground text-sm font-medium rounded-lg hover:opacity-90 transition-all duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Add Vehicle
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            @if($cars->count() > 0)
            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-card border border-border p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-muted rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-xs font-medium text-muted-foreground dark:text-gray-300">Total Vehicles</p>
                            <p class="text-lg font-light text-foreground">{{ $cars->total() }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-card border border-border p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-xs font-medium text-muted-foreground dark:text-gray-300">Available</p>
                            <p class="text-lg font-light text-foreground">{{ $cars->where('is_available', true)->count() }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-card border border-border p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-xs font-medium text-muted-foreground dark:text-gray-300">Sold</p>
                            <p class="text-lg font-light text-foreground">{{ $cars->where('is_available', false)->count() }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-card border border-border p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-tesla-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-tesla-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-xs font-medium text-muted-foreground dark:text-gray-300">Total Value</p>
                            <p class="text-lg font-light text-foreground">${{ number_format($cars->sum('price'), 0) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cars Grid -->
            <div class="bg-card border border-border overflow-hidden rounded-lg">
                <div class="px-4 py-3 border-b border-border dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-medium text-foreground">Vehicle Inventory</h3>
                        <div class="flex items-center space-x-2">
                            <span class="text-xs text-muted-foreground dark:text-gray-300">{{ $cars->count() }} of {{ $cars->total() }} vehicles</span>
                        </div>
                    </div>
                </div>
                
                <div class="p-4">
                    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4">
                        @foreach($cars as $car)
                        <div class="group bg-muted/40 rounded-lg p-4 hover:bg-card hover:shadow-md transition-all duration-200 border border-transparent hover:border-border dark:border-gray-700">
                            <!-- Car Image -->
                            <div class="mb-3">
                                @if($car->first_image)
                                    <a href="{{ route('admin.cars.show', ['car' => $car->getRouteKey()]) }}"
                                       class="block overflow-hidden rounded-lg"
                                       aria-label="View details for {{ $car->title }}">
                                        <img src="{{ strpos($car->first_image, 'http') === 0 ? $car->first_image : asset('storage/' . $car->first_image) }}" 
                                             alt="{{ $car->title }}" 
                                             class="w-full h-40 object-cover rounded-lg group-hover:scale-105 transition-transform duration-200">
                                    </a>
                                @else
                                    <div class="w-full h-40 bg-gradient-to-br from-gray-200 to-gray-300 rounded-lg flex items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-400 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Car Details -->
                            <div class="space-y-2">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <a href="{{ route('admin.cars.show', ['car' => $car->getRouteKey()]) }}"
                                           class="font-medium text-foreground dark:text-white text-sm hover:text-tesla-600 transition-colors">
                                            {{ $car->title }}
                                        </a>
                                        <p class="text-xs text-muted-foreground dark:text-gray-300">{{ $car->year }} {{ $car->make }} {{ $car->model }}</p>
                                    </div>
                                    @if($car->is_available)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-green-100 text-green-800">
                                            <svg class="w-2.5 h-2.5 mr-1" fill="currentColor" viewBox="0 0 8 8">
                                                <circle cx="4" cy="4" r="3"/>
                                            </svg>
                                            Available
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-red-100 text-red-800">
                                            <svg class="w-2.5 h-2.5 mr-1" fill="currentColor" viewBox="0 0 8 8">
                                                <circle cx="4" cy="4" r="3"/>
                                            </svg>
                                            Sold
                                        </span>
                                    @endif
                                </div>
                                
                                <div class="flex items-center space-x-3 text-xs text-muted-foreground dark:text-gray-300">
                                    <span>{{ $car->color }}</span>
                                    <span>•</span>
                                    <span>{{ $car->transmission }}</span>
                                </div>
                                
                                <div class="flex items-center justify-between">
                                    <span class="text-lg font-light text-foreground">{{ $car->formatted_price }}</span>
                                </div>
                                
                                <!-- Actions -->
                                <div class="flex items-center space-x-2 pt-2">
                                    <a href="{{ route('admin.cars.show', ['car' => $car->getRouteKey()]) }}"
                                       class="relative z-10 flex-1 text-center px-2 py-1.5 bg-muted text-foreground text-xs font-medium rounded-md hover:bg-muted dark:hover:bg-gray-700 transition-colors">
                                        View Details
                                    </a>
                                    <a href="{{ route('admin.cars.edit', $car) }}" class="flex-1 text-center px-2 py-1.5 bg-tesla-100 text-tesla-700 text-xs font-medium rounded-md hover:bg-tesla-200 transition-colors">
                                        Edit
                                    </a>
                                    <form action="{{ route('admin.cars.destroy', $car) }}" method="POST" class="flex-1" onsubmit="return confirm('Are you sure you want to delete this vehicle?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-full px-2 py-1.5 bg-red-100 text-red-700 text-xs font-medium rounded-md hover:bg-red-200 transition-colors">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                
                <!-- Pagination -->
                @if($cars->hasPages())
                <div class="px-4 py-3 border-t border-border dark:border-gray-700">
                    {{ $cars->links() }}
                </div>
                @endif
            </div>
            @else
            <!-- Empty State -->
            <div class="bg-card border border-border p-8 text-center rounded-lg">
                <div class="w-16 h-16 bg-muted rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-foreground dark:text-white mb-2">No vehicles in inventory</h3>
                <p class="text-muted-foreground dark:text-gray-300 mb-6 max-w-md mx-auto text-sm">Get started by adding your first vehicle to the inventory. You can add details, photos, and pricing information.</p>
                <a href="{{ route('admin.cars.create') }}" class="inline-flex items-center px-4 py-2 bg-black dark:bg-card text-white dark:text-foreground text-sm font-medium rounded-lg hover:opacity-90 transition-all duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Add Your First Vehicle
                </a>
            </div>
            @endif
        </div>
    </div>
</x-admin-layout> 
