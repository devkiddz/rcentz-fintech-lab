<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ site_name() }}</title>

    @if(site_favicon())
        <link rel="icon" type="image/x-icon" href="{{ site_favicon() }}">
    @endif

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700&display=swap" rel="stylesheet">

    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

    @include('partials.theme-init')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.shell.sidebar-behavior')
</head>

<body class="customer-workspace bg-background text-foreground font-sans antialiased" data-theme-scope="customer">
<div class="min-h-screen">
    <div id="sidebar-overlay"
         class="fixed inset-0 z-[60] hidden bg-black/60 backdrop-blur-[1px] lg:hidden"
         onclick="toggleSidebar()"></div>

    <aside id="sidebar"
           class="fixed inset-y-0 left-0 z-[70] flex w-72 -translate-x-full flex-col border-r border-border bg-card text-card-foreground shadow-sm transition-all duration-300 lg:translate-x-0">
        <div class="flex h-16 shrink-0 items-center justify-between border-b border-border px-4">
            <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('dashboard') }}" class="flex min-w-0 items-center gap-3">
                @if(site_logo_light() || site_logo_dark())
                    <img src="{{ site_logo_light() ?? site_logo_dark() }}"
                         alt="{{ site_name() }}"
                         class="h-7 w-auto max-w-[150px] object-contain dark:hidden">
                    <img src="{{ site_logo_dark() ?? site_logo_light() }}"
                         alt="{{ site_name() }}"
                         class="hidden h-7 w-auto max-w-[150px] object-contain dark:block">
                @else
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-foreground text-xs font-bold text-background">R</div>
                    <span class="sidebar-label truncate text-sm font-semibold">{{ site_name() }}</span>
                @endif
            </a>

            <button type="button"
                    onclick="toggleSidebar()"
                    class="rounded-lg p-2 text-muted-foreground hover:bg-muted hover:text-foreground lg:hidden">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <div class="flex items-center gap-3 border-b border-border px-4 py-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-foreground text-xs font-semibold text-background">
                @if(auth()->user()->profile_image)
                    <img src="{{ asset('storage/' . auth()->user()->profile_image) }}" alt="{{ auth()->user()->name }}" class="h-full w-full object-cover">
                @else
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                @endif
            </div>
            <div class="sidebar-profile-copy min-w-0">
                <p class="truncate text-xs font-semibold">{{ auth()->user()->name }}</p>
                <p class="truncate text-[10px] text-muted-foreground">{{ auth()->user()->email }}</p>
                @if(auth()->user()->isAdmin())
                    <p class="mt-1 inline-flex items-center rounded-full bg-red-500/10 px-2 py-0.5 text-[8px] font-semibold uppercase tracking-[.12em] text-red-600 dark:text-red-400">Customer Audit Mode</p>
                @else
                    <p class="mt-1 text-[9px] font-medium text-muted-foreground">Customer account</p>
                @endif
            </div>
        </div>

        @include('partials.shell.customer-sidebar-nav')

        <div class="border-t border-border p-3">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-[12px] font-medium text-red-600 hover:bg-red-500/10">
                    <i data-lucide="log-out" class="h-4 w-4 shrink-0"></i>
                    <span class="sidebar-label">Sign out</span>
                </button>
            </form>
        </div>
    </aside>

    <div id="workspace-main" class="min-h-screen bg-background lg:ml-72">
        @include('partials.shell.customer-topbar')

        <div class="shell-alert-stack">
            @include('partials.shell.flash-messages')
        </div>

        <main class="min-h-[calc(100vh-4rem)] px-4 py-5 pb-24 sm:px-6 lg:px-8 lg:py-7 lg:pb-8">
            {{ $slot }}
        </main>
    </div>

    <nav class="fixed inset-x-0 bottom-0 z-50 border-t border-border bg-card/95 px-2 py-2 backdrop-blur lg:hidden">
        <div class="mx-auto grid max-w-lg grid-cols-5 items-end">
            <a href="{{ route('dashboard') }}"
               class="flex flex-col items-center gap-1 py-1 text-[10px] {{ request()->routeIs('dashboard') ? 'text-foreground' : 'text-muted-foreground' }}">
                <i data-lucide="home" class="h-5 w-5"></i><span>Home</span>
            </a>

            <a href="{{ route('stocks.index') }}"
               class="flex flex-col items-center gap-1 py-1 text-[10px] {{ request()->routeIs('stocks.*') ? 'text-foreground' : 'text-muted-foreground' }}">
                <i data-lucide="activity" class="h-5 w-5"></i><span>Markets</span>
            </a>

            <a href="{{ route('trading.positions.index') }}"
               class="flex flex-col items-center gap-1 py-1 text-[10px] {{ request()->routeIs('trading.positions.*') ? 'text-foreground' : 'text-muted-foreground' }}">
                <i data-lucide="target" class="h-5 w-5"></i><span>Positions</span>
            </a>

            <a href="{{ route('money.index') }}"
               class="flex flex-col items-center gap-1 py-1 text-[10px] {{ request()->routeIs('money.*','wallet.*') ? 'text-foreground' : 'text-muted-foreground' }}">
                <i data-lucide="wallet" class="h-5 w-5"></i><span>Money</span>
            </a>

            <button type="button" onclick="openSidebar()"
                    class="flex flex-col items-center gap-1 py-1 text-[10px] text-muted-foreground">
                <i data-lucide="menu" class="h-5 w-5"></i><span>Menu</span>
            </button>
        </div>
    </nav>
</div>
    @include('partials.shell.communication-launcher')
</body>
</html>
