<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-light text-lg text-foreground leading-tight mr-4">
                    Automatic NAV Updates
                </h2>
            </div>
            <a href="{{ route('admin.investments.automatic-nav-updates.create') }}" 
               class="px-4 py-2 bg-black dark:bg-card text-white dark:text-foreground text-sm font-medium rounded hover:bg-gray-800 dark:hover:bg-muted transition-colors">
                Create
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <!-- Statistics Overview -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-card border border-border p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-8 h-8 md:w-10 md:h-10 bg-tesla-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 md:w-5 md:h-5 text-tesla-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-2 md:ml-3">
                            <p class="text-xs font-medium text-muted-foreground dark:text-gray-300">Total Updates</p>
                            <p class="text-sm md:text-lg font-light text-foreground">{{ $stats['total_updates'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-card border border-border p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-8 h-8 md:w-10 md:h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 md:w-5 md:h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-2 md:ml-3">
                            <p class="text-xs font-medium text-muted-foreground dark:text-gray-300">Active Updates</p>
                            <p class="text-sm md:text-lg font-light text-foreground">{{ $stats['active_updates'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-card border border-border p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-8 h-8 md:w-10 md:h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 md:w-5 md:h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                        <div class="ml-2 md:ml-3">
                            <p class="text-xs font-medium text-muted-foreground dark:text-gray-300">Due Updates</p>
                            <p class="text-sm md:text-lg font-light text-foreground">{{ $stats['due_updates'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-card border border-border p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-8 h-8 md:w-10 md:h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 md:w-5 md:h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <div class="ml-2 md:ml-3">
                            <p class="text-xs font-medium text-muted-foreground dark:text-gray-300">Total Executions</p>
                            <p class="text-sm md:text-lg font-light text-foreground">{{ number_format($stats['total_executions']) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            @if($automaticUpdates->count() > 0)
            <!-- Automatic Updates List -->
            <div class="bg-card border border-border overflow-hidden rounded-lg">
                <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-medium text-foreground">Automatic NAV Updates</h3>
                        <div class="flex items-center space-x-2">
                            <span class="text-xs text-muted-foreground dark:text-gray-300">{{ $automaticUpdates->count() }} of {{ $automaticUpdates->total() }} updates</span>
                        </div>
                    </div>
                </div>
                
                <div class="p-4">
                    <div class="space-y-4">
                        @foreach($automaticUpdates as $update)
                        <div class="bg-muted/40 dark:bg-dark-muted rounded-lg p-4 border border-border dark:border-gray-700 hover:bg-card hover:shadow-sm transition-all duration-200">
                            <!-- Mobile Layout -->
                            <div class="md:hidden">
                                <div class="flex items-start space-x-3 mb-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-{{ $update->update_type === 'increase' ? 'green' : 'red' }}-500 to-{{ $update->update_type === 'increase' ? 'green' : 'red' }}-600 rounded-lg flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            @if($update->update_type === 'increase')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                                            @endif
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-medium text-foreground truncate">{{ $update->name }}</h4>
                                        <p class="text-xs text-muted-foreground truncate">{{ $update->investmentPlan->name }}</p>
                                        <div class="flex items-center space-x-2 mt-1">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $update->getStatusBadge() }}">
                                                {{ $update->getStatusText() }}
                                            </span>
                                            <span class="text-xs text-muted-foreground dark:text-gray-300">{{ $update->getIntervalDescription() }}</span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-medium text-foreground">{{ $update->update_type === 'increase' ? '+' : '-' }}{{ number_format($update->update_amount, 4) }}</p>
                                        <p class="text-xs text-muted-foreground dark:text-gray-300">{{ number_format($update->total_executions) }} runs</p>
                                    </div>
                                </div>
                                
                                @if($update->is_active && $update->next_execution_at->gt(now()))
                                <div class="mb-3 p-2 bg-tesla-50 rounded text-xs">
                                    <span class="text-tesla-600 font-medium">Next run:</span>
                                    <span class="text-tesla-800">{{ $update->next_execution_at->format('M d, Y H:i') }}</span>
                                    <span class="text-tesla-600">({{ $update->next_execution_at->diffForHumans() }})</span>
                                </div>
                                @endif
                                
                                <!-- Mobile Actions -->
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('admin.investments.automatic-nav-updates.show', $update) }}" 
                                       class="flex-1 px-3 py-2 bg-muted text-muted-foreground text-xs font-medium rounded text-center hover:bg-gray-200 transition-colors">
                                        View Details
                                    </a>
                                    <a href="{{ route('admin.investments.automatic-nav-updates.edit', $update) }}" 
                                       class="flex-1 px-3 py-2 bg-black dark:bg-card text-white dark:text-foreground text-xs font-medium rounded text-center hover:bg-gray-800 dark:hover:bg-muted transition-colors">
                                        Edit
                                    </a>
                                    <form action="{{ route('admin.investments.automatic-nav-updates.toggle', $update) }}" method="POST" class="flex-1">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="w-full px-3 py-2 bg-{{ $update->is_active ? 'yellow' : 'green' }}-100 text-{{ $update->is_active ? 'yellow' : 'green' }}-700 text-xs font-medium rounded hover:bg-{{ $update->is_active ? 'yellow' : 'green' }}-200 transition-colors">
                                            {{ $update->is_active ? 'Pause' : 'Resume' }}
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Desktop Layout -->
                            <div class="hidden md:flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <div class="w-12 h-12 bg-gradient-to-br from-{{ $update->update_type === 'increase' ? 'green' : 'red' }}-500 to-{{ $update->update_type === 'increase' ? 'green' : 'red' }}-600 rounded-lg flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            @if($update->update_type === 'increase')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                                            @endif
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-foreground">{{ $update->name }}</h4>
                                        <p class="text-xs text-muted-foreground dark:text-gray-300">{{ $update->investmentPlan->name }}</p>
                                        <div class="flex items-center space-x-2 mt-1">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $update->getStatusBadge() }}">
                                                {{ $update->getStatusText() }}
                                            </span>
                                            <span class="text-xs text-muted-foreground dark:text-gray-300">Every {{ $update->getIntervalDescription() }}</span>
                                            <span class="text-xs text-muted-foreground dark:text-gray-300">•</span>
                                            <span class="text-xs text-muted-foreground dark:text-gray-300">{{ number_format($update->total_executions) }} executions</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex items-center space-x-6">
                                    <div class="text-center">
                                        <p class="text-xs text-muted-foreground dark:text-gray-300">Update Amount</p>
                                        <p class="text-sm font-medium text-foreground">{{ $update->update_type === 'increase' ? '+' : '-' }}{{ number_format($update->update_amount, 4) }}</p>
                                    </div>
                                    
                                    <div class="text-center">
                                        <p class="text-xs text-muted-foreground dark:text-gray-300">Period</p>
                                        <p class="text-xs text-muted-foreground">{{ $update->start_date->format('M d') }} - {{ $update->end_date->format('M d, Y') }}</p>
                                    </div>
                                    
                                    @if($update->is_active && $update->next_execution_at->gt(now()))
                                    <div class="text-center">
                                        <p class="text-xs text-muted-foreground dark:text-gray-300">Next Run</p>
                                        <p class="text-xs text-muted-foreground">{{ $update->next_execution_at->format('M d, H:i') }}</p>
                                        <p class="text-xs text-tesla-600">{{ $update->next_execution_at->diffForHumans() }}</p>
                                    </div>
                                    @endif
                                    
                                    <div class="flex space-x-2">
                                        <a href="{{ route('admin.investments.automatic-nav-updates.show', $update) }}" 
                                           class="px-3 py-1.5 bg-muted text-muted-foreground text-xs font-medium rounded hover:bg-gray-200 transition-colors">
                                            View
                                        </a>
                                        <a href="{{ route('admin.investments.automatic-nav-updates.edit', $update) }}" 
                                           class="px-3 py-1.5 bg-black dark:bg-card text-white dark:text-foreground text-xs font-medium rounded hover:bg-gray-800 dark:hover:bg-muted transition-colors">
                                            Edit
                                        </a>
                                        <form action="{{ route('admin.investments.automatic-nav-updates.toggle', $update) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="px-3 py-1.5 bg-{{ $update->is_active ? 'yellow' : 'green' }}-100 text-{{ $update->is_active ? 'yellow' : 'green' }}-700 text-xs font-medium rounded hover:bg-{{ $update->is_active ? 'yellow' : 'green' }}-200 transition-colors">
                                                {{ $update->is_active ? 'Pause' : 'Resume' }}
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                
                <!-- Pagination -->
                @if($automaticUpdates->hasPages())
                <div class="px-4 py-3 border-t border-border dark:border-gray-700">
                    {{ $automaticUpdates->links() }}
                </div>
                @endif
            </div>
            @else
            <!-- Empty State -->
            <div class="bg-card border border-border p-8 rounded-lg text-center">
                <div class="w-16 h-16 bg-muted dark:bg-dark-muted rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-foreground dark:text-white mb-2">No automatic NAV updates found</h3>
                <p class="text-xs text-muted-foreground dark:text-gray-300 mb-4">Create your first automatic NAV update to get started with scheduled updates.</p>
                <a href="{{ route('admin.investments.automatic-nav-updates.create') }}" 
                   class="inline-flex items-center px-4 py-2 bg-black dark:bg-card text-white dark:text-foreground text-sm font-medium rounded hover:bg-gray-800 dark:hover:bg-muted transition-colors">
                    Create Automatic Update
                </a>
            </div>
            @endif
        </div>
    </div>
</x-admin-layout>
