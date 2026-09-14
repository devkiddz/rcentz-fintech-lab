<x-guest-layout>
    <x-slot name="title">Reset Password</x-slot>
    <section class="ui-panel overflow-hidden">
        <div class="px-6 py-6 text-center">
            <h1 class="text-xl font-semibold text-foreground">Reset password</h1>
            <p class="mt-1 text-xs text-muted-foreground">We’ll send a reset link to your email.</p>
        </div>
        @if (session('status'))
            <div class="mx-6 mb-4 rounded-lg border border-green-200 bg-green-50 p-3 text-xs text-green-800 dark:border-green-900/50 dark:bg-green-950/30 dark:text-green-300">{{ session('status') }}</div>
        @endif
        <form method="POST" action="{{ route('password.email') }}" class="space-y-4 px-6 pb-6">
            @csrf
            <div>
                <label for="email" class="ui-label">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="ui-input @error('email') border-red-500 @enderror" placeholder="you@example.com">
                @error('email')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="ui-button-primary w-full">Email reset link</button>
        </form>
        <div class="border-t border-border bg-muted/30 px-6 py-4 text-center text-xs text-muted-foreground">
            <a href="{{ route('login') }}" class="font-semibold text-tesla-600 dark:text-tesla-400">Back to sign in</a>
        </div>
    </section>
</x-guest-layout>
