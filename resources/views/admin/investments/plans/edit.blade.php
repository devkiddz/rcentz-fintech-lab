<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-light text-lg text-foreground leading-tight mr-4">
                    Edit Investment Plan
                </h2>
            </div>
            <a href="{{ route('admin.investments.plans.index') }}" 
               class="px-3 py-2 bg-muted text-muted-foreground text-sm font-medium rounded-lg hover:bg-muted transition-colors">
                Back to Plans
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.investments.plans.update', $plan) }}" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PATCH')
                
                <!-- Basic Information -->
                <div class="bg-card border border-border overflow-hidden rounded-lg">
                    <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                        <h3 class="text-base font-medium text-foreground">Basic Information</h3>
                        <p class="text-xs text-muted-foreground mt-1">Essential details about the investment plan</p>
                    </div>
                    <div class="p-4 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="name" class="block text-xs font-medium text-muted-foreground mb-2">Plan Name *</label>
                                <input type="text" name="name" id="name" value="{{ old('name', $plan->name) }}" required
                                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-black transition-colors">
                                @error('name')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="type" class="block text-xs font-medium text-muted-foreground mb-2">Type *</label>
                                <select name="type" id="type" required
                                        class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-black transition-colors">
                                    <option value="">Select Type</option>
                                    <option value="tesla_focused" {{ old('type', $plan->type) == 'tesla_focused' ? 'selected' : '' }}>Tesla Focused</option>
                                    <option value="esg" {{ old('type', $plan->type) == 'esg' ? 'selected' : '' }}>ESG</option>
                                    <option value="mutual_fund" {{ old('type', $plan->type) == 'mutual_fund' ? 'selected' : '' }}>Mutual Fund</option>
                                    <option value="etf" {{ old('type', $plan->type) == 'etf' ? 'selected' : '' }}>ETF</option>
                                    <option value="retirement" {{ old('type', $plan->type) == 'retirement' ? 'selected' : '' }}>Retirement</option>
                                </select>
                                @error('type')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        
                        <div>
                            <label for="description" class="block text-xs font-medium text-muted-foreground mb-2">Description *</label>
                            <textarea name="description" id="description" rows="4" required
                                      class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-black transition-colors">{{ old('description', $plan->description) }}</textarea>
                            @error('description')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="category" class="block text-xs font-medium text-muted-foreground mb-2">Category *</label>
                                <input type="text" name="category" id="category" value="{{ old('category', $plan->category) }}" required
                                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-black transition-colors">
                                @error('category')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="risk_level" class="block text-xs font-medium text-muted-foreground mb-2">Risk Level *</label>
                                <select name="risk_level" id="risk_level" required
                                        class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-black transition-colors">
                                    <option value="">Select Risk Level</option>
                                    <option value="low" {{ old('risk_level', $plan->risk_level) == 'low' ? 'selected' : '' }}>Low</option>
                                    <option value="medium" {{ old('risk_level', $plan->risk_level) == 'medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="high" {{ old('risk_level', $plan->risk_level) == 'high' ? 'selected' : '' }}>High</option>
                                    <option value="very_high" {{ old('risk_level', $plan->risk_level) == 'very_high' ? 'selected' : '' }}>Very High</option>
                                </select>
                                @error('risk_level')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Financial Details -->
                <div class="bg-card border border-border overflow-hidden rounded-lg">
                    <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                        <h3 class="text-base font-medium text-foreground">Financial Details</h3>
                        <p class="text-xs text-muted-foreground mt-1">Pricing and performance information</p>
                    </div>
                    <div class="p-4 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="nav" class="block text-xs font-medium text-muted-foreground mb-2">NAV *</label>
                                <input type="number" name="nav" id="nav" step="0.0001" value="{{ old('nav', $plan->nav) }}" required
                                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-black transition-colors">
                                @error('nav')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="minimum_investment" class="block text-xs font-medium text-muted-foreground mb-2">Minimum Investment *</label>
                                <input type="number" name="minimum_investment" id="minimum_investment" step="0.01" value="{{ old('minimum_investment', $plan->minimum_investment) }}" required
                                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-black transition-colors">
                                @error('minimum_investment')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="total_assets" class="block text-xs font-medium text-muted-foreground mb-2">Total Assets</label>
                                <input type="number" name="total_assets" id="total_assets" step="0.01" value="{{ old('total_assets', $plan->total_assets) }}"
                                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-black transition-colors">
                                @error('total_assets')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="management_fee" class="block text-xs font-medium text-muted-foreground mb-2">Management Fee *</label>
                                <input type="number" name="management_fee" id="management_fee" step="0.0001" value="{{ old('management_fee', $plan->management_fee) }}" required
                                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-black transition-colors">
                                <p class="text-xs text-muted-foreground mt-1">Enter as decimal (e.g., 0.015 for 1.5%)</p>
                                @error('management_fee')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="expense_ratio" class="block text-xs font-medium text-muted-foreground mb-2">Expense Ratio *</label>
                                <input type="number" name="expense_ratio" id="expense_ratio" step="0.0001" value="{{ old('expense_ratio', $plan->expense_ratio) }}" required
                                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-black transition-colors">
                                <p class="text-xs text-muted-foreground mt-1">Enter as decimal (e.g., 0.012 for 1.2%)</p>
                                @error('expense_ratio')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="dividend_yield" class="block text-xs font-medium text-muted-foreground mb-2">Dividend Yield</label>
                                <input type="number" name="dividend_yield" id="dividend_yield" step="0.01" value="{{ old('dividend_yield', $plan->dividend_yield) }}"
                                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-black transition-colors">
                                <p class="text-xs text-muted-foreground mt-1">Enter as percentage (e.g., 2.5 for 2.5%)</p>
                                @error('dividend_yield')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="previous_nav" class="block text-xs font-medium text-muted-foreground mb-2">Previous NAV</label>
                                <input type="number" name="previous_nav" id="previous_nav" step="0.0001" value="{{ old('previous_nav', $plan->previous_nav) }}"
                                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-black transition-colors">
                                @error('previous_nav')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="inception_date" class="block text-xs font-medium text-muted-foreground mb-2">Inception Date *</label>
                                <input type="date" name="inception_date" id="inception_date" value="{{ old('inception_date', $plan->inception_date?->format('Y-m-d')) }}" required
                                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-black transition-colors">
                                @error('inception_date')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Information -->
                <div class="bg-card border border-border overflow-hidden rounded-lg">
                    <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                        <h3 class="text-base font-medium text-foreground">Additional Information</h3>
                        <p class="text-xs text-muted-foreground mt-1">Optional details and settings</p>
                    </div>
                    <div class="p-4 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="prospectus_url" class="block text-xs font-medium text-muted-foreground mb-2">Prospectus URL</label>
                                <input type="url" name="prospectus_url" id="prospectus_url" value="{{ old('prospectus_url', $plan->prospectus_url) }}"
                                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-black transition-colors">
                                @error('prospectus_url')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="fact_sheet_url" class="block text-xs font-medium text-muted-foreground mb-2">Fact Sheet URL</label>
                                <input type="url" name="fact_sheet_url" id="fact_sheet_url" value="{{ old('fact_sheet_url', $plan->fact_sheet_url) }}"
                                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-black transition-colors">
                                @error('fact_sheet_url')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        
                        <div>
                            <label for="image" class="block text-xs font-medium text-muted-foreground mb-2">Plan Image</label>
                            @if($plan->image)
                                <div class="mb-3">
                                    <img src="{{ asset('storage/' . $plan->image) }}" 
                                         alt="{{ $plan->name }}" 
                                         class="w-20 h-20 rounded-lg object-cover">
                                    <p class="text-xs text-muted-foreground mt-1">Current image</p>
                                </div>
                            @endif
                            <input type="file" name="image" id="image" accept="image/*"
                                   class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-black transition-colors">
                            <p class="text-xs text-muted-foreground mt-1">Upload a new image for the investment plan (max 2MB)</p>
                            @error('image')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div class="flex items-center space-x-6">
                            <label class="flex items-center">
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $plan->is_active) ? 'checked' : '' }}
                                       class="w-4 h-4 text-foreground focus:ring-ring border-border rounded">
                                <span class="ml-2 text-xs font-medium text-muted-foreground">Active Plan</span>
                            </label>
                            
                            <label class="flex items-center">
                                <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $plan->is_featured) ? 'checked' : '' }}
                                       class="w-4 h-4 text-foreground focus:ring-ring border-border rounded">
                                <span class="ml-2 text-xs font-medium text-muted-foreground">Featured Plan</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Submit Actions -->
                <div class="flex justify-end space-x-3">
                    <a href="{{ route('admin.investments.plans.index') }}" 
                       class="px-4 py-2 bg-muted text-muted-foreground text-sm font-medium rounded-lg hover:bg-muted transition-colors">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 bg-black dark:bg-card text-white dark:text-foreground text-sm font-medium rounded-lg hover:opacity-90 transition-colors">
                        Update Investment Plan
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
