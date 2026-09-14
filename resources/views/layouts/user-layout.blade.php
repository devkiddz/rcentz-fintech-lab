<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ site_name() }}</title>

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
<script>
            function toggleSidebar() {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('sidebar-overlay');
                const isOpen = sidebar.classList.contains('translate-x-0');
                
                if (isOpen) {
                    sidebar.classList.remove('translate-x-0');
                    sidebar.classList.add('-translate-x-full');
                    overlay.classList.add('hidden');
                } else {
                    sidebar.classList.remove('-translate-x-full');
                    sidebar.classList.add('translate-x-0');
                    overlay.classList.remove('hidden');
                }
            }
        </script>
    </head>
    <body class="font-sans bg-background text-foreground transition-colors duration-200">
        <div class="min-h-screen flex">
            <!-- Mobile Sidebar Overlay -->
            <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-[9998] lg:hidden hidden" onclick="toggleSidebar()"></div>
            
            <!-- Enhanced Sidebar -->
            <div id="sidebar" class="fixed inset-y-0 left-0 z-[9999] w-72 bg-card shadow-2xl transform -translate-x-full transition-all duration-300 ease-in-out lg:translate-x-0 border-r border-border">
                <div class="flex flex-col h-full">
                    <!-- Enhanced Logo Section -->
                    <div class="flex items-center justify-between h-16 px-6 border-b border-border">
                        <div class="flex items-center">
                            @if(site_logo())
                                <img src="{{ site_logo() }}" alt="{{ site_name() }}" class="h-6 w-auto mr-3 dark:filter dark:brightness-0 dark:invert">
                            @else
                                <span class="text-foreground font-bold text-lg">{{ site_name() }}</span>
                            @endif
                        </div>
                        <button onclick="toggleSidebar()" class="lg:hidden text-gray-600 dark:text-dark-text hover:text-gray-900 dark:hover:text-white p-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>

                    <!-- Enhanced User Profile Section -->
                    <div class="px-6 py-4 border-b border-border bg-muted/60 dark:bg-dark-muted/50">
                        <div class="flex items-center space-x-3">
                            <div class="relative">
                                <div class="w-12 h-12 bg-gradient-to-br from-tesla-400 to-tesla-600 dark:from-tesla-400 dark:to-tesla-600 rounded-xl flex items-center justify-center">
                                    @if(auth()->user()->profile_image)
                                        <img src="{{ asset('storage/' . auth()->user()->profile_image) }}" alt="{{ auth()->user()->name }}" class="w-12 h-12 rounded-xl object-cover">
                                    @else
                                        <span class="text-white font-medium text-sm">{{ substr(auth()->user()->name, 0, 1) }}</span>
                                    @endif
                                </div>
                                <!-- KYC Status Badge -->
                                @if(auth()->user()->kyc && auth()->user()->kyc->isApproved())
                                    <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-green-500 rounded-full flex items-center justify-center border-2 border-white dark:border-dark-card">
                                        <i data-lucide="check" class="w-2.5 h-2.5 text-white"></i>
                                    </div>
                                @elseif(auth()->user()->kyc && auth()->user()->kyc->isPending())
                                    <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-yellow-500 rounded-full flex items-center justify-center border-2 border-white dark:border-dark-card">
                                        <i data-lucide="clock" class="w-2.5 h-2.5 text-white"></i>
                                    </div>
                                @elseif(auth()->user()->kyc && auth()->user()->kyc->isRejected())
                                    <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-red-500 rounded-full flex items-center justify-center border-2 border-white dark:border-dark-card">
                                        <i data-lucide="x" class="w-2.5 h-2.5 text-white"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-foreground truncate">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-gray-600 dark:text-dark-text truncate">{{ auth()->user()->email }}</p>
                                <div class="flex items-center mt-1">
                                    @if(auth()->user()->kyc && auth()->user()->kyc->isApproved())
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-500 text-green-800 dark:text-white">
                                            <i data-lucide="shield-check" class="w-3 h-3 mr-1"></i>
                                            KYC Verified
                                        </span>
                                    @elseif(auth()->user()->kyc && auth()->user()->kyc->isPending())
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 dark:bg-yellow-500 text-yellow-800 dark:text-white">
                                            <i data-lucide="clock" class="w-3 h-3 mr-1"></i>
                                            KYC Pending
                                        </span>
                                    @elseif(auth()->user()->kyc && auth()->user()->kyc->isRejected())
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 dark:bg-red-500 text-red-800 dark:text-white">
                                            <i data-lucide="x-circle" class="w-3 h-3 mr-1"></i>
                                            KYC Rejected
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-200 dark:bg-dark-card text-gray-700 dark:text-white">
                                            <i data-lucide="user" class="w-3 h-3 mr-1"></i>
                                            KYC Not Submitted
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Enhanced Navigation -->
                    <nav class="flex-1 px-4 py-4 space-y-1 overflow-y-auto">
                        <!-- Dashboard -->
                        <a href="{{ route('dashboard') }}" 
                           class="group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('dashboard') ? 'bg-tesla-600 text-white' : 'text-gray-700 dark:text-dark-text hover:bg-tesla-50 dark:hover:bg-gray-700 hover:text-tesla-600 dark:hover:text-white' }}">
                            <i data-lucide="layout-dashboard" class="w-4 h-4 mr-3 {{ request()->routeIs('dashboard') ? 'text-white' : 'text-gray-500 dark:text-dark-text' }}"></i>
                            <span>Dashboard</span>
                        </a>

                        <!-- Wallet -->
                        <a href="{{ route('wallet.index') }}" 
                           class="group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('wallet.*') ? 'bg-tesla-600 text-white' : 'text-gray-700 dark:text-dark-text hover:bg-tesla-50 dark:hover:bg-gray-700 hover:text-tesla-600 dark:hover:text-white' }}">
                            <i data-lucide="wallet" class="w-4 h-4 mr-3 {{ request()->routeIs('wallet.*') ? 'text-white' : 'text-gray-500 dark:text-dark-text' }}"></i>
                            <span>Wallet</span>
                        </a>

                        <!-- Investments -->
                        <a href="{{ route('investments.index') }}" 
                           class="group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('investments.*') ? 'bg-tesla-600 text-white' : 'text-gray-700 dark:text-dark-text hover:bg-tesla-50 dark:hover:bg-gray-700 hover:text-tesla-600 dark:hover:text-white' }}">
                            <i data-lucide="trending-up" class="w-4 h-4 mr-3 {{ request()->routeIs('investments.*') ? 'text-white' : 'text-gray-500 dark:text-dark-text' }}"></i>
                            <span>Investments</span>
                        </a>

                        <!-- Stocks -->
                        <a href="{{ route('stocks.index') }}" 
                           class="group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('stocks.*') ? 'bg-tesla-600 text-white' : 'text-gray-700 dark:text-dark-text hover:bg-tesla-50 dark:hover:bg-gray-700 hover:text-tesla-600 dark:hover:text-white' }}">
                            <i data-lucide="bar-chart-3" class="w-4 h-4 mr-3 {{ request()->routeIs('stocks.*') ? 'text-white' : 'text-gray-500 dark:text-dark-text' }}"></i>
                            <span>Stocks</span>
                        </a>

                        <!-- Portfolio -->
                        <a href="{{ route('portfolio.index') }}" 
                           class="group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('portfolio.*', 'watchlist.*') ? 'bg-tesla-600 text-white' : 'text-gray-700 dark:text-dark-text hover:bg-tesla-50 dark:hover:bg-gray-700 hover:text-tesla-600 dark:hover:text-white' }}">
                            <i data-lucide="pie-chart" class="w-4 h-4 mr-3 {{ request()->routeIs('portfolio.*', 'watchlist.*') ? 'text-white' : 'text-gray-500 dark:text-dark-text' }}"></i>
                            <span>Portfolio</span>
                        </a>

                        <!-- Watchlist -->
                        <a href="{{ route('watchlist.index') }}" 
                           class="group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('watchlist.*') ? 'bg-tesla-600 text-white' : 'text-gray-700 dark:text-dark-text hover:bg-tesla-50 dark:hover:bg-gray-700 hover:text-tesla-600 dark:hover:text-white' }}">
                            <i data-lucide="bookmark" class="w-4 h-4 mr-3 {{ request()->routeIs('watchlist.*') ? 'text-white' : 'text-gray-500 dark:text-dark-text' }}"></i>
                            <span>Watchlist</span>
                        </a>

                        <!-- Investment Dashboard -->
                        <a href="{{ route('investment.dashboard') }}" 
                           class="group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('investment.*') ? 'bg-tesla-600 text-white' : 'text-gray-700 dark:text-dark-text hover:bg-tesla-50 dark:hover:bg-gray-700 hover:text-tesla-600 dark:hover:text-white' }}">
                            <i data-lucide="bar-chart-3" class="w-4 h-4 mr-3 {{ request()->routeIs('investment.*') ? 'text-white' : 'text-gray-500 dark:text-dark-text' }}"></i>
                            <span>Investment Dashboard</span>
                        </a>

                        <!-- Browse Cars -->
                        <a href="{{ route('cars.browse') }}" 
                           class="group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('cars.*') ? 'bg-tesla-600 text-white' : 'text-gray-700 dark:text-dark-text hover:bg-tesla-50 dark:hover:bg-gray-700 hover:text-tesla-600 dark:hover:text-white' }}">
                            <i data-lucide="car" class="w-4 h-4 mr-3 {{ request()->routeIs('cars.*') ? 'text-white' : 'text-gray-500 dark:text-dark-text' }}"></i>
                            <span>Inventory</span>
                        </a>

                        <!-- Purchase History -->
                        <a href="{{ route('dashboard.history') }}" 
                           class="group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('dashboard.history') ? 'bg-tesla-600 text-white' : 'text-gray-700 dark:text-dark-text hover:bg-tesla-50 dark:hover:bg-gray-700 hover:text-tesla-600 dark:hover:text-white' }}">
                            <i data-lucide="receipt" class="w-4 h-4 mr-3 {{ request()->routeIs('dashboard.history') ? 'text-white' : 'text-gray-500 dark:text-dark-text' }}"></i>
                            <span>Orders</span>
                        </a>

                        <!-- Profile -->
                        <a href="{{ route('profile.edit') }}" 
                           class="group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('profile.edit') ? 'bg-tesla-600 text-white' : 'text-gray-700 dark:text-dark-text hover:bg-tesla-50 dark:hover:bg-gray-700 hover:text-tesla-600 dark:hover:text-white' }}">
                            <i data-lucide="user" class="w-4 h-4 mr-3 {{ request()->routeIs('profile.edit') ? 'text-white' : 'text-gray-500 dark:text-dark-text' }}"></i>
                            <span>Account</span>
                        </a>

                        <!-- KYC Verification -->
                        <a href="{{ route('profile.kyc') }}" 
                           class="group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('profile.kyc') ? 'bg-tesla-600 text-white' : 'text-gray-700 dark:text-dark-text hover:bg-tesla-50 dark:hover:bg-gray-700 hover:text-tesla-600 dark:hover:text-white' }}">
                            <i data-lucide="shield-check" class="w-4 h-4 mr-3 {{ request()->routeIs('profile.kyc') ? 'text-white' : 'text-gray-500 dark:text-dark-text' }}"></i>
                            <span>KYC Verification</span>
                        </a>

                        <!-- Divider -->
                        <div class="border-t border-border pt-4 mt-4">
                            <!-- Support -->
                            <a href="{{ route('support.index') }}" 
                               class="group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('support.*') ? 'bg-tesla-600 text-white' : 'text-gray-700 dark:text-dark-text hover:bg-tesla-50 dark:hover:bg-gray-700 hover:text-tesla-600 dark:hover:text-white' }} transition-all duration-200">
                                <i data-lucide="help-circle" class="w-4 h-4 mr-3 {{ request()->routeIs('support.*') ? 'text-white' : 'text-gray-500 dark:text-dark-text' }}"></i>
                                <span>Support</span>
                            </a>
                        </div>
                    </nav>

                    <!-- Enhanced User Menu -->
                    <div class="p-4 border-t border-border">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700 dark:text-dark-text">Logout</span>
                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit" class="flex items-center text-sm font-medium text-red-600 dark:text-dark-danger hover:text-red-700 dark:hover:text-red-300 transition-colors p-2 rounded-lg hover:bg-red-50 dark:hover:bg-gray-700" title="Sign Out">
                                    <i data-lucide="log-out" class="w-4 h-4"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="flex-1 flex flex-col overflow-hidden lg:ml-72">
                <!-- Enhanced Top Bar -->
                <header class="bg-card border-b border-border transition-colors duration-200">
                    <div class="flex items-center justify-between px-6 py-3">
                        <div class="flex items-center">
                            <button onclick="toggleSidebar()" class="lg:hidden text-gray-600 dark:text-dark-text hover:text-gray-900 dark:hover:text-white p-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 mr-3">
                                <i data-lucide="menu" class="w-5 h-5"></i>
                            </button>
                            @if (isset($header))
                                <div class="font-light text-lg text-foreground">
                                    {{ $header }}
                                </div>
                            @endif
                        </div>

                        <!-- Enhanced Right side with theme toggle and notifications -->
                        <div class="flex items-center space-x-3">
                            <!-- Theme Toggle Button -->
                            <button 
                                onclick="toggleTheme()" 
                                class="p-2 text-gray-600 dark:text-dark-text hover:text-tesla-600 dark:hover:text-dark-accent rounded-lg hover:bg-tesla-50 dark:hover:bg-gray-700 transition-all duration-200 group"
                                title="Toggle Theme"
                            >
                                <i data-lucide="sun" class="w-5 h-5 hidden dark:block group-hover:rotate-180 transition-transform duration-500"></i>
                                <i data-lucide="moon" class="w-5 h-5 block dark:hidden group-hover:-rotate-90 transition-transform duration-500"></i>
                            </button>

                            <!-- Impersonation Stop Button -->
                            @if(app('impersonate')->isImpersonating())
                                <a href="{{ route('impersonate.leave') }}" 
                                   class="inline-flex items-center px-3 py-2 bg-red-600 text-white text-xs font-medium rounded-lg hover:bg-red-700 transition-all duration-200">
                                    <i data-lucide="log-out" class="w-3 h-3 mr-1"></i>
                                    Stop Impersonating
                                </a>
                            @endif
                            
                            <!-- Notifications -->
                            <div class="relative">
                                <button onclick="toggleNotifications()" class="relative p-2 text-gray-600 dark:text-dark-text hover:text-tesla-600 dark:hover:text-dark-accent rounded-lg hover:bg-tesla-50 dark:hover:bg-gray-700 transition-all duration-200">
                                    <i data-lucide="bell" class="w-5 h-5"></i>
                                    <!-- Notification badge -->
                                    <span id="notification-badge" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center hidden">0</span>
                                </button>

                                <!-- Enhanced Notifications Dropdown -->
                                <div id="notifications-dropdown" class="hidden absolute right-0 mt-2 w-80 bg-card rounded-lg shadow-lg border border-border z-50">
                                    <div class="p-3 border-b border-border">
                                        <div class="flex items-center justify-between">
                                            <h3 class="text-sm font-medium text-foreground">Notifications</h3>
                                            <button onclick="markAllNotificationsAsRead()" class="text-xs text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Mark all read</button>
                                        </div>
                                    </div>
                                    <div id="notifications-list" class="max-h-64 overflow-y-auto">
                                        <!-- Notifications will be loaded here dynamically -->
                                        <div class="p-3 text-center">
                                            <div class="w-6 h-6 bg-muted rounded-full flex items-center justify-center mx-auto mb-2">
                                                <i data-lucide="loader-2" class="w-3 h-3 text-gray-400 dark:text-gray-300 animate-spin"></i>
                                            </div>
                                            <p class="text-xs text-gray-500 dark:text-gray-300">Loading notifications...</p>
                                        </div>
                                    </div>
                                    <div class="p-3 border-t border-border">
                                        <a href="{{ route('notifications.index') }}" class="text-xs text-tesla-600 dark:text-gray-300 hover:text-tesla-700 dark:hover:text-white font-medium">View all notifications</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>

                <!-- Enhanced Flash Messages -->
                @if (session('success'))
                    <div class="bg-green-50 border-l-4 border-green-500 text-green-700 px-4 py-3 mx-6 mt-3 rounded-r-md" role="alert">
                        <div class="flex items-center">
                            <i data-lucide="check-circle" class="w-4 h-4 mr-2 flex-shrink-0"></i>
                            <span class="text-sm">{{ session('success') }}</span>
                            <button onclick="this.parentElement.parentElement.style.display='none'" class="ml-auto text-green-500 hover:text-green-700">
                                <i data-lucide="x" class="w-3 h-3"></i>
                            </button>
                        </div>
                    </div>
                @endif

                @if (session('error'))
                    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-4 py-3 mx-6 mt-3 rounded-r-md" role="alert">
                        <div class="flex items-center">
                            <i data-lucide="alert-circle" class="w-4 h-4 mr-2 flex-shrink-0"></i>
                            <span class="text-sm">{{ session('error') }}</span>
                            <button onclick="this.parentElement.parentElement.style.display='none'" class="ml-auto text-red-500 hover:text-red-700">
                                <i data-lucide="x" class="w-3 h-3"></i>
                            </button>
                        </div>
                    </div>
                @endif

                @if (session('warning'))
                    <div class="bg-yellow-50 border-l-4 border-yellow-500 text-yellow-700 px-4 py-3 mx-6 mt-3 rounded-r-md" role="alert">
                        <div class="flex items-center">
                            <i data-lucide="alert-triangle" class="w-4 h-4 mr-2 flex-shrink-0"></i>
                            <span class="text-sm">{{ session('warning') }}</span>
                            <button onclick="this.parentElement.parentElement.style.display='none'" class="ml-auto text-yellow-500 hover:text-yellow-700">
                                <i data-lucide="x" class="w-3 h-3"></i>
                            </button>
                        </div>
                    </div>
                @endif

                @if (session('info'))
                    <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-700 px-4 py-3 mx-6 mt-3 rounded-r-md" role="alert">
                        <div class="flex items-center">
                            <i data-lucide="info" class="w-4 h-4 mr-2 flex-shrink-0"></i>
                            <span class="text-sm">{{ session('info') }}</span>
                            <button onclick="this.parentElement.parentElement.style.display='none'" class="ml-auto text-blue-500 hover:text-blue-700">
                                <i data-lucide="x" class="w-3 h-3"></i>
                            </button>
                        </div>
                    </div>
                @endif

                <!-- Page Content -->
                <main class="flex-1 overflow-y-auto bg-background p-6 pb-20 transition-colors duration-200 lg:pb-6">
                    {{ $slot }}
                </main>
            </div>
        </div>

        <!-- Enhanced Mobile Bottom Navigation -->
        <div class="fixed bottom-0 left-0 right-0 bg-card border-t border-border lg:hidden z-50 shadow-2xl transition-colors duration-200">
            <div class="flex justify-around px-3 py-2">
                <!-- Dashboard -->
                <a href="{{ route('dashboard') }}" class="flex flex-col items-center py-1.5 px-3 rounded-xl transition-all duration-300 {{ request()->routeIs('dashboard') ? 'text-white bg-tesla-600 shadow-lg scale-105' : 'text-gray-600 dark:text-dark-text hover:text-tesla-600 dark:hover:text-tesla-400 hover:bg-tesla-50 dark:hover:bg-gray-700' }}">
                    <div class="relative">
                        <i data-lucide="layout-dashboard" class="w-5 h-5 mb-1"></i>
                        @if(request()->routeIs('dashboard'))
                            <div class="absolute -top-1 -right-1 w-1.5 h-1.5 bg-white rounded-full"></div>
                        @endif
                    </div>
                    <span class="text-xs font-semibold">Home</span>
                </a>

                <!-- Wallet -->
                <a href="{{ route('wallet.index') }}" class="flex flex-col items-center py-1.5 px-3 rounded-xl transition-all duration-300 {{ request()->routeIs('wallet.*') ? 'text-white bg-tesla-600 shadow-lg scale-105' : 'text-gray-600 dark:text-dark-text hover:text-tesla-600 dark:hover:text-tesla-400 hover:bg-tesla-50 dark:hover:bg-gray-700' }}">
                    <div class="relative">
                        <i data-lucide="wallet" class="w-5 h-5 mb-1"></i>
                        @if(request()->routeIs('wallet.*'))
                            <div class="absolute -top-1 -right-1 w-1.5 h-1.5 bg-white rounded-full"></div>
                        @endif
                    </div>
                    <span class="text-xs font-semibold">Wallet</span>
                </a>

                <!-- Investments -->
                <a href="{{ route('investments.index') }}" class="flex flex-col items-center py-1.5 px-3 rounded-xl transition-all duration-300 {{ request()->routeIs('investments.*') ? 'text-white bg-tesla-600 shadow-lg scale-105' : 'text-gray-600 dark:text-dark-text hover:text-tesla-600 dark:hover:text-tesla-400 hover:bg-tesla-50 dark:hover:bg-gray-700' }}">
                    <div class="relative">
                        <i data-lucide="trending-up" class="w-5 h-5 mb-1"></i>
                        @if(request()->routeIs('investments.*'))
                            <div class="absolute -top-1 -right-1 w-1.5 h-1.5 bg-white rounded-full"></div>
                        @endif
                    </div>
                    <span class="text-xs font-semibold">Invest</span>
                </a>

                <!-- Stocks -->
                <a href="{{ route('stocks.index') }}" class="flex flex-col items-center py-1.5 px-3 rounded-xl transition-all duration-300 {{ request()->routeIs('stocks.*') ? 'text-white bg-tesla-600 shadow-lg scale-105' : 'text-gray-600 dark:text-dark-text hover:text-tesla-600 dark:hover:text-tesla-400 hover:bg-tesla-50 dark:hover:bg-gray-700' }}">
                    <div class="relative">
                        <i data-lucide="bar-chart-3" class="w-5 h-5 mb-1"></i>
                        @if(request()->routeIs('stocks.*'))
                            <div class="absolute -top-1 -right-1 w-1.5 h-1.5 bg-white rounded-full"></div>
                        @endif
                    </div>
                    <span class="text-xs font-semibold">Stocks</span>
                </a>

                <!-- Portfolio -->
                <a href="{{ route('portfolio.index') }}" class="flex flex-col items-center py-1.5 px-3 rounded-xl transition-all duration-300 {{ request()->routeIs('portfolio.*', 'watchlist.*') ? 'text-white bg-tesla-600 shadow-lg scale-105' : 'text-gray-600 dark:text-dark-text hover:text-tesla-600 dark:hover:text-tesla-400 hover:bg-tesla-50 dark:hover:bg-gray-700' }}">
                    <div class="relative">
                        <i data-lucide="pie-chart" class="w-5 h-5 mb-1"></i>
                        @if(request()->routeIs('portfolio.*', 'watchlist.*'))
                            <div class="absolute -top-1 -right-1 w-1.5 h-1.5 bg-white rounded-full"></div>
                        @endif
                    </div>
                    <span class="text-xs font-semibold">Portfolio</span>
                </a>
            </div>
        </div>

        <script>
            // Initialize Lucide icons
            lucide.createIcons();
            
            // Close dropdowns when clicking outside
            document.addEventListener('click', function(event) {
                const notificationsDropdown = document.getElementById('notifications-dropdown');
                
                if (!event.target.closest('#notifications-dropdown') && !event.target.closest('button[onclick="toggleNotifications()"]')) {
                    notificationsDropdown.classList.add('hidden');
                }
            });

            function toggleNotifications() {
                const dropdown = document.getElementById('notifications-dropdown');
                dropdown.classList.toggle('hidden');
                
                if (!dropdown.classList.contains('hidden')) {
                    loadNotifications();
                }
            }

            function markAllNotificationsAsRead() {
                fetch('{{ route("notifications.mark-all-read") }}', {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                    },
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadNotifications();
                        updateNotificationBadge();
                    }
                })
                .catch(error => console.error('Error:', error));
            }

                    function loadNotifications() {
            fetch('{{ route("notifications.api") }}')
                .then(response => response.json())
                .then(data => {
                    const notificationsList = document.getElementById('notifications-list');
                    
                    if (data.notifications.length === 0) {
                        notificationsList.innerHTML = `
                            <div class="p-3 text-center">
                                <div class="w-6 h-6 bg-muted rounded-full flex items-center justify-center mx-auto mb-2">
                                    <i data-lucide="bell" class="w-3 h-3 text-gray-400 dark:text-gray-300"></i>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-300">No notifications</p>
                            </div>
                        `;
                    } else {
                        notificationsList.innerHTML = data.notifications.map(notification => `
                            <div class="p-3 border-b border-gray-100 hover:bg-gray-50 ${notification.is_read ? 'opacity-60' : ''}">
                                <div class="flex items-start">
                                    <div class="w-6 h-6 bg-${notification.color}-500 rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                                        <i data-lucide="${notification.icon}" class="w-3 h-3 text-white"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-medium text-black">${notification.title}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-300">${notification.message}</p>
                                        <p class="text-xs text-gray-400 mt-1">${notification.formatted_time}</p>
                                    </div>
                                </div>
                            </div>
                        `).join('');
                    }
                    
                    // Re-initialize Lucide icons for new content
                    lucide.createIcons();
                })
                .catch(error => {
                    console.error('Error loading notifications:', error);
                    const notificationsList = document.getElementById('notifications-list');
                    notificationsList.innerHTML = `
                        <div class="p-3 text-center">
                            <div class="w-6 h-6 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-2">
                                <i data-lucide="alert-circle" class="w-3 h-3 text-red-400"></i>
                            </div>
                            <p class="text-xs text-red-500">Failed to load notifications</p>
                        </div>
                    `;
                    lucide.createIcons();
                });
            }

            function updateNotificationBadge() {
                fetch('{{ route("notifications.unread-count") }}')
                .then(response => response.json())
                .then(data => {
                    const badge = document.getElementById('notification-badge');
                    if (data.count > 0) {
                        badge.textContent = data.count;
                        badge.classList.remove('hidden');
                    } else {
                        badge.classList.add('hidden');
                    }
                })
                .catch(error => console.error('Error updating badge:', error));
            }

            // Load notifications and update badge on page load
            document.addEventListener('DOMContentLoaded', function() {
                loadNotifications();
                updateNotificationBadge();
                
                // Update every 30 seconds
                setInterval(() => {
                    updateNotificationBadge();
                }, 30000);
            });
        </script>

        <!-- Real-time Stock Updates -->
        <link rel="stylesheet" href="{{ asset('css/stock-animations.css') }}">
        <script src="{{ asset('js/stock-updates.js') }}"></script>
        <script src="{{ asset('js/market-overview.js') }}"></script>
        <script src="{{ asset('js/portfolio-updates.js') }}"></script>
    </body>
</html> 
