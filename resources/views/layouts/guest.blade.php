<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' · '.site_name() : site_name() }}</title>
    @if(site_favicon())<link rel="icon" href="{{ site_favicon() }}">@endif
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    @include('partials.theme-init', ['themeScope' => 'public'])
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-background font-sans text-foreground antialiased">
@php $brandInitial = strtoupper(substr(trim(site_name()),0,1)) ?: 'F'; @endphp
<div class="flex min-h-screen flex-col">
    <nav class="border-b border-border bg-background/95 backdrop-blur">
        <div class="mx-auto flex h-16 w-full max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-3">
                @if(site_logo_light() || site_logo_dark())
                    <img src="{{ site_logo_light() ?? site_logo_dark() }}" alt="{{ site_name() }}" class="h-7 w-auto max-w-[150px] object-contain dark:hidden">
                    <img src="{{ site_logo_dark() ?? site_logo_light() }}" alt="{{ site_name() }}" class="hidden h-7 w-auto max-w-[150px] object-contain dark:block">
                @else
                    <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-foreground text-xs font-bold text-background">{{ $brandInitial }}</span><span class="truncate text-sm font-semibold">{{ site_name() }}</span>
                @endif
            </a>
            <div class="flex items-center gap-2">
                <a href="{{ route('about') }}" class="hidden rounded-lg px-3 py-2 text-xs font-medium text-muted-foreground hover:bg-muted hover:text-foreground md:block">Company</a>
                <a href="{{ route('help-center') }}" class="hidden rounded-lg px-3 py-2 text-xs font-medium text-muted-foreground hover:bg-muted hover:text-foreground md:block">Support</a>
                @auth
                    <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('dashboard') }}" class="hidden rounded-lg px-3 py-2 text-xs font-semibold sm:block">Open account</a>
                @else
                    <a href="{{ route('login') }}" class="hidden rounded-lg px-3 py-2 text-xs font-semibold sm:block">Sign in</a>
                @endauth
                <button type="button" data-theme-toggle onclick="window.AxausTheme.toggle()" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-border bg-card text-muted-foreground transition hover:bg-muted hover:text-foreground" aria-label="Toggle theme"><i data-lucide="sun" class="hidden h-4 w-4 dark:block"></i><i data-lucide="moon" class="h-4 w-4 dark:hidden"></i></button>
            </div>
        </div>
    </nav>

    <main class="flex flex-1 items-center justify-center px-4 py-10 sm:px-6 lg:px-8">
        <div class="w-full {{ isset($wide) && $wide ? 'max-w-2xl' : 'max-w-sm' }}">{{ $slot }}</div>
    </main>

    <footer class="border-t border-border bg-card/60">
        <div class="mx-auto flex w-full max-w-7xl flex-col items-center justify-between gap-3 px-4 py-6 text-xs text-muted-foreground sm:flex-row sm:px-6 lg:px-8">
            <p>{{ setting('footer_text', '© '.date('Y').' '.setting('legal_company_name', setting('company_name', site_name())).'. All rights reserved.') }}</p>
            <div class="flex items-center gap-5"><a href="{{ route('privacy') }}" class="hover:text-foreground">Privacy</a><a href="{{ route('terms') }}" class="hover:text-foreground">Terms</a><a href="{{ route('contact') }}" class="hover:text-foreground">Contact</a></div>
        </div>
    </footer>
</div>
<script>document.addEventListener('DOMContentLoaded',()=>{if(window.lucide)lucide.createIcons();});</script>
</body>
</html>
