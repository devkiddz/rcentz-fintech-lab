<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ isset($title) ? $title . ' - ' . site_name() : site_name() }}</title>

        <!-- Favicon -->
        @if(site_favicon())
            <link rel="icon" type="image/x-icon" href="{{ site_favicon() }}">
        @endif

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Lucide Icons -->
        <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

        @include('partials.theme-init')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
   <body class="font-sans bg-background text-foreground transition-colors duration-200">
    <!-- Top Navigation (Dashboard-style) -->
    <nav class="fixed top-0 left-0 right-0 z-50 border-b" style="background-color: #C8102E; border-color: #A00D25;">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="{{ route('home') }}" class="flex items-center">
                        @if(site_logo_light() && site_logo_dark())
                            <img src="{{ site_logo_light() }}" alt="{{ site_name() }}" class="h-6 w-auto dark:hidden">
                            <img src="{{ site_logo_dark() }}" alt="{{ site_name() }}" class="hidden h-6 w-auto dark:block">
                        @elseif(site_logo_light() || site_logo_dark())
                            <img src="{{ site_logo_light() ?? site_logo_dark() }}" alt="{{ site_name() }}" class="h-6 w-auto">
                        @else
                            <span class="text-lg font-bold text-white">{{ site_name() }}</span>
                        @endif
                    </a>
                </div>

                <!-- Primary Nav -->
                <div class="hidden md:flex items-center space-x-6">
                    <a href="{{ route('cars.browse') }}" class="text-sm font-medium text-white hover:text-gray-200">Inventory</a>
                    <a href="{{ route('investments.index') }}" class="text-sm font-medium text-white hover:text-gray-200">Invest</a>
                    <a href="{{ route('stocks.index') }}" class="text-sm font-medium text-white hover:text-gray-200">Stocks</a>
                    <a href="{{ route('portfolio.index') }}" class="text-sm font-medium text-white hover:text-gray-200">Portfolio</a>
                </div>

                <!-- Right Side -->
                <div class="flex items-center space-x-2">
                    <button type="button" data-theme-toggle onclick="window.AxausTheme.toggle()" class="hidden md:inline-flex items-center gap-2 rounded-md border border-white/20 bg-white/10 px-3 py-2 text-sm font-medium text-white transition hover:bg-white/20" aria-label="Toggle color theme" title="Toggle light/dark theme">
                        <span class="dark:hidden"><i data-lucide="moon" class="h-4 w-4"></i></span>
                        <span class="hidden dark:inline-flex"><i data-lucide="sun" class="h-4 w-4"></i></span>
                        <span data-theme-current>Dark mode</span>
                    </button>
                    @auth
                        <!-- Account Dropdown -->
                        <div class="relative group">
                            <button class="flex items-center text-sm font-medium text-white hover:text-gray-200 px-3 py-2 rounded-md">
                                <i data-lucide="user" class="w-4 h-4 mr-2"></i>
                                Account
                            </button>
                            <div class="absolute right-0 mt-2 w-56 rounded-lg border border-border bg-card text-card-foreground shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                                <div class="py-2">
                                    <div class="px-4 py-3 border-b border-border">
                                        <p class="text-sm font-medium text-foreground">{{ auth()->user()->name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ auth()->user()->email }}</p>
                                    </div>
                                    <a href="{{ route('dashboard') }}" class="block px-4 py-2 text-sm text-muted-foreground hover:bg-muted hover:text-foreground">Dashboard</a>
                                    <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-muted-foreground hover:bg-muted hover:text-foreground">Profile</a>
                                    @if(auth()->user()->isAdmin())
                                        <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">Admin</a>
                                    @endif
                                    <div class="border-t border-border mt-2 pt-2">
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-foreground hover:bg-muted">Sign Out</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-medium text-white hover:text-gray-200 px-3 py-2">Account</a>
                    @endauth

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
                <a href="{{ route('cars.browse') }}" class="block px-3 py-2 text-sm font-medium text-white rounded hover:bg-black/20">Inventory</a>
                <a href="{{ route('investments.index') }}" class="block px-3 py-2 text-sm font-medium text-white rounded hover:bg-black/20">Invest</a>
                <a href="{{ route('stocks.index') }}" class="block px-3 py-2 text-sm font-medium text-white rounded hover:bg-black/20">Stocks</a>
                <a href="{{ route('portfolio.index') }}" class="block px-3 py-2 text-sm font-medium text-white rounded hover:bg-black/20">Portfolio</a>
                <button type="button" data-theme-toggle onclick="window.AxausTheme.toggle()" class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm font-medium text-white rounded hover:bg-black/20">
                    <span class="dark:hidden"><i data-lucide="moon" class="h-4 w-4"></i></span>
                    <span class="hidden dark:inline-flex"><i data-lucide="sun" class="h-4 w-4"></i></span>
                    <span data-theme-current>Dark mode</span>
                </button>
                @auth
                    <div class="border-t mt-2 pt-2" style="border-color: #A00D25;">
                        <a href="{{ route('dashboard') }}" class="block px-3 py-2 text-sm font-medium text-white rounded hover:bg-black/20">Dashboard</a>
                        <a href="{{ route('profile.edit') }}" class="block px-3 py-2 text-sm font-medium text-white rounded hover:bg-black/20">Profile</a>
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="block px-3 py-2 text-sm font-medium text-yellow-300 rounded hover:bg-black/20">Admin</a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}" class="mt-1">
                            @csrf
                            <button type="submit" class="block w-full text-left px-3 py-2 text-sm font-medium text-white rounded hover:bg-black/20">Sign Out</button>
                        </form>
                    </div>
                @else
                    <div class="border-t mt-2 pt-2" style="border-color: #A00D25;">
                        <a href="{{ route('login') }}" class="block px-3 py-2 text-sm font-medium text-white rounded hover:bg-black/20">Account</a>
                    </div>
                @endauth
            </div>
        </div>
    </nav>
        <!-- Flash Messages (Dashboard-style) -->
        <div class="pt-16">
            @if (session('success'))
                <div class="bg-green-50 dark:bg-green-950/30 border-l-4 border-green-500 text-green-700 dark:text-green-300 px-4 py-3 max-w-7xl mx-auto mt-3 rounded-r-md" role="alert">
                    <div class="flex items-center">
                        <i data-lucide="check-circle" class="w-4 h-4 mr-2"></i>
                        <span class="text-sm">{{ session('success') }}</span>
                        <button onclick="this.parentElement.parentElement.style.display='none'" class="ml-auto text-green-500 hover:text-green-700">
                            <i data-lucide="x" class="w-3 h-3"></i>
                        </button>
                    </div>
                </div>
            @endif
            @if (session('error'))
                <div class="bg-red-50 dark:bg-red-950/30 border-l-4 border-red-500 text-red-700 dark:text-red-300 px-4 py-3 max-w-7xl mx-auto mt-3 rounded-r-md" role="alert">
                    <div class="flex items-center">
                        <i data-lucide="alert-circle" class="w-4 h-4 mr-2"></i>
                        <span class="text-sm">{{ session('error') }}</span>
                        <button onclick="this.parentElement.parentElement.style.display='none'" class="ml-auto text-red-500 hover:text-red-700">
                            <i data-lucide="x" class="w-3 h-3"></i>
                        </button>
                    </div>
                </div>
            @endif
            @if (session('warning'))
                <div class="bg-yellow-50 dark:bg-yellow-950/30 border-l-4 border-yellow-500 text-yellow-700 dark:text-yellow-300 px-4 py-3 max-w-7xl mx-auto mt-3 rounded-r-md" role="alert">
                    <div class="flex items-center">
                        <i data-lucide="alert-triangle" class="w-4 h-4 mr-2"></i>
                        <span class="text-sm">{{ session('warning') }}</span>
                        <button onclick="this.parentElement.parentElement.style.display='none'" class="ml-auto text-yellow-500 hover:text-yellow-700">
                            <i data-lucide="x" class="w-3 h-3"></i>
                        </button>
                    </div>
                </div>
            @endif
            @if (session('info'))
                <div class="bg-red-50 dark:bg-red-950/30 border-l-4 border-red-500 text-red-700 dark:text-red-300 px-4 py-3 max-w-7xl mx-auto mt-3 rounded-r-md" role="alert">
                    <div class="flex items-center">
                        <i data-lucide="info" class="w-4 h-4 mr-2"></i>
                        <span class="text-sm">{{ session('info') }}</span>
                        <button onclick="this.parentElement.parentElement.style.display='none'" class="ml-auto text-red-500 hover:text-red-700">
                            <i data-lucide="x" class="w-3 h-3"></i>
                        </button>
                    </div>
                </div>
            @endif
        </div>

        <!-- Main Content -->
        <main class="min-h-[60vh] bg-background">
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="mt-12 border-t border-border bg-card text-card-foreground transition-colors duration-200">
            <div class="max-w-7xl mx-auto px-6 sm:px-10 py-12">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-10 text-sm">
                    <div class="col-span-2 md:col-span-1">
                        @if(site_logo_dark() || site_logo_light())
                            <img src="{{ site_logo_light() ?? site_logo_dark() }}" alt="{{ site_name() }}" class="h-4 w-auto mb-6 dark:hidden">
                            <img src="{{ site_logo_dark() ?? site_logo_light() }}" alt="{{ site_name() }}" class="hidden h-4 w-auto mb-6 dark:block">
                        @else
                            <span class="text-lg font-bold text-foreground mb-6">{{ site_name() }}</span>
                        @endif
                        <p class="text-muted-foreground mt-3">© {{ date('Y') }} {{ site_name() }}</p>
                    </div>
                    <div>
                        <h4 class="font-medium text-base mb-4">Company</h4>
                        <ul class="space-y-3 text-muted-foreground">
                            <li><a href="{{ route('about') }}" class="hover:text-foreground transition-colors duration-200">About</a></li>
                            <li><a href="{{ route('contact') }}" class="hover:text-foreground transition-colors duration-200">Contact</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-medium text-base mb-4">Support</h4>
                        <ul class="space-y-3 text-muted-foreground">
                            <li><a href="{{ route('help-center') }}" class="hover:text-foreground transition-colors duration-200">Help Center</a></li>
                            <li><a href="{{ route('terms') }}" class="hover:text-foreground transition-colors duration-200">Terms</a></li>  
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-medium text-base mb-4">Legal</h4>
                        <ul class="space-y-4 text-muted-foreground">
                            <li><a href="{{ route('privacy') }}" class="hover:text-foreground transition-colors duration-200">Privacy & Legal</a></li>
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

        @stack('scripts')
    </body>
    </html>
