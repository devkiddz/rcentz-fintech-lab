<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ site_name() }} - Admin</title>

        <!-- Favicon -->
        @if(site_favicon())
            <link rel="icon" type="image/x-icon" href="{{ site_favicon() }}">
        @endif

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700&display=swap" rel="stylesheet" />

        @include('partials.theme-init')

        <!-- Scripts -->
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
            
            function toggleSubmenu(id) {
                const submenu = document.getElementById(id);
                const icon = document.getElementById(id + '-icon');
                const isOpen = !submenu.classList.contains('hidden');
                
                if (isOpen) {
                    submenu.classList.add('hidden');
                    icon.classList.remove('rotate-90');
                } else {
                    submenu.classList.remove('hidden');
                    icon.classList.add('rotate-90');
                }
            }
        </script>
    </head>
    <body class="font-sans antialiased bg-background text-foreground">
        <div class="min-h-screen flex">
            <!-- Mobile Sidebar Overlay -->
            <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden hidden" onclick="toggleSidebar()"></div>
            
            <!-- Sidebar -->
            <div id="sidebar" class="fixed inset-y-0 left-0 z-50 w-72 bg-black shadow-2xl transform -translate-x-full transition-transform duration-300 ease-in-out lg:translate-x-0">
                <div class="flex flex-col h-full">
                    <!-- Logo -->
                    <div class="flex items-center justify-between h-16 px-4 border-b border-gray-800">
                        <div class="flex items-center">
                            @if(site_logo())
                                <img src="{{ site_logo() }}" alt="{{ site_name() }}" class="h-6 w-auto mr-3 filter brightness-0 invert">
                            @else
                                <span class="text-white font-bold text-lg">{{ site_name() }}</span>
                            @endif
                        </div>
                        <button onclick="toggleSidebar()" class="lg:hidden text-gray-400 hover:text-white p-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Navigation -->
                    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto text-sm">
                        <!-- Dashboard -->
                        <a href="{{ route('admin.dashboard') }}" 
                           class="group flex items-center px-3 py-2.5 font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.dashboard') ? 'bg-tesla-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                            <svg class="w-4 h-4 mr-3 {{ request()->routeIs('admin.dashboard') ? 'text-white' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6a2 2 0 002 2h4a2 2 0 002-2V9a2 2 0 00-2-2h-2"></path>
                            </svg>
                            <span>Overview</span>
                        </a>

                        <!-- Investment Management -->
                        <div class="space-y-1">
                            <button onclick="toggleSubmenu('investment-submenu')" 
                                    class="w-full group flex items-center justify-between px-3 py-2.5 font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.investments.*') ? 'bg-tesla-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-3 {{ request()->routeIs('admin.investments.*') ? 'text-white' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v18h18"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 13l3 3 7-7"></path>
                                    </svg>
                                    <span>Investment Management</span>
                                </div>
                                <svg id="investment-submenu-icon" class="w-4 h-4 transition-transform duration-200 {{ request()->routeIs('admin.investments.*') ? 'rotate-90' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                            <div id="investment-submenu" class="ml-6 space-y-1 {{ request()->routeIs('admin.investments.*') ? '' : 'hidden' }}">
                                <a href="{{ route('admin.investments.plans.index') }}" 
                                   class="flex items-center px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('admin.investments.plans.*') ? 'text-white bg-gray-800' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                    Investment Plans
                                </a>
                                <a href="{{ route('admin.investments.holdings.index') }}" 
                                   class="flex items-center px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('admin.investments.holdings.*') ? 'text-white bg-gray-800' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                    Holdings
                                </a>
                                <a href="{{ route('admin.investments.transactions.index') }}" 
                                   class="flex items-center px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('admin.investments.transactions.*') ? 'text-white bg-gray-800' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                    </svg>
                                    Transactions
                                </a>
                                <a href="{{ route('admin.investments.nav-updates.index') }}" 
                                   class="flex items-center px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('admin.investments.nav-updates.*') ? 'text-white bg-gray-800' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                    </svg>
                                    NAV Updates
                                </a>
                                <a href="{{ route('admin.investments.automatic-nav-updates.index') }}" 
                                   class="flex items-center px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('admin.investments.automatic-nav-updates.*') ? 'text-white bg-gray-800' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Automatic NAV Updates
                                </a>
                            </div>
                        </div>

                        <!-- Stock Management -->
                        <div class="space-y-1">
                            <button onclick="toggleSubmenu('stocks-submenu')" 
                                    class="w-full group flex items-center justify-between px-3 py-2.5 font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.stocks.*') ? 'bg-tesla-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-3 {{ request()->routeIs('admin.stocks.*') ? 'text-white' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3v18M3 13h18"></path>
                                    </svg>
                                    <span>Stock Management</span>
                                </div>
                                <svg id="stocks-submenu-icon" class="w-4 h-4 transition-transform duration-200 {{ request()->routeIs('admin.stocks.*') ? 'rotate-90' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                            <div id="stocks-submenu" class="ml-6 space-y-1 {{ request()->routeIs('admin.stocks.*') ? '' : 'hidden' }}">
                                <a href="{{ route('admin.stocks.index') }}" 
                                   class="flex items-center px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('admin.stocks.index') ? 'text-white bg-gray-800' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3v18M3 13h18"></path>
                                    </svg>
                                    All Stocks
                                </a>
                                <a href="{{ route('admin.stocks.holdings.index') }}" 
                                   class="flex items-center px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('admin.stocks.holdings.*') ? 'text-white bg-gray-800' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                    Holdings
                                </a>
                                <a href="{{ route('admin.stocks.transactions.index') }}" 
                                   class="flex items-center px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('admin.stocks.transactions.*') ? 'text-white bg-gray-800' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                    </svg>
                                    Transactions
                                </a>
                            </div>
                        </div>

                        <!-- Cars Management -->
                        <div class="space-y-1">
                            <button onclick="toggleSubmenu('cars-submenu')" 
                                    class="w-full group flex items-center justify-between px-3 py-2.5 font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.cars.*') ? 'bg-tesla-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-3 {{ request()->routeIs('admin.cars.*') ? 'text-white' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                    </svg>
                                    <span>Fleet Management</span>
                                </div>
                                <svg id="cars-submenu-icon" class="w-4 h-4 transition-transform duration-200 {{ request()->routeIs('admin.cars.*') ? 'rotate-90' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                            <div id="cars-submenu" class="ml-6 space-y-1 {{ request()->routeIs('admin.cars.*') ? '' : 'hidden' }}">
                                <a href="{{ route('admin.cars.index') }}" 
                                   class="flex items-center px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('admin.cars.index') ? 'text-white bg-gray-800' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                    All Vehicles
                                </a>
                                <a href="{{ route('admin.cars.create') }}" 
                                   class="flex items-center px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('admin.cars.create') ? 'text-white bg-gray-800' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    Add New Vehicle
                                </a>
                            </div>
                        </div>

                        <!-- Users Management -->
                        <a href="{{ route('admin.users.index') }}" 
                           class="group flex items-center px-3 py-2.5 font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.users.*') ? 'bg-tesla-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                            <svg class="w-4 h-4 mr-3 {{ request()->routeIs('admin.users.*') ? 'text-white' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            <span>Customer Management</span>
                        </a>

                        <!-- KYC Management -->
                        <a href="{{ route('admin.kyc.index') }}" 
                           class="group flex items-center px-3 py-2.5 font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.kyc.*') ? 'bg-tesla-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                            <svg class="w-4 h-4 mr-3 {{ request()->routeIs('admin.kyc.*') ? 'text-white' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                            <span>KYC Management</span>
                        </a>

                        <!-- Wallet Transactions -->
                        <a href="{{ route('admin.wallet-transactions.index') }}" 
                           class="group flex items-center px-3 py-2.5 font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.wallet-transactions.*') ? 'bg-tesla-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                            <svg class="w-4 h-4 mr-3 {{ request()->routeIs('admin.wallet-transactions.*') ? 'text-white' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                            </svg>
                            <span>Wallet Transactions</span>
                        </a>

                        <!-- Settings -->
                        <a href="{{ route('admin.settings.index') }}" 
                           class="group flex items-center px-3 py-2.5 font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.settings.*') ? 'bg-tesla-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                            <svg class="w-4 h-4 mr-3 {{ request()->routeIs('admin.settings.*') ? 'text-white' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span>Settings</span>
                        </a>

                        <!-- Payment Methods -->
                        <a href="{{ route('admin.payment_methods.index') }}" 
                           class="group flex items-center px-3 py-2.5 font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.payment_methods.*') ? 'bg-tesla-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                            <svg class="w-4 h-4 mr-3 {{ request()->routeIs('admin.payment_methods.*') ? 'text-white' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                            </svg>
                            <span>Payment Methods</span>
                        </a>

                        <!-- Purchase History -->
                        <a href="{{ route('admin.purchases.index') }}" 
                           class="group flex items-center px-3 py-2.5 font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.purchases.*') ? 'bg-tesla-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                            <svg class="w-4 h-4 mr-3 {{ request()->routeIs('admin.purchases.*') ? 'text-white' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                            </svg>
                            <span>Orders</span>
                        </a>

                        <!-- Email Management -->
                        <div class="space-y-1">
                            <button onclick="toggleSubmenu('emails-submenu')" 
                                    class="w-full group flex items-center justify-between px-3 py-2.5 font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.emails.*') ? 'bg-tesla-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-3 {{ request()->routeIs('admin.emails.*') ? 'text-white' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                    <span>Email Management</span>
                                </div>
                                <svg id="emails-submenu-icon" class="w-4 h-4 transition-transform duration-200 {{ request()->routeIs('admin.emails.*') ? 'rotate-90' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                            <div id="emails-submenu" class="ml-6 space-y-1 {{ request()->routeIs('admin.emails.*') ? '' : 'hidden' }}">
                                <a href="{{ route('admin.emails.compose') }}" 
                                   class="flex items-center px-3 py-2 text-sm rounded-md transition-colors {{ request()->routeIs('admin.emails.compose') ? 'text-white bg-gray-800' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                    </svg>
                                    Send Email
                                </a>
                            </div>
                        </div>

                        <!-- Divider -->
                        <div class="border-t border-gray-800 pt-4 mt-6">
                            <!-- Profile -->
                            <a href="{{ route('admin.profile.edit') }}" 
                               class="group flex items-center px-3 py-2.5 font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.profile.*') ? 'bg-tesla-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                                <svg class="w-4 h-4 mr-3 {{ request()->routeIs('admin.profile.*') ? 'text-white' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                <span>Profile Settings</span>
                            </a>
                            
                              <!-- About -->
                        <a href="{{ route('admin.about') }}" class="group flex items-center px-3 py-2.5 font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.about') ? 'bg-tesla-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                            <svg class="w-4 h-4 mr-3 {{ request()->routeIs('admin.about') ? 'text-white' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 20h.01"></path>
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8h.01" />
                            </svg>
                            <span>About Platform</span>
                        </a>

                            
                        </div>
                    </nav>

                    <!-- User Menu -->
                    <div class="p-4 border-t border-gray-800">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center">
                                    <span class="text-black font-medium text-sm">{{ substr(Auth::user()->name, 0, 1) }}</span>
                                </div>
                            </div>
                            <div class="ml-3 flex-1">
                                <p class="text-sm font-medium text-white">{{ Auth::user()->name }}</p>
                                <p class="text-xs text-gray-400">Administrator</p>
                            </div>
                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit" class="flex items-center text-sm font-medium text-gray-400 hover:text-white transition-colors p-2 rounded-md hover:bg-gray-800" title="Sign Out">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="flex-1 flex flex-col overflow-hidden lg:ml-72">
                <!-- Top Bar -->
                <header class="bg-card border-b border-border">
                    <div class="flex items-center justify-between px-4 py-3">
                        <div class="flex items-center">
                            <button onclick="toggleSidebar()" class="lg:hidden text-muted-foreground hover:text-foreground p-2 rounded-md hover:bg-muted mr-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                </svg>
                            </button>
                            @if (isset($header))
                                <div class="font-light text-lg text-foreground">
                                    {{ $header }}
                                </div>
                            @endif
                        </div>
                        <button type="button" onclick="window.toggleTheme()" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-border bg-card text-muted-foreground transition hover:bg-muted hover:text-foreground" aria-label="Toggle theme">
                            <svg class="hidden h-4 w-4 dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364-.707-.707M6.343 6.343l-.707-.707m12.728 0-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            <svg class="h-4 w-4 dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 1012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                        </button>
                    </div>
                </header>

                <!-- Flash Messages -->
                @if (session('success'))
                    <div class="bg-green-50 border-l-4 border-green-500 text-green-700 px-4 py-3 mx-6 mt-4 rounded-r-md" role="alert">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>{{ session('success') }}</span>
                            <button onclick="this.parentElement.parentElement.style.display='none'" class="ml-auto text-green-500 hover:text-green-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                @endif

                @if (session('error'))
                    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-4 py-3 mx-6 mt-4 rounded-r-md" role="alert">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>{{ session('error') }}</span>
                            <button onclick="this.parentElement.parentElement.style.display='none'" class="ml-auto text-red-500 hover:text-red-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                @endif

                <!-- Page Content -->
                <main class="flex-1 overflow-y-auto p-4 sm:p-6 bg-background">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html> 