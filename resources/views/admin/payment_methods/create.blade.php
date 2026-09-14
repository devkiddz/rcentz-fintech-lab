<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-light text-lg text-foreground leading-tight mr-4">
                    {{ __('Add Payment Method') }}
                </h2>
            </div>
            <a href="{{ route('admin.payment_methods.index') }}" 
               class="inline-flex items-center px-3 py-2 bg-muted text-muted-foreground text-sm font-medium rounded-lg hover:bg-gray-200 transition-all duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-card border border-border overflow-hidden rounded-lg">
                <form action="{{ route('admin.payment_methods.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    
                    <div class="px-4 py-3 border-b border-border dark:border-gray-700">
                        <h3 class="text-base font-medium text-foreground">Payment Method Information</h3>
                        <p class="text-xs text-muted-foreground mt-1">Configure a new payment method for customers</p>
                    </div>

                    <div class="px-4 py-4 space-y-4">
                        <!-- Basic Information -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="name" class="block text-xs font-medium text-muted-foreground mb-2">Payment Method Name</label>
                                <input type="text" 
                                       name="name" 
                                       id="name"
                                       value="{{ old('name') }}"
                                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-black focus:border-black @error('name') border-red-500 @enderror"
                                       placeholder="e.g., Bitcoin, Credit Card, PayPal"
                                       required>
                                @error('name')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="type" class="block text-xs font-medium text-muted-foreground mb-2">Payment Type</label>
                                <select name="type" 
                                        id="type" 
                                        class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-black focus:border-black @error('type') border-red-500 @enderror"
                                        onchange="toggleCryptoFields()"
                                        required>
                                    <option value="traditional" {{ old('type') == 'traditional' ? 'selected' : '' }}>Traditional Payment</option>
                                    <option value="cryptocurrency" {{ old('type') == 'cryptocurrency' ? 'selected' : '' }}>Cryptocurrency</option>
                                </select>
                                @error('type')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Details -->
                        <div>
                            <label for="details" class="block text-xs font-medium text-muted-foreground mb-2">Description</label>
                            <textarea name="details" 
                                      id="details" 
                                      rows="3"
                                      class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-black focus:border-black @error('details') border-red-500 @enderror"
                                      placeholder="Additional information about this payment method">{{ old('details') }}</textarea>
                            @error('details')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Logo Upload -->
                        <div>
                            <label for="logo" class="block text-xs font-medium text-muted-foreground mb-2">Logo/Icon</label>
                            <input type="file" 
                                   name="logo" 
                                   id="logo"
                                   accept="image/*"
                                   class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-black focus:border-black @error('logo') border-red-500 @enderror">
                            <p class="mt-1 text-xs text-muted-foreground dark:text-gray-300">Upload a logo or icon for this payment method (PNG, JPG, SVG)</p>
                            @error('logo')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Cryptocurrency Fields -->
                        <div id="crypto-fields" class="space-y-4 p-4 bg-tesla-50 rounded-lg border border-tesla-200" style="display: none;">
                            <h4 class="text-base font-medium text-tesla-900 mb-3">Cryptocurrency Settings</h4>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="crypto_symbol" class="block text-xs font-medium text-muted-foreground mb-2">Crypto Symbol</label>
                                    <input type="text" 
                                           name="crypto_symbol" 
                                           id="crypto_symbol"
                                           value="{{ old('crypto_symbol') }}"
                                           class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-black focus:border-black @error('crypto_symbol') border-red-500 @enderror"
                                           placeholder="e.g., BTC, ETH, LTC">
                                    @error('crypto_symbol')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="network_fee" class="block text-xs font-medium text-muted-foreground mb-2">Network Fee</label>
                                    <input type="number" 
                                           name="network_fee" 
                                           id="network_fee"
                                           value="{{ old('network_fee') }}"
                                           step="0.00000001"
                                           min="0"
                                           class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-black focus:border-black @error('network_fee') border-red-500 @enderror"
                                           placeholder="0.00001">
                                    <p class="mt-1 text-xs text-muted-foreground dark:text-gray-300">Network transaction fee in crypto units</p>
                                    @error('network_fee')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div>
                                <label for="wallet_address" class="block text-xs font-medium text-muted-foreground mb-2">Wallet Address</label>
                                <input type="text" 
                                       name="wallet_address" 
                                       id="wallet_address"
                                       value="{{ old('wallet_address') }}"
                                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-black focus:border-black @error('wallet_address') border-red-500 @enderror font-mono text-sm"
                                       placeholder="Enter the wallet address for receiving payments">
                                @error('wallet_address')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="barcode" class="block text-xs font-medium text-muted-foreground mb-2">QR Code/Barcode</label>
                                <input type="file" 
                                       name="barcode" 
                                       id="barcode"
                                       accept="image/*"
                                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-black focus:border-black @error('barcode') border-red-500 @enderror">
                                <p class="mt-1 text-xs text-muted-foreground dark:text-gray-300">Upload a QR code image for the wallet address</p>
                                @error('barcode')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Permissions -->
                        <div class="space-y-3">
                            <h4 class="text-base font-medium text-foreground">Permissions</h4>
                            
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           name="is_active" 
                                           value="1" 
                                           {{ old('is_active', true) ? 'checked' : '' }}
                                           class="text-foreground focus:ring-black">
                                    <span class="ml-2 text-sm text-foreground">Active (available for customers)</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           name="allow_deposit" 
                                           value="1" 
                                           {{ old('allow_deposit', true) ? 'checked' : '' }}
                                           class="text-foreground focus:ring-black">
                                    <span class="ml-2 text-sm text-foreground">Allow deposits</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           name="allow_withdraw" 
                                           value="1" 
                                           {{ old('allow_withdraw', true) ? 'checked' : '' }}
                                           class="text-foreground focus:ring-black">
                                    <span class="ml-2 text-sm text-foreground">Allow withdrawals</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="px-4 py-4 bg-muted/40 border-t border-border dark:border-gray-700 flex justify-end space-x-3">
                        <a href="{{ route('admin.payment_methods.index') }}" 
                           class="px-4 py-2 bg-muted text-muted-foreground text-sm font-medium rounded-lg hover:bg-gray-200 transition-all duration-200">
                            Cancel
                        </a>
                        <button type="submit" 
                                class="px-6 py-2 bg-black dark:bg-card text-white dark:text-foreground text-sm font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-muted transition-all duration-200">
                            Create Payment Method
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleCryptoFields() {
            const typeSelect = document.getElementById('type');
            const cryptoFields = document.getElementById('crypto-fields');
            
            if (typeSelect.value === 'cryptocurrency') {
                cryptoFields.style.display = 'block';
            } else {
                cryptoFields.style.display = 'none';
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleCryptoFields();
        });
    </script>
</x-admin-layout> 
