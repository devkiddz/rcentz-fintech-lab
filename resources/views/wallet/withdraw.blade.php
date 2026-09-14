<x-user-layout>
    <x-slot name="header">
        Withdraw Funds
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
                        <h1 class="text-xl font-light mb-1">Withdraw Funds</h1>
                        <p class="text-tesla-100 dark:text-gray-300 text-sm">Transfer money from your wallet to your chosen destination</p>
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

        <!-- Enhanced Withdrawal Form -->
        <div class="bg-card rounded-xl p-6 shadow-sm border border-border">
            <form action="{{ route('wallet.process-withdrawal') }}" method="POST" class="space-y-6">
                @csrf
                
                <!-- Amount Input -->
                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">Withdrawal Amount</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-300">{{ currency_symbol() }}</span>
                        <input type="number" 
                               id="amount" 
                               name="amount" 
                               step="0.01" 
                               min="10" 
                               max="{{ auth()->user()->wallet->balance ?? 0 }}"
                               value="{{ old('amount') }}"
                               class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition-colors duration-200"
                               placeholder="0.00"
                               required>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Maximum withdrawal: {{ format_currency(auth()->user()->wallet->balance ?? 0) }}</p>
                    @error('amount')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Withdrawal Method Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">Withdrawal Method</label>
                    <div class="space-y-3">
                        @foreach($paymentMethods->where('allow_withdraw', true) as $method)
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
                                        <i data-lucide="banknote" class="w-4 h-4 text-gray-500 dark:text-gray-300"></i>
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

                <div id="destination-crypto" class="hidden">
                    <label for="wallet_address" class="block text-sm font-medium text-gray-700 mb-2">Destination Wallet Address</label>
                    <input id="wallet_address" name="wallet_address" type="text" value="{{ old('wallet_address') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-black focus:border-transparent" placeholder="Enter your wallet address" />
                    @error('wallet_address')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Enhanced Fee Information -->
                <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-lg p-4 border border-red-200">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-red-800">Fee Breakdown</span>
                        <div class="w-8 h-8 flex items-center justify-center">
                            <i data-lucide="calculator" class="w-4 h-4 text-red-600"></i>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-red-700">Withdrawal Amount:</span>
                            <span class="text-xs font-medium text-red-800" id="withdrawal-amount">{{ currency_symbol() }}0.00</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-red-700">Processing Fee:</span>
                            <span class="text-xs font-medium text-red-800" id="processing-fee">{{ currency_symbol() }}0.00</span>
                        </div>
                        <div class="border-t border-red-300 pt-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-red-800">You'll Receive:</span>
                                <span class="text-xs font-bold text-red-900" id="net-amount">{{ currency_symbol() }}0.00</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-4">
                    <button type="submit" 
                            class="w-full bg-black dark:bg-white text-white dark:text-gray-900 py-3 px-6 rounded-lg font-medium hover:bg-gray-800 dark:hover:bg-gray-100 transition-colors duration-200 flex items-center justify-center">
                        <i data-lucide="minus" class="w-4 h-4 mr-2"></i>
                        Withdraw Funds
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Fee calculation logic
        const amountInput = document.getElementById('amount');
        const withdrawalAmount = document.getElementById('withdrawal-amount');
        const processingFee = document.getElementById('processing-fee');
        const netAmount = document.getElementById('net-amount');

        function updateFeeCalculation() {
            const amount = parseFloat(amountInput.value) || 0;
            const fee = 0; // Processing fee is 0
            const net = amount - fee;

            withdrawalAmount.textContent = `{{ currency_symbol() }}${amount.toFixed(2)}`;
            processingFee.textContent = `{{ currency_symbol() }}${fee.toFixed(2)}`;
            netAmount.textContent = `{{ currency_symbol() }}${net.toFixed(2)}`;
        }

        amountInput.addEventListener('input', updateFeeCalculation);
        updateFeeCalculation();

        // Toggle destination fields based on method type
        const methodRadios = document.querySelectorAll('input[name="payment_method_id"]');
        const cryptoIds = [
            @foreach($paymentMethods->where('allow_withdraw', true)->where('type','cryptocurrency') as $m)
                '{{ $m->id }}',
            @endforeach
        ];
        const destCrypto = document.getElementById('destination-crypto');
        function updateDestinationFields() {
            const selected = document.querySelector('input[name="payment_method_id"]:checked');
            if (!selected) return;
            const isCrypto = cryptoIds.includes(selected.value);
            destCrypto.classList.toggle('hidden', !isCrypto);
            const walletInput = document.getElementById('wallet_address');
            if (walletInput) {
                walletInput.required = isCrypto;
            }
        }
        methodRadios.forEach(r => r.addEventListener('change', updateDestinationFields));
        updateDestinationFields();
    </script>
</x-user-layout> 
