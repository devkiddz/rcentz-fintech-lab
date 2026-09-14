<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ isset($title) ? $title . ' - ' . site_name() : site_name() }}</title>

    @if(site_favicon())
        <link rel="icon" type="image/x-icon" href="{{ site_favicon() }}">
    @endif

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

    @include('partials.theme-init')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans transition-colors duration-200">
    <div class="flex min-h-screen flex-col">
        <nav class="border-b border-border bg-card/95 shadow-sm backdrop-blur">
            <div class="mx-auto flex h-14 w-full max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" class="flex items-center gap-2 font-semibold tracking-tight text-foreground transition hover:opacity-75">
                    @if(site_logo())
                        <img src="{{ site_logo() }}" alt="{{ site_name() }}" class="h-6 w-auto dark:brightness-0 dark:invert">
                    @else
                        <span>{{ site_name() }}</span>
                    @endif
                </a>

                <div class="flex items-center gap-2 sm:gap-4">
                    <a href="{{ route('home') }}" class="hidden text-xs font-medium text-muted-foreground transition hover:text-foreground md:block">Home</a>
                    <a href="{{ route('cars.browse') }}" class="hidden text-xs font-medium text-muted-foreground transition hover:text-foreground md:block">Inventory</a>

                    @auth
                        <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('dashboard') }}" class="hidden text-xs font-medium text-muted-foreground transition hover:text-foreground sm:block">
                            {{ auth()->user()->isAdmin() ? 'Admin' : 'Dashboard' }}
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="hidden text-xs font-medium text-muted-foreground transition hover:text-foreground sm:block">Sign in</a>
                        @if(Route::has('register'))
                            <a href="{{ route('register') }}" class="hidden text-xs font-medium text-muted-foreground transition hover:text-foreground md:block">Register</a>
                        @endif
                    @endauth

                    <button type="button" onclick="toggleTheme()" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-border bg-card text-muted-foreground transition hover:bg-muted hover:text-foreground" aria-label="Toggle color theme">
                        <i data-lucide="sun" class="hidden h-4 w-4 dark:block"></i>
                        <i data-lucide="moon" class="h-4 w-4 dark:hidden"></i>
                    </button>
                </div>
            </div>
        </nav>

        <main class="flex flex-1 items-center justify-center px-4 py-10 sm:px-6 lg:px-8">
            <div class="w-full {{ isset($wide) && $wide ? 'max-w-2xl' : 'max-w-sm' }}">
                {{ $slot }}
            </div>
        </main>

        <footer class="border-t border-border bg-card">
            <div class="mx-auto flex w-full max-w-7xl flex-col items-center justify-between gap-3 px-4 py-6 text-xs text-muted-foreground sm:flex-row sm:px-6 lg:px-8">
                <p>&copy; {{ date('Y') }} {{ site_name() }}. All rights reserved.</p>
                <div class="flex items-center gap-5">
                    <a href="{{ route('privacy') }}" class="transition hover:text-foreground">Privacy</a>
                    <a href="{{ route('terms') }}" class="transition hover:text-foreground">Terms</a>
                    <a href="{{ route('contact') }}" class="transition hover:text-foreground">Contact</a>
                </div>
            </div>
        </footer>
    </div>
</body>
</html>
