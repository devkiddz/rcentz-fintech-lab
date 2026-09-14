<x-guest-layout>
    <x-slot name="title">Sign In</x-slot>

    <section class="ui-panel overflow-hidden">
        <div class="px-6 py-6 text-center">
            <div class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-tesla-600/10 text-tesla-600 dark:text-tesla-400">
                <i data-lucide="log-in" class="h-5 w-5"></i>
            </div>
            <h1 class="text-xl font-semibold tracking-tight text-foreground">Sign in</h1>
            <p class="mt-1 text-xs text-muted-foreground">Access your {{ site_name() }} account</p>
        </div>

        @if (session('status'))
            <div class="mx-6 mb-4 rounded-lg border border-green-200 bg-green-50 p-3 text-xs text-green-800 dark:border-green-900/50 dark:bg-green-950/30 dark:text-green-300">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4 px-6 pb-6">
            @csrf
            <div>
                <label for="email" class="ui-label">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="ui-input @error('email') border-red-500 @enderror" placeholder="you@example.com">
                @error('email')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <div class="mb-1.5 flex items-center justify-between">
                    <label for="password" class="ui-label mb-0">Password</label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-xs font-medium text-tesla-600 hover:text-tesla-700 dark:text-tesla-400">Forgot password?</a>
                    @endif
                </div>
                <input id="password" name="password" type="password" required autocomplete="current-password" class="ui-input @error('password') border-red-500 @enderror" placeholder="Your password">
                @error('password')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>

            <label class="flex items-center gap-2 text-xs text-muted-foreground">
                <input type="checkbox" name="remember" class="rounded border-border text-tesla-600 focus:ring-tesla-500">
                Remember me
            </label>

            <button type="submit" class="ui-button-primary w-full">Sign in</button>
        </form>

        <div class="border-t border-border bg-muted/30 px-6 py-4 text-center text-xs text-muted-foreground">
            Don’t have an account?
            <a href="{{ route('register') }}" class="font-semibold text-tesla-600 hover:text-tesla-700 dark:text-tesla-400">Create account</a>
        </div>
    </section>

    <div class="my-5 flex items-center gap-3 text-xs text-muted-foreground">
        <div class="h-px flex-1 bg-border"></div><span>or</span><div class="h-px flex-1 bg-border"></div>
    </div>

    <a href="{{ route('google.redirect') }}" class="ui-button-secondary w-full">
        <svg class="h-4 w-4" viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M21.6 12.2c0-.7-.1-1.4-.2-2.1H12v4h5.4a4.6 4.6 0 0 1-2 3v2.6h3.2c1.9-1.8 3-4.4 3-7.5Z"/><path fill="#34A853" d="M12 22c2.7 0 5-.9 6.7-2.4l-3.2-2.6c-.9.6-2 1-3.5 1-2.7 0-4.9-1.8-5.7-4.3H3v2.7A10 10 0 0 0 12 22Z"/><path fill="#FBBC05" d="M6.3 13.7a6 6 0 0 1 0-3.4V7.6H3a10 10 0 0 0 0 8.8l3.3-2.7Z"/><path fill="#EA4335" d="M12 6c1.6 0 3 .5 4.1 1.6l3.1-3A10 10 0 0 0 3 7.6l3.3 2.7C7.1 7.8 9.3 6 12 6Z"/></svg>
        Continue with Google
    </a>

    <p class="mt-5 flex items-center justify-center gap-1 text-center text-[11px] text-muted-foreground">
        <i data-lucide="shield-check" class="h-3.5 w-3.5"></i> Secure account access
    </p>
</x-guest-layout>
