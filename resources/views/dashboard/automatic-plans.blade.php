<x-user-layout>
    <x-slot name="header">
        Automatic Investments
    </x-slot>

    <div class="max-w-7xl mx-auto">
        <!-- Enhanced Header -->
        <div class="bg-gradient-to-br from-tesla-600 via-tesla-700 to-tesla-800 dark:from-tesla-700 dark:via-tesla-800 dark:to-tesla-900 rounded-2xl p-6 mb-6 text-white relative overflow-hidden">
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-0 right-0 w-48 h-48 bg-card rounded-full -translate-y-24 translate-x-24"></div>
                <div class="absolute bottom-0 left-0 w-24 h-24 bg-card rounded-full translate-y-12 -translate-x-12"></div>
            </div>
            
            <div class="relative z-10">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                    <div class="mb-4 lg:mb-0 lg:flex-1">
                        <h1 class="text-xl font-light mb-1">Automatic Investments</h1>
                        <p class="text-tesla-100 dark:text-gray-300 text-sm">Manage your recurring investment schedules</p>
                    </div>
                    
                    <!-- Enhanced Stats Card -->
                    <div class="bg-card bg-opacity-15 backdrop-blur-xl rounded-xl p-4 border border-white border-opacity-20 shadow-xl lg:w-80">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <p class="text-xs text-tesla-100 dark:text-gray-300 mb-1">Active Plans</p>
                                <p class="text-lg font-light">{{ $automaticPlans->where('is_active', true)->count() }}</p>
                            </div>
                            <div class="w-10 h-10 flex items-center justify-center">
                                <i data-lucide="repeat" class="w-5 h-5 text-white"></i>
                            </div>
                        </div>
                        <div class="flex space-x-4 text-xs">
                            <div class="flex-1 text-center">
                                <p class="text-tesla-100 dark:text-gray-300">Total Plans</p>
                                <p class="text-white font-medium">{{ $automaticPlans->count() }}</p>
                            </div>
                            <div class="flex-1 text-center">
                                <p class="text-tesla-100 dark:text-gray-300">Monthly Total</p>
                                <p class="text-white font-medium">{{ currency_symbol() }}{{ number_format($automaticPlans->where('is_active', true)->sum('amount'), 0) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-card rounded-xl p-6 shadow-sm border border-gray-100 mb-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-light text-foreground mb-1">Quick Actions</h3>
                    <p class="text-xs text-muted-foreground dark:text-gray-300">Manage your automatic investment plans</p>
                </div>
                <button onclick="openCreateModal()" class="inline-flex items-center px-4 py-2 bg-foreground text-background text-xs font-medium rounded-lg hover:opacity-90 transition-colors duration-200">
                    <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                    Create New Plan
                </button>
            </div>
        </div>

        <!-- Automatic Investment Plans -->
        <div class="bg-card rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-border">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-light text-foreground mb-1">Your Automatic Investment Plans</h3>
                        <p class="text-xs text-muted-foreground dark:text-gray-300">Recurring investment schedules</p>
                    </div>
                </div>
            </div>
            
            @if($automaticPlans->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-muted/30">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Investment Plan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Frequency</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Next Investment</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-card divide-y divide-gray-100 dark:divide-tesla-600">
                            @foreach($automaticPlans as $plan)
                            <tr class="hover:bg-muted/30 transition-colors duration-200">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 w-8 h-8 bg-muted rounded-lg flex items-center justify-center">
                                            <i data-lucide="trending-up" class="w-4 h-4 text-muted-foreground"></i>
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-foreground">{{ $plan->investmentPlan->name }}</div>
                                            <div class="text-xs text-muted-foreground dark:text-gray-300">{{ $plan->investmentPlan->category }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-foreground">{{ currency_symbol() }}{{ number_format($plan->amount, 2) }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-foreground capitalize">{{ $plan->frequency }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-foreground">{{ $plan->next_investment_date ? $plan->next_investment_date->format('M j, Y') : 'N/A' }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $plan->is_active ? 'bg-green-100 text-green-800' : 'bg-muted text-foreground' }}">
                                        {{ $plan->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex space-x-2">
                                        <button onclick="editPlan({{ $plan->id }})" 
                                                class="w-8 h-8 flex items-center justify-center text-tesla-600 hover:text-tesla-800 hover:bg-tesla-50 rounded-lg transition-colors duration-200"
                                                title="Edit Plan">
                                            <i data-lucide="edit" class="w-4 h-4"></i>
                                        </button>
                                        <button onclick="togglePlan({{ $plan->id }})" 
                                                class="w-8 h-8 flex items-center justify-center {{ $plan->is_active ? 'text-red-600 hover:text-red-800 hover:bg-red-50' : 'text-green-600 hover:text-green-800 hover:bg-green-50' }} rounded-lg transition-colors duration-200"
                                                title="{{ $plan->is_active ? 'Pause Plan' : 'Activate Plan' }}">
                                            <i data-lucide="{{ $plan->is_active ? 'pause' : 'play' }}" class="w-4 h-4"></i>
                                        </button>
                                        <button onclick="deletePlan({{ $plan->id }})" 
                                                class="w-8 h-8 flex items-center justify-center text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition-colors duration-200"
                                                title="Delete Plan">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-muted rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="repeat" class="w-8 h-8 text-gray-400 dark:text-gray-300"></i>
                    </div>
                    <h3 class="text-lg font-light text-foreground mb-2">No automatic investment plans</h3>
                    <p class="text-muted-foreground text-sm mb-4">Set up recurring investments to build your portfolio automatically</p>
                    <button onclick="openCreateModal()" class="inline-flex items-center px-4 py-2 bg-foreground text-background text-sm font-medium rounded-lg hover:opacity-90 transition-colors duration-200">
                        <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                        Create Your First Plan
                    </button>
                </div>
            @endif
        </div>

        <!-- Summary Stats -->
        @if($automaticPlans->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
            <!-- Active Plans -->
            <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <p class="text-xs text-muted-foreground dark:text-gray-300 mb-1">Active Plans</p>
                        <p class="text-lg font-light text-foreground">{{ $automaticPlans->where('is_active', true)->count() }}</p>
                    </div>
                    <div class="w-8 h-8 flex items-center justify-center">
                        <i data-lucide="check-circle" class="w-4 h-4 text-green-600"></i>
                    </div>
                </div>
            </div>

            <!-- Total Plans -->
            <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <p class="text-xs text-muted-foreground dark:text-gray-300 mb-1">Total Plans</p>
                        <p class="text-lg font-light text-foreground">{{ $automaticPlans->count() }}</p>
                    </div>
                    <div class="w-8 h-8 flex items-center justify-center">
                        <i data-lucide="repeat" class="w-4 h-4 text-tesla-600"></i>
                    </div>
                </div>
            </div>

            <!-- Monthly Investment -->
            <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <p class="text-xs text-muted-foreground dark:text-gray-300 mb-1">Monthly Investment</p>
                        <p class="text-lg font-light text-foreground">{{ currency_symbol() }}{{ number_format($automaticPlans->where('is_active', true)->sum('amount'), 2) }}</p>
                    </div>
                    <div class="w-8 h-8 flex items-center justify-center">
                        <i data-lucide="dollar-sign" class="w-4 h-4 text-green-600"></i>
                    </div>
                </div>
            </div>

            <!-- Average Amount -->
            <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <p class="text-xs text-muted-foreground dark:text-gray-300 mb-1">Average Amount</p>
                        <p class="text-lg font-light text-foreground">
                            ${{ $automaticPlans->where('is_active', true)->count() > 0 ? number_format($automaticPlans->where('is_active', true)->avg('amount'), 2) : '0.00' }}
                        </p>
                    </div>
                    <div class="w-8 h-8 flex items-center justify-center">
                        <i data-lucide="bar-chart-3" class="w-4 h-4 text-purple-600"></i>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Create/Edit Modal -->
    <div id="planModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-card rounded-xl p-6 w-full max-w-md">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-light text-foreground" id="modalTitle">Create Automatic Investment Plan</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-muted-foreground">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                
                <form id="planForm" method="POST" action="{{ route('automatic-investments.create') }}">
                    @csrf
                    <input type="hidden" id="planId" name="plan_id">
                    
                    <!-- Investment Plan Selection -->
                    <div class="mb-4">
                        <label for="investment_plan_id" class="block text-sm font-medium text-foreground mb-2">Investment Plan</label>
                        <select id="investment_plan_id" name="investment_plan_id" required class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-transparent">
                            <option value="">Select an investment plan</option>
                            @foreach(\App\Models\InvestmentPlan::all() as $plan)
                                <option value="{{ $plan->id }}">{{ $plan->name }} ({{ $plan->category }})</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- Amount -->
                    <div class="mb-4">
                        <label for="amount" class="block text-sm font-medium text-foreground mb-2">Investment Amount</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-muted-foreground dark:text-gray-300">$</span>
                            <input type="number" id="amount" name="amount" step="0.01" min="1" required 
                                   class="w-full pl-8 pr-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-transparent"
                                   placeholder="0.00">
                        </div>
                    </div>
                    
                    <!-- Frequency -->
                    <div class="mb-4">
                        <label for="frequency" class="block text-sm font-medium text-foreground mb-2">Frequency</label>
                        <select id="frequency" name="frequency" required class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-transparent">
                            <option value="">Select frequency</option>
                            <option value="weekly">Weekly</option>
                            <option value="biweekly">Bi-weekly</option>
                            <option value="monthly">Monthly</option>
                            <option value="quarterly">Quarterly</option>
                        </select>
                    </div>
                    
                    <!-- Start Date -->
                    <div class="mb-6">
                        <label for="start_date" class="block text-sm font-medium text-foreground mb-2">Start Date</label>
                        <input type="date" id="start_date" name="start_date" required 
                               class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-transparent"
                               min="{{ date('Y-m-d', strtotime('+1 day')) }}">
                    </div>
                    
                    <div class="flex space-x-2">
                        <button type="button" onclick="closeModal()" class="flex-1 px-4 py-2 border border-border text-foreground text-xs font-medium rounded-lg hover:bg-muted/30 transition-colors duration-200">
                            Cancel
                        </button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-foreground text-background text-xs font-medium rounded-lg hover:opacity-90 transition-colors duration-200">
                            Create Plan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-card rounded-xl p-6 w-full max-w-md">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-light text-foreground">Edit Automatic Investment Plan</h3>
                    <button onclick="closeEditModal()" class="text-gray-400 hover:text-muted-foreground">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                
                <form id="editForm" method="POST">
                    @csrf
                    @method('PATCH')
                    
                    <!-- Amount -->
                    <div class="mb-4">
                        <label for="edit_amount" class="block text-sm font-medium text-foreground mb-2">Investment Amount</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-muted-foreground dark:text-gray-300">$</span>
                            <input type="number" id="edit_amount" name="amount" step="0.01" min="1" required 
                                   class="w-full pl-8 pr-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-transparent"
                                   placeholder="0.00">
                        </div>
                    </div>
                    
                    <!-- Frequency -->
                    <div class="mb-4">
                        <label for="edit_frequency" class="block text-sm font-medium text-foreground mb-2">Frequency</label>
                        <select id="edit_frequency" name="frequency" required class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-transparent">
                            <option value="weekly">Weekly</option>
                            <option value="biweekly">Bi-weekly</option>
                            <option value="monthly">Monthly</option>
                            <option value="quarterly">Quarterly</option>
                        </select>
                    </div>
                    
                    <!-- Active Status -->
                    <div class="mb-6">
                        <label class="flex items-center">
                            <input type="checkbox" id="edit_is_active" name="is_active" value="1" class="w-4 h-4 text-foreground border-border rounded focus:ring-ring">
                            <span class="ml-2 text-sm text-foreground">Active</span>
                        </label>
                    </div>
                    
                    <div class="flex space-x-2">
                        <button type="button" onclick="closeEditModal()" class="flex-1 px-4 py-2 border border-border text-foreground text-xs font-medium rounded-lg hover:bg-muted/30 transition-colors duration-200">
                            Cancel
                        </button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-foreground text-background text-xs font-medium rounded-lg hover:opacity-90 transition-colors duration-200">
                            Update Plan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openCreateModal() {
            document.getElementById('planModal').classList.remove('hidden');
            document.getElementById('modalTitle').textContent = 'Create Automatic Investment Plan';
            document.getElementById('planForm').action = '{{ route('automatic-investments.create') }}';
            document.getElementById('planId').value = '';
            document.getElementById('investment_plan_id').value = '';
            document.getElementById('amount').value = '';
            document.getElementById('frequency').value = '';
            document.getElementById('start_date').value = '';
        }

        function closeModal() {
            document.getElementById('planModal').classList.add('hidden');
        }

        function editPlan(planId) {
            // Fetch plan details and populate edit modal
            fetch(`/automatic-investments/${planId}/edit`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('editModal').classList.remove('hidden');
                    document.getElementById('editForm').action = `{{ url('automatic-investments') }}/${planId}`;
                    document.getElementById('edit_amount').value = data.amount;
                    document.getElementById('edit_frequency').value = data.frequency;
                    document.getElementById('edit_is_active').checked = data.is_active;
                })
                .catch(error => {
                    console.error('Error fetching plan details:', error);
                    alert('Error loading plan details');
                });
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        function togglePlan(planId) {
            if (confirm('Are you sure you want to toggle this automatic investment plan?')) {
                fetch(`{{ url('automatic-investments') }}/${planId}/toggle`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error toggling plan: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error toggling plan:', error);
                    alert('Error toggling plan');
                });
            }
        }

        function deletePlan(planId) {
            if (confirm('Are you sure you want to delete this automatic investment plan?')) {
                fetch(`{{ url('automatic-investments') }}/${planId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error deleting plan: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error deleting plan:', error);
                    alert('Error deleting plan');
                });
            }
        }
    </script>
</x-user-layout> 
