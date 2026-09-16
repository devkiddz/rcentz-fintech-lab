<x-admin-layout>
<div class="ui-page max-w-[1500px]" data-market-runtime>
    <section class="ui-page-header !mb-5">
        <div class="min-w-0">
            <p class="ui-kicker">Admin · Trading Command</p>
            <h1 class="ui-heading">Trading Desk</h1>
            <p class="ui-lead max-w-3xl">
                Select an instrument, inspect its price context, then open the execution desk for
                Strategy Trade, Admin Direct Trade or Trade for User.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.settings.index', ['section' => 'market']) }}" class="ui-btn ui-btn-primary">
                <i data-lucide="sliders-horizontal" class="h-4 w-4"></i>
                Market Settings
            </a>

            <a href="{{ route('admin.trading.index') }}" class="ui-btn ui-btn-secondary">
                <i data-lucide="layout-dashboard" class="h-4 w-4"></i>
                Trading Overview
            </a>

            <a href="{{ route('admin.trading.positions') }}" class="ui-btn ui-btn-secondary">
                <i data-lucide="target" class="h-4 w-4"></i>
                Open Positions
            </a>

            <a href="{{ route('admin.trading.history') }}" class="ui-btn ui-btn-secondary">
                <i data-lucide="history" class="h-4 w-4"></i>
                Trade History
            </a>
        </div>
    </section>

    <section class="ui-panel mb-4 p-4">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div class="flex flex-wrap gap-2">
                <span class="inline-flex items-center gap-2 rounded-lg border border-border bg-muted/40 px-3 py-2 text-[11px] font-medium">
                    <i data-lucide="workflow" class="h-4 w-4 text-muted-foreground"></i>
                    Strategy Trade
                </span>
                <span class="inline-flex items-center gap-2 rounded-lg border border-border bg-muted/40 px-3 py-2 text-[11px] font-medium">
                    <i data-lucide="shield" class="h-4 w-4 text-muted-foreground"></i>
                    Admin Direct Trade
                </span>
                <span class="inline-flex items-center gap-2 rounded-lg border border-border bg-muted/40 px-3 py-2 text-[11px] font-medium">
                    <i data-lucide="user-round-cog" class="h-4 w-4 text-muted-foreground"></i>
                    Trade for User
                </span>
            </div>

            <div class="relative w-full xl:w-[320px]">
                <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"></i>
                <input
                    id="market-search"
                    type="search"
                    class="ui-input w-full pl-9"
                    placeholder="Search symbol or company..."
                    autocomplete="off">
            </div>
        </div>
    </section>

    <section class="ui-panel overflow-hidden">
        <div class="border-b border-border px-4 py-3">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-[.12em] text-muted-foreground">
                        Active Markets
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{ $stocks->total() }} tradable {{ Str::plural('asset', $stocks->total()) }}
                    </p>
                </div>

                <div class="flex items-center gap-2 text-[10px] text-muted-foreground">
                    <span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
                    Available for desk execution
                </div>
            </div>
        </div>

        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left">
                <thead class="border-b border-border bg-muted/20">
                    <tr class="text-[9px] uppercase tracking-[.12em] text-muted-foreground">
                        <th class="px-4 py-3 font-medium">Market</th>
                        <th class="px-4 py-3 font-medium">Company</th>
                        <th class="px-4 py-3 font-medium">Current Price</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>

                <tbody id="market-table-body" class="divide-y divide-border">
                    @forelse($stocks as $stock)
                        <tr
                            class="market-row transition-colors hover:bg-muted/20"
                            data-market="{{ strtolower($stock->symbol.' '.$stock->name) }}">
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-lg border border-border bg-muted/30 text-[11px] font-bold">
                                        {{ substr($stock->symbol,0,2) }}
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold">{{ $stock->symbol }}</p>
                                        <p class="mt-0.5 text-[9px] text-muted-foreground">US Equity</p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-4">
                                <p class="max-w-[280px] truncate text-xs font-medium">{{ $stock->name }}</p>
                            </td>

                            <td class="px-4 py-4">
                                <p class="text-xs font-semibold"
                                   data-market-price-symbol="{{ $stock->symbol }}"
                                   data-marketplace="{{ app(\App\Services\MarketPriceRouter::class)->activeMarketplace() }}">
                                    {{ currency_symbol() }}{{ number_format((float)$stock->current_price,2) }}
                                </p>
                            </td>

                            <td class="px-4 py-4">
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2 py-1 text-[9px] font-semibold text-emerald-600">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    Active
                                </span>
                            </td>

                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-2">
                                    <a
                                        href="{{ route('admin.stocks.show',$stock) }}"
                                        class="ui-btn ui-btn-secondary !h-8 !px-3 !text-[10px]">
                                        <i data-lucide="scan-search" class="h-3.5 w-3.5"></i>
                                        Inspect
                                    </a>

                                    <a
                                        href="{{ route('admin.stocks.trade',$stock) }}"
                                        class="ui-btn ui-btn-primary !h-8 !px-3 !text-[10px]">
                                        <i data-lucide="candlestick-chart" class="h-3.5 w-3.5"></i>
                                        Open Desk
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-xs text-muted-foreground">
                                No active stocks are available for trading.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div id="market-card-list" class="divide-y divide-border md:hidden">
            @forelse($stocks as $stock)
                <div
                    class="market-row p-4"
                    data-market="{{ strtolower($stock->symbol.' '.$stock->name) }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-border bg-muted/30 text-[11px] font-bold">
                                {{ substr($stock->symbol,0,2) }}
                            </div>

                            <div class="min-w-0">
                                <p class="text-xs font-semibold">{{ $stock->symbol }}</p>
                                <p class="mt-1 truncate text-[10px] text-muted-foreground">{{ $stock->name }}</p>
                            </div>
                        </div>

                        <p class="shrink-0 text-xs font-semibold"
                           data-market-price-symbol="{{ $stock->symbol }}"
                           data-marketplace="{{ app(\App\Services\MarketPriceRouter::class)->activeMarketplace() }}">
                            {{ currency_symbol() }}{{ number_format((float)$stock->current_price,2) }}
                        </p>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-2">
                        <a href="{{ route('admin.stocks.show',$stock) }}" class="ui-btn ui-btn-secondary justify-center">
                            Inspect
                        </a>
                        <a href="{{ route('admin.stocks.trade',$stock) }}" class="ui-btn ui-btn-primary justify-center">
                            Open Desk
                        </a>
                    </div>
                </div>
            @empty
                <div class="p-10 text-center text-xs text-muted-foreground">
                    No active stocks are available for trading.
                </div>
            @endforelse
        </div>

        <div id="market-empty-filter" class="hidden px-4 py-12 text-center text-xs text-muted-foreground">
            No markets match your search.
        </div>
    </section>

    <div class="mt-4">
        {{ $stocks->links() }}
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('market-search');
    if (!input) return;

    const rows = Array.from(document.querySelectorAll('.market-row'));
    const empty = document.getElementById('market-empty-filter');

    input.addEventListener('input', () => {
        const query = input.value.trim().toLowerCase();
        let visible = 0;

        rows.forEach((row) => {
            const match = !query || row.dataset.market.includes(query);
            row.classList.toggle('hidden', !match);
            if (match) visible++;
        });

        empty?.classList.toggle('hidden', visible !== 0);
    });
});
</script>
</x-admin-layout>
