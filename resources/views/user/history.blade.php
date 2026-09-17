<x-user-layout>
    <x-slot name="header">
        Purchase History
    </x-slot>

    <div class="ui-page max-w-7xl">
        <section class="ui-page-header">
            <div>
                <p class="ui-kicker">Account · Purchases</p>
                <h1 class="ui-heading">Purchase history</h1>
                <p class="ui-lead">Review vehicle orders, payment status and invoices from your account.</p>
            </div>
            <div class="ui-header-actions">
                <a href="{{ route('dashboard') }}" class="ui-btn ui-btn-secondary">
                    <i data-lucide="layout-dashboard" class="h-4 w-4"></i>
                    Overview
                </a>
                <a href="{{ route('cars.browse') }}" class="ui-btn ui-btn-primary">
                    <i data-lucide="car-front" class="h-4 w-4"></i>
                    Browse vehicles
                </a>
            </div>
        </section>

        <section class="ui-metric-grid mb-5 sm:grid-cols-3">
            <article class="ui-metric-card">
                <p class="ui-label">Total purchases</p>
                <p class="mt-2 text-xl font-semibold text-foreground">{{ number_format($purchases->total()) }}</p>
            </article>
            <article class="ui-metric-card">
                <p class="ui-label">Completed on this page</p>
                <p class="mt-2 text-xl font-semibold text-foreground">{{ $purchases->where('status', 'completed')->count() }}</p>
            </article>
            <article class="ui-metric-card">
                <p class="ui-label">Pending on this page</p>
                <p class="mt-2 text-xl font-semibold text-foreground">{{ $purchases->where('status', 'pending')->count() }}</p>
            </article>
        </section>

        <!-- Purchase History Table -->
        <div class="ui-table-shell">
            <div class="p-5 sm:p-6 border-b border-border">
                <h2 class="text-lg font-semibold text-foreground">All Purchases</h2>
                <p class="text-sm text-muted-foreground mt-1">Complete history of your vehicle purchases</p>
            </div>
            
            @if($purchases->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="ui-table-head">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Purchase</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Vehicle</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @foreach($purchases as $purchase)
                                <tr class="hover:bg-muted/20">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-muted rounded-lg flex items-center justify-center mr-3">
                                                <i data-lucide="car" class="w-5 h-5 text-muted-foreground"></i>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-foreground">#{{ $purchase->id }}</div>
                                                <div class="text-sm text-muted-foreground">{{ $purchase->paymentMethod->name ?? 'N/A' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            @if($purchase->car->image_url)
                                                <img src="{{ $purchase->car->image_url }}" alt="{{ $purchase->car->name }}" class="w-10 h-10 rounded-lg mr-3">
                                            @else
                                                <div class="w-10 h-10 bg-muted rounded-lg flex items-center justify-center mr-3">
                                                    <i data-lucide="car" class="w-4 h-4 text-muted-foreground"></i>
                                                </div>
                                            @endif
                                            <div>
                                                <div class="text-sm font-medium text-foreground">{{ $purchase->car->name }}</div>
                                                <div class="text-sm text-muted-foreground">{{ $purchase->car->year }} • {{ $purchase->car->color }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-foreground">${{ number_format($purchase->amount, 2) }}</div>
                                        @if($purchase->fee > 0)
                                            <div class="text-xs text-muted-foreground">Fee: ${{ number_format($purchase->fee, 2) }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $purchase->status === 'completed' ? 'bg-green-100 text-green-800' : '' }} {{ $purchase->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }} {{ $purchase->status === 'cancelled' ? 'bg-red-100 text-red-800' : '' }} {{ $purchase->status === 'failed' ? 'bg-muted text-foreground' : '' }}">
                                            {{ ucfirst($purchase->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground">
                                        {{ $purchase->created_at->format('M j, Y') }}
                                        <div class="text-xs text-muted-foreground">{{ $purchase->created_at->format('g:i A') }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            @if($purchase->status === 'completed')
                                                <a href="{{ route('dashboard.invoice', $purchase) }}" 
                                                   class="ui-icon-btn"
                                                   title="Download Invoice">
                                                    <i data-lucide="download" class="w-4 h-4"></i>
                                                </a>
                                            @endif
                                             <button onclick="viewPurchaseDetails({{ $purchase->toJson() }})" 
                                                    class="ui-icon-btn"
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
                    <div class="px-6 py-4 border-t border-border">
                        {{ $purchases->links() }}
                    </div>
                @endif
            @else
                <div class="p-8 text-center">
                    <div class="w-16 h-16 bg-muted rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="receipt" class="w-8 h-8 text-white"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-foreground mb-2">No purchase history</h3>
                    <p class="text-muted-foreground mb-6">You haven't made any vehicle purchases yet.</p>
                    <a href="{{ route('cars.browse') }}" 
                       class="ui-btn ui-btn-primary">
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
                    failed: 'bg-muted text-foreground',
                };
                return map[String(status || '').toLowerCase()] || 'bg-muted text-foreground';
            };

            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-40 overflow-y-auto h-full w-full z-[1000]';
            modal.innerHTML = `
                <div class="relative mx-auto my-10 w-full max-w-2xl">
                    <div class="rounded-2xl bg-card shadow-2xl border border-border overflow-hidden">
                        <div class="border-b border-border bg-muted/30 px-6 py-5">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="text-xs uppercase tracking-wider text-muted-foreground">Purchase</div>
                                    <div class="text-xl font-semibold text-foreground">#${purchase.id}</div>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${statusBadge(purchase.status)}">${(purchase.status || '').toString().charAt(0).toUpperCase() + (purchase.status || '').toString().slice(1)}</span>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <h4 class="text-sm font-medium text-foreground mb-2">Details</h4>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between"><span class="text-muted-foreground">Amount</span><span class="font-medium">${formatMoney(purchase.amount)}</span></div>
                                        <div class="flex justify-between"><span class="text-muted-foreground">Payment Method</span><span class="font-medium">${method.name || 'N/A'}</span></div>
                                        <div class="flex justify-between"><span class="text-muted-foreground">Date</span><span class="font-medium">${formatDate(purchasedAt || createdAt)}</span></div>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="text-sm font-medium text-foreground mb-2">Vehicle</h4>
                                    <div class="flex items-center">
                                        ${car.image_url ? `<img src="${car.image_url}" alt="${car.name || ''}" class="w-14 h-14 rounded-lg mr-3 object-cover">` : `<div class=\"w-14 h-14 bg-muted rounded-lg mr-3 flex items-center justify-center\"><svg xmlns=\"http://www.w3.org/2000/svg\" class=\"h-6 w-6 text-gray-400\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M3 13l2-2m0 0l7-7 7 7M13 5v6m0 0l-2 2m2-2h6\" /></svg></div>`}
                                        <div>
                                            <div class="text-sm font-medium text-foreground">${car.name || car.title || '—'}</div>
                                            <div class="text-xs text-muted-foreground">${[car.year, car.color].filter(Boolean).join(' • ')}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            ${purchase.transaction_hash ? `
                            <div class="mt-6">
                                <h4 class="text-sm font-medium text-foreground mb-2">Transaction</h4>
                                <div class="bg-muted/30 border border-border rounded-lg p-3 text-xs font-mono break-all">${purchase.transaction_hash}</div>
                            </div>` : ''}

                            <div class="mt-6 flex justify-end gap-2">
                                ${(purchase.status || '').toLowerCase() === 'completed' ? `<a href="${window.APP_ROUTES?.invoice?.replace(':id', purchase.id) || '#'}" class="inline-flex items-center px-4 py-2 bg-foreground text-background rounded-lg text-xs hover:bg-gray-800"><svg xmlns=\"http://www.w3.org/2000/svg\" class=\"h-4 w-4 mr-2\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M12 4v16m8-8H4\" /></svg>Invoice</a>`: ''}
                                <button onclick="this.closest('.fixed').remove()" class="inline-flex items-center px-4 py-2 bg-card text-foreground border border-border rounded-lg text-xs hover:bg-muted/30">Close</button>
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
