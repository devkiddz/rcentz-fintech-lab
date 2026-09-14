<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-light text-lg text-foreground leading-tight mr-4">
                    {{ $car->title }}
                </h2>
                <p class="text-xs text-muted-foreground mt-1">{{ $car->year }} {{ $car->make }} {{ $car->model }}</p>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('admin.cars.index') }}" class="inline-flex items-center px-3 py-2 bg-muted text-muted-foreground text-sm font-medium rounded-lg hover:bg-gray-200 transition-all duration-200">
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
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <div class="xl:col-span-2">
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="p-4">
                            @if($car->images && count($car->images) > 0)
                            <div id="car-carousel" class="relative">
                                <div id="main-image" class="mb-4">
                                    <img src="{{ strpos($car->images[0], 'http') === 0 ? $car->images[0] : asset('storage/' . $car->images[0]) }}"
                                         alt="{{ $car->title }}"
                                         class="w-full h-80 object-cover rounded-lg">
                                </div>

                                @if(count($car->images) > 1)
                                <div class="grid grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-2">
                                    @foreach($car->images as $index => $image)
                                    <button onclick="changeMainImage('{{ strpos($image, 'http') === 0 ? $image : asset('storage/' . $image) }}', {{ $index }})"
                                            class="rounded-md overflow-hidden hover:opacity-75 transition-all duration-200 {{ $index === 0 ? 'ring-2 ring-black' : 'ring-1 ring-gray-200' }}"
                                            id="thumb-{{ $index }}">
                                        <img src="{{ strpos($image, 'http') === 0 ? $image : asset('storage/' . $image) }}"
                                             alt="Thumbnail {{ $index + 1 }}"
                                             class="w-full h-12 object-cover">
                                    </button>
                                    @endforeach
                                </div>
                                @endif
                            </div>
                            @else
                            <div class="bg-gradient-to-br from-gray-100 to-gray-200 rounded-lg flex items-center justify-center h-80">
                                <div class="text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <p class="mt-3 text-sm font-medium text-muted-foreground dark:text-gray-300">No images available</p>
                                    <p class="text-xs text-gray-400 dark:text-gray-300">Add images to showcase this vehicle</p>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <div class="mt-6 bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground flex items-center">
                                <svg class="w-4 h-4 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path>
                                </svg>
                                Description
                            </h3>
                        </div>
                        <div class="p-4">
                            <div class="prose max-w-none">
                                <p class="text-muted-foreground leading-relaxed text-sm">{{ $car->description }}</p>
                            </div>
                        </div>
                    </div>

                    @if($car->purchases->count() > 0)
                    <div class="mt-6 bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground flex items-center">
                                <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                </svg>
                                Purchase History
                            </h3>
                        </div>
                        <div class="p-4">
                            <div class="space-y-3">
                                @foreach($car->purchases as $purchase)
                                <div class="flex items-center justify-between p-3 bg-muted/40 dark:bg-dark-muted rounded-lg border border-border dark:border-gray-700">
                                    <div class="flex items-center space-x-3">
                                                                            @if($purchase->user->profile_image)
                                        <img src="{{ asset('storage/' . $purchase->user->profile_image) }}" 
                                             alt="{{ $purchase->user->name }}" 
                                             class="w-10 h-10 rounded-lg object-cover">
                                    @else
                                        <div class="w-10 h-10 bg-gradient-to-br from-tesla-500 to-purple-600 rounded-lg flex items-center justify-center">
                                            <span class="text-white font-bold text-xs">{{ strtoupper(substr($purchase->user->name, 0, 2)) }}</span>
                                        </div>
                                    @endif
                                        <div>
                                            <p class="font-medium text-foreground dark:text-white text-sm">{{ $purchase->user->name }}</p>
                                            <p class="text-xs text-muted-foreground dark:text-gray-300">{{ $purchase->user->email }}</p>
                                            <p class="text-xs text-muted-foreground dark:text-gray-300">{{ $purchase->purchased_at ? $purchase->purchased_at->format('M d, Y g:i A') : 'Date not available' }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-light text-base text-foreground">{{ $purchase->formatted_amount }}</p>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium {{ $purchase->status_badge }}">
                                            {{ ucfirst($purchase->status) }}
                                        </span>
                                        <p class="text-xs text-muted-foreground mt-1">{{ $purchase->paymentMethod->name }}</p>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                <div class="space-y-4">
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground flex items-center">
                                <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                                Pricing & Status
                            </h3>
                        </div>
                        <div class="p-4">
                            <div class="text-center mb-3">
                                <div class="text-2xl font-light text-foreground dark:text-white mb-2">{{ $car->formatted_price }}</div>
                                @if($car->is_available)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 8 8">
                                            <circle cx="4" cy="4" r="3"/>
                                        </svg>
                                        Available for Sale
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 8 8">
                                            <circle cx="4" cy="4" r="3"/>
                                        </svg>
                                        Sold
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground flex items-center">
                                <svg class="w-4 h-4 mr-2 text-tesla-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                </svg>
                                Specifications
                            </h3>
                        </div>
                        <div class="p-4">
                            <dl class="space-y-3">
                                <div class="flex justify-between py-2 border-b border-border">
                                    <dt class="text-xs font-medium text-muted-foreground dark:text-gray-300">Year</dt>
                                    <dd class="text-xs font-medium text-foreground">{{ $car->year }}</dd>
                                </div>
                                <div class="flex justify-between py-2 border-b border-border">
                                    <dt class="text-xs font-medium text-muted-foreground dark:text-gray-300">Make</dt>
                                    <dd class="text-xs font-medium text-foreground">{{ $car->make }}</dd>
                                </div>
                                <div class="flex justify-between py-2 border-b border-border">
                                    <dt class="text-xs font-medium text-muted-foreground dark:text-gray-300">Model</dt>
                                    <dd class="text-xs font-medium text-foreground">{{ $car->model }}</dd>
                                </div>
                                <div class="flex justify-between py-2 border-b border-border">
                                    <dt class="text-xs font-medium text-muted-foreground dark:text-gray-300">Color</dt>
                                    <dd class="text-xs font-medium text-foreground">{{ $car->color }}</dd>
                                </div>
                                <div class="flex justify-between py-2 border-b border-border">
                                    <dt class="text-xs font-medium text-muted-foreground dark:text-gray-300">Engine</dt>
                                    <dd class="text-xs font-medium text-foreground">{{ $car->engine }}</dd>
                                </div>
                                <div class="flex justify-between py-2">
                                    <dt class="text-xs font-medium text-muted-foreground dark:text-gray-300">Transmission</dt>
                                    <dd class="text-xs font-medium text-foreground">{{ $car->transmission }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground flex items-center">
                                <svg class="w-4 h-4 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                Quick Actions
                            </h3>
                        </div>
                        <div class="p-4 space-y-2">
                            <a href="{{ route('cars.show', $car->id) }}" target="_blank"
                               class="w-full inline-flex items-center justify-center px-3 py-2 bg-muted text-muted-foreground text-sm font-medium rounded-lg hover:bg-gray-200 transition-all duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-2M10 6V4a2 2 0 112 4M10 6l4 4m0 0l-4 4m4-4H3"></path>
                                </svg>
                                View on Frontend
                            </a>

                            <a href="{{ route('admin.cars.edit', $car) }}"
                               class="w-full inline-flex items-center justify-center px-3 py-2 bg-black dark:bg-card text-white dark:text-foreground text-sm font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-muted transition-all duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                Edit Vehicle
                            </a>

                            <form action="{{ route('admin.cars.destroy', $car) }}" method="POST"
                                  onsubmit="return confirm('Are you sure you want to delete this vehicle? This action cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="w-full inline-flex items-center justify-center px-3 py-2 bg-red-50 text-red-700 text-sm font-medium rounded-lg hover:bg-red-100 border border-red-200 transition-all duration-200">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    Delete Vehicle
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground flex items-center">
                                <svg class="w-4 h-4 mr-2 text-muted-foreground dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                System Info
                            </h3>
                        </div>
                        <div class="p-4">
                            <dl class="space-y-2">
                                <div class="flex justify-between py-2 border-b border-border">
                                    <dt class="text-xs font-medium text-muted-foreground dark:text-gray-300">Created</dt>
                                    <dd class="text-xs text-foreground">{{ $car->created_at->format('M d, Y g:i A') }}</dd>
                                </div>
                                <div class="flex justify-between py-2">
                                    <dt class="text-xs font-medium text-muted-foreground dark:text-gray-300">Last Updated</dt>
                                    <dd class="text-xs text-foreground">{{ $car->updated_at->format('M d, Y g:i A') }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

       </div>
    </div>

    <script>
        function changeMainImage(src, index) {
            document.getElementById('main-image').querySelector('img').src = src;

            // Update active thumbnail
            document.querySelectorAll('[id^="thumb-"]').forEach(thumb => {
                thumb.classList.remove('ring-2', 'ring-black');
                thumb.classList.add('ring-1', 'ring-gray-200');
            });
            document.getElementById('thumb-' + index).classList.remove('ring-1', 'ring-gray-200');
            document.getElementById('thumb-' + index).classList.add('ring-2', 'ring-black');
        }
    </script>
</x-admin-layout>
