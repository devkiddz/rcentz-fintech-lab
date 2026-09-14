<x-user-layout>
    <x-slot name="header">
        Sell {{ $plan->name }}
    </x-slot>

    <div class="max-w-2xl mx-auto">
        <!-- Enhanced Header -->
        <div class="bg-gradient-to-br from-tesla-600 via-tesla-700 to-tesla-800 dark:from-tesla-700 dark:via-tesla-800 dark:to-tesla-900 rounded-2xl p-6 mb-6 text-white relative overflow-hidden">
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-0 right-0 w-32 h-32 bg-card rounded-full -translate-y-16 translate-x-16"></div>
                <div class="absolute bottom-0 left-0 w-16 h-16 bg-card rounded-full translate-y-8 -translate-x-8"></div>
            </div>
            
            <div class="relative z-10">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                    <div class="mb-4 lg:mb-0 lg:flex-1">
                        <h1 class="text-xl font-light mb-1">Sell {{ $plan->name }}</h1>
                        <p class="text-tesla-100 dark:text-gray-300 text-sm">{{ $plan->category }} • {{ ucfirst($plan->risk_level) }} Risk</p>
                    </div>
                    
                    <!-- Enhanced Plan Stats Card -->
                    <div class="bg-card bg-opacity-15 backdrop-blur-xl rounded-xl p-4 border border-white border-opacity-20 shadow-xl lg:w-64">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-tesla-100 dark:text-gray-300 mb-1">Current NAV</p>
                                <p class="text-lg font-light">{{ currency_symbol() }}{{ number_format($plan->nav, 4) }}</p>
                            </div>
                            <div class="w-10 h-10 flex items-center justify-center">
                                <i data-lucide="trending-down" class="w-5 h-5 text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Sell Form -->
        <div class="bg-card rounded-xl p-6 shadow-sm border border-border">
            <form action="{{ route('investments.execute-sell', $plan) }}" method="POST" class="space-y-6">
                @csrf
                
                <!-- Current Holdings Summary -->
                <div class="bg-gradient-to-br from-tesla-50 to-tesla-100 rounded-lg p-4 border border-tesla-200">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-tesla-800">Your Holdings</span>
                        <div class="w-8 h-8 flex items-center justify-center">
                            <i data-lucide="pie-chart" class="w-4 h-4 text-tesla-600"></i>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-tesla-700 mb-1">Total Units</p>
                            <p class="text-sm font-medium text-tesla-800">{{ number_format($holding->units, 4) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-tesla-700 mb-1">Current Value</p>
                            <p class="text-sm font-medium text-tesla-800">{{ currency_symbol() }}{{ number_format($holding->units * $plan->nav, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-tesla-700 mb-1">Average Cost</p>
                            <p class="text-sm font-medium text-tesla-800">{{ currency_symbol() }}{{ number_format($holding->average_cost, 4) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-tesla-700 mb-1">Total Invested</p>
                            <p class="text-sm font-medium text-tesla-800">{{ currency_symbol() }}{{ number_format($holding->total_invested, 2) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Sell Options -->
                <div>
                    <label class="block text-sm font-medium text-foreground mb-3">Sell Options</label>
                    <div class="space-y-3">
                        <label class="flex items-center p-3 border border-border rounded-lg cursor-pointer hover:border-border hover:bg-muted/30 transition-all duration-200">
                            <input type="radio" 
                                   name="sell_option" 
                                   value="units" 
                                   class="w-4 h-4 text-foreground border-border focus:ring-ring"
                                   checked>
                            <div class="ml-3 flex-1">
                                <p class="font-medium text-foreground text-sm">Sell by Units</p>
                                <p class="text-xs text-muted-foreground dark:text-gray-300">Specify the number of units to sell</p>
                            </div>
                        </label>
                        
                        <label class="flex items-center p-3 border border-border rounded-lg cursor-pointer hover:border-border hover:bg-muted/30 transition-all duration-200">
                            <input type="radio" 
                                   name="sell_option" 
                                   value="amount" 
                                   class="w-4 h-4 text-foreground border-border focus:ring-ring">
                            <div class="ml-3 flex-1">
                                <p class="font-medium text-foreground text-sm">Sell by Amount</p>
                                <p class="text-xs text-muted-foreground dark:text-gray-300">Specify the dollar amount to sell</p>
                            </div>
                        </label>
                        
                        <label class="flex items-center p-3 border border-border rounded-lg cursor-pointer hover:border-border hover:bg-muted/30 transition-all duration-200">
                            <input type="radio" 
                                   name="sell_option" 
                                   value="all" 
                                   class="w-4 h-4 text-foreground border-border focus:ring-ring">
                            <div class="ml-3 flex-1">
                                <p class="font-medium text-foreground text-sm">Sell All</p>
                                <p class="text-xs text-muted-foreground dark:text-gray-300">Sell your entire holding</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Sell Amount/Units Input -->
                <div id="sell-input-section">
                    <label for="sell_value" class="block text-sm font-medium text-foreground mb-2">Amount to Sell</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground dark:text-gray-300">$</span>
                        <input type="number" 
                               id="sell_value" 
                               name="sell_value" 
                               step="0.01" 
                               min="0.01" 
                                max="{{ $holding->units * $plan->nav }}"
                               value="{{ old('sell_value') }}"
                               class="w-full pl-8 pr-4 py-3 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-transparent transition-colors duration-200"
                               placeholder="0.00"
                               required>
                    </div>
                    <p class="text-xs text-muted-foreground mt-1">Maximum: {{ currency_symbol() }}{{ number_format($holding->units * $plan->nav, 2) }}</p>
                    @error('sell_value')
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
                <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-lg p-4 border border-red-200">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-red-800">Sale Breakdown</span>
                        <div class="w-8 h-8 flex items-center justify-center">
                            <i data-lucide="calculator" class="w-4 h-4 text-red-600"></i>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-red-700">Sale Amount:</span>
                            <span class="text-xs font-medium text-red-800" id="sale-amount">{{ currency_symbol() }}0.00</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-red-700">Units to Sell:</span>
                            <span class="text-xs font-medium text-red-800" id="units-to-sell">0.0000</span>
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

                <!-- Profit/Loss Calculation -->
                <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-lg p-4 border border-border">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-foreground">Profit/Loss</span>
                        <div class="w-8 h-8 flex items-center justify-center">
                            <i data-lucide="trending-up" class="w-4 h-4 text-muted-foreground"></i>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-foreground">Average Cost:</span>
                            <span class="text-xs font-medium text-foreground">{{ currency_symbol() }}{{ number_format($holding->average_cost, 4) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-foreground">Current NAV:</span>
                            <span class="text-xs font-medium text-foreground">{{ currency_symbol() }}{{ number_format($plan->nav, 4) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-foreground">Gain/Loss:</span>
                            <span class="text-xs font-medium" id="gain-loss">{{ currency_symbol() }}0.00 (0.00%)</span>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-4">
                    <button type="submit" 
                            class="w-full bg-foreground text-background py-3 px-6 rounded-lg font-medium hover:opacity-90 transition-colors duration-200 flex items-center justify-center">
                        <i data-lucide="minus" class="w-4 h-4 mr-2"></i>
                        Sell Investment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
       // Investment sell calculation logic
const sellValueInput = document.getElementById('sell_value');
const saleAmount = document.getElementById('sale-amount');
const unitsToSell = document.getElementById('units-to-sell');
const processingFee = document.getElementById('processing-fee');
const netAmount = document.getElementById('net-amount');
const gainLoss = document.getElementById('gain-loss');

const userHoldingUnits = {{ $holding->units }};
const userHoldingAverageCost = {{ $holding->average_cost }};
const nav = {{ $plan->nav }};
const maxAmount = Math.floor((userHoldingUnits * nav) * 100) / 100; // Round down to nearest cent

function updateCalculation() {
    const selected = document.querySelector('input[name="sell_option"]:checked')?.value || 'units';
    let amount = 0;
    let units = 0;

    if (selected === 'units') {
        units = parseFloat(sellValueInput.value) || 0;
        if (units > userHoldingUnits) units = userHoldingUnits;
        amount = units * nav;
    } else if (selected === 'amount') {
        amount = parseFloat(sellValueInput.value) || 0;
        units = amount / nav;
        if (units > userHoldingUnits) {
            units = userHoldingUnits;
            amount = units * nav;
        }
    } else if (selected === 'all') {
        units = userHoldingUnits;
        amount = units * nav;
        sellValueInput.value = maxAmount.toFixed(2); // Use the rounded maxAmount
    }

    const fee = amount * 0.0025; // 0.25% processing fee
    const net = amount - fee;
    const costBasis = units * userHoldingAverageCost;
    const gainLossValue = amount - costBasis;
    const gainLossPercent = costBasis > 0 ? ((gainLossValue / costBasis) * 100) : 0;

    saleAmount.textContent = `{{ currency_symbol() }}${amount.toFixed(2)}`;
    unitsToSell.textContent = units.toFixed(4);
    processingFee.textContent = `{{ currency_symbol() }}${fee.toFixed(2)}`;
    netAmount.textContent = `{{ currency_symbol() }}${net.toFixed(2)}`;
    
    const gainLossText = `{{ currency_symbol() }}${gainLossValue.toFixed(2)} (${gainLossPercent.toFixed(2)}%)`;
    gainLoss.textContent = gainLossText;
    gainLoss.className = `text-xs font-medium ${gainLossValue >= 0 ? 'text-green-600' : 'text-red-600'}`;

    // Sync hidden units input for server validation
    let hiddenUnits = document.getElementById('units');
    if (!hiddenUnits) {
        hiddenUnits = document.createElement('input');
        hiddenUnits.type = 'hidden';
        hiddenUnits.name = 'units';
        hiddenUnits.id = 'units';
        sellValueInput.form.appendChild(hiddenUnits);
    }
    hiddenUnits.value = units > 0 ? units.toFixed(6) : '';

    // Update button state based on selection
    const submitButton = document.querySelector('button[type="submit"]');
    if (units > userHoldingUnits || amount > maxAmount) {
        submitButton.disabled = true;
        submitButton.classList.add('opacity-50', 'cursor-not-allowed');
        submitButton.innerHTML = '<i data-lucide="alert-circle" class="w-4 h-4 mr-2"></i>Amount Exceeds Holdings';
    } else if (units <= 0 || amount <= 0) {
        submitButton.disabled = true;
        submitButton.classList.add('opacity-50', 'cursor-not-allowed');
        submitButton.innerHTML = '<i data-lucide="alert-circle" class="w-4 h-4 mr-2"></i>Invalid Amount';
    } else {
        submitButton.disabled = false;
        submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
        submitButton.innerHTML = '<i data-lucide="minus" class="w-4 h-4 mr-2"></i>Sell Investment';
    }
}

// Handle sell option changes
const sellOptions = document.querySelectorAll('input[name="sell_option"]');
sellOptions.forEach(option => {
    option.addEventListener('change', function() {
        const sellInputSection = document.getElementById('sell-input-section');
        const sellValueInput = document.getElementById('sell_value');
        const label = sellInputSection.querySelector('label');
        const input = sellInputSection.querySelector('input');
        const maxText = sellInputSection.querySelector('p');

        if (this.value === 'units') {
            label.textContent = 'Units to Sell';
            input.placeholder = '0.0000';
            input.step = '0.0001';
            input.max = userHoldingUnits;
            input.removeAttribute('min'); // Remove min for units
            maxText.textContent = `Maximum: ${userHoldingUnits.toFixed(4)} units`;
        } else if (this.value === 'amount') {
            label.textContent = 'Amount to Sell';
            input.placeholder = '0.00';
            input.step = '0.01';
            input.max = maxAmount;
            input.setAttribute('min', '0.01');
            maxText.textContent = `Maximum: $${maxAmount.toFixed(2)}`;
        } else if (this.value === 'all') {
            label.textContent = 'Sell All';
            input.placeholder = maxAmount.toFixed(2);
            input.step = '0.01';
            input.removeAttribute('max'); // Remove max constraint for "sell all"
            input.removeAttribute('min'); // Remove min constraint for "sell all"
            input.value = maxAmount;
            maxText.textContent = `Selling all ${userHoldingUnits.toFixed(4)} units`;
        }
        
        updateCalculation();
    });
});

sellValueInput.addEventListener('input', updateCalculation);
updateCalculation();
    </script>
</x-user-layout> 
