<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ locale_direction() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ site_name() }} - Admin</title>

    @if(site_favicon())
        <link rel="icon" type="image/x-icon" href="{{ site_favicon() }}">
    @endif

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700&display=swap" rel="stylesheet">

    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

    @include('partials.theme-init', ['themeScope' => 'admin'])
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.shell.sidebar-behavior')

    <style>
        .admin-workspace #sidebar{
            border-right:0!important;
            background:linear-gradient(180deg,hsl(var(--card)) 0%,color-mix(in srgb,hsl(var(--card)) 94%,var(--brand-primary) 6%) 100%);
            box-shadow:18px 0 48px rgba(15,23,42,.055);
        }
        .dark .admin-workspace #sidebar{box-shadow:18px 0 54px rgba(0,0,0,.20)}
        .admin-workspace .sidebar-header{border-bottom:0!important;padding-top:.35rem}
        .admin-workspace .sidebar-profile{margin:.35rem .75rem .65rem;border:0!important;border-radius:1rem;background:color-mix(in srgb,var(--brand-primary) 6%,hsl(var(--muted)));box-shadow:inset 0 0 0 1px color-mix(in srgb,var(--brand-primary) 8%,transparent)}
        .admin-workspace [data-sidebar-nav]{padding-top:.15rem}
        .admin-workspace .sidebar-section-label{color:hsl(var(--muted-foreground)/.68)!important;letter-spacing:.18em!important}
        .admin-workspace #sidebar [data-sidebar-nav] > a,
        .admin-workspace #sidebar [data-sidebar-nav] > details > summary{
            border:0!important;box-shadow:none!important;min-height:2.55rem;border-radius:.9rem!important;
        }
        .admin-workspace #sidebar [data-sidebar-nav] > a:hover,
        .admin-workspace #sidebar [data-sidebar-nav] > details > summary:hover{background:hsl(var(--muted)/.72)!important}
        .admin-workspace #sidebar details[open] > summary{background:color-mix(in srgb,var(--brand-primary) 7%,hsl(var(--muted)))!important;color:hsl(var(--foreground))!important;box-shadow:inset 3px 0 0 color-mix(in srgb,var(--brand-primary) 70%,transparent)!important}
        .admin-workspace #sidebar [class*="bg-red-500"]{background:color-mix(in srgb,var(--brand-primary) 7%,hsl(var(--muted)))!important;color:color-mix(in srgb,var(--brand-primary) 82%,hsl(var(--foreground)))!important;box-shadow:inset 3px 0 0 color-mix(in srgb,var(--brand-primary) 72%,transparent)!important}
        .admin-workspace #sidebar .sidebar-subnav{position:relative;border-left:0!important;margin-left:.8rem!important;padding-left:1rem!important}
        .admin-workspace #sidebar .sidebar-subnav::before{content:"";position:absolute;left:.14rem;top:.3rem;bottom:.3rem;width:1px;background:linear-gradient(180deg,transparent,hsl(var(--border)/.85) 15%,hsl(var(--border)/.85) 85%,transparent)}
        .admin-workspace #sidebar .sidebar-subnav a{border-radius:.8rem!important}
        .admin-workspace #sidebar > .sidebar-footer{border-top:0!important;padding-top:.45rem!important}
        .admin-shell-topbar{position:sticky;top:0;z-index:40;padding:.65rem .9rem 0;background:linear-gradient(180deg,hsl(var(--background)) 60%,transparent)}
        .admin-shell-topbar-inner{display:flex;min-height:3.65rem;align-items:center;justify-content:space-between;gap:1rem;border-radius:1rem;background:hsl(var(--card)/.84);padding:.55rem .75rem;box-shadow:0 14px 34px rgba(15,23,42,.07),inset 0 0 0 1px hsl(var(--border)/.58);backdrop-filter:blur(18px)}
        .dark .admin-shell-topbar-inner{box-shadow:0 16px 36px rgba(0,0,0,.24),inset 0 0 0 1px hsl(var(--border)/.55)}
        .admin-shell-icon{display:inline-flex;height:2.35rem;width:2.35rem;align-items:center;justify-content:center;border:0;border-radius:.8rem;background:hsl(var(--muted)/.58);color:hsl(var(--muted-foreground));transition:.18s ease}
        .admin-shell-icon:hover{background:hsl(var(--muted));color:hsl(var(--foreground))}
        .admin-shell-topbar .shell-icon-button,.admin-shell-topbar [data-admin-notification-center] > summary{display:inline-flex!important;height:2.35rem!important;width:2.35rem!important;align-items:center!important;justify-content:center!important;border:0!important;border-radius:.8rem!important;background:hsl(var(--muted)/.58)!important;color:hsl(var(--muted-foreground))!important;box-shadow:none!important;transition:.18s ease!important}
        .admin-shell-topbar .shell-icon-button:hover,.admin-shell-topbar [data-admin-notification-center] > summary:hover{background:hsl(var(--muted))!important;color:hsl(var(--foreground))!important}
        .admin-shell-chip{display:inline-flex;height:2.35rem;align-items:center;gap:.45rem;border-radius:.8rem;background:hsl(var(--muted)/.58);padding:0 .8rem;color:hsl(var(--muted-foreground));font-size:.72rem;font-weight:600;transition:.18s ease}
        .admin-shell-chip:hover{background:hsl(var(--muted));color:hsl(var(--foreground))}
        .admin-mobile-dock{border:0!important;background:hsl(var(--card)/.94)!important;box-shadow:0 -8px 34px rgba(15,23,42,.11),inset 0 0 0 1px hsl(var(--border)/.55);backdrop-filter:blur(18px)}
        .dark .admin-mobile-dock{box-shadow:0 -10px 36px rgba(0,0,0,.30),inset 0 0 0 1px hsl(var(--border)/.45)}
    </style>
