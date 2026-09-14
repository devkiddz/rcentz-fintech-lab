<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-light text-lg text-foreground leading-tight mr-4">
                    Edit Automatic NAV Update
                </h2>
                <p class="text-xs text-muted-foreground mt-1">{{ $automaticNavUpdate->name }}</p>
            </div>
            <a href="{{ route('admin.investments.automatic-nav-updates.show', $automaticNavUpdate) }}" 
               class="px-3 py-2 bg-muted text-muted-foreground text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                Back
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.investments.automatic-nav-updates.update', $automaticNavUpdate) }}" class="space-y-6">
                @csrf
                @method('PUT')
                
                <!-- Basic Information -->
                <div class="bg-card border border-border overflow-hidden rounded-lg">
                    <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                        <h3 class="text-base font-medium text-foreground">Basic Information</h3>
                        <p class="text-xs text-muted-foreground mt-1">Essential details about the automatic NAV update</p>
                    </div>
                    <div class="p-4 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="name" class="block text-xs font-medium text-muted-foreground mb-2">Update Name *</label>
                                <input type="text" name="name" id="name" value="{{ old('name', $automaticNavUpdate->name) }}" required
                                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-black focus:border-black transition-colors">
                                @error('name')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="investment_plan_id" class="block text-xs font-medium text-muted-foreground mb-2">Investment Plan *</label>
                                <select name="investment_plan_id" id="investment_plan_id" required
                                        class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-black focus:border-black transition-colors">
                                    <option value="">Select Investment Plan</option>
                                    @foreach($investmentPlans as $plan)
                                        <option value="{{ $plan->id }}" 
                                                data-current-nav="{{ $plan->nav }}"
                                                {{ old('investment_plan_id', $automaticNavUpdate->investment_plan_id) == $plan->id ? 'selected' : '' }}>
                                            {{ $plan->name }} (NAV: {{ number_format($plan->nav, 4) }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('investment_plan_id')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="description" class="block text-xs font-medium text-muted-foreground mb-2">Description</label>
                            <textarea name="description" id="description" rows="3"
                                      class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-black focus:border-black transition-colors">{{ old('description', $automaticNavUpdate->description) }}</textarea>
                            @error('description')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Update Configuration -->
                <div class="bg-card border border-border overflow-hidden rounded-lg">
                    <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                        <h3 class="text-base font-medium text-foreground">Update Configuration</h3>
                        <p class="text-xs text-muted-foreground mt-1">Configure how the NAV will be updated</p>
                    </div>
                    <div class="p-4 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="update_type" class="block text-xs font-medium text-muted-foreground mb-2">Update Type *</label>
                                <select name="update_type" id="update_type" required
                                        class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-black focus:border-black transition-colors">
                                    <option value="">Select Update Type</option>
                                    <option value="increase" {{ old('update_type', $automaticNavUpdate->update_type) == 'increase' ? 'selected' : '' }}>
                                        Increase NAV
                                    </option>
                                    <option value="decrease" {{ old('update_type', $automaticNavUpdate->update_type) == 'decrease' ? 'selected' : '' }}>
                                        Decrease NAV
                                    </option>
                                </select>
                                @error('update_type')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="update_amount" class="block text-xs font-medium text-muted-foreground mb-2">Update Amount *</label>
                                <input type="number" name="update_amount" id="update_amount" 
                                       value="{{ old('update_amount', $automaticNavUpdate->update_amount) }}" 
                                       step="0.0001" min="0.0001" max="999.9999" required
                                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-black focus:border-black transition-colors">
                                @error('update_amount')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                                <p class="text-xs text-muted-foreground mt-1">Amount to add/subtract from NAV per interval</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Schedule Configuration -->
                <div class="bg-card border border-border overflow-hidden rounded-lg">
                    <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                        <h3 class="text-base font-medium text-foreground">Schedule Configuration</h3>
                        <p class="text-xs text-muted-foreground mt-1">Set when and how often the update should run</p>
                    </div>
                    <div class="p-4 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="update_interval_value" class="block text-xs font-medium text-muted-foreground mb-2">Interval Value *</label>
                                <input type="number" name="update_interval_value" id="update_interval_value" 
                                       value="{{ old('update_interval_value', $automaticNavUpdate->update_interval_value) }}" 
                                       min="1" max="999" required
                                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-black focus:border-black transition-colors">
                                @error('update_interval_value')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="update_interval_unit" class="block text-xs font-medium text-muted-foreground mb-2">Interval Unit *</label>
                                <select name="update_interval_unit" id="update_interval_unit" required
                                        class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-black focus:border-black transition-colors">
                                    <option value="">Select Unit</option>
                                    <option value="minutes" {{ old('update_interval_unit', $automaticNavUpdate->update_interval_unit) == 'minutes' ? 'selected' : '' }}>Minutes</option>
                                    <option value="hours" {{ old('update_interval_unit', $automaticNavUpdate->update_interval_unit) == 'hours' ? 'selected' : '' }}>Hours</option>
                                    <option value="days" {{ old('update_interval_unit', $automaticNavUpdate->update_interval_unit) == 'days' ? 'selected' : '' }}>Days</option>
                                </select>
                                @error('update_interval_unit')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="start_date" class="block text-xs font-medium text-muted-foreground mb-2">Start Date <span class="text-xs text-muted-foreground dark:text-gray-300">(UTC)</span> *</label>
                                <input type="datetime-local" name="start_date" id="start_date" 
                                       value="{{ old('start_date', $automaticNavUpdate->start_date->format('Y-m-d\TH:i')) }}" required
                                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-black focus:border-black transition-colors">
                                @error('start_date')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="end_date" class="block text-xs font-medium text-muted-foreground mb-2">End Date <span class="text-xs text-muted-foreground dark:text-gray-300">(UTC)</span> *</label>
                                <input type="datetime-local" name="end_date" id="end_date" 
                                       value="{{ old('end_date', $automaticNavUpdate->end_date->format('Y-m-d\TH:i')) }}" required
                                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-black focus:border-black transition-colors">
                                @error('end_date')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status Configuration -->
                <div class="bg-card border border-border overflow-hidden rounded-lg">
                    <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                        <h3 class="text-base font-medium text-foreground">Status Configuration</h3>
                        <p class="text-xs text-muted-foreground mt-1">Control the active status of this automatic update</p>
                    </div>
                    <div class="p-4">
                        <div class="flex items-center">
                            <input type="checkbox" name="is_active" id="is_active" value="1" 
                                   {{ old('is_active', $automaticNavUpdate->is_active) ? 'checked' : '' }}
                                   class="h-4 w-4 text-foreground focus:ring-black border-border rounded">
                            <label for="is_active" class="ml-2 block text-sm text-muted-foreground">
                                Active (automatic update will run according to schedule)
                            </label>
                        </div>
                        @error('is_active')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Current Status Info -->
                <div class="bg-tesla-50 border border-tesla-200 overflow-hidden rounded-lg">
                    <div class="p-4">
                        <h4 class="text-sm font-medium text-tesla-800 mb-2">Current Status</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                            <div>
                                <span class="text-tesla-600">Total Executions:</span>
                                <span class="text-tesla-800 font-medium">{{ number_format($automaticNavUpdate->total_executions) }}</span>
                            </div>
                            @if($automaticNavUpdate->last_executed_at)
                            <div>
                                <span class="text-tesla-600">Last Executed:</span>
                                <span class="text-tesla-800 font-medium">{{ $automaticNavUpdate->last_executed_at->format('M d, Y H:i') }}</span>
                            </div>
                            @endif
                            @if($automaticNavUpdate->is_active && $automaticNavUpdate->next_execution_at->gt(now()))
                            <div>
                                <span class="text-tesla-600">Next Execution:</span>
                                <span class="text-tesla-800 font-medium">{{ $automaticNavUpdate->next_execution_at->format('M d, Y H:i') }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end space-x-3">
                    <a href="{{ route('admin.investments.automatic-nav-updates.show', $automaticNavUpdate) }}" 
                       class="px-4 py-2 bg-muted text-muted-foreground text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 bg-black dark:bg-card text-white dark:text-foreground text-sm font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-muted transition-colors">
                        Update Automatic NAV Update
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');

        // Set minimum end date when start date changes
        startDateInput.addEventListener('change', function() {
            endDateInput.min = this.value;
            if (endDateInput.value && endDateInput.value <= this.value) {
                endDateInput.value = '';
            }
        });

        // Set initial minimum end date
        if (startDateInput.value) {
            endDateInput.min = startDateInput.value;
        }
    });
    </script>
    @endpush
</x-admin-layout>
