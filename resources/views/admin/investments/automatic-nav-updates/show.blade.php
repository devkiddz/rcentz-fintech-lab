<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-light text-lg text-foreground leading-tight mr-4">
                    Automatic NAV Update Details
                </h2>
                <p class="text-xs text-muted-foreground mt-1">{{ $automaticNavUpdate->name }}</p>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('admin.investments.automatic-nav-updates.edit', $automaticNavUpdate) }}" 
                   class="px-3 py-2 bg-black dark:bg-card text-white dark:text-foreground text-sm font-medium rounded-lg hover:opacity-90 transition-colors">
                    Edit
                </a>
                <a href="{{ route('admin.investments.automatic-nav-updates.index') }}" 
                   class="px-3 py-2 bg-muted text-muted-foreground text-sm font-medium rounded-lg hover:bg-muted transition-colors">
                    Back
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Status Overview -->
            <div class="bg-card border border-border overflow-hidden rounded-lg">
                <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                    <h3 class="text-base font-medium text-foreground">Status Overview</h3>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="text-center">
                            <div class="w-12 h-12 bg-gradient-to-br from-{{ $automaticNavUpdate->update_type === 'increase' ? 'green' : 'red' }}-500 to-{{ $automaticNavUpdate->update_type === 'increase' ? 'green' : 'red' }}-600 rounded-lg flex items-center justify-center mx-auto mb-2">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    @if($automaticNavUpdate->update_type === 'increase')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                                    @endif
                                </svg>
                            </div>
                            <p class="text-xs text-muted-foreground dark:text-gray-300">Status</p>
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $automaticNavUpdate->getStatusBadge() }}">
                                {{ $automaticNavUpdate->getStatusText() }}
                            </span>
                        </div>
                        
                        <div class="text-center">
                            <p class="text-2xl font-light text-foreground">{{ number_format($automaticNavUpdate->total_executions) }}</p>
                            <p class="text-xs text-muted-foreground dark:text-gray-300">Total Executions</p>
                        </div>
                        
                        <div class="text-center">
                            <p class="text-2xl font-light text-foreground">{{ $automaticNavUpdate->update_type === 'increase' ? '+' : '-' }}{{ number_format($automaticNavUpdate->update_amount, 4) }}</p>
                            <p class="text-xs text-muted-foreground dark:text-gray-300">NAV Change per Interval</p>
                        </div>
                        
                        <div class="text-center">
                            <p class="text-2xl font-light text-foreground">{{ $automaticNavUpdate->getIntervalDescription() }}</p>
                            <p class="text-xs text-muted-foreground dark:text-gray-300">Update Interval</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Basic Information -->
            <div class="bg-card border border-border overflow-hidden rounded-lg">
                <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                    <h3 class="text-base font-medium text-foreground">Basic Information</h3>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-medium text-muted-foreground mb-1">Update Name</label>
                            <p class="text-sm text-foreground">{{ $automaticNavUpdate->name }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-medium text-muted-foreground mb-1">Investment Plan</label>
                            <p class="text-sm text-foreground">{{ $automaticNavUpdate->investmentPlan->name }}</p>
                            <p class="text-xs text-muted-foreground dark:text-gray-300">Current NAV: {{ number_format($automaticNavUpdate->investmentPlan->nav, 4) }}</p>
                        </div>
                        
                        @if($automaticNavUpdate->description)
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-muted-foreground mb-1">Description</label>
                            <p class="text-sm text-foreground">{{ $automaticNavUpdate->description }}</p>
                        </div>
                        @endif
                        
                        <div>
                            <label class="block text-xs font-medium text-muted-foreground mb-1">Created By</label>
                            <p class="text-sm text-foreground">{{ $automaticNavUpdate->creator->name }}</p>
                            <p class="text-xs text-muted-foreground dark:text-gray-300">{{ $automaticNavUpdate->created_at->format('M d, Y H:i') }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-medium text-muted-foreground mb-1">Last Updated</label>
                            <p class="text-sm text-foreground">{{ $automaticNavUpdate->updated_at->format('M d, Y H:i') }}</p>
                            <p class="text-xs text-muted-foreground dark:text-gray-300">{{ $automaticNavUpdate->updated_at->diffForHumans() }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Schedule Information -->
            <div class="bg-card border border-border overflow-hidden rounded-lg">
                <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                    <h3 class="text-base font-medium text-foreground">Schedule Information</h3>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-medium text-muted-foreground mb-1">Start Date <span class="text-xs text-muted-foreground dark:text-gray-300">(UTC)</span></label>
                            <p class="text-sm text-foreground">{{ $automaticNavUpdate->start_date->format('M d, Y H:i') }}</p>
                            <p class="text-xs text-muted-foreground dark:text-gray-300">{{ $automaticNavUpdate->start_date->diffForHumans() }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-medium text-muted-foreground mb-1">End Date <span class="text-xs text-muted-foreground dark:text-gray-300">(UTC)</span></label>
                            <p class="text-sm text-foreground">{{ $automaticNavUpdate->end_date->format('M d, Y H:i') }}</p>
                            <p class="text-xs text-muted-foreground dark:text-gray-300">{{ $automaticNavUpdate->end_date->diffForHumans() }}</p>
                        </div>
                        
                        @if($automaticNavUpdate->last_executed_at)
                        <div>
                            <label class="block text-xs font-medium text-muted-foreground mb-1">Last Execution</label>
                            <p class="text-sm text-foreground">{{ $automaticNavUpdate->last_executed_at->format('M d, Y H:i') }}</p>
                            <p class="text-xs text-muted-foreground dark:text-gray-300">{{ $automaticNavUpdate->last_executed_at->diffForHumans() }}</p>
                        </div>
                        @endif
                        
                        @if($automaticNavUpdate->is_active && $automaticNavUpdate->next_execution_at->gt(now()))
                        <div>
                            <label class="block text-xs font-medium text-muted-foreground mb-1">Next Execution</label>
                            <p class="text-sm text-foreground">{{ $automaticNavUpdate->next_execution_at->format('M d, Y H:i') }}</p>
                            <p class="text-xs text-tesla-600">{{ $automaticNavUpdate->next_execution_at->diffForHumans() }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Update Configuration -->
            <div class="bg-card border border-border overflow-hidden rounded-lg">
                <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                    <h3 class="text-base font-medium text-foreground">Update Configuration</h3>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-xs font-medium text-muted-foreground mb-1">Update Type</label>
                            <div class="flex items-center space-x-2">
                                @if($automaticNavUpdate->update_type === 'increase')
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                        </svg>
                                        Increase
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                                        </svg>
                                        Decrease
                                    </span>
                                @endif
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-medium text-muted-foreground mb-1">Update Amount</label>
                            <p class="text-sm text-foreground">{{ number_format($automaticNavUpdate->update_amount, 4) }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-medium text-muted-foreground mb-1">Update Interval</label>
                            <p class="text-sm text-foreground">Every {{ $automaticNavUpdate->getIntervalDescription() }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end space-x-3">
                <form action="{{ route('admin.investments.automatic-nav-updates.toggle', $automaticNavUpdate) }}" method="POST" class="inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="px-4 py-2 bg-{{ $automaticNavUpdate->is_active ? 'yellow' : 'green' }}-100 text-{{ $automaticNavUpdate->is_active ? 'yellow' : 'green' }}-700 text-sm font-medium rounded-lg hover:bg-{{ $automaticNavUpdate->is_active ? 'yellow' : 'green' }}-200 transition-colors">
                        {{ $automaticNavUpdate->is_active ? 'Pause Update' : 'Resume Update' }}
                    </button>
                </form>
                
                <a href="{{ route('admin.investments.automatic-nav-updates.edit', $automaticNavUpdate) }}" 
                   class="px-4 py-2 bg-black dark:bg-card text-white dark:text-foreground text-sm font-medium rounded-lg hover:opacity-90 transition-colors">
                    Edit Update
                </a>
                
                <form action="{{ route('admin.investments.automatic-nav-updates.destroy', $automaticNavUpdate) }}" 
                      method="POST" class="inline" 
                      onsubmit="return confirm('Are you sure you want to delete this automatic update? This action cannot be undone.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 bg-red-100 text-red-700 text-sm font-medium rounded-lg hover:bg-red-200 transition-colors">
                        Delete Update
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
