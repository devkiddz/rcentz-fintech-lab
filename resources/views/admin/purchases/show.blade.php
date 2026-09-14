<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-light text-lg text-foreground leading-tight mr-4">
                    Purchase #{{ $purchase->id }}
                </h2>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('admin.purchases.index') }}" 
                   class="inline-flex items-center px-3 py-2 bg-muted text-muted-foreground text-sm font-medium rounded-lg hover:bg-muted transition-all duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back
                </a>
                <button onclick="confirmDelete('{{ $purchase->id }}', '{{ $purchase->user->name }}', '{{ Str::limit($purchase->car->title, 30) }}')" 
                        class="inline-flex items-center px-3 py-2 bg-red-100 text-red-700 text-sm font-medium rounded-lg hover:bg-red-200 transition-all duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Delete Purchase
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Purchase Details -->
                <div class="lg:col-span-2 space-y-4">
                    <!-- Transaction Information -->
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <div class="flex items-center justify-between">
                                <h3 class="text-base font-medium text-foreground">Transaction Details</h3>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium {{ $purchase->status_badge }}">
                                    {{ ucfirst($purchase->status) }}
                                </span>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Purchase ID</label>
                                    <p class="text-base font-medium text-foreground">#{{ $purchase->id }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Amount</label>
                                    <p class="text-base font-medium text-green-600">{{ currency_symbol() }}{{ number_format($purchase->amount, 0) }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Payment Method</label>
                                    <p class="text-base font-medium text-foreground">{{ $purchase->paymentMethod->name }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Purchase Date</label>
                                    <p class="text-base font-medium text-foreground">{{ $purchase->purchased_at->format('M d, Y h:i A') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Vehicle Information -->
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Vehicle Details</h3>
                        </div>
                        <div class="p-4">
                            <div class="flex items-start space-x-4">
                                @if($purchase->car->images && count($purchase->car->images) > 0)
                                    <img src="{{ strpos($purchase->car->images[0], 'http') === 0 ? $purchase->car->images[0] : asset('storage/' . $purchase->car->images[0]) }}" 
                                         alt="{{ $purchase->car->title }}" 
                                         class="w-24 h-18 object-cover rounded-lg border border-border dark:border-gray-700">
                                @else
                                    <div class="w-24 h-18 bg-muted rounded-lg flex items-center justify-center border border-border dark:border-gray-700">
                                        <svg class="w-8 h-8 text-gray-400 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                        </svg>
                                    </div>
                                @endif
                                <div class="flex-1">
                                    <h4 class="text-base font-medium text-foreground dark:text-white mb-2">{{ $purchase->car->title }}</h4>
                                    <div class="grid grid-cols-2 gap-3 text-xs">
                                        <div>
                                            <span class="text-muted-foreground dark:text-gray-300">Year:</span>
                                            <span class="font-medium text-foreground ml-2">{{ $purchase->car->year }}</span>
                                        </div>
                                        <div>
                                            <span class="text-muted-foreground dark:text-gray-300">Mileage:</span>
                                            <span class="font-medium text-foreground ml-2">{{ number_format($purchase->car->mileage) }} miles</span>
                                        </div>
                                        <div>
                                            <span class="text-muted-foreground dark:text-gray-300">Color:</span>
                                            <span class="font-medium text-foreground ml-2">{{ $purchase->car->color }}</span>
                                        </div>
                                        <div>
                                            <span class="text-muted-foreground dark:text-gray-300">Original Price:</span>
                                            <span class="font-medium text-foreground ml-2">{{ currency_symbol() }}{{ number_format($purchase->car->price, 0) }}</span>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <a href="{{ route('admin.cars.show', $purchase->car) }}" 
                                           class="inline-flex items-center px-3 py-1.5 bg-tesla-100 text-tesla-700 text-xs font-medium rounded hover:bg-tesla-200 transition-colors">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                            View Vehicle Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customer Information -->
                <div class="space-y-4">
                    <!-- Customer Details -->
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Customer Information</h3>
                        </div>
                        <div class="p-4">
                            <div class="flex items-center space-x-3 mb-4">
                                @if($purchase->user->profile_image)
                                    <img src="{{ asset('storage/' . $purchase->user->profile_image) }}" 
                                         alt="{{ $purchase->user->name }}" 
                                         class="w-12 h-12 rounded-lg object-cover">
                                @else
                                    <div class="w-12 h-12 bg-gradient-to-br from-tesla-500 to-purple-600 rounded-lg flex items-center justify-center">
                                        <span class="text-white font-bold text-sm">{{ strtoupper(substr($purchase->user->name, 0, 2)) }}</span>
                                    </div>
                                @endif
                                <div>
                                    <h4 class="text-base font-medium text-foreground">{{ $purchase->user->name }}</h4>
                                    <p class="text-xs text-muted-foreground dark:text-gray-300">{{ $purchase->user->email }}</p>
                                    @if($purchase->user->is_admin)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-purple-100 text-purple-800 mt-1">
                                            Admin
                                        </span>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Member Since</label>
                                    <p class="text-sm font-medium text-foreground">{{ $purchase->user->created_at->format('M d, Y') }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Total Purchases</label>
                                    <p class="text-sm font-medium text-foreground">{{ $purchase->user->purchases->count() }} orders</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Total Spent</label>
                                    <p class="text-sm font-medium text-green-600">{{ currency_symbol() }}{{ number_format($purchase->user->purchases->where('status', 'completed')->sum('amount'), 0) }}</p>
                                </div>
                            </div>

                            <div class="mt-4">
                                <a href="{{ route('admin.users.show', $purchase->user) }}" 
                                   class="w-full inline-flex items-center justify-center px-3 py-2 bg-muted text-muted-foreground text-sm font-medium rounded hover:bg-muted transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    View Customer Profile
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Status Management -->
                    @if($purchase->status !== 'completed')
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Status Management</h3>
                        </div>
                        <div class="p-4">
                            <form method="POST" action="{{ route('admin.purchases.update-status', $purchase) }}">
                                @csrf
                                @method('PATCH')
                                <div class="space-y-3">
                                    <div>
                                        <label for="status" class="block text-xs font-medium text-muted-foreground mb-2">Update Status</label>
                                        <select name="status" id="status" class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-black">
                                            <option value="pending" {{ $purchase->status == 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="processing" {{ $purchase->status == 'processing' ? 'selected' : '' }}>Processing</option>
                                            <option value="completed" {{ $purchase->status == 'completed' ? 'selected' : '' }}>Completed</option>
                                            <option value="cancelled" {{ $purchase->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                            <option value="refunded" {{ $purchase->status == 'refunded' ? 'selected' : '' }}>Refunded</option>
                                        </select>
                                    </div>
                                    <button type="submit" 
                                            class="w-full inline-flex items-center justify-center px-4 py-2 bg-black dark:bg-card text-white dark:text-foreground text-sm font-medium rounded hover:opacity-90 transition-all duration-200">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                        Update Status
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    @endif

                    <!-- Timeline -->
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Purchase Timeline</h3>
                        </div>
                        <div class="p-4">
                            <div class="space-y-3">
                                <div class="flex items-center space-x-3">
                                    <div class="w-6 h-6 bg-tesla-100 rounded-full flex items-center justify-center">
                                        <svg class="w-3 h-3 text-tesla-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-foreground">Purchase Created</p>
                                        <p class="text-xs text-muted-foreground dark:text-gray-300">{{ $purchase->created_at->format('M d, Y h:i A') }}</p>
                                    </div>
                                </div>
                                
                                @if($purchase->purchased_at)
                                <div class="flex items-center space-x-3">
                                    <div class="w-6 h-6 bg-green-100 rounded-full flex items-center justify-center">
                                        <svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-foreground">Purchase Confirmed</p>
                                        <p class="text-xs text-muted-foreground dark:text-gray-300">{{ $purchase->purchased_at->format('M d, Y h:i A') }}</p>
                                    </div>
                                </div>
                                @endif
                                
                                <div class="flex items-center space-x-3">
                                    <div class="w-6 h-6 {{ $purchase->status == 'completed' ? 'bg-green-100' : 'bg-muted' }} rounded-full flex items-center justify-center">
                                        <svg class="w-3 h-3 {{ $purchase->status == 'completed' ? 'text-green-600' : 'text-muted-foreground' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium {{ $purchase->status == 'completed' ? 'text-foreground' : 'text-muted-foreground' }}">
                                            {{ $purchase->status == 'completed' ? 'Purchase Completed' : 'Awaiting Completion' }}
                                        </p>
                                        @if($purchase->status == 'completed')
                                            <p class="text-xs text-muted-foreground dark:text-gray-300">{{ $purchase->updated_at->format('M d, Y h:i A') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
                            class="px-4 py-2 bg-muted text-muted-foreground text-sm font-medium rounded hover:bg-muted transition-colors">
                        Cancel
                    </button>
                    <button onclick="submitDelete()" 
                            class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded hover:bg-red-700 transition-colors">
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
