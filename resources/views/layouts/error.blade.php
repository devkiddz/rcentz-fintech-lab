<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>
            @hasSection('title')
                @yield('title') - {{ site_name() }}
            @else
                {{ site_name() }}
            @endif
        </title>

        <!-- Favicon -->
        @if(site_favicon())
            <link rel="icon" type="image/x-icon" href="{{ site_favicon() }}">
        @endif

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Lucide Icons -->
        <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-card text-foreground">
        <main>
            @yield('content')
        </main>

        <script>
            if (window.lucide) {
                lucide.createIcons();
            }
        </script>
    </body>
    </html>


