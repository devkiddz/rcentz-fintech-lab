<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ locale_direction() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ setting('site_description', 'Modern financial markets, intelligence and investment access.') }}">
    <title>{{ isset($title) ? $title.' · '.site_name() : site_name() }}</title>

    @if(site_favicon())<link rel="icon" href="{{ site_favicon() }}">@endif
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    @include('partials.theme-init', ['themeScope' => 'public'])
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .public-footer-shell{background:linear-gradient(180deg,#080c12 0%,#05080d 100%);position:relative;overflow:hidden}
        .public-footer-shell::before{content:"";position:absolute;inset:0 auto auto 0;width:100%;height:1px;background:linear-gradient(90deg,transparent,var(--brand-primary),transparent)}
        .public-footer-glow{position:absolute;right:-8rem;top:-8rem;width:26rem;height:26rem;border-radius:9999px;background:color-mix(in srgb,var(--brand-primary) 18%,transparent);filter:blur(110px);pointer-events:none}
        .public-footer-grid{display:grid;grid-template-columns:minmax(0,1.45fr) repeat(3,minmax(0,.75fr));gap:3rem;align-items:start}
        .public-footer-links{display:grid;gap:.72rem;margin-top:1rem}
        .public-footer-link{font-size:.78rem;color:rgba(255,255,255,.56);transition:color .18s ease,transform .18s ease}
        .public-footer-link:hover{color:#fff;transform:translateX(2px)}
        .public-footer-label{font-size:.62rem;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:rgba(255,255,255,.30)}
        @media(max-width:900px){.public-footer-grid{grid-template-columns:1.15fr 1fr 1fr;gap:2rem}.public-footer-brand{grid-column:1/-1;max-width:42rem}}
        @media(max-width:620px){.public-footer-grid{grid-template-columns:1fr 1fr;gap:2rem 1.4rem}.public-footer-brand{grid-column:1/-1}.public-footer-support{grid-column:1/-1}}
        .public-nav-shell{background:hsl(var(--background)/.84);box-shadow:0 14px 38px rgba(15,23,42,.08),inset 0 0 0 1px hsl(var(--border)/.52);backdrop-filter:blur(20px)}
        .dark .public-nav-shell{box-shadow:0 16px 42px rgba(0,0,0,.28),inset 0 0 0 1px hsl(var(--border)/.46)}
        .public-nav-links{background:hsl(var(--muted)/.42)}
        .public-nav-link{display:inline-flex;height:2.25rem;align-items:center;border-radius:.75rem;padding:0 .82rem;font-size:.72rem;font-weight:600;color:hsl(var(--muted-foreground));transition:background-color .16s ease,color .16s ease,transform .16s ease}
        .public-nav-link:hover{background:hsl(var(--background)/.84);color:hsl(var(--foreground));transform:translateY(-1px)}
        .public-nav-icon{display:inline-flex;height:2.25rem;width:2.25rem;align-items:center;justify-content:center;border:0;border-radius:.75rem;background:hsl(var(--muted)/.55);color:hsl(var(--muted-foreground));transition:.16s ease}
        .public-nav-icon:hover{background:hsl(var(--muted));color:hsl(var(--foreground))}
        .public-mobile-nav{background:hsl(var(--background)/.94);box-shadow:0 18px 48px rgba(15,23,42,.12),inset 0 0 0 1px hsl(var(--border)/.52);backdrop-filter:blur(20px)}
        .dark .public-mobile-nav{box-shadow:0 20px 54px rgba(0,0,0,.32),inset 0 0 0 1px hsl(var(--border)/.46)}
    </style>
</head>
@php
    $configuredBrandPrimary = (string) setting('brand_primary_color', '#c8102e');
    $brandPrimary = preg_match('/^#[0-9a-fA-F]{6}$/', $configuredBrandPrimary) ? $configuredBrandPrimary : '#c8102e';
    $configuredBrandSecondary = (string) setting('brand_secondary_color', '#7c3aed');
    $brandSecondary = preg_match('/^#[0-9a-fA-F]{6}$/', $configuredBrandSecondary) ? $configuredBrandSecondary : '#7c3aed';
@endphp
<body class="bg-background font-sans text-foreground antialiased" style="--brand-primary: {{ $brandPrimary }}; --brand-secondary: {{ $brandSecondary }}">
    @php
        $brandInitial = strtoupper(substr(trim(site_name()), 0, 1)) ?: 'F';
        $dashboardUrl = auth()->check() ? (auth()->user()->isAdmin() ? route('admin.dashboard') : route('dashboard')) : route('login');
        $footerCompany = setting('legal_company_name', setting('company_name', site_name()));
        $footerEmail = trim((string) site_email());
        $hasPublicSupportEmail = $footerEmail !== '' && ! str_ends_with(strtolower($footerEmail), '@example.com');
    @endphp

    <header class="fixed inset-x-0 top-0 z-50">
        <div class="mx-auto max-w-7xl px-3 sm:px-5 lg:px-7">
            <div class="public-nav-shell mt-2 flex h-14 items-center justify-between gap-4 rounded-2xl px-3 sm:px-4">
                <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-3" aria-label="{{ site_name() }} home">
                    @if(site_logo_light() || site_logo_dark())
                        <img src="{{ site_logo_light() ?? site_logo_dark() }}" alt="{{ site_name() }}" class="h-7 w-auto max-w-[160px] object-contain dark:hidden">
                        <img src="{{ site_logo_dark() ?? site_logo_light() }}" alt="{{ site_name() }}" class="hidden h-7 w-auto max-w-[160px] object-contain dark:block">
                    @else
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl text-xs font-bold text-white" style="background:var(--brand-primary)">{{ $brandInitial }}</span>
                        <span class="truncate text-sm font-semibold tracking-tight">{{ site_name() }}</span>
                    @endif
                </a>

                <nav class="public-nav-links hidden items-center gap-0.5 rounded-xl p-1 md:flex" aria-label="Primary navigation">
                    <a href="{{ route('home') }}#markets" class="public-nav-link">{{ localize('ui.nav.markets', 'Markets') }}</a>
                    <a href="{{ route('home') }}#opportunities" class="public-nav-link">{{ localize('ui.nav.investments', 'Investments') }}</a>
                    <a href="{{ route('home') }}#systems" class="public-nav-link">{{ localize('ui.nav.automation', 'Automation') }}</a>
                    <a href="{{ route('cars.browse') }}" class="public-nav-link {{ request()->routeIs('cars.*') ? 'bg-background text-foreground shadow-sm' : '' }}">{{ localize('ui.nav.inventory', 'Inventory') }}</a>
                    <a href="{{ route('about') }}" class="public-nav-link {{ request()->routeIs('about') ? 'bg-background text-foreground shadow-sm' : '' }}">{{ localize('ui.nav.company', 'Company') }}</a>
                </nav>

                <div class="flex shrink-0 items-center gap-1.5 sm:gap-2">
                    @include('partials.language-switcher')
                    <button type="button" data-theme-toggle onclick="window.AxausTheme.toggle()" class="public-nav-icon" aria-label="Toggle theme" title="Toggle theme">
                        <i data-lucide="sun" class="hidden h-4 w-4 dark:block"></i><i data-lucide="moon" class="h-4 w-4 dark:hidden"></i>
                    </button>
                    @auth
                        <a href="{{ $dashboardUrl }}" class="hidden h-9 items-center gap-2 rounded-xl px-3.5 text-xs font-semibold text-white shadow-sm transition hover:brightness-110 sm:inline-flex" style="background:var(--brand-primary)"><i data-lucide="layout-dashboard" class="h-3.5 w-3.5"></i>{{ localize('ui.nav.workspace', 'Workspace') }}</a>
                    @else
                        <a href="{{ route('login') }}" class="hidden h-9 items-center px-2.5 text-xs font-semibold text-muted-foreground transition hover:text-foreground sm:inline-flex">{{ localize('ui.nav.sign_in', 'Sign in') }}</a>
                        @if(Route::has('register'))<a href="{{ route('register') }}" class="hidden h-9 items-center rounded-xl px-3.5 text-xs font-semibold text-white shadow-sm transition hover:brightness-110 sm:inline-flex" style="background:var(--brand-primary)">{{ localize('ui.nav.create_account', 'Create account') }}</a>@endif
                    @endauth
                    <button id="public-menu-button" type="button" class="public-nav-icon md:hidden" aria-label="Open navigation" aria-controls="public-mobile-menu" aria-expanded="false"><i data-lucide="menu" class="h-4 w-4"></i></button>
                </div>
            </div>

            <div id="public-mobile-menu" class="public-mobile-nav mt-2 hidden rounded-2xl p-2 md:hidden">
                <div class="grid gap-1">
                    <a href="{{ route('home') }}#markets" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium hover:bg-muted"><i data-lucide="chart-candlestick" class="h-4 w-4 text-muted-foreground"></i>{{ localize('ui.nav.markets', 'Markets') }}</a>
                    <a href="{{ route('home') }}#opportunities" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium hover:bg-muted"><i data-lucide="gem" class="h-4 w-4 text-muted-foreground"></i>{{ localize('ui.nav.investments', 'Investments') }}</a>
                    <a href="{{ route('home') }}#systems" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium hover:bg-muted"><i data-lucide="bot" class="h-4 w-4 text-muted-foreground"></i>{{ localize('ui.nav.automation', 'Automation') }}</a>
                    <a href="{{ route('cars.browse') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium hover:bg-muted"><i data-lucide="shopping-bag" class="h-4 w-4 text-muted-foreground"></i>{{ localize('ui.nav.inventory', 'Inventory') }}</a>
                    <a href="{{ route('about') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium hover:bg-muted"><i data-lucide="building-2" class="h-4 w-4 text-muted-foreground"></i>{{ localize('ui.nav.company', 'Company') }}</a>
                    <a href="{{ $dashboardUrl }}" class="mt-1 flex items-center justify-center gap-2 rounded-xl px-3 py-2.5 text-sm font-semibold text-white" style="background:var(--brand-primary)"><i data-lucide="{{ auth()->check() ? 'layout-dashboard' : 'log-in' }}" class="h-4 w-4"></i>{{ auth()->check() ? localize('ui.common.open_workspace', 'Open workspace') : localize('ui.nav.sign_in', 'Sign in') }}</a>
                </div>
            </div>
        </div>
    </header>
    <div class="pt-20">
        @if(session('success') || session('error') || session('warning') || session('info'))
            <div class="mx-auto mt-4 max-w-7xl px-4 sm:px-6 lg:px-8">
                @foreach(['success'=>'emerald','error'=>'red','warning'=>'amber','info'=>'sky'] as $type=>$tone)
                    @if(session($type))<div class="mb-2 rounded-xl border border-border bg-card px-4 py-3 text-sm shadow-sm">{{ session($type) }}</div>@endif
                @endforeach
            </div>
        @endif

        <main class="min-h-[60vh]">@yield('content')</main>

        <footer class="public-footer-shell border-t border-white/10 text-white">
            <div class="public-footer-glow"></div>
            <div class="relative mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-14">
                <div class="public-footer-grid">
                    <div class="public-footer-brand">
                        <a href="{{ route('home') }}" class="inline-flex items-center gap-3">
                            @if(site_logo_light() || site_logo_dark())
                                <img src="{{ site_logo_dark() ?? site_logo_light() }}" alt="{{ site_name() }}" class="h-8 w-auto max-w-[190px] object-contain">
                            @else
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl text-xs font-bold text-white" style="background:var(--brand-primary)">{{ $brandInitial }}</span>
                                <span class="text-base font-semibold tracking-tight">{{ site_name() }}</span>
                            @endif
                        </a>
                        <p class="mt-5 max-w-md text-base font-semibold leading-7 text-white/82">{{ setting('site_tagline', 'Markets, intelligence and financial control.') }}</p>
                        <p class="mt-2 max-w-md text-sm leading-6 text-white/45">{{ setting('site_description', 'Modern financial markets, intelligence and investment access.') }}</p>
                        <div class="mt-6 flex flex-wrap gap-2.5">
                            <a href="{{ $dashboardUrl }}" class="inline-flex h-10 items-center gap-2 rounded-xl px-4 text-xs font-semibold text-white transition hover:brightness-110" style="background:var(--brand-primary)"><i data-lucide="layout-dashboard" class="h-4 w-4"></i>{{ auth()->check() ? localize('ui.common.open_workspace', 'Open workspace') : localize('ui.common.client_access', 'Client access') }}</a>
                            <a href="{{ route('contact') }}" class="inline-flex h-10 items-center gap-2 rounded-xl border border-white/10 bg-white/[.035] px-4 text-xs font-semibold text-white/75 transition hover:bg-white/[.07] hover:text-white"><i data-lucide="headphones" class="h-4 w-4"></i>{{ localize('ui.common.contact_support', 'Contact support') }}</a>
                        </div>
                    </div>

                    <div>
                        <p class="public-footer-label">{{ localize('ui.common.explore', 'Explore') }}</p>
                        <div class="public-footer-links">
                            <a href="{{ route('home') }}#markets" class="public-footer-link">{{ localize('ui.nav.markets', 'Markets') }}</a>
                            <a href="{{ route('home') }}#opportunities" class="public-footer-link">{{ localize('ui.common.opportunities', 'Opportunities') }}</a>
                            <a href="{{ route('cars.browse') }}" class="public-footer-link">{{ localize('ui.nav.inventory', 'Inventory') }}</a>
                            <a href="{{ route('home') }}#systems" class="public-footer-link">{{ localize('ui.nav.automation', 'Automation') }}</a>
                        </div>
                    </div>

                    <div>
                        <p class="public-footer-label">{{ localize('ui.nav.company', 'Company') }}</p>
                        <div class="public-footer-links">
                            <a href="{{ route('about') }}" class="public-footer-link">{{ localize('ui.common.about', 'About') }}</a>
                            <a href="{{ route('contact') }}" class="public-footer-link">{{ localize('ui.nav.contact', 'Contact') }}</a>
                            <a href="{{ route('help-center') }}" class="public-footer-link">{{ localize('ui.common.help_center', 'Help center') }}</a>
                            <a href="{{ route('privacy') }}" class="public-footer-link">{{ localize('ui.nav.privacy', 'Privacy') }}</a>
                        </div>
                    </div>

                    <div class="public-footer-support">
                        <p class="public-footer-label">{{ localize('ui.common.support_legal', 'Support & legal') }}</p>
                        <div class="public-footer-links">
                            @if($hasPublicSupportEmail)
                                <a href="mailto:{{ $footerEmail }}" class="public-footer-link inline-flex items-center gap-2"><i data-lucide="mail" class="h-3.5 w-3.5"></i>{{ $footerEmail }}</a>
                            @else
                                <a href="{{ route('contact') }}" class="public-footer-link inline-flex items-center gap-2"><i data-lucide="mail" class="h-3.5 w-3.5"></i>{{ localize('ui.common.contact_support', 'Contact support') }}</a>
                            @endif
                            @if(site_phone())<a href="tel:{{ site_phone() }}" class="public-footer-link inline-flex items-center gap-2"><i data-lucide="phone" class="h-3.5 w-3.5"></i>{{ site_phone() }}</a>@endif
                            <a href="{{ route('terms') }}" class="public-footer-link">{{ localize('ui.common.terms_service', 'Terms of service') }}</a>
                            <a href="{{ route('privacy') }}" class="public-footer-link">{{ localize('ui.common.privacy_legal', 'Privacy & legal') }}</a>
                        </div>
                    </div>
                </div>

                <div class="mt-10 flex flex-col gap-3 border-t border-white/10 pt-5 text-[10px] text-white/30 sm:flex-row sm:items-center sm:justify-between">
                    <p>{{ setting('footer_text', '© '.date('Y').' '.$footerCompany.'. All rights reserved.') }}</p>
                    <div class="flex flex-wrap items-center gap-3">
                        <span>{{ $footerCompany }}</span>
                        @if(is_setting_enabled('developer_credit_enabled') && setting('developer_name'))
                            <span class="h-1 w-1 rounded-full bg-white/20"></span>
                            @if(setting('developer_url'))<a href="{{ setting('developer_url') }}" class="transition hover:text-white/70" rel="noopener">{{ setting('developer_name') }}</a>@else<span>{{ setting('developer_name') }}</span>@endif
                        @endif
                    </div>
                </div>
            </div>
        </footer>

    </div>

    @include('partials.shell.communication-launcher')

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.lucide) lucide.createIcons();
            const button = document.getElementById('public-menu-button');
            const menu = document.getElementById('public-mobile-menu');
            button?.addEventListener('click', function () {
                const hidden = menu?.classList.toggle('hidden');
                button.setAttribute('aria-expanded', hidden ? 'false' : 'true');
            });
        });
    </script>
    @stack('scripts')
</body>
</html>
