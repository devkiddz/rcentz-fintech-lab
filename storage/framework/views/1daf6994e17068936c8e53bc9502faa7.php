<?php $__env->startSection('content'); ?>
<div style="background: linear-gradient(to bottom right, #8B0A1E, #C8102E, #8B0A1E);">
  <header class="relative overflow-hidden text-white" style="background: linear-gradient(to bottom right, #8B0A1E, #C8102E, #8B0A1E);">
    <svg class="absolute inset-0 opacity-[0.06] z-0 pointer-events-none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none" aria-hidden="true" focusable="false">
      <defs>
        <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
          <path d="M 40 0 L 0 0 0 40" fill="none" stroke="white" stroke-width="1" />
        </pattern>
      </defs>
      <rect width="100%" height="100%" fill="url(#grid)" />
    </svg>

    <div class="absolute inset-0 z-0 pointer-events-none" aria-hidden="true">
      <img
        src="<?php echo e(asset('images/tesla-hero.jpg')); ?>"
        alt=""
        class="h-full w-full object-cover opacity-30 mix-blend-lighten"
        loading="eager"
        decoding="async"
      />
      <div class="absolute inset-0" style="background: linear-gradient(to bottom, rgba(139, 10, 30, 0.9), rgba(200, 16, 46, 0.8), rgba(139, 10, 30, 0.95));"></div>
    </div>

    <div class="relative z-10">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-16 lg:py-24">
        <div class="grid grid-cols-1 items-center gap-10 lg:grid-cols-2 lg:gap-16">
          <div>
            <h1 class="text-4xl font-semibold tracking-tight sm:text-5xl">
              Invest. Trade. Drive.
            </h1>
            <p class="mt-4 max-w-xl text-base text-red-100 dark:text-red-200 sm:text-lg">
              All-in-one platform for crypto wallet funding, automated investments, live stocks, and premium EV inventory.
            </p>

            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
              <a
                href="<?php echo e(route('investments.index')); ?>"
                class="inline-flex items-center rounded-md px-5 py-3 text-sm font-medium text-white shadow-sm transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/60"
                style="background-color: #C8102E;" onmouseover="this.style.backgroundColor='#A00D25'" onmouseout="this.style.backgroundColor='#C8102E'"
              >
                <i data-lucide="trending-up" class="mr-2 h-4 w-4" aria-hidden="true"></i>
                Start Investing
              </a>
              <a
                href="<?php echo e(route('stocks.index')); ?>"
                class="inline-flex items-center rounded-md border border-white/20 px-5 py-3 text-sm font-medium text-white transition hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/60 focus-visible:ring-offset-2 focus-visible:ring-offset-black"
              >
                <i data-lucide="bar-chart-3" class="mr-2 h-4 w-4" aria-hidden="true"></i>
                Explore Stocks
              </a>
              <a
                href="<?php echo e(route('cars.browse')); ?>"
                class="inline-flex items-center rounded-md border border-white/20 px-5 py-3 text-sm font-medium text-white transition hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/60 focus-visible:ring-offset-2 focus-visible:ring-offset-black"
              >
                <i data-lucide="car" class="mr-2 h-4 w-4" aria-hidden="true"></i>
                View Inventory
              </a>
            </div>

            <div class="mt-8 grid grid-cols-1 gap-3 sm:grid-cols-3">
              <div class="rounded-lg border border-white/20 bg-white/5 p-4 shadow-lg backdrop-blur-sm">
                <p class="text-xs text-red-300">Live Stocks</p>
                <p class="mt-1 text-lg font-semibold text-white">Realtime</p>
              </div>
              <div class="rounded-lg border border-white/20 bg-white/5 p-4 shadow-lg backdrop-blur-sm">
                <p class="text-xs text-red-300">Wallet</p>
                <p class="mt-1 text-lg font-semibold text-white">Crypto</p>
              </div>
              <div class="rounded-lg border border-white/20 bg-white/5 p-4 shadow-lg backdrop-blur-sm">
                <p class="text-xs text-red-300">EV Inventory</p>
                <p class="mt-1 text-lg font-semibold text-white">Premium</p>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <article class="rounded-2xl border border-white/20 bg-white/5 p-4 shadow-lg backdrop-blur-sm">
              <h3 class="text-xs text-red-300">Investments</h3>
              <p class="mt-2 text-2xl font-semibold text-white">Automated</p>
              <p class="mt-1 text-sm text-red-200">Flexible plans, recurring contributions.</p>
            </article>
            <article class="rounded-2xl border border-white/20 bg-white/5 p-4 shadow-lg backdrop-blur-sm">
              <h3 class="text-xs text-red-300">Stocks</h3>
              <p class="mt-2 text-2xl font-semibold text-white">Realtime</p>
              <p class="mt-1 text-sm text-red-200">Quotes, news, and watchlists.</p>
            </article>
            <article class="rounded-2xl border border-white/20 bg-white/5 p-4 shadow-lg backdrop-blur-sm">
              <h3 class="text-xs text-red-300">Wallet</h3>
              <p class="mt-2 text-2xl font-semibold text-white">Crypto</p>
              <p class="mt-1 text-sm text-red-200">Deposit and withdraw easily.</p>
            </article>
            <article class="rounded-2xl border border-white/20 bg-white/5 p-4 shadow-lg backdrop-blur-sm">
              <h3 class="text-xs text-red-300">Marketplace</h3>
              <p class="mt-2 text-2xl font-semibold text-white">Tesla</p>
              <p class="mt-1 text-sm text-red-200">Curated EV selection.</p>
            </article>
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

  <section class="bg-[#0b0b0b] text-white" aria-labelledby="quick-actions-heading">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">
      <h2 id="quick-actions-heading" class="sr-only">Quick actions</h2>
      <nav class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4" aria-label="Quick actions">
        <a href="<?php echo e(route('wallet.index')); ?>" class="group rounded-xl border border-white/10 p-5 text-white shadow-sm transition hover:bg-white/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/60 focus-visible:ring-offset-2 focus-visible:ring-offset-[#0b0b0b]">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs text-red-300">Wallet</p>
              <p class="mt-1 text-sm font-medium">Fund or withdraw</p>
            </div>
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-white/10 text-white transition" style="" onmouseover="this.style.backgroundColor='#C8102E'" onmouseout="this.style.backgroundColor='rgba(255,255,255,0.1)'">
              <i data-lucide="wallet" class="h-4 w-4" aria-hidden="true"></i>
            </span>
          </div>
        </a>

        <a href="<?php echo e(route('investments.index')); ?>" class="group rounded-xl border border-white/10 p-5 text-white shadow-sm transition hover:bg-white/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/60 focus-visible:ring-offset-2 focus-visible:ring-offset-[#0b0b0b]">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs text-red-300">Investments</p>
              <p class="mt-1 text-sm font-medium">Create a plan</p>
            </div>
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-white/10 text-white transition" onmouseover="this.style.backgroundColor='#C8102E'" onmouseout="this.style.backgroundColor='rgba(255,255,255,0.1)'">
              <i data-lucide="trending-up" class="h-4 w-4" aria-hidden="true"></i>
            </span>
          </div>
        </a>

        <a href="<?php echo e(route('stocks.index')); ?>" class="group rounded-xl border border-white/10 p-5 text-white shadow-sm transition hover:bg-white/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/60 focus-visible:ring-offset-2 focus-visible:ring-offset-[#0b0b0b]">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs text-red-300">Stocks</p>
              <p class="mt-1 text-sm font-medium">Market overview</p>
            </div>
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-white/10 text-white transition" onmouseover="this.style.backgroundColor='#C8102E'" onmouseout="this.style.backgroundColor='rgba(255,255,255,0.1)'">
              <i data-lucide="bar-chart-3" class="h-4 w-4" aria-hidden="true"></i>
            </span>
          </div>
        </a>

        <a href="<?php echo e(route('portfolio.index')); ?>" class="group rounded-xl border border-white/10 p-5 text-white shadow-sm transition hover:bg-white/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/60 focus-visible:ring-offset-2 focus-visible:ring-offset-[#0b0b0b]">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs text-red-300">Portfolio</p>
              <p class="mt-1 text-sm font-medium">Track performance</p>
            </div>
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-white/10 text-white transition" onmouseover="this.style.backgroundColor='#C8102E'" onmouseout="this.style.backgroundColor='rgba(255,255,255,0.1)'">
              <i data-lucide="pie-chart" class="h-4 w-4" aria-hidden="true"></i>
            </span>
          </div>
        </a>
      </nav>
    </div>
  </section>

  <?php if(!empty($featuredCars) && $featuredCars->count() > 0): ?>
    <section id="featured-cars" class="bg-red-50 dark:bg-dark-bg py-16" aria-labelledby="inventory-heading">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-8 flex items-center justify-between">
          <div>
            <h2 id="inventory-heading" class="text-xl font-semibold text-black sm:text-2xl">Available Inventory</h2>
            <p class="mt-1 text-sm text-red-600 dark:text-red-300">Explore a curated selection ready for delivery.</p>
          </div>
          <a href="<?php echo e(route('cars.browse')); ?>" class="text-sm font-medium text-black underline-offset-4 hover:underline">View all</a>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 xl:grid-cols-3 lg:gap-8">
          <?php $__currentLoopData = $featuredCars->take(6); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $car): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <article class="group cursor-pointer rounded-xl border border-red-200 dark:border-red-700 bg-card shadow-sm transition hover:shadow-md focus-within:ring-2 focus-within:ring-black/40">
              <a href="<?php echo e(route('cars.show', $car->id)); ?>" class="block overflow-hidden rounded-t-xl">
                <div class="relative aspect-[16/9]">
                  <?php if($car->first_image): ?>
                    <img
                      src="<?php echo e(str_starts_with($car->first_image, 'http') ? $car->first_image : asset('storage/' . $car->first_image)); ?>"
                      alt="<?php echo e($car->title); ?>"
                      class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                      loading="lazy"
                      decoding="async"
                      sizes="(min-width: 1280px) 384px, (min-width: 1024px) 50vw, 100vw"
                    />
                  <?php else: ?>
                    <div class="flex h-full w-full items-center justify-center bg-red-100">
                      <div class="text-center">
                        <svg class="mx-auto mb-2 h-12 w-12 text-red-400 dark:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        <p class="text-sm text-red-500 dark:text-red-400"><?php echo e($car->title); ?></p>
                      </div>
                    </div>
                  <?php endif; ?>

                  <?php if(empty($car->is_available) || !$car->is_available): ?>
                    <div class="absolute right-3 top-3 rounded-full bg-red-600 px-2.5 py-1 text-[11px] font-medium text-white">Sold Out</div>
                  <?php endif; ?>
                </div>
              </a>

              <div class="p-4">
                <h3 class="mb-1 text-base font-medium text-black group-hover:text-black/80 sm:text-lg">
                  <a href="<?php echo e(route('cars.show', $car->id)); ?>" class="focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-black/40">
                    <?php echo e($car->title); ?>

                  </a>
                </h3>

                <div class="mb-3 flex items-center gap-6 text-xs text-red-600 dark:text-red-400">
                  <div>
                    <span class="font-medium"><?php echo e($car->engine_range ?? '410'); ?>mi</span>
                    <span class="block text-[10px]">Range</span>
                  </div>
                  <div>
                    <span class="font-medium"><?php echo e($car->acceleration ?? '3.1'); ?>s</span>
                    <span class="block text-[10px]">0-60 mph</span>
                  </div>
                  <div>
                    <span class="font-medium"><?php echo e($car->top_speed ?? '130'); ?>mph</span>
                    <span class="block text-[10px]">Top Speed</span>
                  </div>
                </div>

                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm font-medium text-black">Starting at <?php echo e($car->formatted_price); ?>*</p>
                    <p class="text-xs text-red-500 dark:text-red-400">After Est. Gas Savings</p>
                  </div>
                  <div class="flex items-center gap-2">
                    <a href="<?php echo e(route('cars.show', $car->id)); ?>" class="rounded border px-3 py-1.5 text-xs font-medium text-white transition focus-visible:outline-none focus-visible:ring-2" style="border-color: #C8102E; color: #C8102E;" onmouseover="this.style.backgroundColor='rgba(200,16,46,0.1)'" onmouseout="this.style.backgroundColor='transparent'">Learn</a>
                    <?php if(!empty($car->is_available) && $car->is_available): ?>
                      <a href="<?php echo e(route('cars.show', $car->id)); ?>" class="rounded px-3 py-1.5 text-xs font-medium text-white transition focus-visible:outline-none focus-visible:ring-2" style="background-color: #C8102E;" onmouseover="this.style.backgroundColor='#A00D25'" onmouseout="this.style.backgroundColor='#C8102E'">Order</a>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </article>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <!-- Tesla-style Product Slider Section -->
  <section class="bg-red-50 dark:bg-dark-bg py-16" aria-labelledby="products-heading">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <h2 id="products-heading" class="sr-only">Featured Products</h2>
      
      <!-- Slider Container -->
      <div class="relative group">
        <!-- Slider Wrapper -->
        <div class="overflow-hidden rounded-lg">
          <div id="product-slider" class="flex transition-transform duration-700 ease-in-out">
            
            <!-- Slide 1: Solar Panels / Investment Plans -->
            <div class="min-w-full flex-shrink-0">
              <div class="relative h-[400px] sm:h-[450px] lg:h-[500px] overflow-hidden">
                <!-- Background Image -->
                <img src="https://images.unsplash.com/photo-1508514177221-188b1cf16e9d?w=1600&q=80" 
                     alt="Solar Panels" 
                     class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                <div class="absolute inset-0" style="background: linear-gradient(to top, rgba(139, 10, 30, 0.9), rgba(200, 16, 46, 0.4), transparent);"></div>
                
                <!-- Content Overlay -->
                <div class="absolute bottom-0 left-0 right-0 p-8 sm:p-10 lg:p-12 text-white">
                  <h3 class="text-3xl sm:text-4xl font-semibold mb-3">Solar Panels</h3>
                  <p class="text-base sm:text-lg mb-6 max-w-md">Use Solar Energy to Power Your Home and Charge Your Tesla</p>
                  <div class="flex gap-4">
                    <a href="<?php echo e(route('investments.index')); ?>" class="px-8 py-3 text-white text-sm font-medium rounded transition-colors" style="background-color: #C8102E;" onmouseover="this.style.backgroundColor='#A00D25'" onmouseout="this.style.backgroundColor='#C8102E'">
                      Order Now
                    </a>
                    <a href="<?php echo e(route('investments.index')); ?>" class="px-8 py-3 bg-white/10 hover:bg-white/20 backdrop-blur-sm text-white text-sm font-medium rounded border border-white/30 transition-colors">
                      Learn More
                    </a>
                  </div>
                </div>
              </div>
            </div>

            <!-- Slide 2: Powerwall / Stock Trading -->
            <div class="min-w-full flex-shrink-0">
              <div class="relative h-[400px] sm:h-[450px] lg:h-[500px] overflow-hidden">
                <!-- Background Image -->
                <img src="https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=1600&q=80" 
                     alt="Powerwall" 
                     class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                <div class="absolute inset-0" style="background: linear-gradient(to top, rgba(139, 10, 30, 0.9), rgba(200, 16, 46, 0.4), transparent);"></div>
                
                <!-- Content Overlay -->
                <div class="absolute bottom-0 left-0 right-0 p-8 sm:p-10 lg:p-12 text-white">
                  <h3 class="text-3xl sm:text-4xl font-semibold mb-3">Powerwall</h3>
                  <p class="text-base sm:text-lg mb-6 max-w-md">Keep Your Lights On During Outages</p>
                  <div class="flex gap-4">
                    <a href="<?php echo e(route('stocks.index')); ?>" class="px-8 py-3 text-white text-sm font-medium rounded transition-colors" style="background-color: #C8102E;" onmouseover="this.style.backgroundColor='#A00D25'" onmouseout="this.style.backgroundColor='#C8102E'">
                      Order Now
                    </a>
                    <a href="<?php echo e(route('stocks.index')); ?>" class="px-8 py-3 bg-white/10 hover:bg-white/20 backdrop-blur-sm text-white text-sm font-medium rounded border border-white/30 transition-colors">
                      Learn More
                    </a>
                  </div>
                </div>
              </div>
            </div>

            <!-- Slide 3: Model S / Crypto Wallet -->
            <div class="min-w-full flex-shrink-0">
              <div class="relative h-[400px] sm:h-[450px] lg:h-[500px] overflow-hidden">
                <!-- Background Image -->
                <img src="<?php echo e(asset('images/tesla-hero.jpg')); ?>" 
                     alt="Tesla Model S" 
                     class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                <div class="absolute inset-0" style="background: linear-gradient(to top, rgba(139, 10, 30, 0.9), rgba(200, 16, 46, 0.4), transparent);"></div>
                
                <!-- Content Overlay -->
                <div class="absolute bottom-0 left-0 right-0 p-8 sm:p-10 lg:p-12 text-white">
                  <h3 class="text-3xl sm:text-4xl font-semibold mb-3">Model S</h3>
                  <p class="text-base sm:text-lg mb-6 max-w-md">Plaid - Beyond Ludicrous Performance</p>
                  <div class="flex gap-4">
                    <a href="<?php echo e(route('wallet.index')); ?>" class="px-8 py-3 text-white text-sm font-medium rounded transition-colors" style="background-color: #C8102E;" onmouseover="this.style.backgroundColor='#A00D25'" onmouseout="this.style.backgroundColor='#C8102E'">
                      Order Now
                    </a>
                    <a href="<?php echo e(route('wallet.index')); ?>" class="px-8 py-3 bg-white/10 hover:bg-white/20 backdrop-blur-sm text-white text-sm font-medium rounded border border-white/30 transition-colors">
                      Learn More
                    </a>
                  </div>
                </div>
              </div>
            </div>

            <!-- Slide 4: Model 3 / Investment Plans -->
            <div class="min-w-full flex-shrink-0">
              <div class="relative h-[400px] sm:h-[450px] lg:h-[500px] overflow-hidden">
                <!-- Background Image -->
                <img src="https://images.unsplash.com/photo-1560958089-b8a1929cea89?w=1600&q=80" 
                     alt="Tesla Model 3" 
                     class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                <div class="absolute inset-0" style="background: linear-gradient(to top, rgba(139, 10, 30, 0.9), rgba(200, 16, 46, 0.4), transparent);"></div>
                
                <!-- Content Overlay -->
                <div class="absolute bottom-0 left-0 right-0 p-8 sm:p-10 lg:p-12 text-white">
                  <h3 class="text-3xl sm:text-4xl font-semibold mb-3">Model 3</h3>
                  <p class="text-base sm:text-lg mb-6 max-w-md">Quickest Acceleration - From 3.1 Seconds 0-60 mph</p>
                  <div class="flex gap-4">
                    <a href="<?php echo e(route('cars.browse')); ?>" class="px-8 py-3 text-white text-sm font-medium rounded transition-colors" style="background-color: #C8102E;" onmouseover="this.style.backgroundColor='#A00D25'" onmouseout="this.style.backgroundColor='#C8102E'">
                      Order Now
                    </a>
                    <a href="<?php echo e(route('cars.browse')); ?>" class="px-8 py-3 bg-white/10 hover:bg-white/20 backdrop-blur-sm text-white text-sm font-medium rounded border border-white/30 transition-colors">
                      Learn More
                    </a>
                  </div>
                </div>
              </div>
            </div>

            <!-- Slide 5: Model Y / Portfolio -->
            <div class="min-w-full flex-shrink-0">
              <div class="relative h-[400px] sm:h-[450px] lg:h-[500px] overflow-hidden">
                <!-- Background Image -->
                <img src="https://images.unsplash.com/photo-1617788138017-80ad40651399?w=1600&q=80" 
                     alt="Tesla Model Y" 
                     class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                <div class="absolute inset-0" style="background: linear-gradient(to top, rgba(139, 10, 30, 0.9), rgba(200, 16, 46, 0.4), transparent);"></div>
                
                <!-- Content Overlay -->
                <div class="absolute bottom-0 left-0 right-0 p-8 sm:p-10 lg:p-12 text-white">
                  <h3 class="text-3xl sm:text-4xl font-semibold mb-3">Model Y</h3>
                  <p class="text-base sm:text-lg mb-6 max-w-md">Versatile Electric SUV for Every Adventure</p>
                  <div class="flex gap-4">
                    <a href="<?php echo e(route('portfolio.index')); ?>" class="px-8 py-3 text-white text-sm font-medium rounded transition-colors" style="background-color: #C8102E;" onmouseover="this.style.backgroundColor='#A00D25'" onmouseout="this.style.backgroundColor='#C8102E'">
                      Order Now
                    </a>
                    <a href="<?php echo e(route('portfolio.index')); ?>" class="px-8 py-3 bg-white/10 hover:bg-white/20 backdrop-blur-sm text-white text-sm font-medium rounded border border-white/30 transition-colors">
                      Learn More
                    </a>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>

        <!-- Navigation Arrows with Icons - Inside -->
        <button id="prev-slide" class="absolute left-4 sm:left-6 lg:left-8 top-1/2 -translate-y-1/2 z-20 rounded-full bg-white/80 hover:bg-white p-3 shadow-lg transition-all opacity-0 group-hover:opacity-100" aria-label="Previous slide">
          <svg class="w-6 h-6 text-red-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path>
          </svg>
        </button>
        <button id="next-slide" class="absolute right-4 sm:right-6 lg:right-8 top-1/2 -translate-y-1/2 z-20 rounded-full bg-white/80 hover:bg-white p-3 shadow-lg transition-all opacity-0 group-hover:opacity-100" aria-label="Next slide">
          <svg class="w-6 h-6 text-red-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
          </svg>
        </button>
      </div>

      <!-- Dot Indicators - Below Slider -->
      <div class="mt-6 flex justify-center gap-2.5">
        <button class="slider-dot h-2 w-2 rounded-full bg-red-800 transition-all duration-300" data-slide="0" aria-label="Go to slide 1"></button>
        <button class="slider-dot h-2 w-2 rounded-full bg-red-300 transition-all duration-300" data-slide="1" aria-label="Go to slide 2"></button>
        <button class="slider-dot h-2 w-2 rounded-full bg-red-300 transition-all duration-300" data-slide="2" aria-label="Go to slide 3"></button>
        <button class="slider-dot h-2 w-2 rounded-full bg-red-300 transition-all duration-300" data-slide="3" aria-label="Go to slide 4"></button>
        <button class="slider-dot h-2 w-2 rounded-full bg-red-300 transition-all duration-300" data-slide="4" aria-label="Go to slide 5"></button>
      </div>
    </div>
  </section>

  <?php
    $hasStocks = isset($featuredStocks) && $featuredStocks->count() > 0;
    $hasGainers = isset($gainers) && $gainers->count() > 0;
    $hasLosers = isset($losers) && $losers->count() > 0;
    $hasMostActive = isset($mostActive) && $mostActive->count() > 0;
  ?>

  <?php if($hasStocks || $hasGainers || $hasLosers || $hasMostActive): ?>
    <section class="bg-[#0b0b0b] py-16 text-white" aria-labelledby="markets-heading">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-8 flex items-center justify-between">
          <div>
            <h2 id="markets-heading" class="text-xl font-semibold sm:text-2xl">Stock Markets</h2>
            <p class="mt-1 text-sm text-red-400 dark:text-red-300">Featured picks, top gainers, losers, and most active.</p>
          </div>
          <a href="<?php echo e(route('stocks.index')); ?>" class="text-sm font-medium text-white underline-offset-4 hover:underline">Open markets</a>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
          <div class="lg:col-span-1">
            <div class="rounded-xl border border-white/10 from-white/5 to-white/0 bg-gradient-to-br p-4">
              <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-semibold">Featured</h3>
                <a href="<?php echo e(route('stocks.index')); ?>" class="text-xs text-red-300 hover:text-white">See all</a>
              </div>
              <div class="space-y-3">
                <?php $__empty_1 = true; $__currentLoopData = $featuredStocks ?? collect(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                  <a href="<?php echo e(route('stocks.show', $s->id)); ?>" class="flex items-center justify-between rounded-lg p-3 transition hover:bg-white/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/60">
                    <div class="min-w-0 flex items-center gap-3">
                      <?php if(!empty($s->logo_url)): ?>
                        <img src="<?php echo e($s->logo_url); ?>" alt="<?php echo e($s->symbol); ?> logo" class="h-6 w-6 rounded bg-white" loading="lazy" decoding="async" />
                      <?php else: ?>
                        <div class="flex h-6 w-6 items-center justify-center rounded bg-white/10 text-xs"><?php echo e(strtoupper(substr($s->symbol,0,1))); ?></div>
                      <?php endif; ?>
                      <div class="min-w-0">
                        <p class="truncate text-sm font-medium"><?php echo e($s->symbol); ?> <span class="font-normal text-red-400 dark:text-red-400">• <?php echo e($s->company_name); ?></span></p>
                        <p class="text-xs text-red-400 dark:text-red-400"><?php echo e($s->sector ?? '—'); ?></p>
                      </div>
                    </div>
                    <div class="text-right">
                      <p class="text-sm font-semibold"><?php echo e($s->formatted_current_price); ?></p>
                      <p class="text-xs <?php echo e($s->change_color); ?>"><?php echo e($s->formatted_change_amount); ?> (<?php echo e($s->formatted_change_percentage); ?>)</p>
                    </div>
                  </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                  <p class="text-sm text-red-400">No featured stocks.</p>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <div class="lg:col-span-2 grid grid-cols-1 gap-6 md:grid-cols-3">
            <div class="rounded-xl border border-white/10 from-white/5 to-white/0 bg-gradient-to-br p-4">
              <h3 class="mb-3 text-sm font-semibold">Top Gainers</h3>
              <div class="space-y-3">
                <?php $__empty_1 = true; $__currentLoopData = $gainers ?? collect(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                  <a href="<?php echo e(route('stocks.show', $s->id)); ?>" class="flex items-center justify-between rounded-lg p-3 transition hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/60">
                    <div class="min-w-0 flex items-center gap-3">
                      <?php if(!empty($s->logo_url)): ?>
                        <img src="<?php echo e($s->logo_url); ?>" alt="<?php echo e($s->symbol); ?> logo" class="h-5 w-5 rounded bg-white" loading="lazy" decoding="async" />
                      <?php else: ?>
                        <div class="flex h-5 w-5 items-center justify-center rounded bg-white/10 text-[10px]"><?php echo e(strtoupper(substr($s->symbol,0,1))); ?></div>
                      <?php endif; ?>
                      <span class="truncate text-sm font-medium"><?php echo e($s->symbol); ?></span>
                    </div>
                    <span class="text-xs font-semibold text-green-400"><?php echo e($s->formatted_change_percentage); ?></span>
                  </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                  <p class="text-sm text-red-400">No data</p>
                <?php endif; ?>
              </div>
            </div>

            <div class="rounded-xl border border-white/10 from-white/5 to-white/0 bg-gradient-to-br p-4">
              <h3 class="mb-3 text-sm font-semibold">Top Losers</h3>
              <div class="space-y-3">
                <?php $__empty_1 = true; $__currentLoopData = $losers ?? collect(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                  <a href="<?php echo e(route('stocks.show', $s->id)); ?>" class="flex items-center justify-between rounded-lg p-3 transition hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/60">
                    <div class="min-w-0 flex items-center gap-3">
                      <?php if(!empty($s->logo_url)): ?>
                        <img src="<?php echo e($s->logo_url); ?>" alt="<?php echo e($s->symbol); ?> logo" class="h-5 w-5 rounded bg-white" loading="lazy" decoding="async" />
                      <?php else: ?>
                        <div class="flex h-5 w-5 items-center justify-center rounded bg-white/10 text-[10px]"><?php echo e(strtoupper(substr($s->symbol,0,1))); ?></div>
                      <?php endif; ?>
                      <span class="truncate text-sm font-medium"><?php echo e($s->symbol); ?></span>
                    </div>
                    <span class="text-xs font-semibold text-red-400"><?php echo e($s->formatted_change_percentage); ?></span>
                  </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                  <p class="text-sm text-red-400">No data</p>
                <?php endif; ?>
              </div>
            </div>

            <div class="rounded-xl border border-white/10 from-white/5 to-white/0 bg-gradient-to-br p-4">
              <h3 class="mb-3 text-sm font-semibold">Most Active</h3>
              <div class="space-y-3">
                <?php $__empty_1 = true; $__currentLoopData = $mostActive ?? collect(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                  <a href="<?php echo e(route('stocks.show', $s->id)); ?>" class="flex items-center justify-between rounded-lg p-3 transition hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/60">
                    <div class="min-w-0 flex items-center gap-3">
                      <?php if(!empty($s->logo_url)): ?>
                        <img src="<?php echo e($s->logo_url); ?>" alt="<?php echo e($s->symbol); ?> logo" class="h-5 w-5 rounded bg-white" loading="lazy" decoding="async" />
                      <?php else: ?>
                        <div class="flex h-5 w-5 items-center justify-center rounded bg-white/10 text-[10px]"><?php echo e(strtoupper(substr($s->symbol,0,1))); ?></div>
                      <?php endif; ?>
                      <span class="truncate text-sm font-medium"><?php echo e($s->symbol); ?></span>
                    </div>
                    <span class="text-xs text-red-400">Vol <?php echo e($s->formatted_volume); ?></span>
                  </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                  <p class="text-sm text-red-400">No data</p>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <?php if(isset($latestNews)): ?>
    <section class="mt-12 bg-[#0B1220] py-16" aria-labelledby="news-heading">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-8 flex items-center justify-between">
          <div>
            <h2 id="news-heading" class="text-xl font-semibold text-white sm:text-2xl">Market News</h2>
            <p class="mt-1 text-sm text-red-400 dark:text-red-300">Latest headlines impacting your watchlist.</p>
          </div>
          <a href="<?php echo e(route('stocks.index')); ?>" class="text-sm font-medium text-white underline-offset-4 hover:underline">View stocks</a>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
          <?php $__empty_1 = true; $__currentLoopData = $latestNews; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $news): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <a href="<?php echo e($news->url ?? '#'); ?>" target="_blank" rel="noopener noreferrer" class="group rounded-xl border border-white/10 bg-white/5 p-4 transition hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/60">
              <div class="flex items-start gap-3">
                <?php if(!empty($news->image_url)): ?>
                  <img src="<?php echo e($news->image_url); ?>" alt="<?php echo e($news->headline); ?>" class="h-16 w-16 flex-shrink-0 rounded object-cover" loading="lazy" decoding="async" />
                <?php else: ?>
                  <div class="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded bg-white/10">
                    <i data-lucide="newspaper" class="h-5 w-5 text-white" aria-hidden="true"></i>
                  </div>
                <?php endif; ?>

                <div class="min-w-0">
                  <div class="flex items-center gap-2 text-[11px] text-red-300">
                    <?php if(!empty($news->symbol)): ?>
                      <span class="rounded bg-white/10 px-1.5 py-0.5 text-white"><?php echo e($news->symbol); ?></span>
                    <?php endif; ?>
                    <?php if(!empty($news->source)): ?>
                      <span><?php echo e($news->source); ?></span>
                    <?php endif; ?>
                    <span>•</span>
                    <span><?php echo e($news->formatted_published_date); ?></span>
                  </div>

                  <h3 class="mt-1 line-clamp-2 text-sm font-semibold text-white"><?php echo e($news->headline); ?></h3>

                  <?php if(!empty($news->excerpt)): ?>
                    <p class="mt-1 line-clamp-2 text-xs text-red-300"><?php echo e($news->excerpt); ?></p>
                  <?php endif; ?>

                  <?php if(!is_null($news->sentiment_score)): ?>
                    <p class="mt-2 text-[11px] <?php echo e($news->sentiment_color); ?>">Sentiment: <?php echo e($news->sentiment_label); ?></p>
                  <?php endif; ?>
                </div>
              </div>
            </a>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <p class="text-sm text-red-400">No news available yet.</p>
          <?php endif; ?>
        </div>
      </div>
    </section>
  <?php endif; ?>
  <section class="mt-12 py-16 text-white" style="background: linear-gradient(to bottom right, #8B0A1E, #C8102E, #8B0A1E);" aria-labelledby="cta-heading">
    <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
      <h2 id="cta-heading" class="text-2xl font-semibold">Ready to build your portfolio?</h2>
      <p class="mt-2 text-red-300">Create an investment plan, follow stocks, and shop inventory in one place.</p>
      <div class="mt-6 flex flex-col justify-center gap-3 sm:flex-row">
        <a href="<?php echo e(route('register')); ?>" class="rounded-md px-6 py-3 text-sm font-medium text-white transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/60" style="background-color: white; color: #C8102E;" onmouseover="this.style.backgroundColor='#f5f5f5'" onmouseout="this.style.backgroundColor='white'">Get Started</a>
        <a href="<?php echo e(route('login')); ?>" class="rounded-md border border-white/20 px-6 py-3 text-sm font-medium text-white transition hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/60 focus-visible:ring-offset-2 focus-visible:ring-offset-black">Sign In</a>
      </div>
    </div>
  </section>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
  // Product Slider Functionality
  (function() {
    const slider = document.getElementById('product-slider');
    const prevBtn = document.getElementById('prev-slide');
    const nextBtn = document.getElementById('next-slide');
    const dots = document.querySelectorAll('.slider-dot');
    
    if (!slider || !prevBtn || !nextBtn || !dots.length) {
      console.error('Slider elements not found');
      return;
    }

    let currentSlide = 0;
    const totalSlides = 5;
    let autoPlayInterval;

    function goToSlide(slideIndex) {
      currentSlide = slideIndex;
      const offset = -slideIndex * 100;
      slider.style.transform = `translateX(${offset}%)`;
      
      // Update dots
      dots.forEach((dot, index) => {
        if (index === slideIndex) {
          dot.classList.remove('bg-red-300', 'w-2');
          dot.classList.add('bg-red-800', 'w-8');
        } else {
          dot.classList.remove('bg-red-800', 'w-8');
          dot.classList.add('bg-red-300', 'w-2');
        }
      });
    }

    function nextSlide() {
      currentSlide = (currentSlide + 1) % totalSlides;
      goToSlide(currentSlide);
    }

    function prevSlide() {
      currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
      goToSlide(currentSlide);
    }

    // Event listeners
    prevBtn.addEventListener('click', function(e) {
      e.preventDefault();
      prevSlide();
      resetAutoPlay();
    });

    nextBtn.addEventListener('click', function(e) {
      e.preventDefault();
      nextSlide();
      resetAutoPlay();
    });

    dots.forEach((dot, index) => {
      dot.addEventListener('click', function(e) {
        e.preventDefault();
        goToSlide(index);
        resetAutoPlay();
      });
    });

    // Auto-play functionality
    function startAutoPlay() {
      autoPlayInterval = setInterval(nextSlide, 6000);
    }

    function stopAutoPlay() {
      if (autoPlayInterval) {
        clearInterval(autoPlayInterval);
      }
    }

    function resetAutoPlay() {
      stopAutoPlay();
      startAutoPlay();
    }

    // Keyboard navigation
    document.addEventListener('keydown', function(e) {
      if (e.key === 'ArrowLeft') {
        prevSlide();
        resetAutoPlay();
      } else if (e.key === 'ArrowRight') {
        nextSlide();
        resetAutoPlay();
      }
    });

    // Touch/swipe support
    let touchStartX = 0;
    let touchEndX = 0;

    slider.addEventListener('touchstart', function(e) {
      touchStartX = e.changedTouches[0].screenX;
      stopAutoPlay();
    });

    slider.addEventListener('touchend', function(e) {
      touchEndX = e.changedTouches[0].screenX;
      handleSwipe();
      resetAutoPlay();
    });

    function handleSwipe() {
      const swipeThreshold = 50;
      if (touchEndX < touchStartX - swipeThreshold) {
        nextSlide();
      }
      if (touchEndX > touchStartX + swipeThreshold) {
        prevSlide();
      }
    }

    // Pause auto-play on hover
    const sliderSection = slider.closest('section');
    if (sliderSection) {
      sliderSection.addEventListener('mouseenter', stopAutoPlay);
      sliderSection.addEventListener('mouseleave', startAutoPlay);
    }

    // Initialize
    goToSlide(0);
    startAutoPlay();

    console.log('Slider initialized with', totalSlides, 'slides');
  })();
</script>
<?php $__env->stopPush(); ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.main', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\tesla.com\resources\views/frontend/home.blade.php ENDPATH**/ ?>