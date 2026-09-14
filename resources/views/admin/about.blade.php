<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-foreground">About Platform</h2>
    </x-slot>

    <div class="mx-auto max-w-4xl py-4">
        <section class="ui-panel overflow-hidden">
            <div class="border-b border-border px-6 py-6 sm:px-8">
                <div class="flex items-start gap-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-tesla-600/10 text-tesla-600 dark:text-tesla-400">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-semibold text-foreground">{{ site_name() }}</h1>
                        <p class="mt-1 max-w-2xl text-sm leading-6 text-muted-foreground">A Laravel application for vehicle purchasing, wallets, investments, stock tracking, portfolio management, KYC, notifications, and administrative operations.</p>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 p-6 sm:grid-cols-2 sm:p-8">
                @foreach([
                    ['Customer platform', 'Account, KYC, wallet, purchases, investments, stocks, portfolio and support.'],
                    ['Administration', 'Users, KYC, vehicles, investment plans, holdings, transactions, email and settings.'],
                    ['Operations', 'Queued jobs and token-protected cron endpoints for market and maintenance tasks.'],
                    ['Configuration', 'Branding, mail, APIs and bootstrap credentials are environment-driven for deployment safety.'],
                ] as [$title, $description])
                    <article class="rounded-xl border border-border bg-muted/25 p-4">
                        <h3 class="text-sm font-semibold text-foreground">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-6 text-muted-foreground">{{ $description }}</p>
                    </article>
                @endforeach
            </div>
        </section>
    </div>
</x-admin-layout>
