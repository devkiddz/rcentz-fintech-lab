@extends('layouts.main')

@section('title', 'Payment - ' . $purchase->car->title)

@section('content')
<div class="min-h-screen bg-white">
    <!-- Header / Progress (revamped) -->
    <header class="relative overflow-hidden bg-gradient-to-br from-black via-gray-900 to-black text-white">
        <svg class="absolute inset-0 opacity-[0.06] z-0 pointer-events-none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none" aria-hidden="true" focusable="false">
            <defs>
                <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                    <path d="M 40 0 L 0 0 0 40" fill="none" stroke="white" stroke-width="1" />
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#grid)" />
        </svg>
        <div class="absolute inset-0 z-0 pointer-events-none" aria-hidden="true">
            @if($purchase->car->first_image)
                <img src="{{ strpos($purchase->car->first_image, 'http') === 0 ? $purchase->car->first_image : asset('storage/' . $purchase->car->first_image) }}" alt="" class="h-full w-full object-cover opacity-20" loading="eager" decoding="async" />
            @endif
            <div class="absolute inset-0 bg-gradient-to-b from-black/80 via-black/70 to-black/90"></div>
        </div>
        <div class="relative z-10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
                <div class="flex items-center justify-between">
                    <!-- Breadcrumb -->
                    <nav class="flex items-center space-x-2 text-sm text-tesla-100 dark:text-gray-300" aria-label="Breadcrumb">
                        <a href="{{ route('home') }}" class="hover:text-white transition-colors">Home</a>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        <a href="{{ route('cars.show', $purchase->car->id) }}" class="hover:text-white transition-colors">{{ $purchase->car->title }}</a>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        <a href="{{ route('checkout.form', $purchase->car->id) }}" class="hover:text-white transition-colors">Checkout</a>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        <span class="text-white font-medium">Payment</span>
                    </nav>

                    <!-- Progress Steps -->
                    <ol class="hidden md:flex items-center space-x-4" aria-label="Progress">
                        <li class="flex items-center">
                            <span class="w-8 h-8 bg-white text-black rounded-full flex items-center justify-center text-sm font-medium">✓</span>
                            <span class="ml-2 text-sm font-medium text-white">Order Details</span>
                        </li>
                        <li class="w-8 h-px bg-white/30" aria-hidden="true"></li>
                        <li class="flex items-center">
                            <span class="w-8 h-8 bg-white text-black rounded-full flex items-center justify-center text-sm font-medium">2</span>
                            <span class="ml-2 text-sm text-white">Payment</span>
                        </li>
                        <li class="w-8 h-px bg-white/30" aria-hidden="true"></li>
                        <li class="flex items-center">
                            <span class="w-8 h-8 bg-white/10 text-white rounded-full flex items-center justify-center text-sm font-medium">3</span>
                            <span class="ml-2 text-sm text-tesla-100 dark:text-gray-300">Confirmation</span>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </header>

    <!-- Payment Timer moved inside payment card header -->

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-16">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-12">
                <!-- Header with logo, title, and inline timer -->
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div class="text-center lg:text-left">
                        <h1 class="text-3xl sm:text-4xl font-light text-black dark:text-white mb-1">
                            @php $sym = strtoupper($purchase->paymentMethod->crypto_symbol); @endphp
                            Complete Your {{ $sym === 'BTC' ? 'Bitcoin (BTC)' : $sym }} Payment
                        </h1>
                        <p class="text-gray-600 font-light">Send the exact amount to complete your purchase</p>
                    </div>
                    <div class="flex items-center justify-center lg:justify-end">
                        <div id="inline-timer-box" class="flex items-center gap-3 bg-gray-50 border border-border px-4 py-2 rounded-lg">
                            <div class="w-8 h-8 text-white rounded flex items-center justify-center" style="background-color: #C8102E;">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="text-right min-w-[140px]">
                                <div id="timer" class="text-xl sm:text-2xl font-light font-mono" aria-live="polite" role="status">30:00</div>
                                <p class="text-xs text-gray-500 font-light leading-none">Time Remaining</p>
                                <div class="mt-1 h-1 bg-gray-200 rounded overflow-hidden">
                                    <div id="timer-progress" class="h-1 rounded" style="width: 100%; background-color: #C8102E;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Details Card -->
                <div class="bg-card border border-border p-8 rounded-2xl shadow-sm">
                    <div class="flex items-center mb-10">
                        @if($purchase->paymentMethod->logo_url)
                            <img src="{{ $purchase->paymentMethod->logo_url }}" 
                                 alt="{{ $purchase->paymentMethod->name }}" 
                                 class="w-12 h-12 object-contain mr-4">
                        @else
                            <div class="w-12 h-12 bg-orange-100 flex items-center justify-center mr-4">
                                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                </svg>
                            </div>
                        @endif
                        <div>
                            <h3 class="text-xl font-medium text-black">{{ $purchase->paymentMethod->name }}</h3>
                            <p class="text-gray-600 font-light">{{ strtoupper($purchase->paymentMethod->crypto_symbol) }} Payment</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Amount Section -->
                        <div>
                            <label class="block text-sm font-medium text-black dark:text-white mb-4">Amount to Send</label>
                            <div class="bg-gray-50 border border-border p-6 rounded-xl">
                                <div class="text-2xl font-light text-black dark:text-white mb-3">{{ $purchase->formatted_crypto_amount }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-300 mb-4 font-light">≈ {{ $purchase->formatted_amount }} USD</div>
                                @if($purchase->paymentMethod->network_fee)
                                    <div class="text-xs text-orange-600 mb-4 font-light">+ {{ $purchase->paymentMethod->formatted_network_fee }} network fee</div>
                                @endif
                                <button onclick="copyToClipboard('{{ $purchase->crypto_amount }}')" 
                                        class="text-sm text-black hover:text-gray-600 underline transition-colors duration-200">
                                    Copy Amount
                                </button>
                            </div>
                        </div>

                        <!-- Wallet Address Section -->
                        <div>
                            <label class="block text-sm font-medium text-black dark:text-white mb-4">Wallet Address</label>
                            <div class="bg-gray-50 border border-border p-6 rounded-xl">
                                <div class="flex items-center justify-between mb-4">
                                    <code class="text-sm font-mono text-black break-all pr-2">{{ $purchase->paymentMethod->wallet_address }}</code>
                                    <button onclick="copyToClipboard('{{ $purchase->paymentMethod->wallet_address }}')" 
                                            class="flex-shrink-0 p-2 text-gray-500 hover:text-black hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-200"
                                            title="Copy Address">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                        </svg>
                                    </button>
                                </div>
                                <button onclick="copyToClipboard('{{ $purchase->paymentMethod->wallet_address }}')" 
                                        class="text-sm text-black hover:text-gray-600 underline transition-colors duration-200">
                                    Copy Address
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- QR Code Section -->
                    <div class="mt-10">
                        <label class="block text-sm font-medium text-black dark:text-white mb-4">QR Code</label>
                        <div class="bg-gray-50 border border-border p-8 rounded-xl text-center">
                            @php
                                $cryptoSymbol = strtolower($purchase->paymentMethod->crypto_symbol);
                                $paymentUri = $cryptoSymbol . ':' . $purchase->paymentMethod->wallet_address . '?amount=' . $purchase->crypto_amount;
                                $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&format=png&data=' . urlencode($paymentUri);
                            @endphp
                            
                            <div class="relative inline-block">
                                <img id="qr-code" 
                                     src="{{ $qrCodeUrl }}" 
                                     alt="Payment QR Code" 
                                     class="mx-auto w-48 h-48 border border-border"
                                     onerror="this.style.display='none'; document.getElementById('qr-fallback').style.display='block';">
                                
                                <div id="qr-fallback" style="display: none;" class="w-48 h-48 mx-auto bg-gray-100 border border-border flex items-center justify-center">
                                    <div class="text-center">
                                        <svg class="w-12 h-12 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 16h4.01M12 8h4.01M12 16h.01m0 0h3.99m-3.99 0v4"/>
                                        </svg>
                                        <p class="text-xs text-gray-500 dark:text-gray-300">QR Code unavailable</p>
                                        <p class="text-xs text-gray-400 font-light">Use wallet address above</p>
                                    </div>
                                </div>
                                
                                <button onclick="refreshQRCode()" 
                                        class="absolute top-2 right-2 bg-white/90 hover:bg-white p-2 border border-border transition-all duration-200"
                                        title="Refresh QR Code">
                                    <svg class="w-4 h-4 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                </button>
                            </div>
                            
                            <div class="mt-8 space-y-2">
                                <p class="text-sm text-gray-600 font-light">Scan with your {{ strtoupper($purchase->paymentMethod->crypto_symbol) }} wallet</p>
                                <p class="text-xs text-gray-500 font-light">Amount: {{ $purchase->formatted_crypto_amount }}</p>
                                <button onclick="copyToClipboard('{{ $paymentUri }}')" 
                                        class="text-sm text-black hover:text-gray-600 underline transition-colors duration-200">
                                    Copy Payment URI
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Instructions -->
                <div class="bg-card border border-border p-8 rounded-2xl shadow-sm">
                    <h3 class="text-xl font-medium text-black dark:text-white mb-10">Payment Instructions</h3>
                    <div class="space-y-8">
                        <div class="flex items-start">
                            <div class="w-8 h-8 text-white flex items-center justify-center text-sm font-medium mr-6 mt-0.5 flex-shrink-0" style="background-color: #C8102E;">1</div>
                            <div>
                                <h4 class="font-medium text-black dark:text-white mb-3">Open Your Wallet</h4>
                                <p class="text-gray-600 font-light">Open your {{ strtoupper($purchase->paymentMethod->crypto_symbol) }} wallet app or scan the QR code above</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="w-8 h-8 text-white flex items-center justify-center text-sm font-medium mr-6 mt-0.5 flex-shrink-0" style="background-color: #C8102E;">2</div>
                            <div>
                                <h4 class="font-medium text-black dark:text-white mb-3">Send Exact Amount</h4>
                                <p class="text-gray-600 font-light">Send exactly <strong>{{ $purchase->formatted_crypto_amount }}</strong> to the wallet address provided</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="w-8 h-8 text-white flex items-center justify-center text-sm font-medium mr-6 mt-0.5 flex-shrink-0" style="background-color: #C8102E;">3</div>
                            <div>
                                <h4 class="font-medium text-black dark:text-white mb-3">Get Transaction Hash</h4>
                                <p class="text-gray-600 font-light">After sending, copy the transaction hash from your wallet</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="w-8 h-8 text-white flex items-center justify-center text-sm font-medium mr-6 mt-0.5 flex-shrink-0" style="background-color: #C8102E;">4</div>
                            <div>
                                <h4 class="font-medium text-black dark:text-white mb-3">Confirm Payment</h4>
                                <p class="text-gray-600 font-light">Paste the transaction hash below to complete your purchase</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transaction Hash Form -->
                <div class="bg-card border border-border p-8 rounded-2xl shadow-sm">
                    <h3 class="text-xl font-medium text-black dark:text-white mb-10">Confirm Your Payment</h3>
                    <form action="{{ route('checkout.crypto-payment.confirm', $purchase->id) }}" method="POST" class="space-y-8" id="payment-form">
                        @csrf
                        <div>
                            <label for="transaction_hash" class="block text-sm font-medium text-black dark:text-white mb-4">Transaction Hash</label>
                            <input type="text" 
                                   id="transaction_hash" 
                                   name="transaction_hash" 
                                   value="{{ old('transaction_hash') }}"
                                   class="w-full px-4 py-4 border border-gray-300 focus:outline-none focus:border-black font-mono text-sm transition-colors duration-200 @error('transaction_hash') border-red-500 @enderror"
                                   placeholder="Enter your transaction hash here..."
                                   required>
                            @error('transaction_hash')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-3 text-xs text-gray-500 font-light">You can find this in your wallet after sending the payment</p>
                        </div>

                        <button type="submit" 
                                class="w-full text-white py-4 px-6 text-lg font-medium transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                                style="background-color: #C8102E;"
                                onmouseover="if(!this.disabled) this.style.backgroundColor='#A00D25'"
                                onmouseout="if(!this.disabled) this.style.backgroundColor='#C8102E'"
                                id="confirm-button">
                            <span class="confirm-text">Confirm Payment</span>
                            <span class="loading-text hidden">Verifying...</span>
                        </button>
                    </form>
                </div>

                <!-- Important Notice -->
                <div class="bg-red-50 border border-red-200 p-8 rounded-2xl">
                    <div class="flex items-start">
                        <svg class="w-6 h-6 text-red-600 mr-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                        <div>
                            <h4 class="font-medium text-red-900 mb-6">Important Notice</h4>
                            <ul class="text-sm text-red-800 space-y-4 font-light">
                                <li class="flex items-start">
                                    <span class="w-1.5 h-1.5 bg-red-600 mt-2 mr-4 flex-shrink-0"></span>
                                    Send only {{ strtoupper($purchase->paymentMethod->crypto_symbol) }} to this address
                                </li>
                                <li class="flex items-start">
                                    <span class="w-1.5 h-1.5 bg-red-600 mt-2 mr-4 flex-shrink-0"></span>
                                    Double-check the amount and address before sending
                                </li>
                                <li class="flex items-start">
                                    <span class="w-1.5 h-1.5 bg-red-600 mt-2 mr-4 flex-shrink-0"></span>
                                    Cryptocurrency transactions are irreversible
                                </li>
                                <li class="flex items-start">
                                    <span class="w-1.5 h-1.5 bg-red-600 mt-2 mr-4 flex-shrink-0"></span>
                                    Your purchase will be processed after blockchain confirmation
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Summary Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-card border border-border p-8 rounded-2xl shadow-sm sticky top-24">
                    <h2 class="text-xl font-medium text-black dark:text-white mb-10">Order Summary</h2>
                    
                    <!-- Car Details -->
                    <div class="flex space-x-4 mb-10">
                        <div class="w-20 h-20 bg-gray-100 overflow-hidden flex-shrink-0">
                            @if($purchase->car->first_image)
                                <img src="{{ strpos($purchase->car->first_image, 'http') === 0 ? $purchase->car->first_image : asset('storage/' . $purchase->car->first_image) }}" 
                                     alt="{{ $purchase->car->title }}" 
                                     class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full bg-gradient-to-br from-gray-200 to-gray-300 flex items-center justify-center">
                                    <svg class="w-8 h-8 text-gray-500 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                    </svg>
                                </div>
                            @endif
                        </div>
                        <div class="flex-1">
                            <h3 class="font-medium text-black dark:text-white mb-2">{{ $purchase->car->title }}</h3>
                            <p class="text-sm text-gray-600 font-light">{{ $purchase->car->year }} {{ $purchase->car->make }} {{ $purchase->car->model }}</p>
                            <p class="text-sm text-gray-600 font-light">{{ $purchase->car->color }}</p>
                        </div>
                    </div>

                    <!-- Payment Details -->
                    <div class="space-y-4 border-t border-border pt-10 mb-10">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-300">Vehicle Price</span>
                            <span class="font-medium text-black">{{ $purchase->formatted_amount }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-300">Payment Method</span>
                            <span class="font-medium text-black">{{ $purchase->paymentMethod->name }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-300">Crypto Amount</span>
                            <span class="font-medium text-black">{{ $purchase->formatted_crypto_amount }}</span>
                        </div>
                        @if($purchase->paymentMethod->network_fee)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-300">Network Fee</span>
                            <span class="font-medium text-orange-600">{{ $purchase->paymentMethod->formatted_network_fee }}</span>
                        </div>
                        @endif
                        <div class="border-t border-border pt-6">
                            <div class="flex justify-between">
                                <span class="text-lg font-medium text-black">Total (USD)</span>
                                <span class="text-lg font-medium text-black">{{ $purchase->formatted_amount }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="p-8 bg-orange-50 border border-orange-200 mb-10">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-orange-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-sm font-medium text-orange-800">Payment Pending</span>
                        </div>
                        <p class="text-xs text-orange-700 mt-2 font-light">Complete your cryptocurrency payment to proceed</p>
                    </div>

                    <!-- Security Features -->
                    <div class="space-y-4">
                        <div class="flex items-center space-x-3 text-sm text-gray-600 dark:text-gray-300">
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="font-light">Blockchain Verified</span>
                        </div>
                        <div class="flex items-center space-x-3 text-sm text-gray-600 dark:text-gray-300">
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="font-light">Secure Transaction</span>
                        </div>
                        <div class="flex items-center space-x-3 text-sm text-gray-600 dark:text-gray-300">
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="font-light">Real-time Monitoring</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Timer variables
    let timeLeft = 30 * 60; // 30 minutes in seconds
    let timerInterval;
    let isTimerExpired = false;

    // Start timer
    function startTimer() {
        timerInterval = setInterval(function() {
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                handleTimerExpired();
                return;
            }

            updateTimerDisplay();
            updateProgressBar();
            timeLeft--;
        }, 1000);
    }

    function updateTimerDisplay() {
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        const display = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        document.getElementById('timer').textContent = display;
        
        // Change color when time is running low (last 5 minutes)
        if (timeLeft <= 300) {
            document.getElementById('timer').classList.add('animate-pulse');
        }
    }

    function updateProgressBar() {
        const bar = document.getElementById('timer-progress');
        if (!bar) {
            return; // progress bar not present (e.g., timer layout changed)
        }
        const totalTime = 30 * 60;
        const progressPercentage = Math.max(0, Math.min(100, (timeLeft / totalTime) * 100));
        bar.style.width = progressPercentage + '%';
    }

    function handleTimerExpired() {
        isTimerExpired = true;
        document.getElementById('timer').textContent = '00:00';
        document.getElementById('timer-progress').style.width = '0%';
        
        // Show expiration message
        const timerBox = document.getElementById('inline-timer-box');
        if (timerBox) {
            timerBox.classList.add('opacity-60');
            const t = document.getElementById('timer');
            if (t) t.textContent = '00:00';
        }
        
        // Disable the payment form
        const form = document.getElementById('payment-form');
        const submitButton = document.getElementById('confirm-button');
        submitButton.disabled = true;
        submitButton.textContent = 'Session Expired';
        submitButton.classList.add('opacity-50', 'cursor-not-allowed');
    }

    // Copy to clipboard function
    window.copyToClipboard = function(text) {
        if (isTimerExpired) {
            alert('Payment session has expired. Please refresh the page.');
            return;
        }

        navigator.clipboard.writeText(text).then(function() {
            const button = event.target.closest('button');
            const originalText = button.textContent;
            const originalHTML = button.innerHTML;
            
            if (button.querySelector('svg')) {
                button.innerHTML = '<svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
                setTimeout(() => {
                    button.innerHTML = originalHTML;
                }, 2000);
            } else {
                button.textContent = 'Copied!';
                button.classList.add('text-green-600');
                setTimeout(() => {
                    button.textContent = originalText;
                    button.classList.remove('text-green-600');
                }, 2000);
            }
        }).catch(function(err) {
            console.error('Could not copy text: ', err);
            alert('Failed to copy to clipboard. Please copy manually.');
        });
    };

    // Refresh QR Code function
    window.refreshQRCode = function() {
        if (isTimerExpired) {
            alert('Payment session has expired. Please refresh the page.');
            return;
        }

        const qrImage = document.getElementById('qr-code');
        const qrFallback = document.getElementById('qr-fallback');
        
        qrImage.style.opacity = '0.5';
        
        const cryptoSymbol = '{{ strtolower($purchase->paymentMethod->crypto_symbol) }}';
        const walletAddress = '{{ $purchase->paymentMethod->wallet_address }}';
        const cryptoAmount = '{{ $purchase->crypto_amount }}';
        const paymentUri = cryptoSymbol + ':' + walletAddress + '?amount=' + cryptoAmount;
        const timestamp = new Date().getTime();
        const newQrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&format=png&data=' + encodeURIComponent(paymentUri) + '&t=' + timestamp;
        
        qrImage.onload = function() {
            qrImage.style.opacity = '1';
            qrFallback.style.display = 'none';
            qrImage.style.display = 'block';
        };
        
        qrImage.onerror = function() {
            qrImage.style.display = 'none';
            qrFallback.style.display = 'block';
        };
        
        qrImage.src = newQrUrl;
    };

    // Form submission handling
    const form = document.getElementById('payment-form');
    const confirmButton = document.getElementById('confirm-button');

    form.addEventListener('submit', function(e) {
        if (isTimerExpired) {
            e.preventDefault();
            alert('Payment session has expired. Please refresh the page to start a new session.');
            return;
        }

        const confirmText = confirmButton.querySelector('.confirm-text');
        const loadingText = confirmButton.querySelector('.loading-text');
        
        confirmText.classList.add('hidden');
        loadingText.classList.remove('hidden');
        confirmButton.disabled = true;
    });

    // Prevent leaving page warning
    window.addEventListener('beforeunload', function(e) {
        if (!isTimerExpired && timeLeft > 0) {
            const message = 'Your payment session is still active. Are you sure you want to leave?';
            e.returnValue = message;
            return message;
        }
    });

    // Start the timer
    startTimer();
});
</script>
@endsection 
