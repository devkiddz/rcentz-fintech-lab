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
    <body class="customer-workspace font-sans bg-background text-foreground transition-colors duration-200" data-theme-scope="customer">
        <div class="min-h-screen flex">
            <!-- Mobile Sidebar Overlay -->
            <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-[9998] lg:hidden hidden" onclick="toggleSidebar()"></div>
            
            <!-- Enhanced Sidebar -->
            <div id="sidebar" class="fixed inset-y-0 left-0 z-[9999] w-72 bg-card shadow-sm transform -translate-x-full transition-all duration-300 ease-in-out lg:translate-x-0 border-r border-border">
                <div class="flex flex-col h-full">
                    <!-- Enhanced Logo Section -->
                    <div class="flex items-center justify-between h-16 px-6 border-b border-border">
                        <div class="flex items-center">
                            @if(site_logo_light() || site_logo_dark())
                                <img src="{{ site_logo_light() ?? site_logo_dark() }}" alt="{{ site_name() }}" class="h-6 w-auto mr-3 dark:hidden">
                                <img src="{{ site_logo_dark() ?? site_logo_light() }}" alt="{{ site_name() }}" class="hidden h-6 w-auto mr-3 dark:block">
                            @else
                                <span class="text-foreground font-bold text-lg">{{ site_name() }}</span>
                            @endif
                        </div>
                        <button onclick="toggleSidebar()" class="lg:hidden text-muted-foreground dark:text-dark-text hover:text-gray-900 dark:hover:text-white p-1.5 rounded-md hover:bg-muted dark:hover:bg-gray-700">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>

                    <!-- Enhanced User Profile Section -->
                    <div class="px-5 py-4 border-b border-border bg-card">
                        <div class="flex items-center space-x-3">
                            <div class="relative">
                                <div class="w-10 h-10 bg-foreground dark:from-tesla-400 dark:to-tesla-600 rounded-lg flex items-center justify-center">
                                    @if(auth()->user()->profile_image)
                                        <img src="{{ asset('storage/' . auth()->user()->profile_image) }}" alt="{{ auth()->user()->name }}" class="w-10 h-10 rounded-lg object-cover">
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
                                <p class="text-xs text-muted-foreground dark:text-dark-text truncate">{{ auth()->user()->email }}</p>
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
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-muted dark:bg-dark-card text-foreground dark:text-white">
                                            <i data-lucide="user" class="w-3 h-3 mr-1"></i>
                                            KYC Not Submitted
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Grouped Navigation (mobile-first reference shell) -->
                    <nav class="flex-1 px-3 py-3 overflow-y-auto space-y-1">
                        <a href="{{ route('dashboard') }}" class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-muted text-foreground' : 'text-foreground hover:bg-muted' }}">
                            <i data-lucide="home" class="w-4 h-4 mr-3"></i><span>Overview</span>
                        </a>

                        <details class="group rounded-xl" {{ request()->routeIs('cars.*','dashboard.history') ? 'open' : '' }}>
                            <summary class="list-none cursor-pointer flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-medium text-foreground hover:bg-muted">
                                <span class="flex items-center"><i data-lucide="car" class="w-4 h-4 mr-3"></i>Car Gallery</span>
                                <i data-lucide="chevron-down" class="w-4 h-4 transition-transform group-open:rotate-180"></i>
                            </summary>
                            <div class="ml-5 mt-1 pl-5 border-l border-border space-y-1">
                                <a href="{{ route('cars.browse') }}" class="flex items-center px-3 py-2 text-sm rounded-lg text-muted-foreground hover:text-foreground hover:bg-muted"><i data-lucide="search" class="w-4 h-4 mr-3"></i>Browse Cars</a>
                                <a href="{{ route('dashboard.history') }}" class="flex items-center px-3 py-2 text-sm rounded-lg text-muted-foreground hover:text-foreground hover:bg-muted"><i data-lucide="package-check" class="w-4 h-4 mr-3"></i>My Orders</a>
                            </div>
                        </details>

                        <details class="group rounded-xl" {{ request()->routeIs('investments.*','investment.*','portfolio.*','watchlist.*') ? 'open' : '' }}>
                            <summary class="list-none cursor-pointer flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-medium text-foreground hover:bg-muted">
                                <span class="flex items-center"><i data-lucide="gem" class="w-4 h-4 mr-3"></i>Investments</span>
                                <i data-lucide="chevron-down" class="w-4 h-4 transition-transform group-open:rotate-180"></i>
                            </summary>
                            <div class="ml-5 mt-1 pl-5 border-l border-border space-y-1">
                                <a href="{{ route('investments.index') }}" class="flex items-center px-3 py-2 text-sm rounded-lg text-muted-foreground hover:text-foreground hover:bg-muted"><i data-lucide="sparkles" class="w-4 h-4 mr-3"></i>Browse Plans</a>
                                <a href="{{ route('portfolio.index') }}" class="flex items-center px-3 py-2 text-sm rounded-lg text-muted-foreground hover:text-foreground hover:bg-muted"><i data-lucide="pie-chart" class="w-4 h-4 mr-3"></i>My Portfolio</a>
                                <a href="{{ route('watchlist.index') }}" class="flex items-center px-3 py-2 text-sm rounded-lg text-muted-foreground hover:text-foreground hover:bg-muted"><i data-lucide="bookmark" class="w-4 h-4 mr-3"></i>Watchlist</a>
                            </div>
                        </details>

                        <details class="group rounded-xl" {{ request()->routeIs('stocks.*','trading.*') ? 'open' : '' }}>
                            <summary class="list-none cursor-pointer flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-medium text-foreground hover:bg-muted">
                                <span class="flex items-center"><i data-lucide="trending-up" class="w-4 h-4 mr-3"></i>Trading</span>
                                <i data-lucide="chevron-down" class="w-4 h-4 transition-transform group-open:rotate-180"></i>
                            </summary>
                            <div class="ml-5 mt-1 pl-5 border-l border-border space-y-1">
                                <a href="{{ route('stocks.index') }}" class="flex items-center px-3 py-2 text-sm rounded-lg text-muted-foreground hover:text-foreground hover:bg-muted"><i data-lucide="candlestick-chart" class="w-4 h-4 mr-3"></i>Live Markets</a>
                            </div>
                        </details>

                        <details class="group rounded-xl" {{ request()->routeIs('wallet.*') ? 'open' : '' }}>
                            <summary class="list-none cursor-pointer flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-medium text-foreground hover:bg-muted">
                                <span class="flex items-center"><i data-lucide="wallet-cards" class="w-4 h-4 mr-3"></i>Wallet & Finance</span>
                                <i data-lucide="chevron-down" class="w-4 h-4 transition-transform group-open:rotate-180"></i>
                            </summary>
                            <div class="ml-5 mt-1 pl-5 border-l border-border space-y-1">
                                <a href="{{ route('wallet.deposit') }}" class="flex items-center px-3 py-2 text-sm rounded-lg text-muted-foreground hover:text-foreground hover:bg-muted"><i data-lucide="circle-plus" class="w-4 h-4 mr-3"></i>Deposit Funds</a>
                                <a href="{{ route('wallet.withdraw') }}" class="flex items-center px-3 py-2 text-sm rounded-lg text-muted-foreground hover:text-foreground hover:bg-muted"><i data-lucide="circle-minus" class="w-4 h-4 mr-3"></i>Withdraw Funds</a>
                                <a href="{{ route('wallet.transfer') }}" class="flex items-center px-3 py-2 text-sm rounded-lg text-muted-foreground hover:text-foreground hover:bg-muted"><i data-lucide="arrow-right-left" class="w-4 h-4 mr-3"></i>Internal Transfer</a>
                                <a href="{{ route('wallet.connections') }}" class="flex items-center px-3 py-2 text-sm rounded-lg text-muted-foreground hover:text-foreground hover:bg-muted"><i data-lucide="link" class="w-4 h-4 mr-3"></i>Connected Wallets</a>
                            </div>
                        </details>

                        <details class="group rounded-xl" {{ request()->routeIs('dashboard','account.*','profile.*','support.*') ? 'open' : '' }}>
                            <summary class="list-none cursor-pointer flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-medium text-foreground hover:bg-muted">
                                <span class="flex items-center"><i data-lucide="circle-user" class="w-4 h-4 mr-3"></i>Account</span>
                                <i data-lucide="chevron-down" class="w-4 h-4 transition-transform group-open:rotate-180"></i>
                            </summary>
                            <div class="ml-5 mt-1 pl-5 border-l border-border space-y-1">
                                <a href="{{ route('dashboard') }}" class="flex items-center px-3 py-2 text-sm rounded-lg text-muted-foreground hover:text-foreground hover:bg-muted"><i data-lucide="layout-dashboard" class="w-4 h-4 mr-3"></i>Account Overview</a>
                                <a href="{{ route('profile.edit') }}" class="flex items-center px-3 py-2 text-sm rounded-lg text-muted-foreground hover:text-foreground hover:bg-muted"><i data-lucide="user-round-cog" class="w-4 h-4 mr-3"></i>Profile Settings</a>
                                <a href="{{ route('profile.kyc') }}" class="flex items-center px-3 py-2 text-sm rounded-lg text-muted-foreground hover:text-foreground hover:bg-muted"><i data-lucide="shield-check" class="w-4 h-4 mr-3"></i>Verify Identity @if(!auth()->user()->kyc || !auth()->user()->kyc->isApproved())<span class="ml-auto text-[10px] bg-red-100 text-red-700 rounded-full px-2 py-0.5">Required</span>@endif</a>
                                <a href="{{ route('support.index') }}" class="flex items-center px-3 py-2 text-sm rounded-lg text-muted-foreground hover:text-foreground hover:bg-muted"><i data-lucide="headphones" class="w-4 h-4 mr-3"></i>Support Center</a>
                            </div>
                        </details>
                    </nav>

                    <!-- Enhanced User Menu -->
                    <div class="p-4 border-t border-border">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-foreground dark:text-dark-text">Logout</span>
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
            <div class="flex-1 flex min-w-0 flex-col overflow-hidden lg:ml-72 bg-background">
                @include('partials.shell.customer-topbar')

                <div class="shell-alert-stack">
                    @include('partials.shell.flash-messages')
                </div>



                <!-- Page Content -->
                <main class="flex-1 overflow-y-auto bg-background px-4 py-5 pb-24 transition-colors duration-200 sm:px-6 lg:px-8 lg:py-7 lg:pb-8">
                    {{ $slot }}
                </main>
            </div>
        </div>

        <!-- Mobile Bottom Navigation + Quick Actions -->
        <div class="fixed bottom-0 left-0 right-0 bg-card border-t border-border lg:hidden z-50 shadow-2xl transition-colors duration-200">
            <div class="grid grid-cols-5 items-end px-2 py-2">
                <a href="{{ route('dashboard') }}" class="flex flex-col items-center gap-1 py-1 text-xs {{ request()->routeIs('dashboard') ? 'text-foreground' : 'text-muted-foreground' }}"><i data-lucide="home" class="w-5 h-5"></i><span>Home</span></a>
                <a href="{{ route('stocks.index') }}" class="flex flex-col items-center gap-1 py-1 text-xs {{ request()->routeIs('stocks.*','trading.*') ? 'text-foreground' : 'text-muted-foreground' }}"><i data-lucide="candlestick-chart" class="w-5 h-5"></i><span>Markets</span></a>
                <button type="button" onclick="toggleQuickActions()" class="-mt-7 mx-auto w-14 h-14 rounded-full bg-muted text-foreground shadow-xl ring-4 ring-card flex items-center justify-center" aria-label="Open quick actions"><i data-lucide="plus" class="w-6 h-6"></i></button>
                <a href="{{ route('wallet.index') }}" class="flex flex-col items-center gap-1 py-1 text-xs {{ request()->routeIs('wallet.*') ? 'text-foreground' : 'text-muted-foreground' }}"><i data-lucide="wallet" class="w-5 h-5"></i><span>Wallet</span></a>
                <button type="button" onclick="toggleSidebar()" class="flex flex-col items-center gap-1 py-1 text-xs text-muted-foreground"><i data-lucide="menu" class="w-5 h-5"></i><span>Menu</span></button>
            </div>
        </div>

        <div id="quick-actions-overlay" class="fixed inset-0 z-[10020] hidden lg:hidden">
            <button type="button" aria-label="Close quick actions" onclick="toggleQuickActions(false)" class="absolute inset-0 bg-black/60 backdrop-blur-[1px]"></button>
            <div style="bottom:6rem;border-radius:1.5rem" class="absolute left-5 right-5 max-w-sm mx-auto border border-border bg-card shadow-2xl p-5">
                <div class="flex items-center justify-between mb-4">
                    <div><p class="text-xs text-muted-foreground">Quick actions</p><h3 class="text-lg font-semibold text-foreground">What do you want to do?</h3></div>
                    <button type="button" onclick="toggleQuickActions(false)" class="p-2 rounded-full hover:bg-muted text-muted-foreground"><i data-lucide="x" class="w-5 h-5"></i></button>
                </div>
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <a href="{{ route('investments.index') }}" class="rounded-2xl border border-border bg-background p-4 flex flex-col items-center justify-center gap-2 h-24 hover:border-foreground/40"><i data-lucide="trending-up" class="w-7 h-7 text-foreground"></i><span class="font-medium">Invest</span></a>
                    <a href="{{ route('wallet.withdraw') }}" class="rounded-2xl border border-border bg-background p-4 flex flex-col items-center justify-center gap-2 h-24 hover:border-emerald-500"><i data-lucide="wallet-cards" class="w-7 h-7 text-emerald-500"></i><span class="font-medium">Withdraw</span></a>
                </div>
                <div class="space-y-1">
                    <a href="{{ route('wallet.transfer') }}" class="w-full flex items-center gap-3 px-3 py-3 rounded-xl hover:bg-muted text-left"><i data-lucide="refresh-cw" class="w-5 h-5 text-violet-500"></i><span>Transfer Funds</span></a>
                    <a href="{{ route('wallet.connections') }}" class="w-full flex items-center gap-3 px-3 py-3 rounded-xl hover:bg-muted"><i data-lucide="link" class="w-5 h-5 text-indigo-500"></i><span>Connected Wallets</span></a>
                    <a href="{{ route('support.index') }}" class="w-full flex items-center gap-3 px-3 py-3 rounded-xl hover:bg-muted"><i data-lucide="life-buoy" class="w-5 h-5 text-cyan-500"></i><span>Support</span></a>
                </div>
            </div>
        </div>

        <script>
            function toggleQuickActions(force) {
                const el = document.getElementById('quick-actions-overlay');
                if (!el) return;
                const shouldOpen = typeof force === 'boolean' ? force : el.classList.contains('hidden');
                el.classList.toggle('hidden', !shouldOpen);
                document.body.classList.toggle('overflow-hidden', shouldOpen);
                if (window.lucide) lucide.createIcons();
            }

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
                                <p class="text-xs text-muted-foreground dark:text-gray-300">No notifications</p>
                            </div>
                        `;
                    } else {
                        notificationsList.innerHTML = data.notifications.map(notification => `
                            <div class="p-3 border-b border-gray-100 hover:bg-muted/30 ${notification.is_read ? 'opacity-60' : ''}">
                                <div class="flex items-start">
                                    <div class="w-6 h-6 bg-${notification.color}-500 rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                                        <i data-lucide="${notification.icon}" class="w-3 h-3 text-white"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-medium text-foreground">${notification.title}</p>
                                        <p class="text-xs text-muted-foreground dark:text-gray-300">${notification.message}</p>
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
