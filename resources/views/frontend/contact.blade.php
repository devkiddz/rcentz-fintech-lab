@extends('layouts.main')

@section('content')
<div class="bg-white dark:bg-dark-bg">
  <!-- Hero Section -->
  <header class="relative overflow-hidden bg-gradient-to-br from-black via-gray-900 to-black dark:from-tesla-700 dark:via-tesla-800 dark:to-tesla-900 text-white">
    <svg class="absolute inset-0 opacity-[0.06] z-0 pointer-events-none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none" aria-hidden="true" focusable="false">
      <defs>
        <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
          <path d="M 40 0 L 0 0 0 40" fill="none" stroke="white" stroke-width="1" />
        </pattern>
      </defs>
      <rect width="100%" height="100%" fill="url(#grid)" />
    </svg>

    <div class="absolute inset-0 z-0 pointer-events-none" aria-hidden="true">
      <div class="absolute inset-0 bg-gradient-to-b from-black/80 via-black/70 to-black/90"></div>
    </div>

    <div class="relative z-10">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-16 lg:py-24">
        <div class="text-center">
          <h1 class="text-4xl font-semibold tracking-tight sm:text-5xl">
            Get in Touch
          </h1>
          <p class="mt-4 max-w-2xl mx-auto text-base text-tesla-100 dark:text-gray-300 sm:text-lg">
            Have questions about our platform? We're here to help. Reach out to our team for support, partnerships, or general inquiries.
          </p>
        </div>
      </div>
    </div>

    <div class="absolute bottom-0 left-0 right-0 translate-y-[1px]" aria-hidden="true">
      <svg viewBox="0 0 1440 80" xmlns="http://www.w3.org/2000/svg">
        <path fill="#0b0b0b" fill-opacity="1" d="M0,64L48,69.3C96,75,192,85,288,85.3C384,85,480,75,576,53.3C672,32,768,0,864,0C960,0,1056,32,1152,53.3C1248,75,1344,85,1392,90.7L1440,96L1440,160L1392,160C1344,160,1248,160,1152,160C1050,160,960,160,864,160C768,160,672,160,576,160C480,160,384,160,288,160C192,160,96,160,48,160L0,160Z"></path>
      </svg>
    </div>
  </header>

  <!-- Contact Form Section -->
  <section class="bg-[#0b0b0b] dark:bg-dark-bg text-white py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
        <!-- Contact Information -->
        <div>
          <h2 class="text-3xl font-semibold mb-6">Contact Information</h2>
          <p class="text-tesla-100 dark:text-gray-300 mb-8">
            Our team is available to assist you with any questions about our platform, services, or technical support.
          </p>
          
          <div class="space-y-6">
            <div class="flex items-start">
              <div class="flex-shrink-0">
                <div class="w-10 h-10 bg-white/10 dark:bg-dark-card rounded-full flex items-center justify-center">
                  <i data-lucide="mail" class="w-5 h-5 text-white"></i>
                </div>
              </div>
              <div class="ml-4">
                <h3 class="text-lg font-medium text-white">Email</h3>
                <p class="text-tesla-100 dark:text-gray-300">{{ site_email() }}</p>
                <p class="text-sm text-gray-400 dark:text-tesla-300 mt-1">We typically respond within 24 hours</p>
              </div>
            </div>
            
            <div class="flex items-start">
              <div class="flex-shrink-0">
                <div class="w-10 h-10 bg-white/10 dark:bg-dark-card rounded-full flex items-center justify-center">
                  <i data-lucide="phone" class="w-5 h-5 text-white"></i>
                </div>
              </div>
              <div class="ml-4">
                <h3 class="text-lg font-medium text-white">Phone</h3>
                <p class="text-tesla-100 dark:text-gray-300">{{ site_phone() }}</p>
                <p class="text-sm text-gray-400 dark:text-tesla-300 mt-1">Monday - Friday, 9AM - 6PM EST</p>
              </div>
            </div>
            
            <div class="flex items-start">
              <div class="flex-shrink-0">
                <div class="w-10 h-10 bg-white/10 dark:bg-dark-card rounded-full flex items-center justify-center">
                  <i data-lucide="map-pin" class="w-5 h-5 text-white"></i>
                </div>
              </div>
              <div class="ml-4">
                <h3 class="text-lg font-medium text-white">Address</h3>
                <p class="text-tesla-100 dark:text-gray-300">123 Innovation Drive<br>Tech City, TC 12345</p>
                <p class="text-sm text-gray-400 dark:text-tesla-300 mt-1">By appointment only</p>
              </div>
            </div>
          </div>
          
          <div class="mt-8">
            <h3 class="text-lg font-medium text-white mb-4">Follow Us</h3>
            <div class="flex space-x-4">
              <a href="#" class="w-10 h-10 bg-white/10 dark:bg-dark-card rounded-full flex items-center justify-center hover:bg-white/20 dark:hover:bg-gray-800 transition-colors">
                <i data-lucide="twitter" class="w-5 h-5 text-white"></i>
              </a>
              <a href="#" class="w-10 h-10 bg-white/10 dark:bg-dark-card rounded-full flex items-center justify-center hover:bg-white/20 dark:hover:bg-gray-800 transition-colors">
                <i data-lucide="linkedin" class="w-5 h-5 text-white"></i>
              </a>
              <a href="#" class="w-10 h-10 bg-white/10 dark:bg-dark-card rounded-full flex items-center justify-center hover:bg-white/20 dark:hover:bg-gray-800 transition-colors">
                <i data-lucide="facebook" class="w-5 h-5 text-white"></i>
              </a>
            </div>
          </div>
        </div>
        
        <!-- Contact Form -->
        <div class="bg-white/5 dark:bg-dark-card rounded-2xl p-8 border border-white/10 dark:border-gray-700">
          <h3 class="text-2xl font-semibold text-white mb-6">Send us a Message</h3>
          
          <form action="{{ route('contact') }}" method="POST" class="space-y-6">
            @csrf
            
            @if(session('success'))
              <div class="bg-green-500/10 border border-green-500/20 text-green-300 px-4 py-3 rounded-lg">
                {{ session('success') }}
              </div>
            @endif
            
            @if($errors->any())
              <div class="bg-red-500/10 border border-red-500/20 text-red-300 px-4 py-3 rounded-lg">
                <ul class="list-disc list-inside space-y-1">
                  @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                  @endforeach
                </ul>
              </div>
            @endif
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
              <div>
                <label for="first_name" class="block text-sm font-medium text-tesla-100 dark:text-gray-300 mb-2">First Name</label>
                <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" required class="w-full px-4 py-3 bg-white/10 dark:bg-dark-muted border border-white/20 dark:border-gray-700 rounded-lg text-white placeholder-gray-400 dark:placeholder-tesla-200 focus:outline-none focus:ring-2 focus:ring-white/50 focus:border-transparent">
              </div>
              <div>
                <label for="last_name" class="block text-sm font-medium text-tesla-100 dark:text-gray-300 mb-2">Last Name</label>
                <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" required class="w-full px-4 py-3 bg-white/10 dark:bg-dark-muted border border-white/20 dark:border-gray-700 rounded-lg text-white placeholder-gray-400 dark:placeholder-tesla-200 focus:outline-none focus:ring-2 focus:ring-white/50 focus:border-transparent">
              </div>
            </div>
            
            <div>
              <label for="email" class="block text-sm font-medium text-tesla-100 dark:text-gray-300 mb-2">Email</label>
              <input type="email" id="email" name="email" value="{{ old('email') }}" required class="w-full px-4 py-3 bg-white/10 dark:bg-dark-muted border border-white/20 dark:border-gray-700 rounded-lg text-white placeholder-gray-400 dark:placeholder-tesla-200 focus:outline-none focus:ring-2 focus:ring-white/50 focus:border-transparent">
            </div>
            
            <div>
              <label for="subject" class="block text-sm font-medium text-tesla-100 dark:text-gray-300 mb-2">Subject</label>
              <select id="subject" name="subject" required class="w-full px-4 py-3 bg-white/10 dark:bg-dark-muted border border-white/20 dark:border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-white/50 focus:border-transparent">
                <option value="" class="text-gray-900">Select a topic</option>
                <option value="general" class="text-gray-900" {{ old('subject') == 'general' ? 'selected' : '' }}>General Inquiry</option>
                <option value="support" class="text-gray-900" {{ old('subject') == 'support' ? 'selected' : '' }}>Technical Support</option>
                <option value="billing" class="text-gray-900" {{ old('subject') == 'billing' ? 'selected' : '' }}>Billing & Payments</option>
                <option value="partnership" class="text-gray-900" {{ old('subject') == 'partnership' ? 'selected' : '' }}>Partnership</option>
                <option value="press" class="text-gray-900" {{ old('subject') == 'press' ? 'selected' : '' }}>Press & Media</option>
              </select>
            </div>
            
            <div>
              <label for="message" class="block text-sm font-medium text-tesla-100 dark:text-gray-300 mb-2">Message</label>
              <textarea id="message" name="message" rows="6" required class="w-full px-4 py-3 bg-white/10 dark:bg-dark-muted border border-white/20 dark:border-gray-700 rounded-lg text-white placeholder-gray-400 dark:placeholder-tesla-200 focus:outline-none focus:ring-2 focus:ring-white/50 focus:border-transparent">{{ old('message') }}</textarea>
            </div>
            
            <div>
              <button type="submit" class="w-full bg-white text-black dark:bg-white dark:text-gray-900 px-6 py-3 rounded-lg font-medium hover:bg-gray-100 dark:hover:bg-gray-100 transition-colors duration-200">
                Send Message
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </section>

  <!-- FAQ Section -->
  <section class="bg-white dark:bg-dark-bg py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-12">
        <h2 class="text-3xl font-semibold text-black dark:text-white mb-4">Frequently Asked Questions</h2>
        <p class="text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">
          Find quick answers to common questions about our platform and services.
        </p>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div class="space-y-6">
          <div>
            <h3 class="text-lg font-semibold text-black dark:text-white mb-2">How do I get started with investing?</h3>
            <p class="text-gray-600 dark:text-gray-300">Create an account, complete KYC verification, fund your wallet, and start investing in our automated plans or individual stocks.</p>
          </div>
          
          <div>
            <h3 class="text-lg font-semibold text-black dark:text-white mb-2">What payment methods do you accept?</h3>
            <p class="text-gray-600 dark:text-gray-300">We accept major cryptocurrencies including Bitcoin, Ethereum, and USDC for wallet funding and purchases.</p>
          </div>
          
          <div>
            <h3 class="text-lg font-semibold text-black dark:text-white mb-2">Is my money safe?</h3>
            <p class="text-gray-600 dark:text-gray-300">Yes, we use enterprise-grade security measures and regulatory compliance to protect your assets and personal information.</p>
          </div>
        </div>
        
        <div class="space-y-6">
          <div>
            <h3 class="text-lg font-semibold text-black dark:text-white mb-2">How do I purchase a Tesla vehicle?</h3>
            <p class="text-gray-600 dark:text-gray-300">Browse our inventory, select your preferred vehicle, and complete the purchase process through our secure checkout system.</p>
          </div>
          
          <div>
            <h3 class="text-lg font-semibold text-black dark:text-white mb-2">What are the trading fees?</h3>
            <p class="text-gray-600 dark:text-gray-300">We offer competitive trading fees starting at 0.1% per trade, with volume discounts available for active traders.</p>
          </div>
          
          <div>
            <h3 class="text-lg font-semibold text-black dark:text-white mb-2">How can I contact support?</h3>
            <p class="text-gray-600 dark:text-gray-300">You can reach our support team via email, phone, or through the support portal in your account dashboard.</p>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection
