<x-guest-layout>
    <x-slot name="title">Reset Password</x-slot>
    <section class="ui-panel overflow-hidden">
        <div class="px-6 py-6 text-center">
            <h1 class="text-xl font-semibold text-foreground">Choose a new password</h1>
            <p class="mt-1 text-xs text-muted-foreground">Use a strong password you do not reuse elsewhere.</p>
        </div>
        <form method="POST" action="{{ route('password.store') }}" class="space-y-4 px-6 pb-6">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">
            <div>
                <label for="email" class="ui-label">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username" class="ui-input @error('email') border-red-500 @enderror">
                @error('email')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password" class="ui-label">New password</label>
                <input id="password" name="password" type="password" required autocomplete="new-password" class="ui-input @error('password') border-red-500 @enderror">
                @error('password')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password_confirmation" class="ui-label">Confirm new password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="ui-input">
            </div>
            <button type="submit" class="ui-button-primary w-full">Reset password</button>
        </form>
    </section>
</x-guest-layout>
