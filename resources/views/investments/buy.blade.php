<x-user-layout>
    <x-slot name="header">
        Buy {{ $plan->name }}
    </x-slot>

    <div class="max-w-2xl mx-auto">
        <!-- Enhanced Header -->
        <div class="bg-gradient-to-br from-tesla-600 via-tesla-700 to-tesla-800 dark:from-tesla-700 dark:via-tesla-800 dark:to-tesla-900 rounded-2xl p-6 mb-6 text-white relative overflow-hidden">
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-0 right-0 w-32 h-32 bg-white rounded-full -translate-y-16 translate-x-16"></div>
                <div class="absolute bottom-0 left-0 w-16 h-16 bg-white rounded-full translate-y-8 -translate-x-8"></div>
            </div>
            
            <div class="relative z-10">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                    <div class="mb-4 lg:mb-0 lg:flex-1">
                        <h1 class="text-xl font-light mb-1">Buy {{ $plan->name }}</h1>
                        <p class="text-tesla-100 dark:text-gray-300 text-sm">{{ $plan->category }} • {{ ucfirst($plan->risk_level) }} Risk</p>
                    </div>
                    
                    <!-- Enhanced Plan Stats Card -->
                    <div class="bg-white bg-opacity-15 backdrop-blur-xl rounded-xl p-4 border border-white border-opacity-20 shadow-xl lg:w-64">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-tesla-100 dark:text-gray-300 mb-1">Current NAV</p>
                                <p class="text-lg font-light">{{ currency_symbol() }}{{ number_format($plan->nav, 4) }}</p>
                            </div>
                            <div class="w-10 h-10 flex items-center justify-center">
                                <i data-lucide="trending-up" class="w-5 h-5 text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Buy Form -->
        <div class="bg-card rounded-xl p-6 shadow-sm border border-border">
            <form action="{{ route('investments.execute-buy', $plan) }}" method="POST" class="space-y-6">
                @csrf
                
                <!-- Investment Amount -->
                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">Investment Amount</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-300">$</span>
                        <input type="number" 
                               id="amount" 
                               name="amount" 
                               step="0.01" 
                               min="{{ $plan->minimum_investment }}" 
                               max="100000"
                               value="{{ old('amount') }}"
                               class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition-colors duration-200"
                               placeholder="0.00"
                               required>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Minimum investment: {{ currency_symbol() }}{{ number_format($plan->minimum_investment, 2) }}</p>
                    @error('amount')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Plan Information -->
                <div class="bg-gradient-to-br from-tesla-50 to-tesla-100 rounded-lg p-4 border border-tesla-200">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-tesla-800">Plan Information</span>
                        <div class="w-8 h-8 flex items-center justify-center">
                            <i data-lucide="info" class="w-4 h-4 text-tesla-600"></i>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-tesla-700 mb-1">Current NAV</p>
                            <p class="text-sm font-medium text-tesla-800">{{ currency_symbol() }}{{ number_format($plan->nav, 4) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-tesla-700 mb-1">1Y Return</p>
                            <p class="text-sm font-medium {{ $plan->nav_change_percentage >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $plan->nav_change_percentage >= 0 ? '+' : '' }}{{ number_format($plan->nav_change_percentage, 2) }}%
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-tesla-700 mb-1">Management Fee</p>
                            <p class="text-sm font-medium text-tesla-800">{{ number_format($plan->management_fee * 100, 2) }}%</p>
                        </div>
                        <div>
                            <p class="text-xs text-tesla-700 mb-1">Risk Level</p>
                            <p class="text-sm font-medium text-tesla-800">{{ ucfirst($plan->risk_level) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Calculation Breakdown -->
                <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4 border border-green-200">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-green-800">Investment Breakdown</span>
                        <div class="w-8 h-8 flex items-center justify-center">
                            <i data-lucide="calculator" class="w-4 h-4 text-green-600"></i>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-green-700">Investment Amount:</span>
                            <span class="text-xs font-medium text-green-800" id="investment-amount">{{ currency_symbol() }}0.00</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-green-700">Units to Buy:</span>
                            <span class="text-xs font-medium text-green-800" id="units-to-buy">0.0000</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-green-700">Processing Fee:</span>
                            <span class="text-xs font-medium text-green-800" id="processing-fee">{{ currency_symbol() }}0.00</span>
                        </div>
                        <div class="border-t border-green-300 pt-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-green-800">Total Cost:</span>
                                <span class="text-xs font-bold text-green-900" id="total-cost">{{ currency_symbol() }}0.00</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Wallet Balance Check -->
                <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-lg p-4 border border-border">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-gray-800">Wallet Balance</span>
                        <div class="w-8 h-8 flex items-center justify-center">
                            <i data-lucide="wallet" class="w-4 h-4 text-gray-600 dark:text-gray-300"></i>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-700">Available Balance:</span>
                        <span class="text-xs font-medium text-gray-800">{{ currency_symbol() }}{{ number_format($wallet->balance, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between mt-1">
                        <span class="text-xs text-gray-700">After Purchase:</span>
                        <span class="text-xs font-medium text-gray-800" id="remaining-balance">{{ currency_symbol() }}{{ number_format($wallet->balance, 2) }}</span>
                    </div>
                </div>

                <!-- User Holdings (if any) -->
                @if($userHolding)
                <div class="bg-gradient-to-br from-tesla-50 to-tesla-100 rounded-lg p-4 border border-tesla-200">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-tesla-800">Your Current Holdings</span>
                        <div class="w-8 h-8 flex items-center justify-center">
                            <i data-lucide="pie-chart" class="w-4 h-4 text-tesla-600"></i>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-tesla-700 mb-1">Current Units</p>
                            <p class="text-sm font-medium text-tesla-800">{{ number_format($userHolding->units, 4) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-tesla-700 mb-1">Current Value</p>
                            <p class="text-sm font-medium text-tesla-800">{{ currency_symbol() }}{{ number_format($userHolding->units * $plan->nav, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-tesla-700 mb-1">Average Cost</p>
                            <p class="text-sm font-medium text-tesla-800">{{ currency_symbol() }}{{ number_format($userHolding->average_price, 4) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-tesla-700 mb-1">Total Invested</p>
                            <p class="text-sm font-medium text-tesla-800">{{ currency_symbol() }}{{ number_format($userHolding->total_invested, 2) }}</p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Submit Button -->
                <div class="pt-4">
                    <button type="submit" 
                            class="w-full bg-black dark:bg-white text-white dark:text-gray-900 py-3 px-6 rounded-lg font-medium hover:bg-gray-800 dark:hover:bg-gray-100 transition-colors duration-200 flex items-center justify-center">
                        <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                        Buy Investment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Investment calculation logic
        const amountInput = document.getElementById('amount');
        const investmentAmount = document.getElementById('investment-amount');
        const unitsToBuy = document.getElementById('units-to-buy');
        const processingFee = document.getElementById('processing-fee');
        const totalCost = document.getElementById('total-cost');
        const remainingBalance = document.getElementById('remaining-balance');
        const walletBalance = {{ $wallet->balance }};
        const nav = {{ $plan->nav }};

        function updateCalculation() {
            const amount = parseFloat(amountInput.value) || 0;
            const units = amount / nav;
            const fee = amount * 0.0025; // 0.25% processing fee
            const total = amount + fee;
            const remaining = walletBalance - total;

            investmentAmount.textContent = `{{ currency_symbol() }}${amount.toFixed(2)}`;
            unitsToBuy.textContent = units.toFixed(4);
            processingFee.textContent = `{{ currency_symbol() }}${fee.toFixed(2)}`;
            totalCost.textContent = `{{ currency_symbol() }}${total.toFixed(2)}`;
            remainingBalance.textContent = `{{ currency_symbol() }}${remaining.toFixed(2)}`;

            // Update button state based on balance
            const submitButton = document.querySelector('button[type="submit"]');
            if (total > walletBalance) {
                submitButton.disabled = true;
                submitButton.classList.add('opacity-50', 'cursor-not-allowed');
                submitButton.innerHTML = '<i data-lucide="alert-circle" class="w-4 h-4 mr-2"></i>Insufficient Funds';
            } else {
                submitButton.disabled = false;
                submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
                submitButton.innerHTML = '<i data-lucide="plus" class="w-4 h-4 mr-2"></i>Buy Investment';
            }
        }

        amountInput.addEventListener('input', updateCalculation);
        updateCalculation();
    </script>
</x-user-layout> 
