<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
        <title><?php echo e(isset($title) ? $title . ' - ' . site_name() : site_name()); ?></title>

        <!-- Favicon -->
        <?php if(site_favicon()): ?>
            <link rel="icon" type="image/x-icon" href="<?php echo e(site_favicon()); ?>">
        <?php endif; ?>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Lucide Icons -->
        <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

        <?php echo $__env->make('partials.theme-init', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    </head>
   <body class="font-sans bg-background text-foreground transition-colors duration-200">
    <!-- Top Navigation (Dashboard-style) -->
    <nav class="fixed top-0 left-0 right-0 z-50 border-b" style="background-color: #C8102E; border-color: #A00D25;">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="<?php echo e(route('home')); ?>" class="flex items-center">
                        <?php if(site_logo()): ?>
                            <img src="<?php echo e(site_logo()); ?>" alt="<?php echo e(site_name()); ?>" class="h-6 w-auto filter brightness-0 invert">
                        <?php else: ?>
                            <span class="text-lg font-bold text-white"><?php echo e(site_name()); ?></span>
                        <?php endif; ?>
                    </a>
                </div>

                <!-- Primary Nav -->
                <div class="hidden md:flex items-center space-x-6">
                    <a href="<?php echo e(route('cars.browse')); ?>" class="text-sm font-medium text-white hover:text-gray-200">Inventory</a>
                    <a href="<?php echo e(route('investments.index')); ?>" class="text-sm font-medium text-white hover:text-gray-200">Invest</a>
                    <a href="<?php echo e(route('stocks.index')); ?>" class="text-sm font-medium text-white hover:text-gray-200">Stocks</a>
                    <a href="<?php echo e(route('portfolio.index')); ?>" class="text-sm font-medium text-white hover:text-gray-200">Portfolio</a>
                </div>

                <!-- Right Side -->
                <div class="flex items-center space-x-2">
                    <?php if(auth()->guard()->check()): ?>
                        <!-- Account Dropdown -->
                        <div class="relative group">
                            <button class="flex items-center text-sm font-medium text-white hover:text-gray-200 px-3 py-2 rounded-md">
                                <i data-lucide="user" class="w-4 h-4 mr-2"></i>
                                Account
                            </button>
                            <div class="absolute right-0 mt-2 w-56 rounded-lg border border-border bg-card text-card-foreground shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                                <div class="py-2">
                                    <div class="px-4 py-3 border-b border-border">
                                        <p class="text-sm font-medium text-foreground"><?php echo e(auth()->user()->name); ?></p>
                                        <p class="text-xs text-muted-foreground"><?php echo e(auth()->user()->email); ?></p>
                                    </div>
                                    <a href="<?php echo e(route('dashboard')); ?>" class="block px-4 py-2 text-sm text-muted-foreground hover:bg-muted hover:text-foreground">Dashboard</a>
                                    <a href="<?php echo e(route('profile.edit')); ?>" class="block px-4 py-2 text-sm text-muted-foreground hover:bg-muted hover:text-foreground">Profile</a>
                                    <?php if(auth()->user()->isAdmin()): ?>
                                        <a href="<?php echo e(route('admin.dashboard')); ?>" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">Admin</a>
                                    <?php endif; ?>
                                    <div class="border-t border-border mt-2 pt-2">
                                        <form method="POST" action="<?php echo e(route('logout')); ?>">
                                            <?php echo csrf_field(); ?>
                                            <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Sign Out</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?php echo e(route('login')); ?>" class="text-sm font-medium text-white hover:text-gray-200 px-3 py-2">Account</a>
                    <?php endif; ?>

                    <!-- Mobile menu button -->
                    <button type="button" class="md:hidden p-2 text-white hover:text-gray-200 rounded-md" style="hover:background-color: rgba(200, 16, 46, 0.8);" onmouseover="this.style.backgroundColor='rgba(200,16,46,0.8)'" onmouseout="this.style.backgroundColor='transparent'" id="mobile-menu-button">
                        <span class="sr-only">Open menu</span>
                        <i data-lucide="menu" class="w-5 h-5" id="menu-icon"></i>
                        <i data-lucide="x" class="w-5 h-5 hidden" id="close-icon"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div class="md:hidden hidden border-t" style="background-color: #C8102E; border-color: #A00D25;" id="mobile-menu">
            <div class="px-4 pt-2 pb-4 space-y-1">
                <a href="<?php echo e(route('cars.browse')); ?>" class="block px-3 py-2 text-sm font-medium text-white rounded hover:bg-black/20">Inventory</a>
                <a href="<?php echo e(route('investments.index')); ?>" class="block px-3 py-2 text-sm font-medium text-white rounded hover:bg-black/20">Invest</a>
                <a href="<?php echo e(route('stocks.index')); ?>" class="block px-3 py-2 text-sm font-medium text-white rounded hover:bg-black/20">Stocks</a>
                <a href="<?php echo e(route('portfolio.index')); ?>" class="block px-3 py-2 text-sm font-medium text-white rounded hover:bg-black/20">Portfolio</a>
                <?php if(auth()->guard()->check()): ?>
                    <div class="border-t mt-2 pt-2" style="border-color: #A00D25;">
                        <a href="<?php echo e(route('dashboard')); ?>" class="block px-3 py-2 text-sm font-medium text-white rounded hover:bg-black/20">Dashboard</a>
                        <a href="<?php echo e(route('profile.edit')); ?>" class="block px-3 py-2 text-sm font-medium text-white rounded hover:bg-black/20">Profile</a>
                        <?php if(auth()->user()->isAdmin()): ?>
                            <a href="<?php echo e(route('admin.dashboard')); ?>" class="block px-3 py-2 text-sm font-medium text-yellow-300 rounded hover:bg-black/20">Admin</a>
                        <?php endif; ?>
                        <form method="POST" action="<?php echo e(route('logout')); ?>" class="mt-1">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="block w-full text-left px-3 py-2 text-sm font-medium text-white rounded hover:bg-black/20">Sign Out</button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="border-t mt-2 pt-2" style="border-color: #A00D25;">
                        <a href="<?php echo e(route('login')); ?>" class="block px-3 py-2 text-sm font-medium text-white rounded hover:bg-black/20">Account</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>
        <!-- Flash Messages (Dashboard-style) -->
        <div class="pt-16">
            <?php if(session('success')): ?>
                <div class="bg-green-50 border-l-4 border-green-500 text-green-700 px-4 py-3 max-w-7xl mx-auto mt-3 rounded-r-md" role="alert">
                    <div class="flex items-center">
                        <i data-lucide="check-circle" class="w-4 h-4 mr-2"></i>
                        <span class="text-sm"><?php echo e(session('success')); ?></span>
                        <button onclick="this.parentElement.parentElement.style.display='none'" class="ml-auto text-green-500 hover:text-green-700">
                            <i data-lucide="x" class="w-3 h-3"></i>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
            <?php if(session('error')): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-4 py-3 max-w-7xl mx-auto mt-3 rounded-r-md" role="alert">
                    <div class="flex items-center">
                        <i data-lucide="alert-circle" class="w-4 h-4 mr-2"></i>
                        <span class="text-sm"><?php echo e(session('error')); ?></span>
                        <button onclick="this.parentElement.parentElement.style.display='none'" class="ml-auto text-red-500 hover:text-red-700">
                            <i data-lucide="x" class="w-3 h-3"></i>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
            <?php if(session('warning')): ?>
                <div class="bg-yellow-50 border-l-4 border-yellow-500 text-yellow-700 px-4 py-3 max-w-7xl mx-auto mt-3 rounded-r-md" role="alert">
                    <div class="flex items-center">
                        <i data-lucide="alert-triangle" class="w-4 h-4 mr-2"></i>
                        <span class="text-sm"><?php echo e(session('warning')); ?></span>
                        <button onclick="this.parentElement.parentElement.style.display='none'" class="ml-auto text-yellow-500 hover:text-yellow-700">
                            <i data-lucide="x" class="w-3 h-3"></i>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
            <?php if(session('info')): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-4 py-3 max-w-7xl mx-auto mt-3 rounded-r-md" role="alert">
                    <div class="flex items-center">
                        <i data-lucide="info" class="w-4 h-4 mr-2"></i>
                        <span class="text-sm"><?php echo e(session('info')); ?></span>
                        <button onclick="this.parentElement.parentElement.style.display='none'" class="ml-auto text-red-500 hover:text-red-700">
                            <i data-lucide="x" class="w-3 h-3"></i>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Main Content -->
        <main class="min-h-[60vh] bg-background">
            <?php echo $__env->yieldContent('content'); ?>
        </main>

        <!-- Footer -->
        <footer class="mt-12 border-t border-gray-800 bg-gray-950 text-white transition-colors duration-200">
            <div class="max-w-7xl mx-auto px-6 sm:px-10 py-12">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-10 text-sm">
                    <div class="col-span-2 md:col-span-1">
                        <?php if(site_logo()): ?>
                            <img src="<?php echo e(site_logo()); ?>" alt="<?php echo e(site_name()); ?>" class="h-4 w-auto filter brightness-0 invert mb-6">
                        <?php else: ?>
                            <span class="text-lg font-bold text-white mb-6"><?php echo e(site_name()); ?></span>
                        <?php endif; ?>
                        <p class="text-gray-400 dark:text-dark-text mt-3">© <?php echo e(date('Y')); ?> <?php echo e(site_name()); ?></p>
                    </div>
                    <div>
                        <h4 class="font-medium text-base mb-4">Company</h4>
                        <ul class="space-y-3 text-gray-400 dark:text-dark-text">
                            <li><a href="<?php echo e(route('about')); ?>" class="hover:text-white transition-colors duration-200">About</a></li>
                            <li><a href="<?php echo e(route('contact')); ?>" class="hover:text-white transition-colors duration-200">Contact</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-medium text-base mb-4">Support</h4>
                        <ul class="space-y-3 text-gray-400 dark:text-dark-text">
                            <li><a href="<?php echo e(route('help-center')); ?>" class="hover:text-white transition-colors duration-200">Help Center</a></li>
                            <li><a href="<?php echo e(route('terms')); ?>" class="hover:text-white transition-colors duration-200">Terms</a></li>  
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-medium text-base mb-4">Legal</h4>
                        <ul class="space-y-4 text-gray-400 dark:text-dark-text">
                            <li><a href="<?php echo e(route('privacy')); ?>" class="hover:text-white transition-colors duration-200">Privacy & Legal</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </footer>

        <script>
            // Initialize Lucide icons
            lucide.createIcons();

            // Mobile menu toggle
            document.addEventListener('DOMContentLoaded', function() {
                const mobileMenuButton = document.getElementById('mobile-menu-button');
                const mobileMenu = document.getElementById('mobile-menu');
                const menuIcon = document.getElementById('menu-icon');
                const closeIcon = document.getElementById('close-icon');

                if (mobileMenuButton && mobileMenu) {
                    mobileMenuButton.addEventListener('click', function() {
                        const isOpen = !mobileMenu.classList.contains('hidden');
                        if (isOpen) {
                            mobileMenu.classList.add('hidden');
                            menuIcon.classList.remove('hidden');
                            closeIcon.classList.add('hidden');
                        } else {
                            mobileMenu.classList.remove('hidden');
                            menuIcon.classList.add('hidden');
                            closeIcon.classList.remove('hidden');
                        }
                    });
                }
            });
        </script>

        <?php echo $__env->yieldPushContent('scripts'); ?>
    </body>
    </html>
<?php /**PATH C:\xampp\htdocs\tesla.com\resources\views/layouts/main.blade.php ENDPATH**/ ?>