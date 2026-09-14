@extends('layouts.main')

@section('title', $car->title)

@section('content')
<div class="min-h-screen bg-white dark:bg-dark-bg">
    <!-- Immersive Hero -->
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
            @if($car->images && count($car->images) > 0)
                <img src="{{ strpos($car->images[0], 'http') === 0 ? $car->images[0] : asset('storage/' . $car->images[0]) }}" alt="" class="h-full w-full object-cover opacity-20" loading="eager" decoding="async" />
            @else
                <div class="h-full w-full bg-gradient-to-br from-gray-800 to-gray-900"></div>
            @endif
            <div class="absolute inset-0 bg-gradient-to-b from-black/80 via-black/70 to-black/90"></div>
        </div>

        <div class="relative z-10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-16 lg:py-24 text-center">
                <h1 class="text-4xl font-semibold tracking-tight sm:text-5xl">{{ $car->title }}</h1>
                <p class="mt-4 max-w-2xl mx-auto text-base text-tesla-100 dark:text-gray-300 sm:text-lg">{{ $car->description ?: 'Experience the future of driving with unparalleled performance and luxury.' }}</p>

                <div class="mt-10 grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="rounded-lg border border-white/10 bg-white/5 p-4">
                        <p class="text-xs text-tesla-100 dark:text-gray-300">Range (EPA est.)</p>
                        <p class="mt-1 text-2xl font-light text-white">{{ $car->engine_range ?? '410' }} <span class="text-lg">mi</span></p>
                    </div>
                    <div class="rounded-lg border border-white/10 bg-white/5 p-4">
                        <p class="text-xs text-tesla-100 dark:text-gray-300">0-60 mph</p>
                        <p class="mt-1 text-2xl font-light text-white">{{ $car->acceleration ?? '3.1' }} <span class="text-lg">s</span></p>
                    </div>
                    <div class="rounded-lg border border-white/10 bg-white/5 p-4">
                        <p class="text-xs text-tesla-100 dark:text-gray-300">Top Speed</p>
                        <p class="mt-1 text-2xl font-light text-white">{{ $car->top_speed ?? '130' }} <span class="text-lg">mph</span></p>
                    </div>
                </div>

                <div class="mt-8 flex flex-col sm:flex-row justify-center gap-3">
                    @auth
                        @if($car->is_available)
                            <a href="#order" class="inline-flex items-center rounded-md px-8 py-3 text-sm font-medium text-white transition" style="background-color: #C8102E;" onmouseover="this.style.backgroundColor='#A00D25'" onmouseout="this.style.backgroundColor='#C8102E'">Order Now</a>
                        @else
                            <span class="inline-flex items-center rounded-md bg-gray-600 px-8 py-3 text-sm font-medium text-white">Sold Out</span>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="inline-flex items-center rounded-md px-8 py-3 text-sm font-medium text-white transition" style="background-color: #C8102E;" onmouseover="this.style.backgroundColor='#A00D25'" onmouseout="this.style.backgroundColor='#C8102E'">Order Now</a>
                    @endauth
                    <a href="#order" class="inline-flex items-center rounded-md border border-white/20 px-8 py-3 text-sm font-medium text-white transition hover:bg-white/10">Learn More</a>
                </div>
            </div>
        </div>

        <div class="absolute bottom-0 left-0 right-0 translate-y-[1px]" aria-hidden="true">
            <svg viewBox="0 0 1440 80" xmlns="http://www.w3.org/2000/svg"><path fill="#ffffff" fill-opacity="1" d="M0,64L48,69.3C96,75,192,85,288,85.3C384,85,480,75,576,53.3C672,32,768,0,864,0C960,0,1056,32,1152,53.3C1248,75,1344,85,1392,90.7L1440,96L1440,160L1392,160C1344,160,1248,160,1152,160C1050,160,960,160,864,160C768,160,672,160,576,160C480,160,384,160,288,160C192,160,96,160,48,160L0,160Z"></path></svg>
        </div>
    </header>

    <!-- Order Section -->
    <section id="order" class="py-16 bg-white dark:bg-dark-bg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">
                <!-- Left: Image Gallery -->
                <div class="order-2 lg:order-1">
                    <div class="lg:sticky lg:top-24">
                        <!-- Main Image -->
                        <div class="aspect-w-16 aspect-h-10 mb-6 rounded-xl overflow-hidden border border-border">
                            @if($car->images && count($car->images) > 0)
                                <img 
                                    id="main-image" 
                                    src="{{ strpos($car->images[0], 'http') === 0 ? $car->images[0] : asset('storage/' . $car->images[0]) }}" 
                                    alt="{{ $car->title }}" 
                                    class="w-full h-full object-cover transition-opacity duration-300"
                                >
                            @else
                                <div class="w-full h-full bg-gray-100 flex items-center justify-center">
                                    <p class="text-gray-500 dark:text-gray-300">Image not available</p>
                                </div>
                            @endif
                        </div>

                        <!-- Thumbnail Gallery -->
                        @if($car->images && count($car->images) > 1)
                            <div class="grid grid-cols-4 gap-3">
                                @foreach($car->images as $index => $image)
                                    <button 
                                        onclick="changeImage('{{ strpos($image, 'http') === 0 ? $image : asset('storage/' . $image) }}')"
                                        class="aspect-w-16 aspect-h-10 overflow-hidden border border-border hover:border-black transition-colors duration-200 rounded-lg thumbnail {{ $index === 0 ? 'border-black' : '' }}"
                                    >
                                        <img 
                                            src="{{ strpos($image, 'http') === 0 ? $image : asset('storage/' . $image) }}" 
                                            alt="{{ $car->title }} - View {{ $index + 1 }}" 
                                            class="w-full h-full object-cover"
                                        >
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Right: Order Form -->
                <div class="order-1 lg:order-2">
                    <div class="max-w-lg mx-auto lg:mx-0 border border-border rounded-2xl p-6 shadow-sm bg-card">
                        <!-- Header -->
                        <div class="text-center lg:text-left mb-8">
                            <h2 class="text-3xl lg:text-4xl font-light text-black dark:text-white mb-2">Order Your {{ $car->title }}</h2>
                            <p class="text-gray-600 dark:text-gray-300 font-light">Estimated delivery: 2-4 weeks</p>
                        </div>

                        <!-- Payment Options -->
                        <div class="mb-8">
                            <div class="flex border-b border-border mb-6">
                                <button class="payment-tab flex-1 py-3 text-sm font-medium text-gray-500 dark:text-gray-300 border-b border-transparent hover:text-black dark:hover:text-white hover:border-gray-300 dark:hover:border-tesla-500 transition-colors duration-200" data-type="cash">Purchase</button>
                                <button class="payment-tab flex-1 py-3 text-sm font-medium text-black dark:text-white border-b border-black dark:border-white" data-type="lease">Lease</button>
                                <button class="payment-tab flex-1 py-3 text-sm font-medium text-gray-500 dark:text-gray-300 border-b border-transparent hover:text-black dark:hover:text-white hover:border-gray-300 dark:hover:border-tesla-500 transition-colors duration-200" data-type="finance">Finance</button>
                            </div>

                            <!-- Pricing Display -->
                            <div class="text-center mb-6">
                                <div class="text-4xl font-light text-black dark:text-white mb-1">
                                    <span id="price-display">{{ currency_symbol() }}{{ number_format($car->price / 36, 0) }}</span>
                                    <span id="price-period" class="text-xl text-gray-500 dark:text-gray-300">/mo</span>
                                </div>
                                <p class="text-sm text-gray-500 font-light" id="price-details">Est. Lease | {{ currency_symbol() }}7,500 down, 36 months, 10,000 miles</p>
                            </div>

                            <!-- Vehicle Options -->
                            <div class="space-y-3 mb-6">
                                <div class="vehicle-option border border-black bg-gray-50 p-4 rounded-lg cursor-pointer selected" data-price="{{ $car->price }}" data-name="All-Wheel Drive">
                                    <div class="flex justify-between items-center">
                                        <span class="font-medium text-black">All-Wheel Drive</span>
                                        <span class="text-gray-700">{{ currency_symbol() }}{{ number_format($car->price / 36, 0) }}/mo</span>
                                    </div>
                                </div>
                                
                                @if($car->model && str_contains(strtolower($car->model), 's'))
                                    <div class="vehicle-option border border-border hover:border-gray-400 dark:hover:border-tesla-500 p-4 rounded-lg cursor-pointer transition-colors duration-200" data-price="{{ $car->price + 20000 }}" data-name="Plaid">
                                        <div class="flex justify-between items-center">
                                            <span class="font-medium text-black dark:text-white">Plaid</span>
                                            <span class="text-foreground">{{ currency_symbol() }}{{ number_format(($car->price + 20000) / 36, 0) }}/mo</span>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Features Button -->
                            <button class="w-full flex items-center justify-between p-4 bg-muted hover:bg-gray-100 dark:hover:bg-gray-700 border border-border rounded-lg transition-colors duration-200 mb-6">
                                <div class="flex items-center space-x-4">
                                    @if($car->images && count($car->images) > 0)
                                        <img src="{{ strpos($car->images[0], 'http') === 0 ? $car->images[0] : asset('storage/' . $car->images[0]) }}" alt="Features" class="w-12 h-8 object-cover">
                                    @else
                                        <div class="w-12 h-8 bg-gray-200 dark:bg-tesla-800"></div>
                                    @endif
                                    <span class="font-medium text-black dark:text-white">View & Compare Features</span>
                                </div>
                                <svg class="w-5 h-5 text-gray-500 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>

                            <!-- Gas Savings -->
                            <div class="flex items-center space-x-3 mb-6">
                                <input type="checkbox" id="gas-savings" class="w-4 h-4 text-black dark:text-tesla-600 border-border focus:ring-black">
                                <label for="gas-savings" class="text-sm text-gray-600 dark:text-gray-300">Include est. gas savings of {{ currency_symbol() }}108/mo</label>
                            </div>                            <!-- Action Links -->
                            <div class="flex flex-wrap justify-center lg:justify-start gap-6 text-sm text-gray-500 dark:text-gray-300 mb-8">
                                <a href="#" class="hover:text-black dark:hover:text-white underline transition-colors duration-200">Get Prequalified</a>
                                <a href="#" class="hover:text-black dark:hover:text-white underline transition-colors duration-200">Edit Terms & Savings</a>
                                <a href="#" class="hover:text-black dark:hover:text-white underline transition-colors duration-200">Learn About Financing</a>
                            </div>
                        </div>

                        <!-- Order Button -->
                        <div class="space-y-6">
                            @auth
                                @if($car->is_available)
                                    <a href="{{ route('checkout.form', $car->id) }}" class="block w-full text-center py-4 px-6 font-medium transition-colors duration-200 text-white" style="background-color: #C8102E;" onmouseover="this.style.backgroundColor='#A00D25'" onmouseout="this.style.backgroundColor='#C8102E'">
                                        Order Now
                                    </a>
                                @else
                                    @php
                                        $userPurchase = $car->purchases()->where('user_id', auth()->id())->first();
                                    @endphp
                                    
                                    @if($userPurchase)
                                        <div class="text-center space-y-6">
                                            @if($userPurchase->status === 'completed')
                                                <div class="bg-green-50 border border-green-200 p-8">
                                                    <div class="flex items-center justify-center space-x-2 mb-3">
                                                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                        </svg>
                                                        <span class="text-green-800 font-medium text-lg">Vehicle Owned</span>
                                                    </div>
                                                    <p class="text-green-700 text-sm font-light">This vehicle is in your garage.</p>
                                                </div>
                                                <a href="{{ route('dashboard.history') }}" class="inline-flex items-center px-6 py-3 text-white transition-colors duration-200" style="background-color: #C8102E;" onmouseover="this.style.backgroundColor='#A00D25'" onmouseout="this.style.backgroundColor='#C8102E'">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                                    </svg>
                                                    View Purchase History
                                                </a>
                                            @elseif($userPurchase->status === 'pending')
                                                <div class="bg-orange-50 border border-orange-200 p-8">
                                                    <div class="flex items-center justify-center space-x-2 mb-3">
                                                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                        </svg>
                                                        <span class="text-orange-800 font-medium text-lg">Payment Pending</span>
                                                    </div>
                                                    <p class="text-orange-700 text-sm mb-4 font-light">Complete your payment to finalize purchase.</p>
                                                    <a href="{{ route('dashboard.history') }}" class="bg-orange-600 text-white px-6 py-2 hover:bg-orange-700 transition-colors text-sm">
                                                       Complete Payment
                                                    </a>
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <div class="bg-gray-100 border border-border p-8 text-center">
                                            <span class="text-gray-800 font-medium text-lg">No Longer Available</span>
                                            <p class="text-gray-600 text-sm mt-2 font-light">This vehicle has been sold.</p>
                                        </div>
                                    @endif
                                @endif
                            @else
                                <div class="text-center space-y-6">
                                    <p class="text-gray-600 mb-6 font-light">Sign in to order your {{ $car->title }}</p>
                                    <div class="space-y-4">
                                        <a href="{{ route('login') }}" class="block w-full text-white text-center py-3 px-6 font-medium transition-colors duration-200" style="background-color: #C8102E;" onmouseover="this.style.backgroundColor='#A00D25'" onmouseout="this.style.backgroundColor='#C8102E'">
                                            Sign In
                                        </a>
                                        <a href="{{ route('register') }}" class="block w-full border border-gray-300 text-gray-700 hover:bg-gray-50 text-center py-3 px-6 font-medium transition-colors duration-200">
                                            Create Account
                                        </a>
                                    </div>
                                </div>
                            @endauth
                            
                            <p class="text-xs text-gray-500 text-center font-light">
                                Secure checkout • Free delivery • 30-day return policy
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl lg:text-4xl font-light text-black dark:text-white mb-3">Key Features</h2>
                <p class="text-gray-600 max-w-2xl mx-auto font-light">Experience cutting-edge technology and unparalleled performance in every aspect of your {{ $car->title }}.</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-card border border-border p-6 rounded-xl hover:border-gray-400 transition-colors duration-200">
                    <div class="w-12 h-12 bg-green-100 flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-medium text-black dark:text-white mb-3">100% Electric</h3>
                    <p class="text-gray-600 font-light">Zero emissions with instant torque and incredible power delivery.</p>
                </div>
                
                <div class="bg-card border border-border p-6 rounded-xl hover:border-gray-400 transition-colors duration-200">
                    <div class="w-12 h-12 bg-tesla-100 flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-tesla-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-medium text-black dark:text-white mb-3">Autopilot</h3>
                    <p class="text-gray-600 font-light">Advanced driver assistance features for enhanced safety and convenience.</p>
                </div>
                
                <div class="bg-card border border-border p-6 rounded-xl hover:border-gray-400 transition-colors duration-200">
                    <div class="w-12 h-12 bg-purple-100 flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-medium text-black dark:text-white mb-3">Advanced Security</h3>
                    <p class="text-gray-600 font-light">Comprehensive security system with Sentry Mode protection.</p>
                </div>
                
                <div class="bg-card border border-border p-6 rounded-xl hover:border-gray-400 transition-colors duration-200">
                    <div class="w-12 h-12 bg-red-100 flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-medium text-black dark:text-white mb-3">Premium Interior</h3>
                    <p class="text-gray-600 font-light">Luxurious materials and cutting-edge technology throughout.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Specifications Section -->
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl lg:text-4xl font-light text-black dark:text-white mb-3">Specifications</h2>
                <p class="text-gray-600 max-w-2xl mx-auto font-light">Detailed technical specifications for your {{ $car->title }}.</p>
            </div>
            
            <div class="max-w-4xl mx-auto">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                    <div>
                        <h3 class="text-xl font-medium text-black dark:text-white mb-8">Performance</h3>
                        <div class="space-y-6">
                            <div class="flex justify-between py-4 border-b border-border">
                                <span class="text-gray-600 dark:text-gray-300">Acceleration</span>
                                <span class="font-medium text-black">{{ $car->acceleration ?? '3.1' }}s 0-60 mph</span>
                            </div>
                            <div class="flex justify-between py-4 border-b border-border">
                                <span class="text-gray-600 dark:text-gray-300">Top Speed</span>
                                <span class="font-medium text-black">{{ $car->top_speed ?? '130' }} mph</span>
                            </div>
                            <div class="flex justify-between py-4 border-b border-border">
                                <span class="text-gray-600 dark:text-gray-300">Range</span>
                                <span class="font-medium text-black">{{ $car->engine_range ?? '410' }} miles (EPA est.)</span>
                            </div>
                            <div class="flex justify-between py-4 border-b border-border">
                                <span class="text-gray-600 dark:text-gray-300">Drive</span>
                                <span class="font-medium text-black">All-Wheel Drive</span>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-xl font-medium text-black dark:text-white mb-8">Vehicle Details</h3>
                        <div class="space-y-6">
                            <div class="flex justify-between py-4 border-b border-border">
                                <span class="text-gray-600 dark:text-gray-300">Make</span>
                                <span class="font-medium text-black">{{ $car->make }}</span>
                            </div>
                            <div class="flex justify-between py-4 border-b border-border">
                                <span class="text-gray-600 dark:text-gray-300">Model</span>
                                <span class="font-medium text-black">{{ $car->model }}</span>
                            </div>
                            <div class="flex justify-between py-4 border-b border-border">
                                <span class="text-gray-600 dark:text-gray-300">Year</span>
                                <span class="font-medium text-black">{{ $car->year }}</span>
                            </div>
                            <div class="flex justify-between py-4 border-b border-border">
                                <span class="text-gray-600 dark:text-gray-300">Color</span>
                                <span class="font-medium text-black">{{ $car->color }}</span>
                            </div>
                            <div class="flex justify-between py-4 border-b border-border">
                                <span class="text-gray-600 dark:text-gray-300">Transmission</span>
                                <span class="font-medium text-black">{{ $car->transmission }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentPrice = {{ $car->price }};
    let currentPaymentType = 'lease';
    const gasSavings = 108;

    // Payment tab functionality
    const paymentTabs = document.querySelectorAll('.payment-tab');
    const vehicleOptions = document.querySelectorAll('.vehicle-option');
    const gasSavingsCheckbox = document.getElementById('gas-savings');

    function updatePriceDisplay() {
        const priceDisplay = document.getElementById('price-display');
        const pricePeriod = document.getElementById('price-period');
        const priceDetails = document.getElementById('price-details');
        
        let displayPrice = currentPrice;
        let period = '';
        let details = '';

        if (currentPaymentType === 'cash') {
            period = '';
            details = 'Est. Purchase Price';
        } else {
            displayPrice = Math.round(currentPrice / 36);
            if (gasSavingsCheckbox.checked) {
                displayPrice -= gasSavings;
            }
            period = '/mo';
            details = currentPaymentType === 'lease' 
                ? 'Est. Lease | {{ currency_symbol() }}7,500 down, 36 months, 10,000 miles'
                : 'Est. Finance | {{ currency_symbol() }}7,500 down, 36 months';
        }

        priceDisplay.textContent = '{{ currency_symbol() }}' + displayPrice.toLocaleString();
        pricePeriod.textContent = period;
        priceDetails.textContent = details;
    }

    // Payment tab event listeners
    paymentTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            paymentTabs.forEach(t => {
                t.classList.remove('text-black', 'border-black');
                t.classList.add('text-gray-500', 'border-transparent');
            });
            
            this.classList.add('text-black', 'border-black');
            this.classList.remove('text-gray-500', 'border-transparent');
            
            currentPaymentType = this.dataset.type;
            updatePriceDisplay();
        });
    });

    // Vehicle option event listeners
    vehicleOptions.forEach(option => {
        option.addEventListener('click', function() {
            vehicleOptions.forEach(opt => {
                opt.classList.remove('border-black', 'bg-gray-50', 'selected');
                opt.classList.add('border-border');
            });
            
            this.classList.add('border-black', 'bg-gray-50', 'selected');
            this.classList.remove('border-border');
            
            currentPrice = parseInt(this.dataset.price);
            updatePriceDisplay();
        });
    });

    // Gas savings checkbox
    if (gasSavingsCheckbox) {
        gasSavingsCheckbox.addEventListener('change', updatePriceDisplay);
    }

    // Image gallery functionality
    window.changeImage = function(src) {
        const mainImage = document.getElementById('main-image');
        const thumbnails = document.querySelectorAll('.thumbnail');
        
        if (mainImage) {
            mainImage.style.opacity = '0.5';
            setTimeout(() => {
                mainImage.src = src;
                mainImage.style.opacity = '1';
            }, 150);
        }
        
        thumbnails.forEach(thumb => {
            thumb.classList.remove('border-black');
            thumb.classList.add('border-border');
        });
        
        event.target.closest('.thumbnail').classList.add('border-black');
        event.target.closest('.thumbnail').classList.remove('border-border');
    };

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Initialize price display
    updatePriceDisplay();
});
</script>
@endsection
