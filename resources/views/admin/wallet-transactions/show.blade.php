<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-light text-lg text-foreground leading-tight mr-4">
                    Transaction Details
                </h2>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('admin.wallet-transactions.index') }}" 
                   class="px-3 py-2 bg-muted text-muted-foreground text-sm font-medium rounded-lg hover:bg-muted transition-colors">
                    Back to Transactions
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="xl:col-span-2 space-y-6">
                    <!-- Transaction Overview -->
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Transaction Overview</h3>
                        </div>
                        <div class="p-4">
                            <div class="flex items-start space-x-4 mb-4">
                                <div class="w-16 h-16 bg-gradient-to-br from-tesla-500 to-purple-600 rounded-lg flex items-center justify-center">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h4 class="text-lg font-medium text-foreground dark:text-white mb-2">{{ ucfirst($transaction->type) }} Transaction</h4>
                                    <p class="text-sm text-muted-foreground mb-3">{{ $transaction->description ?? 'No description provided' }}</p>
                                    <div class="flex flex-wrap gap-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $transaction->type === 'deposit' ? 'bg-green-100 text-green-800' : ($transaction->type === 'withdrawal' ? 'bg-red-100 text-red-800' : 'bg-tesla-100 text-tesla-800') }}">
                                            {{ ucfirst($transaction->type) }}
                                        </span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $transaction->status === 'completed' ? 'bg-green-100 text-green-800' : ($transaction->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                            {{ ucfirst($transaction->status) }}
                                        </span>
                                        @if($transaction->paymentMethod)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-tesla-100 text-tesla-800">
                                                {{ $transaction->paymentMethod->name }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Transaction Metrics -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-card border border-border p-4 rounded-lg">
                            <div class="text-center">
                                <p class="text-xs font-medium text-muted-foreground mb-1">Amount</p>
                                <p class="text-lg font-light text-foreground">{{ currency_symbol() }}{{ number_format($transaction->amount, 2) }}</p>
                            </div>
                        </div>

                        <div class="bg-card border border-border p-4 rounded-lg">
                            <div class="text-center">
                                <p class="text-xs font-medium text-muted-foreground mb-1">Fee</p>
                                <p class="text-lg font-light text-foreground">{{ currency_symbol() }}{{ number_format($transaction->fee, 2) }}</p>
                            </div>
                        </div>

                        <div class="bg-card border border-border p-4 rounded-lg">
                            <div class="text-center">
                                <p class="text-xs font-medium text-muted-foreground mb-1">Total</p>
                                <p class="text-lg font-light text-foreground">{{ currency_symbol() }}{{ number_format($transaction->amount + $transaction->fee, 2) }}</p>
                            </div>
                        </div>

                        <div class="bg-card border border-border p-4 rounded-lg">
                            <div class="text-center">
                                <p class="text-xs font-medium text-muted-foreground mb-1">Transaction Date</p>
                                <p class="text-lg font-light text-foreground">{{ $transaction->created_at->format('M d, Y') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Transaction Details -->
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Transaction Details</h3>
                        </div>
                        <div class="p-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Transaction ID</label>
                                    <p class="text-sm font-medium text-foreground">{{ $transaction->id }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Reference ID</label>
                                    <p class="text-sm font-medium text-foreground">{{ $transaction->reference_id ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Type</label>
                                    <p class="text-sm font-medium text-foreground">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $transaction->type === 'deposit' ? 'bg-green-100 text-green-800' : ($transaction->type === 'withdrawal' ? 'bg-red-100 text-red-800' : 'bg-tesla-100 text-tesla-800') }}">
                                            {{ ucfirst($transaction->type) }}
                                        </span>
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Status</label>
                                    <p class="text-sm font-medium text-foreground">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $transaction->status === 'completed' ? 'bg-green-100 text-green-800' : ($transaction->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                            {{ ucfirst($transaction->status) }}
                                        </span>
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Created</label>
                                    <p class="text-sm font-medium text-foreground">{{ $transaction->created_at->format('M d, Y h:i A') }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Updated</label>
                                    <p class="text-sm font-medium text-foreground">{{ $transaction->updated_at->format('M d, Y h:i A') }}</p>
                                </div>
                                @if($transaction->type === 'withdrawal' && $transaction->user_crypto_details)
                                    <div class="col-span-2">
                                        <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Wallet Address</label>
                                        <div class="flex items-center space-x-2">
                                            <p class="text-sm font-medium text-foreground font-mono bg-muted px-2 py-1 rounded">
                                                {{ $transaction->user_crypto_details['wallet_address'] ?? 'N/A' }}
                                            </p>
                                            <button onclick="copyToClipboard('{{ $transaction->user_crypto_details['wallet_address'] ?? '' }}')" 
                                                    class="text-xs text-tesla-600 hover:text-tesla-800 font-medium">
                                                Copy
                                            </button>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    @if($transaction->status === 'pending')
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Actions</h3>
                        </div>
                        <div class="p-4">
                            <div class="flex flex-wrap gap-3">
                                <form method="POST" action="{{ route('admin.wallet-transactions.approve', $transaction) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                                        <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        Approve Transaction
                                    </button>
                                </form>
                                <button onclick="openRejectModal()" 
                                        class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                                    <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    Reject Transaction
                                </button>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Withdrawal Details -->
                    @if($transaction->type === 'withdrawal' && $transaction->user_crypto_details)
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Withdrawal Details</h3>
                        </div>
                        <div class="p-4">
                            <div class="space-y-3">
                                @if(isset($transaction->user_crypto_details['wallet_address']))
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Destination Wallet</label>
                                    <div class="flex items-center space-x-2">
                                        <p class="text-sm font-medium text-foreground font-mono bg-muted px-2 py-1 rounded flex-1">
                                            {{ $transaction->user_crypto_details['wallet_address'] }}
                                        </p>
                                        <button onclick="copyToClipboard('{{ $transaction->user_crypto_details['wallet_address'] }}')" 
                                                class="text-xs text-tesla-600 hover:text-tesla-800 font-medium whitespace-nowrap">
                                            Copy
                                        </button>
                                    </div>
                                </div>
                                @endif
                                @if(isset($transaction->user_crypto_details['crypto_type']))
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Cryptocurrency</label>
                                    <p class="text-sm font-medium text-foreground">{{ strtoupper($transaction->user_crypto_details['crypto_type']) }}</p>
                                </div>
                                @endif
                                @if(isset($transaction->user_crypto_details['network']))
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Network</label>
                                    <p class="text-sm font-medium text-foreground">{{ $transaction->user_crypto_details['network'] }}</p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- User Information -->
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">User Information</h3>
                        </div>
                        <div class="p-4">
                            <div class="flex items-center space-x-3 mb-4">
                                @if($transaction->wallet->user->profile_image)
                                    <img src="{{ asset('storage/' . $transaction->wallet->user->profile_image) }}" 
                                         alt="{{ $transaction->wallet->user->name }}" 
                                         class="w-12 h-12 rounded-lg object-cover">
                                @else
                                    <div class="w-12 h-12 bg-gradient-to-br from-tesla-500 to-purple-600 rounded-lg flex items-center justify-center">
                                        <span class="text-white font-bold text-sm">{{ strtoupper(substr($transaction->wallet->user->name, 0, 2)) }}</span>
                                    </div>
                                @endif
                                <div>
                                    <h4 class="text-sm font-medium text-foreground">{{ $transaction->wallet->user->name }}</h4>
                                    <p class="text-xs text-muted-foreground dark:text-gray-300">{{ $transaction->wallet->user->email }}</p>
                                </div>
                            </div>
                            
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Member Since</label>
                                    <p class="text-sm font-medium text-foreground">{{ $transaction->wallet->user->created_at->format('M d, Y') }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Email Status</label>
                                    <p class="text-sm font-medium text-foreground">
                                        @if($transaction->wallet->user->email_verified_at)
                                            <span class="text-green-600">Verified</span>
                                        @else
                                            <span class="text-red-600">Unverified</span>
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4">
                                <a href="{{ route('admin.users.show', $transaction->wallet->user) }}" 
                                   class="w-full inline-flex items-center justify-center px-3 py-2 bg-black dark:bg-card text-white dark:text-foreground text-sm font-medium rounded-lg hover:opacity-90 transition-colors">
                                   <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                   </svg>
                                   View User Profile
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Wallet Information -->
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Wallet Information</h3>
                        </div>
                        <div class="p-4">
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Current Balance</label>
                                    <p class="text-sm font-medium text-foreground">{{ currency_symbol() }}{{ number_format($transaction->wallet->balance, 2) }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Currency</label>
                                    <p class="text-sm font-medium text-foreground">{{ strtoupper($transaction->wallet->currency) }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Total Deposits</label>
                                    <p class="text-sm font-medium text-foreground">{{ currency_symbol() }}{{ number_format($transaction->wallet->total_deposits, 2) }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Total Withdrawals</label>
                                    <p class="text-sm font-medium text-foreground">{{ currency_symbol() }}{{ number_format($transaction->wallet->total_withdrawals, 2) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method Information -->
                    @if($transaction->paymentMethod)
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Payment Method</h3>
                        </div>
                        <div class="p-4">
                            <div class="flex items-center space-x-3 mb-4">
                                @if($transaction->paymentMethod->logo_url)
                                    <img src="{{ asset($transaction->paymentMethod->logo_url) }}" 
                                         alt="{{ $transaction->paymentMethod->name }}" 
                                         class="w-12 h-12 rounded-lg object-cover">
                                @else
                                    <div class="w-12 h-12 bg-gradient-to-br from-tesla-500 to-purple-600 rounded-lg flex items-center justify-center">
                                        <span class="text-white font-bold text-sm">{{ strtoupper(substr($transaction->paymentMethod->name, 0, 2)) }}</span>
                                    </div>
                                @endif
                                <div>
                                    <h4 class="text-sm font-medium text-foreground">{{ $transaction->paymentMethod->name }}</h4>
                                    <p class="text-xs text-muted-foreground dark:text-gray-300">{{ $transaction->paymentMethod->type }}</p>
                                </div>
                            </div>
                            
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Status</label>
                                    <p class="text-sm font-medium text-foreground">
                                        @if($transaction->paymentMethod->is_active)
                                            <span class="text-green-600">Active</span>
                                        @else
                                            <span class="text-red-600">Inactive</span>
                                        @endif
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Allow Deposits</label>
                                    <p class="text-sm font-medium text-foreground">
                                        @if($transaction->paymentMethod->allow_deposit)
                                            <span class="text-green-600">Yes</span>
                                        @else
                                            <span class="text-red-600">No</span>
                                        @endif
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground dark:text-gray-300 mb-1">Allow Withdrawals</label>
                                    <p class="text-sm font-medium text-foreground">
                                        @if($transaction->paymentMethod->allow_withdraw)
                                            <span class="text-green-600">Yes</span>
                                        @else
                                            <span class="text-red-600">No</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Danger Zone -->
                    @if($transaction->status !== 'completed')
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-red-50">
                            <h3 class="text-base font-medium text-red-800">Danger Zone</h3>
                        </div>
                        <div class="p-4">
                            <form method="POST" action="{{ route('admin.wallet-transactions.destroy', $transaction) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" onclick="return confirm('Are you sure you want to delete this transaction? This action cannot be undone.')" 
                                        class="w-full px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                                    <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    Delete Transaction
                                </button>
                            </form>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border border-border w-96 max-w-[calc(100vw-2rem)] shadow-lg rounded-xl bg-card text-card-foreground">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-foreground dark:text-white mb-4">Reject Transaction</h3>
                <form method="POST" action="{{ route('admin.wallet-transactions.reject', $transaction) }}">
                    @csrf
                    <div class="mb-4">
                        <label for="rejection_reason" class="block text-xs font-medium text-muted-foreground mb-2">Rejection Reason</label>
                        <textarea id="rejection_reason" name="rejection_reason" rows="3" 
                                  class="w-full px-3 py-2 border border-border rounded-lg focus:ring-ring focus:border-black text-sm" 
                                  placeholder="Enter reason for rejection..." required></textarea>
                    </div>
                    <div class="flex space-x-3">
                        <button type="submit" class="flex-1 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                            Reject
                        </button>
                        <button type="button" onclick="closeRejectModal()" 
                                class="flex-1 px-4 py-2 bg-gray-300 text-muted-foreground text-sm font-medium rounded-lg hover:bg-gray-400 transition-colors">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openRejectModal() {
            document.getElementById('rejectModal').classList.remove('hidden');
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').classList.add('hidden');
            document.getElementById('rejection_reason').value = '';
        }

        function copyToClipboard(text) {
            if (text) {
                navigator.clipboard.writeText(text).then(function() {
                    // Show a brief success message
                    const button = event.target;
                    const originalText = button.textContent;
                    button.textContent = 'Copied!';
                    button.classList.add('text-green-600');
                    
                    setTimeout(function() {
                        button.textContent = originalText;
                        button.classList.remove('text-green-600');
                    }, 2000);
                }).catch(function(err) {
                    console.error('Could not copy text: ', err);
                });
            }
        }
    </script>
</x-admin-layout>
