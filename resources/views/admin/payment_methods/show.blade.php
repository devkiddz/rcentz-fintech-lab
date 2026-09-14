<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-light text-lg text-foreground leading-tight mr-4">
                    {{ $paymentMethod->name }}
                </h2>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('admin.payment_methods.edit', $paymentMethod) }}" 
                   class="inline-flex items-center px-4 py-2 bg-black dark:bg-card text-white dark:text-foreground text-sm font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-muted transition-all duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Edit
                </a>
                <a href="{{ route('admin.payment_methods.index') }}" 
                   class="inline-flex items-center px-3 py-2 bg-muted text-muted-foreground text-sm font-medium rounded-lg hover:bg-gray-200 transition-all duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <!-- Payment Method Details -->
                <div class="xl:col-span-2 space-y-4">
                    <!-- Basic Information -->
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground flex items-center">
                                @if($paymentMethod->logo_url)
                                    <div class="w-8 h-8 flex-shrink-0 mr-3">
                                        <img src="{{ $paymentMethod->logo_url }}" 
                                             alt="{{ $paymentMethod->name }}" 
                                             class="w-full h-full object-contain rounded">
                                    </div>
                                @else
                                    <div class="w-8 h-8 {{ $paymentMethod->isCryptocurrency() ? 'bg-orange-500' : 'bg-tesla-500' }} bg-opacity-20 rounded-lg flex items-center justify-center mr-3">
                                        @if($paymentMethod->isCryptocurrency())
                                            <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                            </svg>
                                        @else
                                            <svg class="w-4 h-4 text-tesla-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                            </svg>
                                        @endif
                                    </div>
                                @endif
                                Payment Method Information
                            </h3>
                        </div>
                        
                        <div class="p-4 space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground mb-2">Name</label>
                                    <div class="text-base font-medium text-foreground">{{ $paymentMethod->name }}</div>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground mb-2">Type</label>
                                    @if($paymentMethod->isCryptocurrency())
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-orange-100 text-orange-800">
                                            <svg class="w-2.5 h-2.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                            </svg>
                                            Cryptocurrency
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-tesla-100 text-tesla-800">
                                            <svg class="w-2.5 h-2.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                            </svg>
                                            Traditional
                                        </span>
                                    @endif
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground mb-2">Status</label>
                                    @if($paymentMethod->is_active)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-green-100 text-green-800">
                                            <svg class="w-2.5 h-2.5 mr-1" fill="currentColor" viewBox="0 0 8 8">
                                                <circle cx="4" cy="4" r="3"/>
                                            </svg>
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-red-100 text-red-800">
                                            <svg class="w-2.5 h-2.5 mr-1" fill="currentColor" viewBox="0 0 8 8">
                                                <circle cx="4" cy="4" r="3"/>
                                            </svg>
                                            Inactive
                                        </span>
                                    @endif
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-xs font-medium text-muted-foreground mb-2">Details</label>
                                <div class="text-sm text-foreground bg-muted/40 dark:bg-dark-muted rounded-lg p-3 border border-border dark:border-gray-700">
                                    {{ $paymentMethod->details ?: 'No details provided' }}
                                </div>
                            </div>

                            <!-- Permissions -->
                            <div>
                                <label class="block text-xs font-medium text-muted-foreground mb-2">Permissions</label>
                                <div class="flex space-x-4">
                                    <div class="flex items-center">
                                        <div class="w-3 h-3 rounded-full {{ $paymentMethod->allow_deposit ? 'bg-green-500' : 'bg-gray-300' }} mr-2"></div>
                                        <span class="text-sm text-foreground">Allow deposits</span>
                                    </div>
                                    <div class="flex items-center">
                                        <div class="w-3 h-3 rounded-full {{ $paymentMethod->allow_withdraw ? 'bg-green-500' : 'bg-gray-300' }} mr-2"></div>
                                        <span class="text-sm text-foreground">Allow withdrawals</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Cryptocurrency Specific Information -->
                            @if($paymentMethod->isCryptocurrency())
                            <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                                <h4 class="text-base font-medium text-orange-900 mb-3">Cryptocurrency Details</h4>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    @if($paymentMethod->crypto_symbol)
                                    <div>
                                        <label class="block text-xs font-medium text-muted-foreground mb-2">Crypto Symbol</label>
                                        <div class="text-base font-medium text-orange-800">{{ strtoupper($paymentMethod->crypto_symbol) }}</div>
                                    </div>
                                    @endif

                                    @if($paymentMethod->network_fee)
                                    <div>
                                        <label class="block text-xs font-medium text-muted-foreground mb-2">Network Fee</label>
                                        <div class="text-sm text-foreground">{{ $paymentMethod->formatted_network_fee }}</div>
                                    </div>
                                    @endif
                                </div>

                                @if($paymentMethod->wallet_address)
                                <div class="mt-4">
                                    <label class="block text-xs font-medium text-muted-foreground mb-2">Wallet Address</label>
                                    <div class="bg-card rounded-lg p-3 border border-border">
                                        <div class="flex items-center justify-between">
                                            <code class="text-xs font-mono text-foreground break-all">{{ $paymentMethod->wallet_address }}</code>
                                            <button onclick="copyToClipboard('{{ $paymentMethod->wallet_address }}')" 
                                                    class="ml-2 p-1 text-muted-foreground hover:text-muted-foreground transition-colors">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                @if($paymentMethod->barcode_url)
                                <div class="mt-4">
                                    <label class="block text-xs font-medium text-muted-foreground mb-2">QR Code</label>
                                    <div class="bg-card rounded-lg p-3 border border-border text-center">
                                        <img src="{{ $paymentMethod->barcode_url }}" 
                                             alt="QR Code" 
                                             class="mx-auto max-w-32 max-h-32">
                                        <p class="text-xs text-muted-foreground mt-2">QR code for wallet address</p>
                                    </div>
                                </div>
                                @endif
                            </div>
                            @endif

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground mb-2">Created</label>
                                    <div class="text-sm text-foreground">{{ $paymentMethod->created_at->format('M d, Y') }}</div>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground mb-2">Last Updated</label>
                                    <div class="text-sm text-foreground">{{ $paymentMethod->updated_at->format('M d, Y') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Usage Statistics -->
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Usage Statistics</h3>
                        </div>
                        <div class="p-4">
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div class="text-center">
                                    <div class="w-12 h-12 bg-tesla-100 rounded-lg mx-auto mb-2 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-tesla-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                        </svg>
                                    </div>
                                    <div class="text-lg font-light text-foreground">{{ $paymentMethod->purchases->count() }}</div>
                                    <div class="text-xs text-muted-foreground dark:text-gray-300">Total Purchases</div>
                                </div>
                                <div class="text-center">
                                    <div class="w-12 h-12 bg-green-100 rounded-lg mx-auto mb-2 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                        </svg>
                                    </div>
                                    <div class="text-lg font-light text-foreground">{{ currency_symbol() }}{{ number_format($paymentMethod->purchases->sum('amount'), 0) }}</div>
                                    <div class="text-xs text-muted-foreground dark:text-gray-300">Total Revenue</div>
                                </div>
                                <div class="text-center">
                                    <div class="w-12 h-12 bg-purple-100 rounded-lg mx-auto mb-2 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                    </div>
                                    <div class="text-lg font-light text-foreground">
                                        @if($paymentMethod->purchases->count() > 0)
                                            {{ currency_symbol() }}{{ number_format($paymentMethod->purchases->avg('amount'), 0) }}
                                        @else
                                            {{ currency_symbol() }}0
                                        @endif
                                    </div>
                                    <div class="text-xs text-muted-foreground dark:text-gray-300">Average Sale</div>
                                </div>
                                @if($paymentMethod->isCryptocurrency())
                                <div class="text-center">
                                    <div class="w-12 h-12 bg-orange-100 rounded-lg mx-auto mb-2 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                        </svg>
                                    </div>
                                    <div class="text-lg font-light text-foreground">
                                        {{ $paymentMethod->purchases->where('crypto_currency', '!=', null)->count() }}
                                    </div>
                                    <div class="text-xs text-muted-foreground dark:text-gray-300">Crypto Transactions</div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Purchases -->
                <div class="space-y-4">
                    @if($paymentMethod->purchases->count() > 0)
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Recent Purchases</h3>
                        </div>
                        <div class="p-4">
                            <div class="space-y-3">
                                @foreach($paymentMethod->purchases->take(5) as $purchase)
                                <div class="flex items-center justify-between p-3 bg-muted/40 dark:bg-dark-muted rounded-lg border border-border dark:border-gray-700">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3">
                                            @if($purchase->user->profile_image)
                                                <img src="{{ asset('storage/' . $purchase->user->profile_image) }}" 
                                                     alt="{{ $purchase->user->name }}" 
                                                     class="w-8 h-8 rounded-lg object-cover">
                                            @else
                                                <div class="w-8 h-8 bg-gradient-to-br from-tesla-500 to-purple-600 rounded-lg flex items-center justify-center">
                                                    <span class="text-white font-bold text-xs">{{ strtoupper(substr($purchase->user->name, 0, 2)) }}</span>
                                                </div>
                                            @endif
                                            <div>
                                                <p class="font-medium text-foreground dark:text-white text-sm">{{ $purchase->user->name }}</p>
                                                <p class="text-xs text-muted-foreground dark:text-gray-300">{{ $purchase->car->title }}</p>
                                                <p class="text-xs text-muted-foreground dark:text-gray-300">{{ $purchase->purchased_at->format('M d, Y') }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-light text-base text-foreground">{{ currency_symbol() }}{{ number_format($purchase->amount, 0) }}</p>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium {{ $purchase->status_badge }}">
                                            {{ ucfirst($purchase->status) }}
                                        </span>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @if($paymentMethod->purchases->count() > 5)
                            <div class="mt-3 text-center">
                                <p class="text-xs text-muted-foreground dark:text-gray-300">Showing latest 5 purchases out of {{ $paymentMethod->purchases->count() }} total</p>
                            </div>
                            @endif
                        </div>
                    </div>
                    @else
                    <div class="bg-card border border-border rounded-lg p-6 text-center">
                        <div class="w-12 h-12 bg-muted dark:bg-dark-muted rounded-lg flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6 text-gray-400 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                            </svg>
                        </div>
                        <h3 class="text-sm font-medium text-foreground dark:text-white mb-1">No Purchases</h3>
                        <p class="text-xs text-muted-foreground dark:text-gray-300">This payment method hasn't been used yet</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                // Show a temporary success message
                const button = event.target.closest('button');
                const originalHTML = button.innerHTML;
                button.innerHTML = '<svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
                
                setTimeout(() => {
                    button.innerHTML = originalHTML;
                }, 2000);
            }).catch(function(err) {
                console.error('Could not copy text: ', err);
            });
        }
    </script>
</x-admin-layout> 
