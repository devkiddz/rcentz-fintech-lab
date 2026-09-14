<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-light text-lg text-foreground leading-tight mr-4">
                    Investment Holding Details
                </h2>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('admin.investments.holdings.index') }}" 
                   class="px-3 py-2 bg-muted text-muted-foreground text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                    Back to Holdings
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="xl:col-span-2 space-y-6">
                    <!-- Holding Overview -->
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Holding Overview</h3>
                        </div>
                        <div class="p-4">
                            <div class="flex items-start space-x-4 mb-4">
                                @if($holding->user->profile_image)
                                    <img src="{{ asset('storage/' . $holding->user->profile_image) }}" 
                                         alt="{{ $holding->user->name }}" 
                                         class="w-16 h-16 rounded-lg object-cover">
                                @else
                                    <div class="w-16 h-16 bg-gradient-to-br from-tesla-500 to-purple-600 rounded-lg flex items-center justify-center">
                                        <span class="text-white font-bold text-sm">{{ strtoupper(substr($holding->user->name, 0, 2)) }}</span>
                                    </div>
                                @endif
                                <div class="flex-1">
                                    <h4 class="text-lg font-medium text-foreground dark:text-white mb-2">{{ $holding->user->name }}</h4>
                                    <p class="text-sm text-muted-foreground mb-3">{{ $holding->investmentPlan->name }}</p>
                                    <div class="flex flex-wrap gap-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-tesla-100 text-tesla-800">
                                            {{ ucfirst($holding->investmentPlan->type) }}
                                        </span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $holding->investmentPlan->risk_level_badge }}">
                                            {{ ucfirst($holding->investmentPlan->risk_level) }} Risk
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Metrics -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-card border border-border p-4 rounded-lg">
                            <div class="text-center">
                                <p class="text-xs font-medium text-muted-foreground mb-1">Units</p>
                                <p class="text-lg font-light text-foreground">{{ $holding->formatted_units }}</p>
                            </div>
                        </div>

                        <div class="bg-card border border-border p-4 rounded-lg">
                            <div class="text-center">
                                <p class="text-xs font-medium text-muted-foreground mb-1">Current Value</p>
                                <p class="text-lg font-light text-foreground">{{ $holding->formatted_current_value }}</p>
                            </div>
                        </div>

                        <div class="bg-card border border-border p-4 rounded-lg">
                            <div class="text-center">
                                <p class="text-xs font-medium text-muted-foreground mb-1">Total Invested</p>
                                <p class="text-lg font-light text-foreground">{{ $holding->formatted_total_invested }}</p>
                            </div>
                        </div>

                        <div class="bg-card border border-border p-4 rounded-lg">
                            <div class="text-center">
                                <p class="text-xs font-medium text-muted-foreground mb-1">Average Cost</p>
                                <p class="text-lg font-light text-foreground">{{ $holding->formatted_average_cost }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Performance Metrics -->
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Performance Metrics</h3>
                        </div>
                        <div class="p-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="text-center">
                                    <p class="text-xs font-medium text-muted-foreground mb-2">Unrealized Gain/Loss</p>
                                    <p class="text-2xl font-light {{ $holding->gain_loss_color }}">
                                        {{ $holding->unrealized_gain_loss >= 0 ? '+' : '' }}${{ number_format($holding->unrealized_gain_loss, 2) }}
                                    </p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs font-medium text-muted-foreground mb-2">Return Percentage</p>
                                    <p class="text-2xl font-light {{ $holding->gain_loss_color }}">
                                        {{ $holding->unrealized_gain_loss_percentage >= 0 ? '+' : '' }}{{ number_format($holding->unrealized_gain_loss_percentage, 2) }}%
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Investment Plan Details -->
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Investment Plan Details</h3>
                        </div>
                        <div class="p-4">
                            <div class="flex items-start space-x-4 mb-4">
                                @if($holding->investmentPlan->image)
                                    <img src="{{ asset('storage/' . $holding->investmentPlan->image) }}" 
                                         alt="{{ $holding->investmentPlan->name }}" 
                                         class="w-16 h-16 rounded-lg object-cover">
                                @else
                                    <div class="w-16 h-16 bg-gradient-to-br from-tesla-500 to-purple-600 rounded-lg flex items-center justify-center">
                                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                    </div>
                                @endif
                                <div class="flex-1">
                                    <h4 class="text-lg font-medium text-foreground dark:text-white mb-2">{{ $holding->investmentPlan->name }}</h4>
                                    <p class="text-sm text-muted-foreground mb-3">{{ $holding->investmentPlan->description }}</p>
                                    <div class="flex flex-wrap gap-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-tesla-100 text-tesla-800">
                                            {{ ucfirst($holding->investmentPlan->type) }}
                                        </span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $holding->investmentPlan->risk_level_badge }}">
                                            {{ ucfirst($holding->investmentPlan->risk_level) }} Risk
                                        </span>
                                        @if($holding->investmentPlan->is_active)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Active
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <dt class="text-xs font-medium text-muted-foreground dark:text-gray-300">NAV</dt>
                                    <dd class="text-sm font-medium text-foreground mt-1">{{ $holding->investmentPlan->formatted_nav }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-muted-foreground dark:text-gray-300">Category</dt>
                                    <dd class="text-sm font-medium text-foreground mt-1">{{ $holding->investmentPlan->category }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-muted-foreground dark:text-gray-300">Management Fee</dt>
                                    <dd class="text-sm font-medium text-foreground mt-1">{{ $holding->investmentPlan->formatted_management_fee }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-muted-foreground dark:text-gray-300">Expense Ratio</dt>
                                    <dd class="text-sm font-medium text-foreground mt-1">{{ $holding->investmentPlan->formatted_expense_ratio }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-muted-foreground dark:text-gray-300">Minimum Investment</dt>
                                    <dd class="text-sm font-medium text-foreground mt-1">{{ $holding->investmentPlan->formatted_minimum_investment }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-muted-foreground dark:text-gray-300">Inception Date</dt>
                                    <dd class="text-sm font-medium text-foreground mt-1">{{ $holding->investmentPlan->inception_date->format('M d, Y') }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- User Information -->
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Investor Information</h3>
                        </div>
                        <div class="p-4">
                            <div class="flex items-center space-x-3 mb-4">
                                @if($holding->user->profile_image)
                                    <img src="{{ asset('storage/' . $holding->user->profile_image) }}" 
                                         alt="{{ $holding->user->name }}" 
                                         class="w-12 h-12 rounded-lg object-cover">
                                @else
                                    <div class="w-12 h-12 bg-gradient-to-br from-tesla-500 to-purple-600 rounded-lg flex items-center justify-center">
                                        <span class="text-white font-bold text-sm">{{ strtoupper(substr($holding->user->name, 0, 2)) }}</span>
                                    </div>
                                @endif
                                <div>
                                    <h4 class="text-sm font-medium text-foreground">{{ $holding->user->name }}</h4>
                                    <p class="text-xs text-muted-foreground dark:text-gray-300">{{ $holding->user->email }}</p>
                                    @if($holding->user->is_admin)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-purple-100 text-purple-800 mt-1">
                                            Admin
                                        </span>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Member Since</label>
                                    <p class="text-sm font-medium text-foreground">{{ $holding->user->created_at->format('M d, Y') }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Email Verified</label>
                                    <p class="text-sm font-medium text-foreground">
                                        @if($holding->user->email_verified_at)
                                            <span class="text-green-600">Yes</span>
                                        @else
                                            <span class="text-red-600">No</span>
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4">
                                <a href="{{ route('admin.users.show', $holding->user) }}" 
                                   class="w-full inline-flex items-center justify-center px-3 py-2 bg-black dark:bg-card text-white dark:text-foreground text-sm font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-muted transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    View Investor Profile
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Plan Information -->
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Plan Information</h3>
                        </div>
                        <div class="p-4">
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Plan Type</label>
                                    <p class="text-sm font-medium text-foreground">{{ ucfirst($holding->investmentPlan->type) }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Risk Level</label>
                                    <p class="text-sm font-medium text-foreground">{{ ucfirst($holding->investmentPlan->risk_level) }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Status</label>
                                    <p class="text-sm font-medium text-foreground">
                                        @if($holding->investmentPlan->is_active)
                                            <span class="text-green-600">Active</span>
                                        @else
                                            <span class="text-red-600">Inactive</span>
                                        @endif
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Featured</label>
                                    <p class="text-sm font-medium text-foreground">
                                        @if($holding->investmentPlan->is_featured)
                                            <span class="text-yellow-600">Yes</span>
                                        @else
                                            <span class="text-muted-foreground dark:text-gray-300">No</span>
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4">
                                <a href="{{ route('admin.investments.plans.show', $holding->investmentPlan) }}" 
                                   class="w-full inline-flex items-center justify-center px-3 py-2 bg-muted text-muted-foreground text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                    View Plan Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
