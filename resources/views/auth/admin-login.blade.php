<x-guest-layout>
    <x-slot name="title">Administrator Sign In</x-slot>

    <div class="ui-panel overflow-hidden">
        <div class="border-b border-border px-6 py-6">
            <div class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-xl bg-gray-950 text-white dark:bg-white dark:text-gray-950">
                <i data-lucide="shield-check" class="h-5 w-5"></i>
            </div>
            <div class="text-center">
                <h1 class="text-xl font-semibold tracking-tight">Administrator access</h1>
                <p class="mt-1 text-xs text-muted-foreground">Sign in with an administrator account to continue.</p>
            </div>
        </div>

        <div class="px-6 py-6">
            @if (session('status'))
                <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-xs text-green-800 dark:border-green-900/60 dark:bg-green-950/40 dark:text-green-200">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('adminlogin.authenticate') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="ui-label">Email address</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="username"
                        class="ui-input @error('email') border-red-500 focus:border-red-500 focus:ring-red-500/20 @enderror"
                        placeholder="admin@example.com"
                    >
                    @error('email')
                        <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <div class="mb-1.5 flex items-center justify-between">
                        <label for="password" class="ui-label mb-0">Password</label>
                        <a href="{{ route('password.request') }}" class="text-xs font-medium text-tesla-600 hover:text-tesla-700 dark:text-tesla-400 dark:hover:text-tesla-300">
                            Forgot password?
                        </a>
                    </div>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        required
                        autocomplete="current-password"
                        class="ui-input @error('password') border-red-500 focus:border-red-500 focus:ring-red-500/20 @enderror"
                        placeholder="Your password"
                    >
                    @error('password')
                        <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <label class="flex items-center gap-2 text-xs text-muted-foreground">
                    <input id="remember_me" name="remember" type="checkbox" class="rounded border-border text-tesla-600 focus:ring-tesla-500">
                    <span>Keep me signed in on this device</span>
                </label>

                <button type="submit" class="ui-button-primary w-full">
                    <i data-lucide="log-in" class="h-4 w-4"></i>
                    Sign in to admin
                </button>
            </form>
        </div>

        <div class="border-t border-border bg-muted/40 px-6 py-4 text-center">
            <a href="{{ route('home') }}" class="text-xs font-medium text-muted-foreground transition hover:text-foreground">
                ← Return to website
            </a>
        </div>
    </div>
</x-guest-layout>
