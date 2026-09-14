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
            About {{ site_name() }}
          </h1>
          <p class="mt-4 max-w-2xl mx-auto text-base text-tesla-100 dark:text-gray-300 sm:text-lg">
            Revolutionizing the way people invest, trade, and acquire premium electric vehicles through our innovative all-in-one platform.
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

  <!-- Mission Section -->
  <section class="bg-[#0b0b0b] text-white py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
        <div>
          <h2 class="text-3xl font-semibold mb-6">Our Mission</h2>
          <p class="text-tesla-100 dark:text-gray-300 text-lg mb-6">
            We're building the future of financial services by combining cutting-edge technology with traditional investment principles. Our platform democratizes access to premium investment opportunities and luxury electric vehicles.
          </p>
          <p class="text-tesla-100 dark:text-gray-300 mb-6">
            From automated investment strategies to real-time stock trading and curated Tesla inventory, we provide everything you need to grow your wealth and drive the future.
          </p>
          <div class="grid grid-cols-2 gap-6 mt-8">
            <div class="text-center">
              <div class="text-2xl font-bold text-white mb-2">10K+</div>
              <div class="text-sm text-gray-400 dark:text-gray-300">Active Users</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-white mb-2">{{ currency_symbol() }}50M+</div>
              <div class="text-sm text-gray-400 dark:text-gray-300">Assets Under Management</div>
            </div>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <article class="rounded-2xl border border-white/10 from-white/10 to-white/0 bg-gradient-to-br p-4 shadow backdrop-blur-sm">
            <h3 class="text-xs text-tesla-100 dark:text-gray-300">Investments</h3>
            <p class="mt-2 text-2xl font-semibold text-white">Automated</p>
            <p class="mt-1 text-sm text-tesla-100 dark:text-gray-300">Smart portfolio management.</p>
          </article>
          <article class="rounded-2xl border border-white/10 from-white/10 to-white/0 bg-gradient-to-br p-4 shadow backdrop-blur-sm">
            <h3 class="text-xs text-tesla-100 dark:text-gray-300">Trading</h3>
            <p class="mt-2 text-2xl font-semibold text-white">Realtime</p>
            <p class="mt-1 text-sm text-tesla-100 dark:text-gray-300">Live market data & execution.</p>
          </article>
          <article class="rounded-2xl border border-white/10 from-white/10 to-white/0 bg-gradient-to-br p-4 shadow backdrop-blur-sm">
            <h3 class="text-xs text-tesla-100 dark:text-gray-300">Wallet</h3>
            <p class="mt-2 text-2xl font-semibold text-white">Crypto</p>
            <p class="mt-1 text-sm text-tesla-100 dark:text-gray-300">Secure digital asset storage.</p>
          </article>
          <article class="rounded-2xl border border-white/10 from-white/10 to-white/0 bg-gradient-to-br p-4 shadow backdrop-blur-sm">
            <h3 class="text-xs text-tesla-100 dark:text-gray-300">Marketplace</h3>
            <p class="mt-2 text-2xl font-semibold text-white">Premium</p>
            <p class="mt-1 text-sm text-tesla-100 dark:text-gray-300">Curated EV selection.</p>
          </article>
        </div>
      </div>
    </div>
  </section>

  <!-- Features Section -->
  <section class="bg-tesla-100 dark:bg-dark-card py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-12">
        <h2 class="text-3xl font-semibold text-black dark:text-white mb-4">Our Values</h2>
        <p class="text-gray-600 max-w-2xl mx-auto">
          We're guided by principles that drive innovation, transparency, and user success in everything we do.
        </p>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="text-center">
          <div class="inline-flex items-center justify-center w-16 h-16 text-white rounded-full mb-4" style="background-color: #C8102E;">
            <i data-lucide="shield-check" class="w-8 h-8"></i>
          </div>
          <h3 class="text-xl font-semibold text-black dark:text-white mb-3">Security First</h3>
          <p class="text-gray-600 dark:text-gray-300">
            Your assets and data are protected with enterprise-grade security measures and regulatory compliance.
          </p>
        </div>
        
        <div class="text-center">
          <div class="inline-flex items-center justify-center w-16 h-16 text-white rounded-full mb-4" style="background-color: #C8102E;">
            <i data-lucide="zap" class="w-8 h-8"></i>
          </div>
          <h3 class="text-xl font-semibold text-black dark:text-white mb-3">Innovation</h3>
          <p class="text-gray-600 dark:text-gray-300">
            We continuously push boundaries to deliver cutting-edge financial technology and user experiences.
          </p>
        </div>
        
        <div class="text-center">
          <div class="inline-flex items-center justify-center w-16 h-16 text-white rounded-full mb-4" style="background-color: #C8102E;">
            <i data-lucide="users" class="w-8 h-8"></i>
          </div>
          <h3 class="text-xl font-semibold text-black dark:text-white mb-3">User-Centric</h3>
          <p class="text-gray-600 dark:text-gray-300">
            Every feature and decision is made with our users' success and satisfaction in mind.
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- Team Section -->
  <section class="bg-gray-50 py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-12">
        <h2 class="text-3xl font-semibold text-black dark:text-white mb-4">Leadership Team</h2>
        <p class="text-gray-600 max-w-2xl mx-auto">
          Meet the visionaries behind our platform, bringing together decades of experience in finance, technology, and electric vehicles.
        </p>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="text-center">
          <div class="w-32 h-32 bg-gray-200 rounded-full mx-auto mb-4 flex items-center justify-center">
            <i data-lucide="user" class="w-16 h-16 text-gray-400 dark:text-gray-300"></i>
          </div>
          <h3 class="text-xl font-semibold text-black dark:text-white mb-2">CEO & Founder</h3>
          <p class="text-gray-600 mb-3">Visionary leader with 15+ years in fintech</p>
          <p class="text-sm text-gray-500 dark:text-gray-300">Former executive at leading investment platforms</p>
        </div>
        
        <div class="text-center">
          <div class="w-32 h-32 bg-gray-200 rounded-full mx-auto mb-4 flex items-center justify-center">
            <i data-lucide="user" class="w-16 h-16 text-gray-400 dark:text-gray-300"></i>
          </div>
          <h3 class="text-xl font-semibold text-black dark:text-white mb-2">CTO</h3>
          <p class="text-gray-600 mb-3">Technology expert specializing in blockchain</p>
          <p class="text-sm text-gray-500 dark:text-gray-300">Built scalable systems for millions of users</p>
        </div>
        
        <div class="text-center">
          <div class="w-32 h-32 bg-gray-200 rounded-full mx-auto mb-4 flex items-center justify-center">
            <i data-lucide="user" class="w-16 h-16 text-gray-400 dark:text-gray-300"></i>
          </div>
          <h3 class="text-xl font-semibold text-black dark:text-white mb-2">Head of Investments</h3>
          <p class="text-gray-600 mb-3">Portfolio management and strategy expert</p>
          <p class="text-sm text-gray-500 dark:text-gray-300">20+ years in institutional investment</p>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA Section -->
  <section class="text-white py-16" style="background-color: #C8102E;">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 text-center">
      <h2 class="text-3xl font-semibold mb-4">Ready to Get Started?</h2>
      <p class="text-tesla-100 dark:text-gray-300 mb-8 max-w-2xl mx-auto">
        Join thousands of users who are already building their wealth and driving the future with our platform.
      </p>
      <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <a href="{{ route('investments.index') }}" class="inline-flex items-center rounded-md bg-white px-6 py-3 text-sm font-medium text-black shadow-sm transition hover:bg-gray-100">
          <i data-lucide="trending-up" class="mr-2 h-4 w-4"></i>
          Start Investing
        </a>
        <a href="{{ route('cars.browse') }}" class="inline-flex items-center rounded-md border border-white/20 px-6 py-3 text-sm font-medium text-white transition hover:bg-white/10">
          <i data-lucide="car" class="mr-2 h-4 w-4"></i>
          Browse Inventory
        </a>
      </div>
    </div>
  </section>
</div>
@endsection
