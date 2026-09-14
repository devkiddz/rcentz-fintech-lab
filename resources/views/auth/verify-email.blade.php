<x-guest-layout>
    <x-slot name="title">Verify Email</x-slot>
    <section class="ui-panel overflow-hidden">
        <div class="px-6 py-6 text-center">
            <div class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-tesla-600/10 text-tesla-600 dark:text-tesla-400"><i data-lucide="mail-check" class="h-5 w-5"></i></div>
            <h1 class="text-xl font-semibold text-foreground">Verify your email</h1>
            <p class="mt-1 text-xs leading-5 text-muted-foreground">Open the verification link we sent to your inbox. You can request another one below.</p>
        </div>
        @if (session('status') === 'verification-link-sent')
            <div class="mx-6 mb-4 rounded-lg border border-green-200 bg-green-50 p-3 text-xs text-green-800 dark:border-green-900/50 dark:bg-green-950/30 dark:text-green-300">A new verification link has been sent.</div>
        @endif
        <div class="space-y-3 px-6 pb-6">
            <form method="POST" action="{{ route('verification.send') }}">@csrf<button type="submit" class="ui-button-primary w-full">Resend verification email</button></form>
            <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="ui-button-secondary w-full">Sign out</button></form>
        </div>
    </section>
</x-guest-layout>
