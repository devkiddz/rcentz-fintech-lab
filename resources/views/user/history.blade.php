<x-user-layout>
    <x-slot name="header">
        Purchase History
    </x-slot>

    <div class="max-w-7xl mx-auto">
        <!-- Enhanced Header -->
        <div class="bg-gradient-to-br from-tesla-700 via-tesla-800 to-tesla-900 rounded-2xl p-6 mb-6 text-white relative overflow-hidden">
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-0 right-0 w-48 h-48 bg-white rounded-full -translate-y-24 translate-x-24"></div>
                <div class="absolute bottom-0 left-0 w-24 h-24 bg-white rounded-full translate-y-12 -translate-x-12"></div>
            </div>
            
            <div class="relative z-10">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                    <div class="mb-4 lg:mb-0 lg:flex-1">
                        <h1 class="text-xl font-light mb-1">Purchase History</h1>
                        <p class="text-tesla-100 dark:text-gray-300 text-sm">Track all your vehicle purchases and transactions</p>
                    </div>
                    
                    <!-- Stats Card -->
                    <div class="bg-white bg-opacity-15 backdrop-blur-xl rounded-xl p-4 border border-white border-opacity-20 shadow-xl lg:w-64">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <p class="text-xs text-tesla-100 dark:text-gray-300 mb-1">Total Purchases</p>
                                <p class="text-lg font-light">{{ $purchases->total() }}</p>
                            </div>
                            <div class="w-10 h-10 flex items-center justify-center">
                                <i data-lucide="receipt" class="w-5 h-5 text-white"></i>
                            </div>
                        </div>
                        <div class="flex space-x-4 text-xs">
                            <div class="flex-1 text-center">
                                <p class="text-tesla-100 dark:text-gray-300">Completed</p>
                                <p class="text-white font-medium">{{ $purchases->where('status', 'completed')->count() }}</p>
                            </div>
                            <div class="flex-1 text-center">
                                <p class="text-tesla-100 dark:text-gray-300">Pending</p>
                                <p class="text-white font-medium">{{ $purchases->where('status', 'pending')->count() }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Purchase History Table -->
        <div class="bg-tesla-500 rounded-xl shadow-sm border border-tesla-400">
            <div class="p-6 border-b border-tesla-400">
                <h2 class="text-lg font-semibold text-white">All Purchases</h2>
                <p class="text-sm text-tesla-100 mt-1">Complete history of your vehicle purchases</p>
            </div>
            
            @if($purchases->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-tesla-400">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Purchase</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Vehicle</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-tesla-500 divide-y divide-tesla-400">
                            @foreach($purchases as $purchase)
                                <tr class="hover:bg-tesla-400">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-gradient-to-br from-tesla-300 to-tesla-200 rounded-lg flex items-center justify-center mr-3">
                                                <i data-lucide="car" class="w-5 h-5 text-tesla-900"></i>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-white">#{{ $purchase->id }}</div>
                                                <div class="text-sm text-tesla-100">{{ $purchase->paymentMethod->name ?? 'N/A' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            @if($purchase->car->image_url)
                                                <img src="{{ $purchase->car->image_url }}" alt="{{ $purchase->car->name }}" class="w-10 h-10 rounded-lg mr-3">
                                            @else
                                                <div class="w-10 h-10 bg-tesla-400 rounded-lg flex items-center justify-center mr-3">
                                                    <i data-lucide="car" class="w-4 h-4 text-white"></i>
                                                </div>
                                            @endif
                                            <div>
                                                <div class="text-sm font-medium text-white">{{ $purchase->car->name }}</div>
                                                <div class="text-sm text-tesla-100">{{ $purchase->car->year }} • {{ $purchase->car->color }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-white">${{ number_format($purchase->amount, 2) }}</div>
                                        @if($purchase->fee > 0)
                                            <div class="text-xs text-tesla-100">Fee: ${{ number_format($purchase->fee, 2) }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                   {{ $purchase->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                                                   {{ $purchase->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                   {{ $purchase->status === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}
                                                   {{ $purchase->status === 'failed' ? 'bg-gray-100 text-gray-800' : '' }}">
                                            {{ ucfirst($purchase->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-white">
                                        {{ $purchase->created_at->format('M j, Y') }}
                                        <div class="text-xs text-tesla-100">{{ $purchase->created_at->format('g:i A') }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            @if($purchase->status === 'completed')
                                                <a href="{{ route('dashboard.invoice', $purchase) }}" 
                                                   class="w-8 h-8 flex items-center justify-center text-white hover:text-tesla-100 hover:bg-tesla-400 rounded-lg transition-colors duration-200"
                                                   title="Download Invoice">
                                                    <i data-lucide="download" class="w-4 h-4"></i>
                                                </a>
                                            @endif
                                             <button onclick="viewPurchaseDetails({{ $purchase->toJson() }})" 
                                                    class="w-8 h-8 flex items-center justify-center text-white hover:text-tesla-100 hover:bg-tesla-400 rounded-lg transition-colors duration-200"
                                                    title="View Details">
                                                <i data-lucide="eye" class="w-4 h-4"></i>
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
                    <div class="px-6 py-4 border-t border-tesla-400">
                        {{ $purchases->links() }}
                    </div>
                @endif
            @else
                <div class="p-8 text-center">
                    <div class="w-16 h-16 bg-tesla-400 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="receipt" class="w-8 h-8 text-white"></i>
                    </div>
                    <h3 class="text-lg font-medium text-white mb-2">No Purchase History</h3>
                    <p class="text-tesla-100 mb-6">You haven't made any purchases yet.</p>
                    <a href="{{ route('cars.browse') }}" 
                       class="inline-flex items-center px-4 py-2 bg-white text-tesla-500 rounded-lg hover:bg-tesla-50 transition-colors duration-200">
                        <i data-lucide="car" class="w-4 h-4 mr-2"></i>
                        Browse Vehicles
                    </a>
                </div>
            @endif
        </div>
    </div>

    <script>
        function viewPurchaseDetails(purchase) {
            const car = purchase.car || {};
            const method = purchase.payment_method || purchase.paymentMethod || {};
            const purchasedAt = purchase.purchased_at ? new Date(purchase.purchased_at) : null;
            const createdAt = purchase.created_at ? new Date(purchase.created_at) : null;

            const formatMoney = (v) => `$${Number(v || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            const formatDate = (d) => d ? d.toLocaleString(undefined, { year:'numeric', month:'short', day:'2-digit', hour:'numeric', minute:'2-digit' }) : '';

            const statusBadge = (status) => {
                const map = {
                    completed: 'bg-green-100 text-green-800',
                    pending: 'bg-yellow-100 text-yellow-800',
                    cancelled: 'bg-red-100 text-red-800',
                    failed: 'bg-gray-100 text-gray-800',
                };
                return map[String(status || '').toLowerCase()] || 'bg-gray-100 text-gray-800';
            };

            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-40 overflow-y-auto h-full w-full z-[1000]';
            modal.innerHTML = `
                <div class="relative mx-auto my-10 w-full max-w-2xl">
                    <div class="rounded-2xl bg-white shadow-2xl border border-border overflow-hidden">
                        <div class="bg-gradient-to-br from-black via-gray-900 to-black text-white px-6 py-5">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="text-xs uppercase tracking-wider text-white/70">Purchase</div>
                                    <div class="text-xl font-light">#${purchase.id}</div>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${statusBadge(purchase.status)}">${(purchase.status || '').toString().charAt(0).toUpperCase() + (purchase.status || '').toString().slice(1)}</span>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <h4 class="text-sm font-medium text-black dark:text-white mb-2">Details</h4>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between"><span class="text-gray-600 dark:text-gray-300">Amount</span><span class="font-medium">${formatMoney(purchase.amount)}</span></div>
                                        <div class="flex justify-between"><span class="text-gray-600 dark:text-gray-300">Payment Method</span><span class="font-medium">${method.name || 'N/A'}</span></div>
                                        <div class="flex justify-between"><span class="text-gray-600 dark:text-gray-300">Date</span><span class="font-medium">${formatDate(purchasedAt || createdAt)}</span></div>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="text-sm font-medium text-black dark:text-white mb-2">Vehicle</h4>
                                    <div class="flex items-center">
                                        ${car.image_url ? `<img src="${car.image_url}" alt="${car.name || ''}" class="w-14 h-14 rounded-lg mr-3 object-cover">` : `<div class=\"w-14 h-14 bg-muted rounded-lg mr-3 flex items-center justify-center\"><svg xmlns=\"http://www.w3.org/2000/svg\" class=\"h-6 w-6 text-gray-400\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M3 13l2-2m0 0l7-7 7 7M13 5v6m0 0l-2 2m2-2h6\" /></svg></div>`}
                                        <div>
                                            <div class="text-sm font-medium text-black">${car.name || car.title || '—'}</div>
                                            <div class="text-xs text-gray-600 dark:text-gray-300">${[car.year, car.color].filter(Boolean).join(' • ')}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            ${purchase.transaction_hash ? `
                            <div class="mt-6">
                                <h4 class="text-sm font-medium text-black dark:text-white mb-2">Transaction</h4>
                                <div class="bg-gray-50 border border-border rounded-lg p-3 text-xs font-mono break-all">${purchase.transaction_hash}</div>
                            </div>` : ''}

                            <div class="mt-6 flex justify-end gap-2">
                                ${(purchase.status || '').toLowerCase() === 'completed' ? `<a href="${window.APP_ROUTES?.invoice?.replace(':id', purchase.id) || '#'}" class="inline-flex items-center px-4 py-2 bg-black dark:bg-white text-white dark:text-gray-900 rounded-lg text-xs hover:bg-gray-800"><svg xmlns=\"http://www.w3.org/2000/svg\" class=\"h-4 w-4 mr-2\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M12 4v16m8-8H4\" /></svg>Invoice</a>`: ''}
                                <button onclick="this.closest('.fixed').remove()" class="inline-flex items-center px-4 py-2 bg-white text-black border border-border rounded-lg text-xs hover:bg-gray-50">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            
            modal.addEventListener('click', function(e) { if (e.target === this) this.remove(); });
        }

        // Provide route helper for modal invoice link
        window.APP_ROUTES = window.APP_ROUTES || {};
        window.APP_ROUTES.invoice = "{{ route('dashboard.invoice', ':id') }}";
    </script>
</x-user-layout> 
