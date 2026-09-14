<x-guest-layout>
    <x-slot name="title">Confirm Password</x-slot>
    <section class="ui-panel overflow-hidden">
        <div class="px-6 py-6 text-center">
            <h1 class="text-xl font-semibold text-foreground">Confirm password</h1>
            <p class="mt-1 text-xs text-muted-foreground">Re-enter your password to continue to this secure area.</p>
        </div>
        <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4 px-6 pb-6">
            @csrf
            <div>
                <label for="password" class="ui-label">Password</label>
                <input id="password" name="password" type="password" required autocomplete="current-password" class="ui-input @error('password') border-red-500 @enderror">
                @error('password')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="ui-button-primary w-full">Confirm</button>
        </form>
    </section>
</x-guest-layout>
