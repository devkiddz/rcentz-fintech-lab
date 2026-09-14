<x-user-layout>
    <x-slot name="header">
        Wallet
    </x-slot>

        <div class="max-w-7xl mx-auto">
        <!-- Enhanced Welcome Section with Wallet Balance -->
        <div class="bg-gradient-to-br from-tesla-600 via-tesla-700 to-tesla-800 dark:from-tesla-700 dark:via-tesla-800 dark:to-tesla-900 rounded-2xl p-6 mb-6 text-white relative overflow-hidden transition-colors duration-200">
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-0 right-0 w-48 h-48 bg-white rounded-full -translate-y-24 translate-x-24"></div>
                <div class="absolute bottom-0 left-0 w-24 h-24 bg-white rounded-full translate-y-12 -translate-x-12"></div>
            </div>
            
            <div class="relative z-10">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                    <div class="mb-4 lg:mb-0 lg:flex-1">
                        <h1 class="text-xl font-light mb-1">Wallet Balance</h1>
                        <p class="text-tesla-100 dark:text-gray-300 text-sm">Manage your funds and transactions</p>
                    </div>
                    
                    <!-- Enhanced Wallet Card -->
                    <div class="bg-white bg-opacity-15 dark:bg-opacity-20 backdrop-blur-xl rounded-xl p-4 border border-white border-opacity-20 shadow-xl lg:w-80">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <p class="text-xs text-tesla-100 dark:text-gray-300 mb-1">Available Balance</p>
                                <p class="text-xl font-light">{{ format_currency(auth()->user()->wallet->balance ?? 0) }}</p>
                            </div>
                            <div class="w-10 h-10 flex items-center justify-center">
                                <i data-lucide="wallet" class="w-5 h-5 text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Quick Actions Grid -->
        <div class="grid grid-cols-2 md:grid-cols-2 gap-3 mb-6">
            <!-- Deposit -->
            <a href="{{ route('wallet.deposit') }}" class="group bg-card rounded-xl p-4 shadow-sm border border-border hover:shadow-lg transition-all duration-300 hover:scale-105 relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-600/20 dark:to-green-500/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                <div class="relative z-10">
                    <div class="w-10 h-10 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform duration-300">
                        <i data-lucide="plus" class="w-5 h-5 text-green-500 dark:text-green-300"></i>
                    </div>
                    <h3 class="font-medium text-black dark:text-white text-sm mb-1">Deposit Funds</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-300 mb-2">Add money to your wallet</p>
                    <div class="flex items-center text-green-600 dark:text-green-300 text-xs font-medium">
                        <span>Add Funds</span>
                        <i data-lucide="arrow-right" class="w-3 h-3 ml-1 group-hover:translate-x-1 transition-transform duration-200"></i>
                    </div>
                </div>
            </a>

            <!-- Withdraw -->
            <a href="{{ route('wallet.withdraw') }}" class="group bg-card rounded-xl p-4 shadow-sm border border-border hover:shadow-lg transition-all duration-300 hover:scale-105 relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-red-50 to-red-100 dark:from-red-600/20 dark:to-red-500/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                <div class="relative z-10">
                    <div class="w-10 h-10 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform duration-300">
                        <i data-lucide="minus" class="w-5 h-5 text-red-500 dark:text-red-300"></i>
                    </div>
                    <h3 class="font-medium text-black dark:text-white text-sm mb-1">Withdraw Funds</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-300 mb-2">Transfer money to your bank</p>
                    <div class="flex items-center text-red-600 dark:text-red-300 text-xs font-medium">
                        <span>Withdraw</span>
                        <i data-lucide="arrow-right" class="w-3 h-3 ml-1 group-hover:translate-x-1 transition-transform duration-200"></i>
                    </div>
                </div>
            </a>
        </div>

        <!-- Enhanced Wallet Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <!-- Total Deposits -->
            <div class="bg-card rounded-xl p-4 shadow-sm border border-border hover:shadow-lg transition-all duration-300 group">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-300 mb-1">Total Deposits</p>
                        <p class="text-lg font-light text-black dark:text-white mb-1">{{ format_currency(auth()->user()->wallet->total_deposits ?? 0) }}</p>
                        <p class="text-xs text-green-600 dark:text-green-300">This month</p>
                    </div>
                    <div class="flex flex-col items-end space-y-1">
                        <div class="w-10 h-10 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                            <i data-lucide="trending-up" class="w-5 h-5 text-green-500 dark:text-green-300"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Withdrawals -->
            <div class="bg-card rounded-xl p-4 shadow-sm border border-border hover:shadow-lg transition-all duration-300 group">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-300 mb-1">Total Withdrawals</p>
                        <p class="text-lg font-light text-black dark:text-white mb-1">{{ format_currency(auth()->user()->wallet->total_withdrawals ?? 0) }}</p>
                        <p class="text-xs text-red-600 dark:text-red-300">This month</p>
                    </div>
                    <div class="flex flex-col items-end space-y-1">
                        <div class="w-10 h-10 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                            <i data-lucide="trending-down" class="w-5 h-5 text-red-500 dark:text-red-300"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Investments -->
            <div class="bg-card rounded-xl p-4 shadow-sm border border-border hover:shadow-lg transition-all duration-300 group">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-300 mb-1">Total Invested</p>
                        <p class="text-lg font-light text-black dark:text-white mb-1">{{ format_currency(auth()->user()->wallet->total_investments ?? 0) }}</p>
                        <p class="text-xs text-tesla-600 dark:text-gray-300">Portfolio value</p>
                    </div>
                    <div class="flex flex-col items-end space-y-1">
                        <div class="w-10 h-10 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                            <i data-lucide="pie-chart" class="w-5 h-5 text-tesla-500 dark:text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="bg-card rounded-xl p-5 shadow-sm border border-border transition-colors duration-200">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-light text-black dark:text-white mb-1">Recent Transactions</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-300">Your latest wallet activity</p>
                </div>
                <a href="{{ route('wallet.transactions') }}" class="text-xs text-black dark:text-gray-300 hover:text-gray-600 dark:hover:text-white transition-colors duration-200 font-medium">
                    View All
                    <i data-lucide="arrow-right" class="w-3 h-3 inline ml-1"></i>
                </a>
            </div>
            
            @if($recentTransactions->count() > 0)
                <div class="space-y-3">
                    @foreach($recentTransactions->take(5) as $transaction)
                    <div class="flex items-center space-x-3 p-3 bg-muted rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200 group">
                        <div class="w-10 h-10 flex items-center justify-center">
                            @if($transaction->type === 'deposit')
                                <i data-lucide="plus" class="w-5 h-5 text-green-500 dark:text-green-300"></i>
                            @elseif($transaction->type === 'withdrawal')
                                <i data-lucide="minus" class="w-5 h-5 text-red-500 dark:text-red-300"></i>
                            @else
                                <i data-lucide="arrow-right-left" class="w-5 h-5 text-tesla-500 dark:text-gray-300"></i>
                            @endif
                        </div>
                        
                        <div class="flex-1 min-w-0">
                            <h4 class="font-medium text-black dark:text-white text-sm truncate">{{ ucfirst($transaction->type) }}</h4>
                            <p class="text-xs text-gray-600 dark:text-gray-300">{{ $transaction->paymentMethod->name ?? 'Direct Transfer' }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-300">{{ $transaction->created_at->format('M d, Y') }}</p>
                        </div>
                        
                        <div class="text-right">
                            <p class="font-medium text-sm {{ $transaction->type === 'deposit' ? 'text-green-600 dark:text-green-300' : ($transaction->type === 'withdrawal' ? 'text-red-600 dark:text-red-300' : 'text-tesla-600 dark:text-gray-300') }}">
                                {{ $transaction->type === 'deposit' ? '+' : ($transaction->type === 'withdrawal' ? '-' : '') }}{{ format_currency($transaction->amount) }}
                            </p>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $transaction->status_badge }}">
                                {{ ucfirst($transaction->status) }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-6">
                    <div class="w-12 h-12 bg-muted rounded-full flex items-center justify-center mx-auto mb-3">
                        <i data-lucide="wallet" class="w-6 h-6 text-gray-400 dark:text-gray-300"></i>
                    </div>
                    <h3 class="text-base font-light text-black dark:text-white mb-1">No transactions yet</h3>
                    <p class="text-gray-600 dark:text-gray-300 text-xs mb-3">Start by depositing funds to your wallet</p>
                    <a href="{{ route('wallet.deposit') }}" class="inline-flex items-center px-4 py-2 bg-black dark:bg-white text-white dark:text-gray-900 text-xs font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-gray-100 transition-colors duration-200">
                        <i data-lucide="plus" class="w-3 h-3 mr-1"></i>
                        Deposit Funds
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-user-layout> 
