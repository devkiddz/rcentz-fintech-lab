<x-admin-layout>
<div class="ui-page max-w-[1500px]">
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker">Admin · Settings</p>
            <h1 class="ui-heading">Integrations</h1>
            <p class="ui-lead max-w-3xl">External providers are indexed here while their operational management remains in the domain that owns them.</p>
        </div>
    </section>

    <main class="min-w-0">
            @include('admin.settings.partials.flash')

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div class="ui-panel p-5">
                    <div class="flex items-center justify-between"><i data-lucide="radio-tower" class="h-4 w-4"></i><span class="text-[8px] uppercase tracking-[.1em] text-muted-foreground">Market data</span></div>
                    <h2 class="mt-4 text-sm font-semibold">External Price Feed</h2>
                    <p class="mt-1 text-[10px] text-muted-foreground">Finnhub: {{ $marketHealth['finnhub_available'] ? 'Configured' : 'Unavailable' }} · Yahoo: {{ $marketHealth['yahoo_available'] ? 'Available' : 'Unavailable' }}</p>
                    <a href="{{ route('admin.settings.index', ['section' => 'market']) }}" class="ui-btn ui-btn-secondary mt-4 w-full justify-center">Market Settings</a>
                </div>

                <div class="ui-panel p-5">
                    <div class="flex items-center justify-between"><i data-lucide="wallet-cards" class="h-4 w-4"></i><span class="text-[8px] uppercase tracking-[.1em] text-muted-foreground">Payments</span></div>
                    <h2 class="mt-4 text-sm font-semibold">Payment Methods</h2>
                    <p class="mt-1 text-[10px] text-muted-foreground">Manage the payment rails already supported by the platform.</p>
                    <a href="{{ route('admin.payment_methods.index') }}" class="ui-btn ui-btn-secondary mt-4 w-full justify-center">Payment Methods</a>
                </div>

                <div class="ui-panel p-5">
                    <div class="flex items-center justify-between"><i data-lucide="mail" class="h-4 w-4"></i><span class="text-[8px] uppercase tracking-[.1em] text-muted-foreground">Communication</span></div>
                    <h2 class="mt-4 text-sm font-semibold">Mail Transport</h2>
                    <p class="mt-1 text-[10px] text-muted-foreground">Inspect runtime mail configuration and verify delivery.</p>
                    <a href="{{ route('admin.settings.index', ['section' => 'mail']) }}" class="ui-btn ui-btn-secondary mt-4 w-full justify-center">Mail Settings</a>
                </div>
            </section>
    </main>
</div>
</x-admin-layout>
