<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-light text-lg text-foreground leading-tight">
                    {{ __('Purchase History') }}
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-card border border-border p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-xs font-medium text-muted-foreground dark:text-gray-300">Total Revenue</p>
                            <p class="text-lg font-light text-foreground">${{ number_format($totalRevenue, 0) }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-card border border-border p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-tesla-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-tesla-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-xs font-medium text-muted-foreground dark:text-gray-300">Total Purchases</p>
                            <p class="text-lg font-light text-foreground">{{ number_format($totalPurchases) }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-card border border-border p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-xs font-medium text-muted-foreground dark:text-gray-300">Pending Orders</p>
                            <p class="text-lg font-light text-foreground">{{ number_format($pendingPurchases) }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-card border border-border p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-xs font-medium text-muted-foreground dark:text-gray-300">Completed</p>
                            <p class="text-lg font-light text-foreground">{{ number_format($completedPurchases) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-card border border-border p-4 rounded-lg mb-6">
                <form method="GET" action="{{ route('admin.purchases.index') }}" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                        <!-- Search -->
                        <div>
                            <label for="search" class="block text-xs font-medium text-muted-foreground mb-2">Search</label>
                            <input type="text" 
                                   name="search" 
                                   id="search"
                                   value="{{ request('search') }}"
                                   placeholder="Customer or car..."
                                   class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-black">
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <label for="status" class="block text-xs font-medium text-muted-foreground mb-2">Status</label>
                            <select name="status" id="status" class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-black">
                                <option value="">All Statuses</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Processing</option>
                                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                <option value="refunded" {{ request('status') == 'refunded' ? 'selected' : '' }}>Refunded</option>
                            </select>
                        </div>

                        <!-- User Filter -->
                        <div>
                            <label for="user_id" class="block text-xs font-medium text-muted-foreground mb-2">Customer</label>
                            <select name="user_id" id="user_id" class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-black">
                                <option value="">All Customers</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Date From -->
                        <div>
                            <label for="date_from" class="block text-xs font-medium text-muted-foreground mb-2">From Date</label>
                            <input type="date" 
                                   name="date_from" 
                                   id="date_from"
                                   value="{{ request('date_from') }}"
                                   class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-black">
                        </div>

                        <!-- Date To -->
                        <div>
                            <label for="date_to" class="block text-xs font-medium text-muted-foreground mb-2">To Date</label>
                            <input type="date" 
                                   name="date_to" 
                                   id="date_to"
                                   value="{{ request('date_to') }}"
                                   class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-black">
                        </div>
                    </div>

                    <div class="flex items-center space-x-3">
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 bg-black dark:bg-card text-white dark:text-foreground text-sm font-medium rounded-lg hover:opacity-90 transition-all duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            Filter
                        </button>
                        <a href="{{ route('admin.purchases.index') }}" 
                           class="inline-flex items-center px-4 py-2 bg-muted text-muted-foreground text-sm font-medium rounded-lg hover:bg-muted transition-all duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            @if($purchases->count() > 0)
            <!-- Purchase History Table -->
            <div class="bg-card border border-border overflow-hidden rounded-lg">
                <div class="px-4 py-3 border-b border-border dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-medium text-foreground">Purchase Transactions</h3>
                        <div class="flex items-center space-x-2">
                            <span class="text-xs text-muted-foreground dark:text-gray-300">{{ $purchases->count() }} of {{ $purchases->total() }} purchases</span>
                        </div>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-muted/40">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Customer & Vehicle</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Amount</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Payment Method</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-card divide-y divide-gray-200">
                            @foreach($purchases as $purchase)
                            <tr class="hover:bg-muted/40 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center">
                                        @if($purchase->car->images && count($purchase->car->images) > 0)
                                            <img src="{{ strpos($purchase->car->images[0], 'http') === 0 ? $purchase->car->images[0] : asset('storage/' . $purchase->car->images[0]) }}" 
                                                 alt="{{ $purchase->car->title }}" 
                                                 class="w-10 h-8 object-cover rounded-lg mr-3">
                                        @else
                                            <div class="w-10 h-8 bg-muted rounded-lg flex items-center justify-center mr-3">
                                                <svg class="w-4 h-4 text-gray-400 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                                </svg>
                                            </div>
                                        @endif
                                        <div>
                                            <div class="text-sm font-medium text-foreground">{{ $purchase->user->name }}</div>
                                            <div class="text-xs text-muted-foreground dark:text-gray-300">{{ $purchase->user->email }}</div>
                                            <div class="text-xs text-gray-400 mt-1">{{ Str::limit($purchase->car->title, 30) }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm font-medium text-foreground">${{ number_format($purchase->amount, 0) }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-foreground">{{ $purchase->paymentMethod->name }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium {{ $purchase->status_badge }}">
                                        {{ ucfirst($purchase->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-foreground">{{ $purchase->purchased_at->format('M d, Y') }}</div>
                                    <div class="text-xs text-muted-foreground dark:text-gray-300">{{ $purchase->purchased_at->format('h:i A') }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center space-x-2">
                                        <a href="{{ route('admin.purchases.show', $purchase) }}" 
                                           class="inline-flex items-center px-2 py-1 bg-tesla-100 text-tesla-700 text-xs font-medium rounded hover:bg-tesla-200 transition-colors">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                            View
                                        </a>
                                        
                                        @if($purchase->status !== 'completed')
                                        <form method="POST" action="{{ route('admin.purchases.update-status', $purchase) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <select name="status" onchange="this.form.submit()" class="text-xs border-border rounded px-2 py-1 bg-muted/40 hover:bg-muted dark:hover:bg-gray-700 transition-colors">
                                                <option value="pending" {{ $purchase->status == 'pending' ? 'selected' : '' }}>Pending</option>
                                                <option value="processing" {{ $purchase->status == 'processing' ? 'selected' : '' }}>Processing</option>
                                                <option value="completed" {{ $purchase->status == 'completed' ? 'selected' : '' }}>Completed</option>
                                                <option value="cancelled" {{ $purchase->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                                <option value="refunded" {{ $purchase->status == 'refunded' ? 'selected' : '' }}>Refunded</option>
                                            </select>
                                        </form>
                                        @endif
                                        
                                        <!-- Delete Button -->
                                        <button onclick="confirmDelete('{{ $purchase->id }}', '{{ $purchase->user->name }}', '{{ Str::limit($purchase->car->title, 30) }}')" 
                                                class="inline-flex items-center px-2 py-1 bg-red-100 text-red-700 text-xs font-medium rounded hover:bg-red-200 transition-colors">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                @if($purchases->hasPages())
                <div class="px-4 py-3 border-t border-border dark:border-gray-700">
                    {{ $purchases->appends(request()->query())->links() }}
                </div>
                @endif
            </div>
            @else
            <!-- Empty State -->
            <div class="bg-card border border-border p-8 rounded-lg text-center">
                <div class="w-16 h-16 bg-muted rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-foreground dark:text-white mb-2">No purchases found</h3>
                <p class="text-xs text-muted-foreground dark:text-gray-300 mb-6 max-w-md mx-auto">
                    @if(request()->hasAny(['search', 'status', 'user_id', 'date_from', 'date_to']))
                        No purchases match your current filters. Try adjusting your search criteria.
                    @else
                        No purchases have been made yet. Purchase history will appear here once customers start buying vehicles.
                    @endif
                </p>
                @if(request()->hasAny(['search', 'status', 'user_id', 'date_from', 'date_to']))
                <a href="{{ route('admin.purchases.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-black dark:bg-card text-white dark:text-foreground text-sm font-medium rounded-lg hover:opacity-90 transition-all duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Clear Filters
                </a>
                @endif
            </div>
            @endif
        </div>
    </div>

    <!-- Hidden Delete Form -->
    <form id="delete-form" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>

    <!-- Delete Confirmation Modal -->
    <div id="delete-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border border-border w-96 max-w-[calc(100vw-2rem)] shadow-lg rounded-xl bg-card text-card-foreground">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <h3 class="text-base font-medium text-foreground dark:text-white mb-2">Delete Purchase Record</h3>
                <p class="text-sm text-muted-foreground dark:text-gray-300 mb-4">
                    Are you sure you want to delete the purchase record for <strong id="customer-name"></strong>?
                </p>
                <p class="text-xs text-gray-400 mb-4">
                    Vehicle: <span id="car-title"></span>
                </p>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4">
                    <div class="flex items-start">
                        <svg class="w-4 h-4 text-yellow-600 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                        <div class="text-left">
                            <p class="text-xs text-yellow-800 font-medium">Warning:</p>
                            <p class="text-xs text-yellow-700">This action cannot be undone. The car's availability will be updated automatically.</p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-center space-x-3">
                    <button onclick="closeDeleteModal()" 
                            class="px-4 py-2 bg-muted text-muted-foreground text-sm font-medium rounded-lg hover:bg-muted transition-colors">
                        Cancel
                    </button>
                    <button onclick="submitDelete()" 
                            class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                        Delete Purchase
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentPurchaseId = null;

        function confirmDelete(purchaseId, customerName, carTitle) {
            currentPurchaseId = purchaseId;
            document.getElementById('customer-name').textContent = customerName;
            document.getElementById('car-title').textContent = carTitle;
            document.getElementById('delete-modal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('delete-modal').classList.add('hidden');
            currentPurchaseId = null;
        }

        function submitDelete() {
            if (currentPurchaseId) {
                const form = document.getElementById('delete-form');
                form.action = `/admin/purchases/${currentPurchaseId}`;
                form.submit();
            }
        }

        // Close modal when clicking outside
        document.getElementById('delete-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDeleteModal();
            }
        });
    </script>
</x-admin-layout> 
