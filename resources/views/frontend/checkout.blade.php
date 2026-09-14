@extends('layouts.main')

@section('title', 'Checkout - ' . $car->title)

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
            @if($car->first_image)
                <img src="{{ strpos($car->first_image, 'http') === 0 ? $car->first_image : asset('storage/' . $car->first_image) }}" alt="" class="h-full w-full object-cover opacity-20" loading="eager" decoding="async" />
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
                        <a href="{{ route('cars.show', $car->id) }}" class="hover:text-white transition-colors">{{ $car->title }}</a>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        <span class="text-white font-medium">Checkout</span>
                    </nav>

                    <!-- Progress Steps -->
                    <ol class="hidden md:flex items-center space-x-4" aria-label="Progress">
                        <li class="flex items-center">
                            <span class="w-8 h-8 bg-gray-100 text-black rounded-full flex items-center justify-center text-sm font-medium">1</span>
                            <span class="ml-2 text-sm font-medium text-black">Order Details</span>
                        </li>
                        <li class="w-8 h-px bg-gray-100" aria-hidden="true"></li>
                        <li class="flex items-center">
                            <span class="w-8 h-8 bg-gray-100 text-black rounded-full flex items-center justify-center text-sm font-medium">2</span>
                            <span class="ml-2 text-sm text-black">Payment</span>
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
        <div class="absolute bottom-0 left-0 right-0 translate-y-[1px]" aria-hidden="true">
            <svg viewBox="0 0 1440 80" xmlns="http://www.w3.org/2000/svg"><path fill="#ffffff" fill-opacity="1" d="M0,64L48,69.3C96,75,192,85,288,85.3C384,85,480,75,576,53.3C672,32,768,0,864,0C960,0,1056,32,1152,53.3C1248,75,1344,85,1392,90.7L1440,96L1440,160L1392,160C1344,160,1248,160,1152,160C1050,160,960,160,864,160C768,160,672,160,576,160C480,160,384,160,288,160C192,160,96,160,48,160L0,160Z"></path></svg>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Header -->
                <div class="text-center lg:text-left">
                    <h1 class="text-4xl font-light text-black dark:text-white mb-3">Complete Your Order</h1>
                    <p class="text-gray-600 font-light">You're one step away from owning your {{ $car->title }}</p>
                </div>

                <form action="{{ route('checkout.process', $car->id) }}" method="POST" class="space-y-8" id="checkout-form">
                    @csrf

                    <!-- Contact Information -->
                    <div class="bg-card border border-border p-6 rounded-2xl shadow-sm">
                        <div class="flex items-center mb-8">
                            <div class="w-8 h-8 text-white rounded-full flex items-center justify-center text-sm font-medium mr-4" style="background-color: #C8102E;">1</div>
                            <h2 class="text-xl font-medium text-black">Contact Information</h2>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="billing_name" class="block text-sm font-medium text-black dark:text-white mb-3">Full Name</label>
                                <input type="text" 
                                       id="billing_name" 
                                       name="billing_name" 
                                       value="{{ old('billing_name', auth()->user()->name) }}"
                                       class="w-full px-4 py-3 border border-gray-300 focus:outline-none focus:border-black transition-colors duration-200 @error('billing_name') border-red-500 @enderror"
                                       required>
                                @error('billing_name')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="billing_email" class="block text-sm font-medium text-black dark:text-white mb-3">Email Address</label>
                                <input type="email" 
                                       id="billing_email" 
                                       name="billing_email" 
                                       value="{{ old('billing_email', auth()->user()->email) }}"
                                       class="w-full px-4 py-3 border border-gray-300 focus:outline-none focus:border-black transition-colors duration-200 @error('billing_email') border-red-500 @enderror"
                                       required>
                                @error('billing_email')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="billing_phone" class="block text-sm font-medium text-black dark:text-white mb-3">Phone Number</label>
                                <input type="tel" 
                                       id="billing_phone" 
                                       name="billing_phone" 
                                       value="{{ old('billing_phone') }}"
                                       class="w-full px-4 py-3 border border-gray-300 focus:outline-none focus:border-black transition-colors duration-200 @error('billing_phone') border-red-500 @enderror"
                                       required>
                                @error('billing_phone')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="company_name" class="block text-sm font-medium text-black dark:text-white mb-3">Company Name <span class="text-gray-500 dark:text-gray-300">(Optional)</span></label>
                                <input type="text" 
                                       id="company_name" 
                                       name="company_name" 
                                       value="{{ old('company_name') }}"
                                       class="w-full px-4 py-3 border border-gray-300 focus:outline-none focus:border-black transition-colors duration-200 @error('company_name') border-red-500 @enderror">
                                @error('company_name')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Billing Address -->
                    <div class="bg-card border border-border p-6 rounded-2xl shadow-sm">
                        <div class="flex items-center mb-8">
                            <div class="w-8 h-8 text-white rounded-full flex items-center justify-center text-sm font-medium mr-4" style="background-color: #C8102E;">2</div>
                            <h2 class="text-xl font-medium text-black">Billing Address</h2>
                        </div>

                        <div class="space-y-6">
                            <div>
                                <label for="billing_address" class="block text-sm font-medium text-black dark:text-white mb-3">Street Address</label>
                                <textarea id="billing_address" 
                                          name="billing_address" 
                                          rows="2"
                                          class="w-full px-4 py-3 border border-gray-300 focus:outline-none focus:border-black transition-colors duration-200 @error('billing_address') border-red-500 @enderror"
                                          required>{{ old('billing_address') }}</textarea>
                                @error('billing_address')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="billing_city" class="block text-sm font-medium text-black dark:text-white mb-3">City</label>
                                    <input type="text" 
                                           id="billing_city" 
                                           name="billing_city" 
                                           value="{{ old('billing_city') }}"
                                           class="w-full px-4 py-3 border border-gray-300 focus:outline-none focus:border-black transition-colors duration-200 @error('billing_city') border-red-500 @enderror"
                                           required>
                                    @error('billing_city')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="billing_state" class="block text-sm font-medium text-black dark:text-white mb-3">State/Province</label>
                                    <input type="text" 
                                           id="billing_state" 
                                           name="billing_state" 
                                           value="{{ old('billing_state') }}"
                                           class="w-full px-4 py-3 border border-gray-300 focus:outline-none focus:border-black transition-colors duration-200 @error('billing_state') border-red-500 @enderror"
                                           required>
                                    @error('billing_state')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="billing_postal_code" class="block text-sm font-medium text-black dark:text-white mb-3">Postal Code</label>
                                    <input type="text" 
                                           id="billing_postal_code" 
                                           name="billing_postal_code" 
                                           value="{{ old('billing_postal_code') }}"
                                           class="w-full px-4 py-3 border border-gray-300 focus:outline-none focus:border-black transition-colors duration-200 @error('billing_postal_code') border-red-500 @enderror"
                                           required>
                                    @error('billing_postal_code')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="billing_country" class="block text-sm font-medium text-black dark:text-white mb-3">Country</label>
                                    <select id="billing_country" 
                                            name="billing_country" 
                                            class="w-full px-4 py-3 border border-gray-300 focus:outline-none focus:border-black transition-colors duration-200 @error('billing_country') border-red-500 @enderror"
                                            required>
                                        <option value="">Select Country</option>
                                        <option value="US" {{ old('billing_country') == 'US' ? 'selected' : '' }}>United States</option>
                                        <option value="CA" {{ old('billing_country') == 'CA' ? 'selected' : '' }}>Canada</option>
                                        <option value="GB" {{ old('billing_country') == 'GB' ? 'selected' : '' }}>United Kingdom</option>
                                        <option value="DE" {{ old('billing_country') == 'DE' ? 'selected' : '' }}>Germany</option>
                                        <option value="FR" {{ old('billing_country') == 'FR' ? 'selected' : '' }}>France</option>
                                        <option value="AU" {{ old('billing_country') == 'AU' ? 'selected' : '' }}>Australia</option>
                                    </select>
                                    @error('billing_country')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div>
                                <label for="tax_id" class="block text-sm font-medium text-black dark:text-white mb-3">Tax ID/VAT Number <span class="text-gray-500 dark:text-gray-300">(Optional)</span></label>
                                <input type="text" 
                                       id="tax_id" 
                                       name="tax_id" 
                                       value="{{ old('tax_id') }}"
                                       class="w-full px-4 py-3 border border-gray-300 focus:outline-none focus:border-black transition-colors duration-200 @error('tax_id') border-red-500 @enderror">
                                @error('tax_id')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="bg-card border border-border p-6 rounded-2xl shadow-sm">
                        <div class="flex items-center mb-8">
                            <div class="w-8 h-8 text-white rounded-full flex items-center justify-center text-sm font-medium mr-4" style="background-color: #C8102E;">3</div>
                            <h2 class="text-xl font-medium text-black">Payment Method</h2>
                        </div>

                        <div class="space-y-4">
                            @foreach($paymentMethods as $method)
                                <label class="relative block cursor-pointer">
                                    <input type="radio" 
                                           name="payment_method_id" 
                                           value="{{ $method->id }}" 
                                           class="sr-only payment-method-radio"
                                           {{ old('payment_method_id') == $method->id ? 'checked' : '' }}
                                           required>
                                    
                                    <div class="payment-method-card border border-border p-6 rounded-xl transition-all duration-200 hover:border-gray-400">
                                        <div class="flex items-center space-x-4">
                                            <!-- Radio Button -->
                                            <div class="payment-method-indicator w-5 h-5 border-2 border-gray-300 rounded-full flex items-center justify-center">
                                                <div class="w-2.5 h-2.5 rounded-full opacity-0 transition-opacity duration-200" style="background-color: #C8102E;"></div>
                                            </div>

                                            <!-- Payment Method Logo -->
                                            @if($method->logo_url)
                                                <div class="w-12 h-12 flex-shrink-0">
                                                    <img src="{{ $method->logo_url }}" 
                                                         alt="{{ $method->name }}" 
                                                         class="w-full h-full object-contain">
                                                </div>
                                            @else
                                                <div class="w-12 h-12 bg-gray-100 flex items-center justify-center flex-shrink-0">
                                                    @if($method->isCryptocurrency())
                                                        <svg class="w-6 h-6 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                                        </svg>
                                                    @else
                                                        <svg class="w-6 h-6 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                                        </svg>
                                                    @endif
                                                </div>
                                            @endif
                                            
                                            <!-- Payment Method Details -->
                                            <div class="flex-1">
                                                <div class="flex items-center justify-between">
                                                    <h3 class="font-medium text-black">{{ $method->name }}</h3>
                                                    @if($method->isCryptocurrency())
                                                        <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium bg-orange-100 text-orange-800">
                                                            {{ strtoupper($method->crypto_symbol) }}
                                                        </span>
                                                    @endif
                                                </div>
                                                @if($method->details)
                                                    <p class="text-sm text-gray-600 mt-1">{{ $method->details }}</p>
                                                @endif
                                                @if($method->isCryptocurrency() && $method->formatted_network_fee)
                                                    <p class="text-xs text-gray-500 mt-1">Network fee: {{ $method->formatted_network_fee }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                            @error('payment_method_id')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Terms and Conditions -->
                    <div class="bg-card border border-border p-6 rounded-2xl shadow-sm">
                        <div class="flex items-start space-x-3">
                            <input type="checkbox" 
                                   id="terms_accepted" 
                                   name="terms_accepted" 
                                   class="mt-1 w-4 h-4 text-black border-gray-300 focus:ring-black"
                                   required>
                            <div class="text-sm text-gray-600 dark:text-gray-300">
                                <label for="terms_accepted" class="cursor-pointer">
                                    I agree to the <a href="#" class="text-black hover:underline">Terms of Service</a> and <a href="#" class="text-black hover:underline">Privacy Policy</a>. I understand that this purchase is final and subject to the terms outlined in the purchase agreement.
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Mobile Submit Button -->
                    <div class="lg:hidden">
                        <button type="submit" 
                                class="w-full bg-black hover:bg-gray-800 text-white py-4 px-6 text-lg font-medium transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                                id="mobile-submit-btn">
                            <span class="submit-text">Complete Purchase - {{ $car->formatted_price }}</span>
                            <span class="loading-text hidden">Processing...</span>
                        </button>
                        <p class="text-sm text-gray-500 mt-4 text-center flex items-center justify-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            Your payment information is secure and encrypted
                        </p>
                    </div>
                </form>
            </div>

            <!-- Order Summary Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-card border border-border p-6 rounded-2xl shadow-sm sticky top-24">
                    <h2 class="text-xl font-medium text-black dark:text-white mb-8">Order Summary</h2>
                    
                    <!-- Car Details -->
                    <div class="flex space-x-4 mb-8">
                        <div class="w-20 h-20 bg-gray-100 overflow-hidden flex-shrink-0">
                            @if($car->first_image)
                                <img src="{{ strpos($car->first_image, 'http') === 0 ? $car->first_image : asset('storage/' . $car->first_image) }}" 
                                     alt="{{ $car->title }}" 
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
                            <h3 class="font-medium text-black dark:text-white mb-1">{{ $car->title }}</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-300">{{ $car->year }} {{ $car->make }} {{ $car->model }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300">{{ $car->color }}</p>
                        </div>
                    </div>

                    <!-- Key Features -->
                    <div class="mb-8 p-6 bg-gray-50 border border-border">
                        <h4 class="text-sm font-medium text-black dark:text-white mb-4">Key Features</h4>
                        <div class="grid grid-cols-2 gap-3 text-xs text-gray-600 dark:text-gray-300">
                            <div class="flex items-center">
                                <svg class="w-3 h-3 text-green-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                {{ $car->engine_range ?? '410' }}mi Range
                            </div>
                            <div class="flex items-center">
                                <svg class="w-3 h-3 text-green-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                {{ $car->acceleration ?? '3.1' }}s 0-60mph
                            </div>
                            <div class="flex items-center">
                                <svg class="w-3 h-3 text-green-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Autopilot
                            </div>
                            <div class="flex items-center">
                                <svg class="w-3 h-3 text-green-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                All-Wheel Drive
                            </div>
                        </div>
                    </div>

                    <!-- Price Breakdown -->
                    <div class="space-y-3 border-t border-border pt-8 mb-8">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-300">Vehicle Price</span>
                            <span class="font-medium text-black">{{ $car->formatted_price }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-300">Destination & Doc Fee</span>
                            <span class="font-medium text-green-600">Included</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-300">Order Fee</span>
                            <span class="font-medium text-green-600">{{ currency_symbol() }}0</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-300">Taxes & Fees</span>
                            <span class="text-sm text-gray-500 dark:text-gray-300">Due at delivery</span>
                        </div>
                        <div class="border-t border-border pt-4">
                            <div class="flex justify-between">
                                <span class="text-lg font-medium text-black">Order Total</span>
                                <span class="text-lg font-medium text-black">{{ $car->formatted_price }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Security Features -->
                    <div class="space-y-3 mb-8">
                        <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-300">
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>256-bit SSL Encryption</span>
                        </div>
                        <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-300">
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>7-Day Return Policy</span>
                        </div>
                        <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-300">
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>4-Year Limited Warranty</span>
                        </div>
                    </div>

                    <!-- Desktop Submit Button -->
                    <div class="hidden lg:block">
                        <button type="submit" 
                                form="checkout-form"
                                class="w-full bg-black hover:bg-gray-800 text-white py-4 px-6 text-lg font-medium transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                                id="desktop-submit-btn">
                            <span class="submit-text">Complete Purchase</span>
                            <span class="loading-text hidden">Processing...</span>
                        </button>
                        <p class="text-xs text-gray-500 mt-4 text-center flex items-center justify-center">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            Secure checkout powered by Tesla
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Payment method selection
    const paymentRadios = document.querySelectorAll('.payment-method-radio');
    const paymentCards = document.querySelectorAll('.payment-method-card');
    const paymentIndicators = document.querySelectorAll('.payment-method-indicator');

    function updatePaymentSelection() {
        paymentRadios.forEach((radio, index) => {
            const card = paymentCards[index];
            const indicator = paymentIndicators[index];
            const dot = indicator.querySelector('div');

            if (radio.checked) {
                card.classList.add('border-black', 'bg-gray-50');
                card.classList.remove('border-border');
                indicator.classList.add('border-black');
                indicator.classList.remove('border-gray-300');
                dot.classList.add('opacity-100');
                dot.classList.remove('opacity-0');
            } else {
                card.classList.remove('border-black', 'bg-gray-50');
                card.classList.add('border-border');
                indicator.classList.remove('border-black');
                indicator.classList.add('border-gray-300');
                dot.classList.remove('opacity-100');
                dot.classList.add('opacity-0');
            }
        });
    }

    paymentRadios.forEach(radio => {
        radio.addEventListener('change', updatePaymentSelection);
    });

    // Initialize payment selection
    updatePaymentSelection();

    // Form submission handling
    const form = document.getElementById('checkout-form');
    const submitButtons = document.querySelectorAll('#mobile-submit-btn, #desktop-submit-btn');

    form.addEventListener('submit', function(e) {
        submitButtons.forEach(button => {
            const submitText = button.querySelector('.submit-text');
            const loadingText = button.querySelector('.loading-text');
            
            submitText.classList.add('hidden');
            loadingText.classList.remove('hidden');
            button.disabled = true;
        });
    });

    // Form validation feedback
    const inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.hasAttribute('required') && !this.value.trim()) {
                this.classList.add('border-red-500');
                this.classList.remove('border-gray-300');
            } else {
                this.classList.remove('border-red-500');
                this.classList.add('border-gray-300');
            }
        });

        input.addEventListener('input', function() {
            if (this.classList.contains('border-red-500') && this.value.trim()) {
                this.classList.remove('border-red-500');
                this.classList.add('border-gray-300');
            }
        });
    });

    // Auto-format phone number
    const phoneInput = document.getElementById('billing_phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 6) {
                value = value.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');
            } else if (value.length >= 3) {
                value = value.replace(/(\d{3})(\d{0,3})/, '($1) $2');
            }
            e.target.value = value;
        });
    }

    // Auto-format postal code
    const postalInput = document.getElementById('billing_postal_code');
    if (postalInput) {
        postalInput.addEventListener('input', function(e) {
            let value = e.target.value.toUpperCase();
            // US ZIP code format
            if (/^\d{5}$/.test(value)) {
                e.target.value = value;
            }
            // Canadian postal code format
            else if (/^[A-Z]\d[A-Z]\d[A-Z]\d$/.test(value.replace(/\s/g, ''))) {
                value = value.replace(/\s/g, '');
                e.target.value = value.replace(/([A-Z]\d[A-Z])(\d[A-Z]\d)/, '$1 $2');
            }
        });
    }
});
</script>
@endsection
