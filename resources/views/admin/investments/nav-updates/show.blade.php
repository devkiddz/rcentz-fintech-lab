<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-light text-lg text-foreground leading-tight mr-4">
                    NAV History - {{ $plan->name }}
                </h2>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('admin.investments.nav-updates.index') }}" 
                   class="px-3 py-2 bg-muted text-muted-foreground text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                    Back to NAV Updates
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="xl:col-span-2 space-y-6">
                    <!-- Plan Overview -->
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Plan Overview</h3>
                        </div>
                        <div class="p-4">
                            <div class="flex items-start space-x-4 mb-4">
                                @if($plan->image)
                                    <img src="{{ asset('storage/' . $plan->image) }}" 
                                         alt="{{ $plan->name }}" 
                                         class="w-16 h-16 rounded-lg object-cover">
                                @else
                                    <div class="w-16 h-16 bg-gradient-to-br from-tesla-500 to-purple-600 rounded-lg flex items-center justify-center">
                                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                    </div>
                                @endif
                                <div class="flex-1">
                                    <h4 class="text-lg font-medium text-foreground dark:text-white mb-2">{{ $plan->name }}</h4>
                                    <p class="text-sm text-muted-foreground mb-3">{{ $plan->description }}</p>
                                    <div class="flex flex-wrap gap-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-tesla-100 text-tesla-800">
                                            {{ ucfirst($plan->type) }}
                                        </span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $plan->risk_level_badge }}">
                                            {{ ucfirst($plan->risk_level) }} Risk
                                        </span>
                                        @if($plan->is_active)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Active
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Current NAV Metrics -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-card border border-border p-4 rounded-lg">
                            <div class="text-center">
                                <p class="text-xs font-medium text-muted-foreground mb-1">Current NAV</p>
                                <p class="text-lg font-light text-foreground">{{ $plan->formatted_nav }}</p>
                            </div>
                        </div>

                        <div class="bg-card border border-border p-4 rounded-lg">
                            <div class="text-center">
                                <p class="text-xs font-medium text-muted-foreground mb-1">Previous NAV</p>
                                <p class="text-lg font-light text-foreground">{{ $plan->previous_nav ? '$' . number_format($plan->previous_nav, 4) : 'N/A' }}</p>
                            </div>
                        </div>

                        <div class="bg-card border border-border p-4 rounded-lg">
                            <div class="text-center">
                                <p class="text-xs font-medium text-muted-foreground mb-1">Change</p>
                                <p class="text-lg font-light {{ $plan->nav_change_color }}">
                                    {{ $plan->nav_change ? ($plan->nav_change >= 0 ? '+' : '') . '$' . number_format($plan->nav_change, 4) : 'N/A' }}
                                </p>
                            </div>
                        </div>

                        <div class="bg-card border border-border p-4 rounded-lg">
                            <div class="text-center">
                                <p class="text-xs font-medium text-muted-foreground mb-1">Change %</p>
                                <p class="text-lg font-light {{ $plan->nav_change_color }}">
                                    {{ $plan->nav_change_percentage ? ($plan->nav_change_percentage >= 0 ? '+' : '') . number_format($plan->nav_change_percentage, 2) . '%' : 'N/A' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- NAV History -->
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">NAV History</h3>
                            <p class="text-xs text-muted-foreground mt-1">Recent NAV updates and changes</p>
                        </div>
                        <div class="p-4">
                            @if($navHistory->count() > 0)
                            <div class="space-y-3">
                                @foreach($navHistory as $entry)
                                <div class="border border-border dark:border-gray-700 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <div>
                                            <p class="text-sm font-medium text-foreground">
                                                ${{ number_format($entry['nav'], 4) }}
                                            </p>
                                            <p class="text-xs text-muted-foreground dark:text-gray-300">
                                                {{ \Carbon\Carbon::parse($entry['date'])->format('M d, Y h:i A') }}
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            @if(isset($entry['change']))
                                            <p class="text-sm font-medium {{ $entry['change'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                {{ $entry['change'] >= 0 ? '+' : '' }}${{ number_format($entry['change'], 4) }}
                                            </p>
                                            <p class="text-xs {{ $entry['change'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                {{ $entry['change_percentage'] >= 0 ? '+' : '' }}{{ number_format($entry['change_percentage'], 2) }}%
                                            </p>
                                            @else
                                            <p class="text-xs text-muted-foreground dark:text-gray-300">Initial NAV</p>
                                            @endif
                                        </div>
                                    </div>
                                    @if(isset($entry['reason']) && $entry['reason'])
                                    <div class="mt-2 p-2 bg-muted/40 dark:bg-dark-muted rounded">
                                        <p class="text-xs text-muted-foreground dark:text-gray-300">
                                            <span class="font-medium">Reason:</span> {{ $entry['reason'] }}
                                        </p>
                                    </div>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                            @else
                            <div class="text-center py-8">
                                <div class="w-16 h-16 bg-muted dark:bg-dark-muted rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-gray-400 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-medium text-foreground dark:text-white mb-2">No NAV history found</h3>
                                <p class="text-xs text-muted-foreground dark:text-gray-300">This plan hasn't had any NAV updates yet.</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Plan Information -->
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Plan Information</h3>
                        </div>
                        <div class="p-4">
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Plan Type</label>
                                    <p class="text-sm font-medium text-foreground">{{ ucfirst($plan->type) }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Category</label>
                                    <p class="text-sm font-medium text-foreground">{{ $plan->category }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Risk Level</label>
                                    <p class="text-sm font-medium text-foreground">{{ ucfirst($plan->risk_level) }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Status</label>
                                    <p class="text-sm font-medium text-foreground">
                                        @if($plan->is_active)
                                            <span class="text-green-600">Active</span>
                                        @else
                                            <span class="text-red-600">Inactive</span>
                                        @endif
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Last NAV Update</label>
                                    <p class="text-sm font-medium text-foreground">
                                        {{ $plan->last_nav_update ? $plan->last_nav_update->format('M d, Y h:i A') : 'Never' }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4">
                                <a href="{{ route('admin.investments.plans.show', $plan) }}" 
                                   class="w-full inline-flex items-center justify-center px-3 py-2 bg-black dark:bg-card text-white dark:text-foreground text-sm font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-muted transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                    View Plan Details
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Holdings Summary -->
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Holdings Summary</h3>
                        </div>
                        <div class="p-4">
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Total Holdings</label>
                                    <p class="text-sm font-medium text-foreground">{{ $plan->holdings->count() }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Total Value</label>
                                    <p class="text-sm font-medium text-foreground">${{ number_format($plan->holdings->sum('current_value'), 2) }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Total Invested</label>
                                    <p class="text-sm font-medium text-foreground">${{ number_format($plan->holdings->sum('total_invested'), 2) }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Total Gain/Loss</label>
                                    <p class="text-sm font-medium {{ $plan->holdings->sum('unrealized_gain_loss') >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $plan->holdings->sum('unrealized_gain_loss') >= 0 ? '+' : '' }}${{ number_format($plan->holdings->sum('unrealized_gain_loss'), 2) }}
                                    </p>
                                </div>
                            </div>

                            @if($plan->holdings->count() > 0)
                            <div class="mt-4">
                                <a href="{{ route('admin.investments.holdings.index') }}?plan={{ $plan->id }}" 
                                   class="w-full inline-flex items-center justify-center px-3 py-2 bg-muted text-muted-foreground text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                    View Holdings
                                </a>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
