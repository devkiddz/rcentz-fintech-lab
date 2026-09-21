<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ locale_direction() }}">
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

    @include('partials.theme-init', ['themeScope' => 'customer'])
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.shell.sidebar-behavior')

    <style>
        .customer-workspace #sidebar{border-right:0!important;background:linear-gradient(180deg,hsl(var(--card)) 0%,color-mix(in srgb,hsl(var(--card)) 96%,var(--brand-primary) 4%) 100%);box-shadow:18px 0 44px rgba(15,23,42,.045)}
        .dark .customer-workspace #sidebar{box-shadow:18px 0 50px rgba(0,0,0,.18)}
        .customer-workspace .customer-sidebar-head{border-bottom:0!important;padding-top:.35rem}
        .customer-workspace .customer-sidebar-profile{margin:.35rem .75rem .65rem;border:0!important;border-radius:1rem;background:hsl(var(--muted)/.58);box-shadow:inset 0 0 0 1px hsl(var(--border)/.42)}
        .customer-workspace #sidebar [data-sidebar-nav] > a,
        .customer-workspace #sidebar [data-sidebar-nav] > details > summary{border:0!important;box-shadow:none!important;min-height:2.5rem;border-radius:.9rem!important}
        .customer-workspace #sidebar [data-sidebar-nav] > a:hover,
        .customer-workspace #sidebar [data-sidebar-nav] > details > summary:hover{background:hsl(var(--muted)/.72)!important}
        .customer-workspace #sidebar details[open] > summary{background:color-mix(in srgb,var(--brand-primary) 5%,hsl(var(--muted)))!important;box-shadow:inset 3px 0 0 color-mix(in srgb,var(--brand-primary) 62%,transparent)!important}
        .customer-workspace #sidebar [class*="bg-red-500"]{background:color-mix(in srgb,var(--brand-primary) 6%,hsl(var(--muted)))!important;color:color-mix(in srgb,var(--brand-primary) 78%,hsl(var(--foreground)))!important;box-shadow:inset 3px 0 0 color-mix(in srgb,var(--brand-primary) 68%,transparent)!important}
        .customer-workspace #sidebar .sidebar-subnav{position:relative;border-left:0!important;margin-left:.8rem!important;padding-left:1rem!important}
        .customer-workspace #sidebar .sidebar-subnav::before{content:"";position:absolute;left:.14rem;top:.3rem;bottom:.3rem;width:1px;background:linear-gradient(180deg,transparent,hsl(var(--border)/.78) 15%,hsl(var(--border)/.78) 85%,transparent)}
        .customer-workspace #sidebar .sidebar-subnav a{border-radius:.8rem!important}
        .customer-shell-topbar{position:sticky;top:0;z-index:40;padding:.65rem .9rem 0;background:linear-gradient(180deg,hsl(var(--background)) 60%,transparent)}
        .customer-shell-topbar-inner{display:flex;min-height:3.65rem;align-items:center;justify-content:space-between;gap:1rem;border-radius:1rem;background:hsl(var(--card)/.84);padding:.55rem .75rem;box-shadow:0 14px 34px rgba(15,23,42,.06),inset 0 0 0 1px hsl(var(--border)/.55);backdrop-filter:blur(18px)}
        .dark .customer-shell-topbar-inner{box-shadow:0 16px 36px rgba(0,0,0,.22),inset 0 0 0 1px hsl(var(--border)/.50)}
        .customer-shell-icon{display:inline-flex;height:2.35rem;width:2.35rem;align-items:center;justify-content:center;border:0;border-radius:.8rem;background:hsl(var(--muted)/.58);color:hsl(var(--muted-foreground));transition:.18s ease}
        .customer-shell-icon:hover{background:hsl(var(--muted));color:hsl(var(--foreground))}
        .customer-shell-topbar .shell-icon-button{display:inline-flex!important;height:2.35rem!important;width:2.35rem!important;align-items:center!important;justify-content:center!important;border:0!important;border-radius:.8rem!important;background:hsl(var(--muted)/.58)!important;color:hsl(var(--muted-foreground))!important;box-shadow:none!important;transition:.18s ease!important}
        .customer-shell-topbar .shell-icon-button:hover{background:hsl(var(--muted))!important;color:hsl(var(--foreground))!important}
        .customer-mobile-dock{border:0!important;background:hsl(var(--card)/.94)!important;box-shadow:0 -8px 34px rgba(15,23,42,.10),inset 0 0 0 1px hsl(var(--border)/.52);backdrop-filter:blur(18px)}
        .dark .customer-mobile-dock{box-shadow:0 -10px 36px rgba(0,0,0,.28),inset 0 0 0 1px hsl(var(--border)/.42)}
    </style>
</head>

@php
    $configuredBrandPrimary = (string) setting('brand_primary_color', '#c8102e');
    $brandPrimary = preg_match('/^#[0-9a-fA-F]{6}$/', $configuredBrandPrimary) ? $configuredBrandPrimary : '#c8102e';
    $configuredBrandSecondary = (string) setting('brand_secondary_color', '#7c3aed');
    $brandSecondary = preg_match('/^#[0-9a-fA-F]{6}$/', $configuredBrandSecondary) ? $configuredBrandSecondary : '#7c3aed';
@endphp
<body class="customer-workspace bg-background text-foreground font-sans antialiased" data-theme-scope="customer" style="--brand-primary: {{ $brandPrimary }}; --brand-secondary: {{ $brandSecondary }}">
<div class="min-h-screen">
    <div id="sidebar-overlay"
         class="fixed inset-0 z-[60] hidden bg-black/60 backdrop-blur-[1px] lg:hidden"
         onclick="toggleSidebar()"></div>

    <aside id="sidebar"
           class="fixed inset-y-0 left-0 z-[70] flex w-72 -translate-x-full flex-col bg-card text-card-foreground transition-all duration-300 lg:translate-x-0">
        <div class="customer-sidebar-head flex h-16 shrink-0 items-center justify-between px-4">
            <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('dashboard') }}" class="flex min-w-0 items-center gap-3">
                @if(site_logo_light() || site_logo_dark())
                    <div class="sidebar-brand-compact hidden h-8 w-8 shrink-0 items-center justify-center rounded-lg text-xs font-bold text-white" style="background:var(--brand-primary)">{{ strtoupper(substr(trim(site_name()), 0, 1)) ?: 'F' }}</div>
                    <img src="{{ site_logo_light() ?? site_logo_dark() }}"
                         alt="{{ site_name() }}"
                         class="sidebar-brand-full h-7 w-auto max-w-[150px] object-contain dark:hidden">
                    <img src="{{ site_logo_dark() ?? site_logo_light() }}"
                         alt="{{ site_name() }}"
                         class="sidebar-brand-full hidden h-7 w-auto max-w-[150px] object-contain dark:block">
                @else
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-foreground text-xs font-bold text-background">{{ strtoupper(substr(trim(site_name()), 0, 1)) ?: 'F' }}</div>
                    <span class="sidebar-label truncate text-sm font-semibold">{{ site_name() }}</span>
                @endif
            </a>

            <button type="button"
                    onclick="toggleSidebar()"
                    class="rounded-lg p-2 text-muted-foreground hover:bg-muted hover:text-foreground lg:hidden">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <div class="customer-sidebar-profile flex items-center gap-3 px-4 py-3">
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

    <nav class="customer-mobile-dock fixed inset-x-3 bottom-3 z-50 rounded-2xl px-2 py-2 lg:hidden">
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
