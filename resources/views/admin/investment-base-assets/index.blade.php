<x-admin-layout>
<div class="ui-page max-w-[1400px]">
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker">Investments · Instruments</p>
            <h1 class="ui-heading">Base Assets</h1>
            <p class="ui-lead max-w-3xl">Investment products may reference approved Public Base Assets or RCENTZ-maintained Private Base Assets. Market Instruments are not Investment Base Assets until they are deliberately designated here.</p>
        </div>
        <a href="{{ route('admin.investments.control.index') }}" class="ui-btn ui-btn-secondary">Investment Products</a>
    </section>

    <section class="grid gap-4 md:grid-cols-2">
        <a href="{{ route('admin.investments.instruments.private.base-assets.index') }}" class="ui-panel block p-6 transition hover:border-red-500/30">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="ui-kicker">Private Authority</p>
                    <h2 class="mt-2 text-xl font-semibold">Private Base Assets</h2>
                    <p class="mt-2 max-w-xl text-xs leading-5 text-muted-foreground">Create and maintain internally valued underlying assets such as property, private businesses, private debt or other non-public references.</p>
                </div>
                <i data-lucide="building-2" class="h-5 w-5 text-muted-foreground"></i>
            </div>
            <div class="mt-6 grid grid-cols-2 gap-3 border-t border-border pt-4">
                <div><p class="text-[9px] uppercase text-muted-foreground">Total</p><p class="mt-1 text-lg font-semibold">{{ $stats['private_total'] }}</p></div>
                <div><p class="text-[9px] uppercase text-muted-foreground">Active</p><p class="mt-1 text-lg font-semibold">{{ $stats['private_active'] }}</p></div>
            </div>
        </a>

        <a href="{{ route('admin.investments.instruments.public.base-assets.index') }}" class="ui-panel block p-6 transition hover:border-red-500/30">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="ui-kicker">Public Authority</p>
                    <h2 class="mt-2 text-xl font-semibold">Public Base Assets</h2>
                    <p class="mt-2 max-w-xl text-xs leading-5 text-muted-foreground">Designate existing Market Instruments as approved investment underlyings. Pricing remains owned by the public market authority.</p>
                </div>
                <i data-lucide="chart-candlestick" class="h-5 w-5 text-muted-foreground"></i>
            </div>
            <div class="mt-6 grid grid-cols-2 gap-3 border-t border-border pt-4">
                <div><p class="text-[9px] uppercase text-muted-foreground">Designated</p><p class="mt-1 text-lg font-semibold">{{ $stats['public_total'] }}</p></div>
                <div><p class="text-[9px] uppercase text-muted-foreground">Active</p><p class="mt-1 text-lg font-semibold">{{ $stats['public_active'] }}</p></div>
            </div>
        </a>
    </section>

    <section class="ui-panel mt-4 p-5">
        <p class="ui-kicker">Authority Rule</p>
        <div class="mt-3 grid gap-3 lg:grid-cols-2">
            <div class="rounded-xl border border-border p-4">
                <p class="text-xs font-semibold">Public</p>
                <p class="mt-2 text-xs text-muted-foreground">Market Instrument → approved Public Base Asset → Investment Product.</p>
            </div>
            <div class="rounded-xl border border-border p-4">
                <p class="text-xs font-semibold">Private</p>
                <p class="mt-2 text-xs text-muted-foreground">Private Base Asset → RCENTZ valuation authority → Investment Product.</p>
            </div>
        </div>
    </section>
</div>
</x-admin-layout>
