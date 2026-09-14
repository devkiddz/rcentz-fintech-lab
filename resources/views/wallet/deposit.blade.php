<x-user-layout>
    <x-slot name="header">
        Deposit Funds
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
                        <h1 class="text-xl font-light mb-1">Deposit Funds</h1>
                        <p class="text-tesla-100 dark:text-gray-300 text-sm">Add money to your wallet using your preferred payment method</p>
                    </div>
                    
                    <!-- Enhanced Balance Card -->
                    <div class="bg-white bg-opacity-15 dark:bg-opacity-20 backdrop-blur-xl rounded-xl p-4 border border-white border-opacity-20 shadow-xl lg:w-80">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <p class="text-xs text-tesla-100 dark:text-gray-300 mb-1">Available Balance</p>
                                <p class="text-lg font-light">{{ format_currency(auth()->user()->wallet->balance ?? 0) }}</p>
                            </div>
                            <div class="w-10 h-10 flex items-center justify-center">
                                <i data-lucide="wallet" class="w-5 h-5 text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Deposit Form -->
        <div class="bg-card rounded-xl p-6 shadow-sm border border-border">
            <form action="{{ route('wallet.process-deposit') }}" method="POST" class="space-y-6">
                @csrf
                
                <!-- Amount Input -->
                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">Deposit Amount</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-300">{{ currency_symbol() }}</span>
                        <input type="number" 
                               id="amount" 
                               name="amount" 
                               step="0.01" 
                               min="1" 
                               max="100000"
                               value="{{ old('amount') }}"
                               class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition-colors duration-200"
                               placeholder="0.00"
                               required>
                    </div>
                    @error('amount')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Payment Method Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">Payment Method</label>
                    <div class="space-y-3">
                        @foreach($paymentMethods->where('allow_deposit', true) as $method)
                        <label class="flex items-center p-4 border border-border rounded-lg cursor-pointer hover:border-gray-300 hover:bg-gray-50 transition-all duration-200 group">
                            <input type="radio" 
                                   name="payment_method_id" 
                                   value="{{ $method->id }}" 
                                   class="w-4 h-4 text-black border-gray-300 focus:ring-black"
                                   {{ old('payment_method_id') == $method->id ? 'checked' : '' }}
                                   required>
                            <div class="ml-3 flex items-center flex-1">
                                @if($method->logo)
                                    <img src="{{ asset('storage/' . $method->logo) }}" alt="{{ $method->name }}" class="w-8 h-8 mr-3">
                                @else
                                    <div class="w-8 h-8 flex items-center justify-center mr-3">
                                        <i data-lucide="credit-card" class="w-4 h-4 text-gray-500 dark:text-gray-300"></i>
                                    </div>
                                @endif
                                <div class="flex-1">
                                    <p class="font-medium text-black dark:text-white text-sm">{{ $method->name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-300">{{ $method->description }}</p>
                                </div>
                                <div class="w-6 h-6 flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                                    <i data-lucide="arrow-right" class="w-4 h-4 text-gray-400 group-hover:text-gray-600 dark:text-gray-300"></i>
                                </div>
                            </div>
                        </label>
                        @endforeach
                    </div>
                    @error('payment_method_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Enhanced Fee Information -->
                <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4 border border-green-200">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-green-800">Fee Breakdown</span>
                        <div class="w-8 h-8 flex items-center justify-center">
                            <i data-lucide="calculator" class="w-4 h-4 text-green-600"></i>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-green-700">Deposit Amount:</span>
                            <span class="text-xs font-medium text-green-800" id="deposit-amount">{{ currency_symbol() }}0.00</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-green-700">Processing Fee:</span>
                            <span class="text-xs font-medium text-green-800" id="processing-fee">{{ currency_symbol() }}0.00</span>
                        </div>
                        <div class="border-t border-green-300 pt-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-green-800">Total Amount:</span>
                                <span class="text-xs font-bold text-green-900" id="total-amount">{{ currency_symbol() }}0.00</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-4">
                    <button type="submit" 
                            class="w-full bg-black dark:bg-white text-white dark:text-gray-900 py-3 px-6 rounded-lg font-medium hover:bg-gray-800 dark:hover:bg-gray-100 transition-colors duration-200 flex items-center justify-center">
                        <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                        Deposit Funds
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Fee calculation logic
        const amountInput = document.getElementById('amount');
        const depositAmount = document.getElementById('deposit-amount');
        const processingFee = document.getElementById('processing-fee');
        const totalAmount = document.getElementById('total-amount');

        function updateFeeCalculation() {
            const amount = parseFloat(amountInput.value) || 0;
            const fee = 0; // Processing fee is 0
            const total = amount + fee;

            depositAmount.textContent = `{{ currency_symbol() }}${amount.toFixed(2)}`;
            processingFee.textContent = `{{ currency_symbol() }}${fee.toFixed(2)}`;
            totalAmount.textContent = `{{ currency_symbol() }}${total.toFixed(2)}`;
        }

        amountInput.addEventListener('input', updateFeeCalculation);
        updateFeeCalculation();
    </script>
</x-user-layout> 