</head>

@php
    $configuredBrandPrimary = (string) setting('brand_primary_color', '#c8102e');
    $brandPrimary = preg_match('/^#[0-9a-fA-F]{6}$/', $configuredBrandPrimary) ? $configuredBrandPrimary : '#c8102e';
    $configuredBrandSecondary = (string) setting('brand_secondary_color', '#7c3aed');
    $brandSecondary = preg_match('/^#[0-9a-fA-F]{6}$/', $configuredBrandSecondary) ? $configuredBrandSecondary : '#7c3aed';
@endphp
<body class="admin-workspace bg-background text-foreground font-sans antialiased" data-theme-scope="admin" style="--brand-primary: {{ $brandPrimary }}; --brand-secondary: {{ $brandSecondary }}">
<div class="min-h-screen">
    <div id="sidebar-overlay"
         class="fixed inset-0 z-[60] hidden bg-black/60 backdrop-blur-[1px] lg:hidden"
         aria-hidden="true"
         onclick="closeSidebar()"></div>

    <aside id="sidebar"
           aria-label="Admin navigation"
           class="fixed inset-y-0 left-0 z-[70] flex w-72 -translate-x-full flex-col bg-card text-card-foreground transition-all duration-300 lg:translate-x-0">
        <div class="sidebar-header flex h-16 shrink-0 items-center justify-between px-4">
            <a href="{{ route('admin.dashboard') }}" class="sidebar-brand flex min-w-0 items-center gap-3" title="{{ site_name() }} admin">
                @if(site_logo_light() || site_logo_dark())
                    <div class="sidebar-brand-compact hidden h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-foreground text-xs font-bold text-background">{{ strtoupper(substr(trim(site_name()), 0, 1)) ?: 'F' }}</div>
                    <img src="{{ site_logo_light() ?? site_logo_dark() }}"
                         alt="{{ site_name() }}"
                         class="sidebar-brand-full h-7 w-auto max-w-[150px] object-contain dark:hidden">
                    <img src="{{ site_logo_dark() ?? site_logo_light() }}"
                         alt="{{ site_name() }}"
                         class="sidebar-brand-full hidden h-7 w-auto max-w-[150px] object-contain dark:block">
                @else
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-foreground text-xs font-bold text-background">{{ strtoupper(substr(trim(site_name()), 0, 1)) ?: 'F' }}</div>
                    <span class="sidebar-label truncate text-sm font-semibold">{{ site_name() }}</span>
                @endif
            </a>

            <button type="button"
                    onclick="closeSidebar()"
                    data-sidebar-mobile-toggle
                    aria-controls="sidebar"
                    aria-expanded="false"
                    aria-label="Close admin navigation"
                    class="rounded-lg p-2 text-muted-foreground hover:bg-muted hover:text-foreground lg:hidden">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <div class="sidebar-profile flex items-center gap-3 px-4 py-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-foreground text-xs font-semibold text-background">
                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
            </div>
            <div class="sidebar-profile-copy min-w-0">
                <p class="truncate text-xs font-semibold">{{ Auth::user()->name }}</p>
                <p class="truncate text-[10px] text-muted-foreground">Administrator · Platform Control</p>
            </div>
        </div>

        @include('partials.shell.admin-sidebar-nav')

        <div class="sidebar-footer p-3">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        title="Sign out"
                        class="sidebar-utility flex w-full items-center gap-3 rounded-lg px-3 py-2 text-[12px] font-medium text-red-600 hover:bg-red-500/10">
                    <i data-lucide="log-out" class="h-4 w-4 shrink-0"></i>
                    <span class="sidebar-label">Sign out</span>
                </button>
            </form>
        </div>
    </aside>

    <div id="workspace-main" class="min-h-screen bg-background lg:ml-72">
        @include('partials.shell.admin-topbar')

        <div class="shell-alert-stack">
            @include('partials.shell.flash-messages')
        </div>

        <main class="min-h-[calc(100vh-4rem)] px-4 py-5 pb-24 sm:px-6 lg:px-8 lg:py-7 lg:pb-8">
            {{ $slot }}
        </main>
    </div>

    <nav class="admin-mobile-dock fixed inset-x-3 bottom-3 z-50 rounded-2xl px-2 py-2 lg:hidden">
        <div class="mx-auto grid max-w-lg grid-cols-5 items-end">
            <a href="{{ route('admin.dashboard') }}"
               class="flex flex-col items-center gap-1 py-1 text-[10px] {{ request()->routeIs('admin.dashboard') ? 'text-foreground' : 'text-muted-foreground' }}">
                <i data-lucide="home" class="h-5 w-5"></i><span>Home</span>
            </a>

            <a href="{{ route('admin.trading.index') }}"
               class="flex flex-col items-center gap-1 py-1 text-[10px] {{ request()->routeIs('admin.trading.*','admin.stocks.*') ? 'text-foreground' : 'text-muted-foreground' }}">
                <i data-lucide="candlestick-chart" class="h-5 w-5"></i><span>Trading</span>
            </a>

            <a href="{{ route('admin.users.index') }}"
               class="flex flex-col items-center gap-1 py-1 text-[10px] {{ request()->routeIs('admin.users.*') ? 'text-foreground' : 'text-muted-foreground' }}">
                <i data-lucide="users" class="h-5 w-5"></i><span>Users</span>
            </a>

            <a href="{{ route('admin.wallet-transactions.index') }}"
               class="flex flex-col items-center gap-1 py-1 text-[10px] {{ request()->routeIs('admin.wallet-transactions.*') ? 'text-foreground' : 'text-muted-foreground' }}">
                <i data-lucide="wallet-cards" class="h-5 w-5"></i><span>Transactions</span>
            </a>

            <button type="button"
                    onclick="openSidebar()"
                    data-sidebar-mobile-toggle
                    aria-controls="sidebar"
                    aria-expanded="false"
                    class="flex flex-col items-center gap-1 py-1 text-[10px] text-muted-foreground">
                <i data-lucide="menu" class="h-5 w-5"></i><span>Menu</span>
            </button>
        </div>
    </nav>
</div>
    @include('partials.shell.communication-launcher')
</body>
</html>
