<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-light text-lg text-foreground leading-tight">
                    {{ __('Add New Vehicle') }}
                </h2>
            </div>
            <a href="{{ route('admin.cars.index') }}" class="inline-flex items-center px-3 py-2 bg-muted text-muted-foreground text-sm font-medium rounded-lg hover:bg-gray-200 transition-all duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <form action="{{ route('admin.cars.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <!-- Basic Information -->
                <div class="bg-card border border-border overflow-hidden rounded-lg">
                    <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                        <h3 class="text-base font-medium text-foreground flex items-center">
                            <svg class="w-4 h-4 mr-2 text-tesla-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Basic Information
                        </h3>
                    </div>
                    <div class="p-4 space-y-4">
                        <!-- Title -->
                        <div>
                            <label for="title" class="block text-xs font-medium text-muted-foreground mb-2">Vehicle Title *</label>
                            <input type="text" name="title" id="title" value="{{ old('title') }}" 
                                   placeholder="e.g., 2023 Tesla Model S Plaid"
                                   class="block w-full rounded-lg border-border shadow-sm focus:border-black focus:ring-black text-foreground placeholder-gray-400 @error('title') border-red-300 ring-red-300 @enderror">
                            @error('title')
                                <p class="mt-2 text-xs text-red-600 flex items-center">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="description" class="block text-xs font-medium text-muted-foreground mb-2">Description *</label>
                            <textarea name="description" id="description" rows="4" 
                                      placeholder="Describe the vehicle's features, condition, and unique selling points..."
                                      class="block w-full rounded-lg border-border shadow-sm focus:border-black focus:ring-black text-foreground placeholder-gray-400 @error('description') border-red-300 ring-red-300 @enderror">{{ old('description') }}</textarea>
                            @error('description')
                                <p class="mt-2 text-xs text-red-600 flex items-center">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Vehicle Specifications -->
                <div class="bg-card border border-border overflow-hidden rounded-lg">
                    <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                        <h3 class="text-base font-medium text-foreground flex items-center">
                            <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                            Vehicle Specifications
                        </h3>
                    </div>
                    <div class="p-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <!-- Make -->
                            <div>
                                <label for="make" class="block text-xs font-medium text-muted-foreground mb-2">Make *</label>
                                <input type="text" name="make" id="make" value="{{ old('make') }}" 
                                       placeholder="e.g., Tesla"
                                       class="block w-full rounded-lg border-border shadow-sm focus:border-black focus:ring-black text-foreground placeholder-gray-400 @error('make') border-red-300 ring-red-300 @enderror">
                                @error('make')
                                    <p class="mt-2 text-xs text-red-600 flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <!-- Model -->
                            <div>
                                <label for="model" class="block text-xs font-medium text-muted-foreground mb-2">Model *</label>
                                <input type="text" name="model" id="model" value="{{ old('model') }}" 
                                       placeholder="e.g., Model S"
                                       class="block w-full rounded-lg border-border shadow-sm focus:border-black focus:ring-black text-foreground placeholder-gray-400 @error('model') border-red-300 ring-red-300 @enderror">
                                @error('model')
                                    <p class="mt-2 text-xs text-red-600 flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <!-- Year -->
                            <div>
                                <label for="year" class="block text-xs font-medium text-muted-foreground mb-2">Year *</label>
                                <input type="number" name="year" id="year" value="{{ old('year') }}" min="1990" max="{{ date('Y') + 1 }}" 
                                       placeholder="{{ date('Y') }}"
                                       class="block w-full rounded-lg border-border shadow-sm focus:border-black focus:ring-black text-foreground placeholder-gray-400 @error('year') border-red-300 ring-red-300 @enderror">
                                @error('year')
                                    <p class="mt-2 text-xs text-red-600 flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <!-- Color -->
                            <div>
                                <label for="color" class="block text-xs font-medium text-muted-foreground mb-2">Color *</label>
                                <input type="text" name="color" id="color" value="{{ old('color') }}" 
                                       placeholder="e.g., Pearl White"
                                       class="block w-full rounded-lg border-border shadow-sm focus:border-black focus:ring-black text-foreground placeholder-gray-400 @error('color') border-red-300 ring-red-300 @enderror">
                                @error('color')
                                    <p class="mt-2 text-xs text-red-600 flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <!-- Engine -->
                            <div>
                                <label for="engine" class="block text-xs font-medium text-muted-foreground mb-2">Engine *</label>
                                <input type="text" name="engine" id="engine" value="{{ old('engine') }}" 
                                       placeholder="e.g., Electric Motor"
                                       class="block w-full rounded-lg border-border shadow-sm focus:border-black focus:ring-black text-foreground placeholder-gray-400 @error('engine') border-red-300 ring-red-300 @enderror">
                                @error('engine')
                                    <p class="mt-2 text-xs text-red-600 flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <!-- Transmission -->
                            <div>
                                <label for="transmission" class="block text-xs font-medium text-muted-foreground mb-2">Transmission *</label>
                                <select name="transmission" id="transmission" 
                                        class="block w-full rounded-lg border-border shadow-sm focus:border-black focus:ring-black text-foreground @error('transmission') border-red-300 ring-red-300 @enderror">
                                    <option value="">Select Transmission</option>
                                    <option value="Automatic" {{ old('transmission') == 'Automatic' ? 'selected' : '' }}>Automatic</option>
                                    <option value="Manual" {{ old('transmission') == 'Manual' ? 'selected' : '' }}>Manual</option>
                                    <option value="CVT" {{ old('transmission') == 'CVT' ? 'selected' : '' }}>CVT</option>
                                </select>
                                @error('transmission')
                                    <p class="mt-2 text-xs text-red-600 flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pricing & Availability -->
                <div class="bg-card border border-border overflow-hidden rounded-lg">
                    <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                        <h3 class="text-base font-medium text-foreground flex items-center">
                            <svg class="w-4 h-4 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                            Pricing & Availability
                        </h3>
                    </div>
                    <div class="p-4 space-y-4">
                        <!-- Price -->
                        <div>
                            <label for="price" class="block text-xs font-medium text-muted-foreground mb-2">Price (USD) *</label>
                            <div class="relative rounded-lg shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-muted-foreground text-sm font-medium">$</span>
                                </div>
                                <input type="number" name="price" id="price" value="{{ old('price') }}" step="0.01" min="0" 
                                       placeholder="75000"
                                       class="pl-6 block w-full rounded-lg border-border shadow-sm focus:border-black focus:ring-black text-foreground placeholder-gray-400 @error('price') border-red-300 ring-red-300 @enderror">
                            </div>
                            @error('price')
                                <p class="mt-2 text-xs text-red-600 flex items-center">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <!-- Availability -->
                        <div class="flex items-center p-3 bg-muted/40 dark:bg-dark-muted rounded-lg">
                            <input type="checkbox" name="is_available" id="is_available" value="1" {{ old('is_available', true) ? 'checked' : '' }}
                                   class="h-4 w-4 text-foreground focus:ring-black border-border rounded">
                            <label for="is_available" class="ml-3 block text-xs font-medium text-foreground">
                                Available for sale
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Add Images -->
                <div class="bg-card border border-border overflow-hidden rounded-lg">
                    <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                        <h3 class="text-base font-medium text-foreground flex items-center">
                            <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Add Images
                        </h3>
                    </div>
                    <div class="p-4 space-y-4">
                        <!-- File Upload -->
                        <div>
                            <label class="block text-xs font-medium text-muted-foreground mb-2">Upload Images</label>
                            <div class="mt-1 flex justify-center px-4 pt-6 pb-6 border-2 border-border border-dashed rounded-lg hover:border-black transition-colors">
                                <div class="space-y-2 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-300" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                    </svg>
                                    <div class="flex text-sm text-muted-foreground dark:text-gray-300">
                                        <label for="images" class="relative cursor-pointer bg-card rounded-md font-medium text-foreground hover:text-tesla-600 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-black">
                                            <span>Upload files</span>
                                            <input id="images" name="images[]" type="file" class="sr-only" multiple accept="image/*">
                                        </label>
                                        <p class="pl-1">or drag and drop</p>
                                    </div>
                                    <p class="text-xs text-muted-foreground dark:text-gray-300">PNG, JPG, GIF up to 10MB each</p>
                                </div>
                            </div>
                            @error('images')
                                <p class="mt-2 text-xs text-red-600 flex items-center">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                            @error('images.*')
                                <p class="mt-2 text-xs text-red-600 flex items-center">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <!-- Image URLs Alternative -->
                        <div class="border-t border-border dark:border-gray-700 pt-4">
                            <label for="image_urls" class="block text-xs font-medium text-muted-foreground mb-2">Or Add Image URLs</label>
                            <textarea name="image_urls" id="image_urls" rows="4" 
                                      placeholder="https://example.com/image1.jpg&#10;https://example.com/image2.jpg&#10;https://example.com/image3.jpg" 
                                      class="block w-full rounded-lg border-border shadow-sm focus:border-black focus:ring-black text-foreground placeholder-gray-400">{{ old('image_urls') }}</textarea>
                            <p class="mt-2 text-xs text-muted-foreground dark:text-gray-300">Enter one image URL per line.</p>
                            @error('image_urls')
                                <p class="mt-2 text-xs text-red-600 flex items-center">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Submit Actions -->
                <div class="bg-card border border-border p-4 rounded-lg">
                    <div class="flex items-center justify-end space-x-3">
                        <a href="{{ route('admin.cars.index') }}" class="inline-flex items-center px-4 py-2 bg-muted text-muted-foreground text-sm font-medium rounded-lg hover:bg-gray-200 transition-all duration-200">
                            Cancel
                        </a>
                        <button type="submit" class="inline-flex items-center px-6 py-2 bg-black dark:bg-card text-white dark:text-foreground text-sm font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-muted transition-all duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Add Vehicle
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout> 
