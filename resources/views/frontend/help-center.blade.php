@extends('layouts.main')

@section('content')
<div class="bg-tesla-50 dark:bg-dark-bg">
  <!-- Hero Section -->
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
      <div class="absolute inset-0 bg-gradient-to-b from-black/80 via-black/70 to-black/90"></div>
    </div>

    <div class="relative z-10">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-16 lg:py-24">
        <div class="text-center">
          <h1 class="text-4xl font-semibold tracking-tight sm:text-5xl">
            Help Center
          </h1>
          <p class="mt-4 max-w-2xl mx-auto text-base text-tesla-100 dark:text-gray-300 sm:text-lg">
            Find answers to your questions, learn how to use our platform, and get the support you need.
          </p>
          
          <!-- Search Bar -->
          <div class="mt-8 max-w-2xl mx-auto">
            <div class="relative">
              <input type="text" placeholder="Search for help articles..." class="w-full px-6 py-4 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-white/50 focus:border-transparent">
              <button class="absolute right-3 top-1/2 transform -translate-y-1/2">
                <i data-lucide="search" class="w-5 h-5 text-gray-400 dark:text-gray-300"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="absolute bottom-0 left-0 right-0 translate-y-[1px]" aria-hidden="true">
      <svg viewBox="0 0 1440 80" xmlns="http://www.w3.org/2000/svg">
        <path fill="#0b0b0b" fill-opacity="1" d="M0,64L48,69.3C96,75,192,85,288,85.3C384,85,480,75,576,53.3C672,32,768,0,864,0C960,0,1056,32,1152,53.3C1248,75,1344,85,1392,90.7L1440,96L1440,160L1392,160C1344,160,1248,160,1152,160C1050,160,960,160,864,160C768,160,672,160,576,160C480,160,384,160,288,160C192,160,96,160,48,160L0,160Z"></path>
      </svg>
    </div>
  </header>

  <!-- Quick Actions Section -->
  <section class="bg-[#0b0b0b] text-white py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <h2 class="text-2xl font-semibold mb-8 text-center">Quick Actions</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <a href="#getting-started" class="group rounded-xl border border-white/10 p-6 text-white shadow-sm transition hover:bg-white/5">
          <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-white/10 rounded-lg flex items-center justify-center">
              <i data-lucide="play" class="w-6 h-6 text-white"></i>
            </div>
          </div>
          <h3 class="text-lg font-medium mb-2">Getting Started</h3>
          <p class="text-sm text-tesla-100 dark:text-gray-300">Learn the basics of our platform</p>
        </a>
        
        <a href="#account" class="group rounded-xl border border-white/10 p-6 text-white shadow-sm transition hover:bg-white/5">
          <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-white/10 rounded-lg flex items-center justify-center">
              <i data-lucide="user" class="w-6 h-6 text-white"></i>
            </div>
          </div>
          <h3 class="text-lg font-medium mb-2">Account & Profile</h3>
          <p class="text-sm text-tesla-100 dark:text-gray-300">Manage your account settings</p>
        </a>
        
        <a href="#wallet" class="group rounded-xl border border-white/10 p-6 text-white shadow-sm transition hover:bg-white/5">
          <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-white/10 rounded-lg flex items-center justify-center">
              <i data-lucide="wallet" class="w-6 h-6 text-white"></i>
            </div>
          </div>
          <h3 class="text-lg font-medium mb-2">Wallet & Payments</h3>
          <p class="text-sm text-tesla-100 dark:text-gray-300">Funding and withdrawal guides</p>
        </a>
        
        <a href="#investments" class="group rounded-xl border border-white/10 p-6 text-white shadow-sm transition hover:bg-white/5">
          <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-white/10 rounded-lg flex items-center justify-center">
              <i data-lucide="trending-up" class="w-6 h-6 text-white"></i>
            </div>
          </div>
          <h3 class="text-lg font-medium mb-2">Investments</h3>
          <p class="text-sm text-tesla-100 dark:text-gray-300">Investment strategies and plans</p>
        </a>
      </div>
    </div>
  </section>

  <!-- Help Categories Section -->
  <section class="bg-tesla-100 dark:bg-tesla-800 py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
        
        <!-- Getting Started -->
        <div id="getting-started">
          <h2 class="text-2xl font-semibold text-black dark:text-white mb-6">Getting Started</h2>
          <div class="space-y-4">
            <div class="border border-border rounded-lg p-4">
              <h3 class="text-lg font-medium text-black dark:text-white mb-2">How to create an account</h3>
              <p class="text-gray-600 text-sm mb-3">Step-by-step guide to setting up your account and completing verification.</p>
              <a href="#" class="text-sm text-black hover:underline">Read more →</a>
            </div>
            
            <div class="border border-border rounded-lg p-4">
              <h3 class="text-lg font-medium text-black dark:text-white mb-2">KYC verification process</h3>
              <p class="text-gray-600 text-sm mb-3">Learn about our Know Your Customer verification requirements and process.</p>
              <a href="#" class="text-sm text-black hover:underline">Read more →</a>
            </div>
            
            <div class="border border-border rounded-lg p-4">
              <h3 class="text-lg font-medium text-black dark:text-white mb-2">Platform overview</h3>
              <p class="text-gray-600 text-sm mb-3">Understanding the main features and navigation of our platform.</p>
              <a href="#" class="text-sm text-black hover:underline">Read more →</a>
            </div>
          </div>
        </div>
        
        <!-- Account & Profile -->
        <div id="account">
          <h2 class="text-2xl font-semibold text-black dark:text-white mb-6">Account & Profile</h2>
          <div class="space-y-4">
            <div class="border border-border rounded-lg p-4">
              <h3 class="text-lg font-medium text-black dark:text-white mb-2">Updating your profile</h3>
              <p class="text-gray-600 text-sm mb-3">How to change your personal information and account settings.</p>
              <a href="#" class="text-sm text-black hover:underline">Read more →</a>
            </div>
            
            <div class="border border-border rounded-lg p-4">
              <h3 class="text-lg font-medium text-black dark:text-white mb-2">Security settings</h3>
              <p class="text-gray-600 text-sm mb-3">Enable two-factor authentication and manage your account security.</p>
              <a href="#" class="text-sm text-black hover:underline">Read more →</a>
            </div>
            
            <div class="border border-border rounded-lg p-4">
              <h3 class="text-lg font-medium text-black dark:text-white mb-2">Notification preferences</h3>
              <p class="text-gray-600 text-sm mb-3">Customize your email and push notification settings.</p>
              <a href="#" class="text-sm text-black hover:underline">Read more →</a>
            </div>
          </div>
        </div>
        
        <!-- Wallet & Payments -->
        <div id="wallet" class="lg:col-span-2">
          <h2 class="text-2xl font-semibold text-black dark:text-white mb-6">Wallet & Payments</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-4">
              <div class="border border-border rounded-lg p-4">
                <h3 class="text-lg font-medium text-black dark:text-white mb-2">Funding your wallet</h3>
                <p class="text-gray-600 text-sm mb-3">How to deposit cryptocurrencies into your wallet.</p>
                <a href="#" class="text-sm text-black hover:underline">Read more →</a>
              </div>
              
              <div class="border border-border rounded-lg p-4">
                <h3 class="text-lg font-medium text-black dark:text-white mb-2">Withdrawal process</h3>
                <p class="text-gray-600 text-sm mb-3">Step-by-step guide to withdrawing funds from your wallet.</p>
                <a href="#" class="text-sm text-black hover:underline">Read more →</a>
              </div>
            </div>
            
            <div class="space-y-4">
              <div class="border border-border rounded-lg p-4">
                <h3 class="text-lg font-medium text-black dark:text-white mb-2">Supported cryptocurrencies</h3>
                <p class="text-gray-600 text-sm mb-3">List of cryptocurrencies we accept for deposits and withdrawals.</p>
                <a href="#" class="text-sm text-black hover:underline">Read more →</a>
              </div>
              
              <div class="border border-border rounded-lg p-4">
                <h3 class="text-lg font-medium text-black dark:text-white mb-2">Transaction fees</h3>
                <p class="text-gray-600 text-sm mb-3">Understanding our fee structure for deposits and withdrawals.</p>
                <a href="#" class="text-sm text-black hover:underline">Read more →</a>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Investments -->
        <div id="investments" class="lg:col-span-2">
          <h2 class="text-2xl font-semibold text-black dark:text-white mb-6">Investments & Trading</h2>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="space-y-4">
              <div class="border border-border rounded-lg p-4">
                <h3 class="text-lg font-medium text-black dark:text-white mb-2">Investment plans</h3>
                <p class="text-gray-600 text-sm mb-3">Understanding our automated investment strategies.</p>
                <a href="#" class="text-sm text-black hover:underline">Read more →</a>
              </div>
              
              <div class="border border-border rounded-lg p-4">
                <h3 class="text-lg font-medium text-black dark:text-white mb-2">Stock trading</h3>
                <p class="text-gray-600 text-sm mb-3">How to buy and sell stocks on our platform.</p>
                <a href="#" class="text-sm text-black hover:underline">Read more →</a>
              </div>
            </div>
            
            <div class="space-y-4">
              <div class="border border-border rounded-lg p-4">
                <h3 class="text-lg font-medium text-black dark:text-white mb-2">Portfolio management</h3>
                <p class="text-gray-600 text-sm mb-3">Tracking your investments and performance.</p>
                <a href="#" class="text-sm text-black hover:underline">Read more →</a>
              </div>
              
              <div class="border border-border rounded-lg p-4">
                <h3 class="text-lg font-medium text-black dark:text-white mb-2">Risk management</h3>
                <p class="text-gray-600 text-sm mb-3">Understanding investment risks and diversification.</p>
                <a href="#" class="text-sm text-black hover:underline">Read more →</a>
              </div>
            </div>
            
            <div class="space-y-4">
              <div class="border border-border rounded-lg p-4">
                <h3 class="text-lg font-medium text-black dark:text-white mb-2">Market analysis</h3>
                <p class="text-gray-600 text-sm mb-3">Using our tools for market research and analysis.</p>
                <a href="#" class="text-sm text-black hover:underline">Read more →</a>
              </div>
              
              <div class="border border-border rounded-lg p-4">
                <h3 class="text-lg font-medium text-black dark:text-white mb-2">Trading fees</h3>
                <p class="text-gray-600 text-sm mb-3">Understanding our commission structure and fees.</p>
                <a href="#" class="text-sm text-black hover:underline">Read more →</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Contact Support Section -->
  <section class="bg-gray-50 py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 text-center">
      <h2 class="text-3xl font-semibold text-black dark:text-white mb-4">Still Need Help?</h2>
      <p class="text-gray-600 mb-8 max-w-2xl mx-auto">
        Can't find what you're looking for? Our support team is here to help you with any questions or issues.
      </p>
      <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <a href="{{ route('support.index') }}" class="inline-flex items-center rounded-md px-6 py-3 text-sm font-medium text-white shadow-sm transition" style="background-color: #C8102E;" onmouseover="this.style.backgroundColor='#A00D25'" onmouseout="this.style.backgroundColor='#C8102E'">
          <i data-lucide="message-circle" class="mr-2 h-4 w-4"></i>
          Contact Support
        </a>
        <a href="{{ route('contact') }}" class="inline-flex items-center rounded-md border border-gray-300 px-6 py-3 text-sm font-medium text-black transition hover:bg-gray-50">
          <i data-lucide="mail" class="mr-2 h-4 w-4"></i>
          Send Email
        </a>
      </div>
    </div>
  </section>
</div>
@endsection
