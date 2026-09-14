<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-light text-lg text-foreground leading-tight mr-4">
                    Investment Transaction Details
                </h2>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('admin.investments.transactions.index') }}" 
                   class="px-3 py-2 bg-muted text-muted-foreground text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                    Back to Transactions
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="xl:col-span-2 space-y-6">
                    <!-- Transaction Overview -->
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Transaction Overview</h3>
                        </div>
                        <div class="p-4">
                            <div class="flex items-start space-x-4 mb-4">
                                @if($transaction->user->profile_image)
                                    <img src="{{ asset('storage/' . $transaction->user->profile_image) }}" 
                                         alt="{{ $transaction->user->name }}" 
                                         class="w-16 h-16 rounded-lg object-cover">
                                @else
                                    <div class="w-16 h-16 bg-gradient-to-br from-tesla-500 to-purple-600 rounded-lg flex items-center justify-center">
                                        <span class="text-white font-bold text-sm">{{ strtoupper(substr($transaction->user->name, 0, 2)) }}</span>
                                    </div>
                                @endif
                                <div class="flex-1">
                                    <h4 class="text-lg font-medium text-foreground dark:text-white mb-2">{{ $transaction->user->name }}</h4>
                                    <p class="text-sm text-muted-foreground mb-3">{{ $transaction->investmentPlan->name }}</p>
                                    <div class="flex flex-wrap gap-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-tesla-100 text-tesla-800">
                                            {{ ucfirst($transaction->type) }}
                                        </span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $transaction->status_badge }}">
                                            {{ ucfirst($transaction->status) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Transaction Details -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-card border border-border p-4 rounded-lg">
                            <div class="text-center">
                                <p class="text-xs font-medium text-muted-foreground mb-1">Units</p>
                                <p class="text-lg font-light text-foreground">{{ $transaction->formatted_units }}</p>
                            </div>
                        </div>

                        <div class="bg-card border border-border p-4 rounded-lg">
                            <div class="text-center">
                                <p class="text-xs font-medium text-muted-foreground mb-1">Total Amount</p>
                                <p class="text-lg font-light text-foreground">{{ $transaction->formatted_total_amount }}</p>
                            </div>
                        </div>

                        <div class="bg-card border border-border p-4 rounded-lg">
                            <div class="text-center">
                                <p class="text-xs font-medium text-muted-foreground mb-1">NAV at Transaction</p>
                                <p class="text-lg font-light text-foreground">{{ $transaction->formatted_nav_at_transaction }}</p>
                            </div>
                        </div>

                        <div class="bg-card border border-border p-4 rounded-lg">
                            <div class="text-center">
                                <p class="text-xs font-medium text-muted-foreground mb-1">Fee</p>
                                <p class="text-lg font-light text-foreground">{{ $transaction->formatted_fee }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Transaction Information -->
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Transaction Information</h3>
                        </div>
                        <div class="p-4">
                            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <dt class="text-xs font-medium text-muted-foreground dark:text-gray-300">Transaction Type</dt>
                                    <dd class="text-sm font-medium text-foreground mt-1">{{ ucfirst($transaction->type) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-muted-foreground dark:text-gray-300">Status</dt>
                                    <dd class="text-sm font-medium text-foreground mt-1">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $transaction->status_badge }}">
                                            {{ ucfirst($transaction->status) }}
                                        </span>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-muted-foreground dark:text-gray-300">Executed At</dt>
                                    <dd class="text-sm font-medium text-foreground mt-1">{{ $transaction->formatted_executed_at }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-muted-foreground dark:text-gray-300">Created At</dt>
                                    <dd class="text-sm font-medium text-foreground mt-1">{{ $transaction->created_at->format('M d, Y h:i A') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-muted-foreground dark:text-gray-300">Units</dt>
                                    <dd class="text-sm font-medium text-foreground mt-1">{{ $transaction->formatted_units }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-muted-foreground dark:text-gray-300">NAV at Transaction</dt>
                                    <dd class="text-sm font-medium text-foreground mt-1">{{ $transaction->formatted_nav_at_transaction }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-muted-foreground dark:text-gray-300">Total Amount</dt>
                                    <dd class="text-sm font-medium text-foreground mt-1">{{ $transaction->formatted_total_amount }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-muted-foreground dark:text-gray-300">Fee</dt>
                                    <dd class="text-sm font-medium text-foreground mt-1">{{ $transaction->formatted_fee }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Investment Plan Details -->
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Investment Plan Details</h3>
                        </div>
                        <div class="p-4">
                            <div class="flex items-start space-x-4 mb-4">
                                @if($transaction->investmentPlan->image)
                                    <img src="{{ asset('storage/' . $transaction->investmentPlan->image) }}" 
                                         alt="{{ $transaction->investmentPlan->name }}" 
                                         class="w-16 h-16 rounded-lg object-cover">
                                @else
                                    <div class="w-16 h-16 bg-gradient-to-br from-tesla-500 to-purple-600 rounded-lg flex items-center justify-center">
                                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                    </div>
                                @endif
                                <div class="flex-1">
                                    <h4 class="text-lg font-medium text-foreground dark:text-white mb-2">{{ $transaction->investmentPlan->name }}</h4>
                                    <p class="text-sm text-muted-foreground mb-3">{{ $transaction->investmentPlan->description }}</p>
                                    <div class="flex flex-wrap gap-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-tesla-100 text-tesla-800">
                                            {{ ucfirst($transaction->investmentPlan->type) }}
                                        </span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $transaction->investmentPlan->risk_level_badge }}">
                                            {{ ucfirst($transaction->investmentPlan->risk_level) }} Risk
                                        </span>
                                        @if($transaction->investmentPlan->is_active)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Active
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <dt class="text-xs font-medium text-muted-foreground dark:text-gray-300">Current NAV</dt>
                                    <dd class="text-sm font-medium text-foreground mt-1">{{ $transaction->investmentPlan->formatted_nav }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-muted-foreground dark:text-gray-300">Category</dt>
                                    <dd class="text-sm font-medium text-foreground mt-1">{{ $transaction->investmentPlan->category }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-muted-foreground dark:text-gray-300">Management Fee</dt>
                                    <dd class="text-sm font-medium text-foreground mt-1">{{ $transaction->investmentPlan->formatted_management_fee }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-muted-foreground dark:text-gray-300">Expense Ratio</dt>
                                    <dd class="text-sm font-medium text-foreground mt-1">{{ $transaction->investmentPlan->formatted_expense_ratio }}</dd>
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
                                @if($transaction->user->profile_image)
                                    <img src="{{ asset('storage/' . $transaction->user->profile_image) }}" 
                                         alt="{{ $transaction->user->name }}" 
                                         class="w-12 h-12 rounded-lg object-cover">
                                @else
                                    <div class="w-12 h-12 bg-gradient-to-br from-tesla-500 to-purple-600 rounded-lg flex items-center justify-center">
                                        <span class="text-white font-bold text-sm">{{ strtoupper(substr($transaction->user->name, 0, 2)) }}</span>
                                    </div>
                                @endif
                                <div>
                                    <h4 class="text-sm font-medium text-foreground">{{ $transaction->user->name }}</h4>
                                    <p class="text-xs text-muted-foreground dark:text-gray-300">{{ $transaction->user->email }}</p>
                                    @if($transaction->user->is_admin)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-purple-100 text-purple-800 mt-1">
                                            Admin
                                        </span>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Member Since</label>
                                    <p class="text-sm font-medium text-foreground">{{ $transaction->user->created_at->format('M d, Y') }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Email Verified</label>
                                    <p class="text-sm font-medium text-foreground">
                                        @if($transaction->user->email_verified_at)
                                            <span class="text-green-600">Yes</span>
                                        @else
                                            <span class="text-red-600">No</span>
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4">
                                <a href="{{ route('admin.users.show', $transaction->user) }}" 
                                   class="w-full inline-flex items-center justify-center px-3 py-2 bg-black dark:bg-card text-white dark:text-foreground text-sm font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-muted transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    View Investor Profile
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Information -->
                    @if($transaction->walletTransaction)
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Payment Information</h3>
                        </div>
                        <div class="p-4">
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Payment Method</label>
                                    <p class="text-sm font-medium text-foreground">{{ $transaction->walletTransaction->paymentMethod->name }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Transaction Type</label>
                                    <p class="text-sm font-medium text-foreground">{{ ucfirst($transaction->walletTransaction->type) }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Amount</label>
                                    <p class="text-sm font-medium text-foreground">${{ number_format($transaction->walletTransaction->amount, 2) }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Status</label>
                                    <p class="text-sm font-medium text-foreground">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $transaction->walletTransaction->status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                            {{ ucfirst($transaction->walletTransaction->status) }}
                                        </span>
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Reference ID</label>
                                    <p class="text-sm font-medium text-foreground">{{ $transaction->walletTransaction->reference_id }}</p>
                                </div>
                                @if($transaction->walletTransaction->description)
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Description</label>
                                    <p class="text-sm font-medium text-foreground">{{ $transaction->walletTransaction->description }}</p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Plan Information -->
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Plan Information</h3>
                        </div>
                        <div class="p-4">
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Plan Type</label>
                                    <p class="text-sm font-medium text-foreground">{{ ucfirst($transaction->investmentPlan->type) }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Risk Level</label>
                                    <p class="text-sm font-medium text-foreground">{{ ucfirst($transaction->investmentPlan->risk_level) }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Status</label>
                                    <p class="text-sm font-medium text-foreground">
                                        @if($transaction->investmentPlan->is_active)
                                            <span class="text-green-600">Active</span>
                                        @else
                                            <span class="text-red-600">Inactive</span>
                                        @endif
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Featured</label>
                                    <p class="text-sm font-medium text-foreground">
                                        @if($transaction->investmentPlan->is_featured)
                                            <span class="text-yellow-600">Yes</span>
                                        @else
                                            <span class="text-muted-foreground dark:text-gray-300">No</span>
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4">
                                <a href="{{ route('admin.investments.plans.show', $transaction->investmentPlan) }}" 
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
