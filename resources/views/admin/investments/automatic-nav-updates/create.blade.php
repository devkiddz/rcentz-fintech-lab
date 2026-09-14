<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-light text-lg text-foreground leading-tight mr-4">
                    Create Automatic NAV Update
                </h2>
            </div>
            <a href="{{ route('admin.investments.automatic-nav-updates.index') }}" 
               class="px-3 py-2 bg-muted text-muted-foreground text-sm font-medium rounded-lg hover:bg-muted transition-colors">
                Back
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.investments.automatic-nav-updates.store') }}" class="space-y-6" id="automaticUpdateForm">
                @csrf
                
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
                                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                       placeholder="e.g., Tesla Growth Fund Daily Increase"
                                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-black transition-colors">
                                @error('name')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="investment_plan_id" class="block text-xs font-medium text-muted-foreground mb-2">Investment Plan *</label>
                                <select name="investment_plan_id" id="investment_plan_id" required
                                        class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-black transition-colors">
                                    <option value="">Select Investment Plan</option>
                                    @foreach($investmentPlans as $plan)
                                        <option value="{{ $plan->id }}" 
                                                data-current-nav="{{ $plan->nav }}"
                                                {{ old('investment_plan_id') == $plan->id ? 'selected' : '' }}>
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
                                      placeholder="Optional description of this automatic update"
                                      class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-black transition-colors">{{ old('description') }}</textarea>
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
                                        class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-black transition-colors">
                                    <option value="">Select Update Type</option>
                                    <option value="increase" {{ old('update_type') == 'increase' ? 'selected' : '' }}>
                                        Increase NAV
                                    </option>
                                    <option value="decrease" {{ old('update_type') == 'decrease' ? 'selected' : '' }}>
                                        Decrease NAV
                                    </option>
                                </select>
                                @error('update_type')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="update_amount" class="block text-xs font-medium text-muted-foreground mb-2">Update Amount *</label>
                                <input type="number" name="update_amount" id="update_amount" value="{{ old('update_amount') }}" 
                                       step="0.0001" min="0.0001" max="999.9999" required
                                       placeholder="0.1000"
                                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-black transition-colors">
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
                                       value="{{ old('update_interval_value', 2) }}" min="1" max="999" required
                                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-black transition-colors">
                                @error('update_interval_value')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="update_interval_unit" class="block text-xs font-medium text-muted-foreground mb-2">Interval Unit *</label>
                                <select name="update_interval_unit" id="update_interval_unit" required
                                        class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-black transition-colors">
                                    <option value="">Select Unit</option>
                                    <option value="minutes" {{ old('update_interval_unit') == 'minutes' ? 'selected' : '' }}>Minutes</option>
                                    <option value="hours" {{ old('update_interval_unit') == 'hours' ? 'selected' : '' }}>Hours</option>
                                    <option value="days" {{ old('update_interval_unit') == 'days' ? 'selected' : '' }}>Days</option>
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
                                       value="{{ old('start_date', now()->format('Y-m-d\TH:i')) }}" required
                                       min="{{ now()->format('Y-m-d\TH:i') }}"
                                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-black transition-colors">
                                @error('start_date')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="end_date" class="block text-xs font-medium text-muted-foreground mb-2">End Date <span class="text-xs text-muted-foreground dark:text-gray-300">(UTC)</span> *</label>
                                <input type="datetime-local" name="end_date" id="end_date" 
                                       value="{{ old('end_date') }}" required
                                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-black transition-colors">
                                @error('end_date')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Preview Section -->
                <div class="bg-card border border-border overflow-hidden rounded-lg" id="previewSection" style="display: none;">
                    <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-tesla-50">
                        <h3 class="text-base font-medium text-tesla-800">Update Preview</h3>
                        <p class="text-xs text-tesla-600 mt-1">Preview of how this automatic update will work</p>
                    </div>
                    <div class="p-4" id="previewContent">
                        <!-- Preview content will be loaded here -->
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end space-x-3">
                    <a href="{{ route('admin.investments.automatic-nav-updates.index') }}" 
                       class="px-4 py-2 bg-muted text-muted-foreground text-sm font-medium rounded-lg hover:bg-muted transition-colors">
                        Cancel
                    </a>
                    <button type="button" id="previewBtn" 
                            class="px-4 py-2 bg-tesla-600 text-white text-sm font-medium rounded-lg hover:bg-tesla-700 transition-colors">
                        Preview Update
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-black dark:bg-card text-white dark:text-foreground text-sm font-medium rounded-lg hover:opacity-90 transition-colors">
                        Create Automatic Update
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('automaticUpdateForm');
        const previewBtn = document.getElementById('previewBtn');
        const previewSection = document.getElementById('previewSection');
        const previewContent = document.getElementById('previewContent');
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');

        // Set minimum end date when start date changes
        startDateInput.addEventListener('change', function() {
            endDateInput.min = this.value;
            if (endDateInput.value && endDateInput.value <= this.value) {
                endDateInput.value = '';
            }
        });

        // Preview functionality
        previewBtn.addEventListener('click', function() {
            const formData = new FormData(form);
            
            // Validate required fields
            const requiredFields = ['investment_plan_id', 'update_type', 'update_amount', 'update_interval_value', 'update_interval_unit', 'start_date', 'end_date'];
            let isValid = true;
            let missingFields = [];
            
            requiredFields.forEach(field => {
                if (!formData.get(field)) {
                    isValid = false;
                    missingFields.push(field);
                }
            });
            
            if (!isValid) {
                alert('Please fill in all required fields before previewing: ' + missingFields.join(', '));
                return;
            }

            // Show loading
            previewContent.innerHTML = '<div class="text-center py-4"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-black mx-auto"></div><p class="text-sm text-muted-foreground mt-2">Generating preview...</p></div>';
            previewSection.style.display = 'block';

            // Make AJAX request
            fetch('{{ route("admin.investments.automatic-nav-updates.preview") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    previewContent.innerHTML = `<div class="text-red-600 text-sm">${data.error}</div>`;
                    return;
                }

                let executionsHtml = '';
                if (data.executions && data.executions.length > 0) {
                    executionsHtml = data.executions.map(exec => `
                        <tr class="border-b border-border">
                            <td class="py-2 text-xs text-muted-foreground dark:text-gray-300">${new Date(exec.date).toLocaleString()}</td>
                            <td class="py-2 text-xs text-right">${exec.nav_before.toFixed(4)}</td>
                            <td class="py-2 text-xs text-right">${exec.nav_after.toFixed(4)}</td>
                            <td class="py-2 text-xs text-right ${exec.change >= 0 ? 'text-green-600' : 'text-red-600'}">${exec.change >= 0 ? '+' : ''}${exec.change.toFixed(4)}</td>
                        </tr>
                    `).join('');
                }

                previewContent.innerHTML = `
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div class="bg-muted/40 p-3 rounded">
                            <p class="text-xs text-muted-foreground dark:text-gray-300">Investment Plan</p>
                            <p class="text-sm font-medium">${data.plan}</p>
                        </div>
                        <div class="bg-muted/40 p-3 rounded">
                            <p class="text-xs text-muted-foreground dark:text-gray-300">Total Executions</p>
                            <p class="text-sm font-medium">${data.total_executions}</p>
                        </div>
                        <div class="bg-muted/40 p-3 rounded">
                            <p class="text-xs text-muted-foreground dark:text-gray-300">Final NAV</p>
                            <p class="text-sm font-medium">${data.final_nav.toFixed(4)}</p>
                        </div>
                    </div>
                    <div class="bg-muted/40 p-3 rounded mb-4">
                        <p class="text-xs text-muted-foreground dark:text-gray-300">Total NAV Change</p>
                        <p class="text-sm font-medium ${data.total_change >= 0 ? 'text-green-600' : 'text-red-600'}">${data.total_change >= 0 ? '+' : ''}${data.total_change.toFixed(4)}</p>
                    </div>
                    ${executionsHtml ? `
                        <div>
                            <h4 class="text-sm font-medium text-foreground dark:text-white mb-2">First 10 Executions Preview</h4>
                            <div class="overflow-x-auto">
                                <table class="w-full text-xs">
                                    <thead>
                                        <tr class="border-b border-border dark:border-gray-700">
                                            <th class="text-left py-2 text-muted-foreground dark:text-gray-300">Date</th>
                                            <th class="text-right py-2 text-muted-foreground dark:text-gray-300">NAV Before</th>
                                            <th class="text-right py-2 text-muted-foreground dark:text-gray-300">NAV After</th>
                                            <th class="text-right py-2 text-muted-foreground dark:text-gray-300">Change</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${executionsHtml}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    ` : ''}
                `;
            })
            .catch(error => {
                previewContent.innerHTML = `<div class="text-red-600 text-sm">Error generating preview: ${error.message}</div>`;
            });
        });
    });
    </script>
</x-admin-layout>
