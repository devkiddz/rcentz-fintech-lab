<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-light text-lg text-foreground leading-tight mr-4">
                    Wallet Transactions
                </h2>
            </div>
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
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                            </svg>
                        </div>
                        <div class="ml-2 md:ml-3">
                            <p class="text-xs font-medium text-muted-foreground dark:text-gray-300">Total Transactions</p>
                            <p class="text-sm md:text-lg font-light text-foreground">{{ $stats['total_transactions'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-card border border-border p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-8 h-8 md:w-10 md:h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 md:w-5 md:h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                        <div class="ml-2 md:ml-3">
                            <p class="text-xs font-medium text-muted-foreground dark:text-gray-300">Total Deposits</p>
                            <p class="text-sm md:text-lg font-light text-foreground">{{ $stats['total_deposits'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-card border border-border p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-8 h-8 md:w-10 md:h-10 bg-red-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 md:w-5 md:h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                        <div class="ml-2 md:ml-3">
                            <p class="text-xs font-medium text-muted-foreground dark:text-gray-300">Total Withdrawals</p>
                            <p class="text-sm md:text-lg font-light text-foreground">{{ $stats['total_withdrawals'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-card border border-border p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-8 h-8 md:w-10 md:h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 md:w-5 md:h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                        <div class="ml-2 md:ml-3">
                            <p class="text-xs font-medium text-muted-foreground dark:text-gray-300">Total Volume</p>
                            <p class="text-sm md:text-lg font-light text-foreground">{{ currency_symbol() }}{{ number_format($stats['total_volume'], 0) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Transactions Stats -->
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-card border border-border p-4 rounded-lg">
                    <div class="text-center">
                        <p class="text-xs font-medium text-muted-foreground mb-1">Pending Deposits</p>
                        <p class="text-lg font-light text-foreground">{{ $stats['pending_deposits'] }}</p>
                    </div>
                </div>

                <div class="bg-card border border-border p-4 rounded-lg">
                    <div class="text-center">
                        <p class="text-xs font-medium text-muted-foreground mb-1">Pending Withdrawals</p>
                        <p class="text-lg font-light text-foreground">{{ $stats['pending_withdrawals'] }}</p>
                    </div>
                </div>

                <div class="bg-card border border-border p-4 rounded-lg">
                    <div class="text-center">
                        <p class="text-xs font-medium text-muted-foreground mb-1">Completed Volume</p>
                        <p class="text-lg font-light text-foreground">{{ currency_symbol() }}{{ number_format($stats['completed_volume'], 0) }}</p>
                    </div>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="bg-card border border-border rounded-lg mb-6">
                <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                    <h3 class="text-base font-medium text-foreground">Filter Transactions</h3>
                </div>
                <div class="p-4">
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('admin.wallet-transactions.index') }}" 
                           class="px-3 py-1.5 text-xs font-medium rounded {{ !request('type') && !request('status') ? 'bg-black dark:bg-card text-white dark:text-foreground' : 'bg-muted text-muted-foreground hover:bg-muted' }}">
                            All
                        </a>
                        <a href="{{ route('admin.wallet-transactions.index', ['type' => 'deposit']) }}" 
                           class="px-3 py-1.5 text-xs font-medium rounded {{ request('type') === 'deposit' ? 'bg-green-600 text-white' : 'bg-muted text-muted-foreground hover:bg-muted' }}">
                            Deposits
                        </a>
                        <a href="{{ route('admin.wallet-transactions.index', ['type' => 'withdrawal']) }}" 
                           class="px-3 py-1.5 text-xs font-medium rounded {{ request('type') === 'withdrawal' ? 'bg-red-600 text-white' : 'bg-muted text-muted-foreground hover:bg-muted' }}">
                            Withdrawals
                        </a>
                        <a href="{{ route('admin.wallet-transactions.index', ['status' => 'pending']) }}" 
                           class="px-3 py-1.5 text-xs font-medium rounded {{ request('status') === 'pending' ? 'bg-yellow-600 text-white' : 'bg-muted text-muted-foreground hover:bg-muted' }}">
                            Pending
                        </a>
                        <a href="{{ route('admin.wallet-transactions.index', ['status' => 'completed']) }}" 
                           class="px-3 py-1.5 text-xs font-medium rounded {{ request('status') === 'completed' ? 'bg-green-600 text-white' : 'bg-muted text-muted-foreground hover:bg-muted' }}">
                            Completed
                        </a>
                        <a href="{{ route('admin.wallet-transactions.index', ['status' => 'rejected']) }}" 
                           class="px-3 py-1.5 text-xs font-medium rounded {{ request('status') === 'rejected' ? 'bg-red-600 text-white' : 'bg-muted text-muted-foreground hover:bg-muted' }}">
                            Rejected
                        </a>
                    </div>
                </div>
            </div>

            <!-- Transactions List -->
            <div class="bg-card border border-border overflow-hidden rounded-lg">
                <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                    <h3 class="text-base font-medium text-foreground">Wallet Transactions</h3>
                    <p class="text-xs text-muted-foreground mt-1">All wallet transactions by users</p>
                </div>
                
                <div class="p-4">
                    @if($transactions->count() > 0)
                    <div class="space-y-4">
                        @foreach($transactions as $transaction)
                        <div class="border border-border dark:border-gray-700 rounded-lg p-4 hover:bg-muted/40 transition-colors">
                            <!-- Mobile Layout -->
                            <div class="md:hidden">
                                <div class="flex items-start space-x-3 mb-3">
                                    @if($transaction->wallet->user->profile_image)
                                        <img src="{{ asset('storage/' . $transaction->wallet->user->profile_image) }}" 
                                             alt="{{ $transaction->wallet->user->name }}" 
                                             class="w-10 h-10 rounded-lg object-cover">
                                    @else
                                        <div class="w-10 h-10 bg-gradient-to-br from-tesla-500 to-purple-600 rounded-lg flex items-center justify-center">
                                            <span class="text-white font-bold text-xs">{{ strtoupper(substr($transaction->wallet->user->name, 0, 2)) }}</span>
                                        </div>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-medium text-foreground truncate">{{ $transaction->wallet->user->name }}</h4>
                                        <p class="text-xs text-muted-foreground truncate">{{ ucfirst($transaction->type) }} • {{ $transaction->paymentMethod->name ?? 'N/A' }}</p>
                                        <p class="text-xs text-muted-foreground mt-1">{{ $transaction->created_at->format('M d, Y h:i A') }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-medium text-foreground">{{ currency_symbol() }}{{ number_format($transaction->amount, 2) }}</p>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $transaction->status === 'completed' ? 'bg-green-100 text-green-800' : ($transaction->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                            {{ ucfirst($transaction->status) }}
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Mobile Actions -->
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('admin.wallet-transactions.show', $transaction) }}" 
                                       class="flex-1 px-3 py-2 bg-muted text-muted-foreground text-xs font-medium rounded text-center hover:bg-muted transition-colors">
                                       View
                                    </a>
                                    @if($transaction->status === 'pending')
                                        <form method="POST" action="{{ route('admin.wallet-transactions.approve', $transaction) }}" class="flex-1">
                                            @csrf
                                            <button type="submit" class="w-full px-3 py-2 bg-green-600 text-white text-xs font-medium rounded text-center hover:bg-green-700 transition-colors">
                                                Approve
                                            </button>
                                        </form>
                                        <button onclick="openRejectModal('{{ $transaction->id }}')" 
                                                class="flex-1 px-3 py-2 bg-red-600 text-white text-xs font-medium rounded text-center hover:bg-red-700 transition-colors">
                                            Reject
                                        </button>
                                    @endif
                                    @if($transaction->status !== 'completed')
                                        <form method="POST" action="{{ route('admin.wallet-transactions.destroy', $transaction) }}" class="flex-1">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" onclick="return confirm('Are you sure you want to delete this transaction?')" 
                                                    class="w-full px-3 py-2 bg-gray-600 text-white text-xs font-medium rounded text-center hover:bg-gray-700 transition-colors">
                                                Delete
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>

                            <!-- Desktop Layout -->
                            <div class="hidden md:flex items-center justify-between">
                                <div class="flex items-center space-x-4">
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
                                        <p class="text-xs text-muted-foreground dark:text-gray-300">{{ ucfirst($transaction->type) }} • {{ $transaction->paymentMethod->name ?? 'N/A' }}</p>
                                        <p class="text-xs text-muted-foreground mt-1">{{ $transaction->created_at->format('M d, Y h:i A') }}</p>
                                    </div>
                                </div>
                                
                                <div class="flex items-center space-x-3">
                                    <div class="text-right">
                                        <p class="text-sm font-medium text-foreground">{{ currency_symbol() }}{{ number_format($transaction->amount, 2) }}</p>
                                        <p class="text-xs text-muted-foreground dark:text-gray-300">{{ currency_symbol() }}{{ number_format($transaction->wallet->balance, 2) }} balance</p>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $transaction->status === 'completed' ? 'bg-green-100 text-green-800' : ($transaction->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                            {{ ucfirst($transaction->status) }}
                                        </span>
                                    </div>
                                    
                                    <div class="flex space-x-2">
                                        <a href="{{ route('admin.wallet-transactions.show', $transaction) }}" 
                                           class="px-3 py-1.5 bg-muted text-muted-foreground text-xs font-medium rounded hover:bg-muted transition-colors">
                                           View
                                        </a>
                                        @if($transaction->status === 'pending')
                                            <form method="POST" action="{{ route('admin.wallet-transactions.approve', $transaction) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="px-3 py-1.5 bg-green-600 text-white text-xs font-medium rounded hover:bg-green-700 transition-colors">
                                                    Approve
                                                </button>
                                            </form>
                                            <button onclick="openRejectModal('{{ $transaction->id }}')" 
                                                    class="px-3 py-1.5 bg-red-600 text-white text-xs font-medium rounded hover:bg-red-700 transition-colors">
                                                Reject
                                            </button>
                                        @endif
                                        @if($transaction->status !== 'completed')
                                            <form method="POST" action="{{ route('admin.wallet-transactions.destroy', $transaction) }}" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" onclick="return confirm('Are you sure you want to delete this transaction?')" 
                                                        class="px-3 py-1.5 bg-gray-600 text-white text-xs font-medium rounded hover:bg-gray-700 transition-colors">
                                                    Delete
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    <div class="mt-6">
                        {{ $transactions->links() }}
                    </div>
                    @else
                    <div class="text-center py-8">
                        <div class="w-16 h-16 bg-muted rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-foreground dark:text-white mb-2">No wallet transactions found</h3>
                        <p class="text-xs text-muted-foreground dark:text-gray-300">There are no wallet transactions at the moment.</p>
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
                <form id="rejectForm" method="POST">
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
        function openRejectModal(transactionId) {
            document.getElementById('rejectForm').action = `/admin/wallet-transactions/${transactionId}/reject`;
            document.getElementById('rejectModal').classList.remove('hidden');
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').classList.add('hidden');
            document.getElementById('rejection_reason').value = '';
        }
    </script>
</x-admin-layout>
