@php
    // M3.1 canonical asset routes under /instruments/{asset-class}/...
    $routeNames = $admin
        ? ['index' => 'admin.instruments.index', 'stock' => 'admin.instruments.stocks', 'forex' => 'admin.instruments.forex', 'crypto' => 'admin.instruments.crypto']
        : ['index' => 'instruments.index', 'stock' => 'instruments.stocks', 'forex' => 'instruments.forex', 'crypto' => 'instruments.crypto'];

    $tabs = [
        [null, 'Overview', 'layout-grid'],
        ['stock', 'Stocks', 'chart-no-axes-combined'],
        ['forex', 'Forex', 'arrow-left-right'],
        ['crypto', 'Crypto', 'bitcoin'],
    ];

    $scopeLabel = match($scope) {
        'stock' => 'Stocks',
        'forex' => 'Forex',
        'crypto' => 'Crypto',
        default => 'All Instruments',
    };

    $formatPrice = function ($instrument, $price) {
        if ($price === null) return '—';
        $prefix = $instrument->asset_class === 'stock' ? currency_symbol() : '';
        return $prefix.number_format((float)$price, (int)$instrument->price_precision);
    };
@endphp

<div class="ui-page max-w-[1600px]" data-market-runtime>
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker text-[10px]">Markets & Intelligence</p>
            <h1 class="ui-heading !text-2xl">{{ $title }}</h1>
            <p class="ui-lead !max-w-3xl !text-[13px]">{{ $description }}</p>
        </div>
        <div class="rounded-xl border border-border bg-muted/15 px-3 py-2 text-right">
            <p class="text-[10px] font-semibold uppercase tracking-[.11em] text-muted-foreground">Active price source</p>
            <p class="mt-1 text-[11px] font-semibold uppercase">{{ $marketplace === 'controlled' ? 'Internal / Controlled' : 'External / Live' }}</p>
        </div>
    </section>

    <div class="mt-4 flex flex-wrap gap-2">
        @foreach($tabs as [$key,$label,$icon])
            @php $name = $key ? $routeNames[$key] : $routeNames['index']; @endphp
            <a href="{{ route($name) }}" class="ui-btn {{ $scope === $key ? 'ui-btn-primary' : 'ui-btn-secondary' }}">
                <i data-lucide="{{ $icon }}" class="h-4 w-4"></i>{{ $label }}
            </a>
        @endforeach
    </div>

    <section class="mt-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach([
            ['Total Instruments',$counts['total'],'layers-3'],
            ['Active',$counts['active'],'activity'],
            ['Stocks',$counts['stock'],'chart-no-axes-combined'],
            [$scope === 'crypto' ? 'Crypto' : 'Forex',$scope === 'crypto' ? $counts['crypto'] : $counts['forex'],$scope === 'crypto' ? 'bitcoin' : 'arrow-left-right'],
        ] as [$label,$value,$icon])
            <div class="ui-panel p-4">
                <div class="flex items-center justify-between gap-3">
                    <div><p class="text-[10px] font-semibold uppercase tracking-[.11em] text-muted-foreground">{{ $label }}</p><p class="mt-2 text-xl font-semibold tabular-nums">{{ number_format($value) }}</p></div>
                    <div class="rounded-lg border border-border bg-muted/40 p-2"><i data-lucide="{{ $icon }}" class="h-4 w-4"></i></div>
                </div>
            </div>
        @endforeach
    </section>

    <section class="ui-panel mt-5 overflow-hidden">
        <div class="border-b border-border/70 px-4 py-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="ui-kicker">Listed market</p>
                    <h2 class="mt-1 text-[14px] font-semibold">{{ $scopeLabel }}</h2>
                    <p class="mt-1 text-[11px] text-muted-foreground">Every row resolves price through the same MarketInstrument authority used by runtime charts and Signals.</p>
                </div>
                <span class="rounded-full border border-border bg-muted/20 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[.11em] text-muted-foreground">MarketInstrument Authority</span>
            </div>
        </div>

        <div class="divide-y divide-border/70">
            @forelse($instruments as $instrument)
                @php
                    $stock = $instrument->canonicalStock ?: $instrument->stock;

                    $viewRoute = match($instrument->asset_class) {
                        'stock' => $stock
                            ? ($admin
                                ? route('admin.instruments.stocks.show', ['stock' => $stock->symbol])
                                : route('instruments.stocks.show', ['stock' => $stock->symbol]))
                            : ($admin ? route('admin.instruments.show', $instrument) : route('instruments.show', $instrument)),
                        'forex' => $admin
                            ? route('admin.instruments.forex.show', ['symbol' => $instrument->symbol])
                            : route('instruments.forex.show', ['symbol' => $instrument->symbol]),
                        'crypto' => $admin
                            ? route('admin.instruments.crypto.show', ['symbol' => $instrument->symbol])
                            : route('instruments.crypto.show', ['symbol' => $instrument->symbol]),
                        default => $admin ? route('admin.instruments.show', $instrument) : route('instruments.show', $instrument),
                    };

                    $priceText = $formatPrice($instrument, $instrument->runtime_price);
                @endphp

                <div class="grid gap-4 px-4 py-4 transition-colors hover:bg-muted/20 lg:grid-cols-[minmax(260px,1.5fr)_minmax(150px,.75fr)_minmax(130px,.7fr)_minmax(150px,.8fr)_auto] lg:items-center">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="flex h-11 min-w-11 items-center justify-center rounded-xl border border-border bg-muted/60 px-2 text-[11px] font-semibold">{{ $instrument->symbol }}</div>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center gap-1 rounded-full border px-2 py-1 text-[10px] font-semibold uppercase tracking-[.11em] {{ $instrument->is_active ? 'border-emerald-500/25 bg-emerald-500/10 text-emerald-400' : 'border-border bg-muted text-muted-foreground' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $instrument->is_active ? 'bg-emerald-400' : 'bg-muted-foreground' }}"></span>{{ $instrument->is_active ? 'Active' : 'Inactive' }}
                                </span>
                                @if($instrument->is_featured)<span class="text-[10px] font-semibold uppercase tracking-[.11em] text-sky-300">Featured</span>@endif
                            </div>
                            <p class="mt-1 text-[13px] font-semibold">{{ $instrument->display_symbol }}</p>
                            <p class="mt-0.5 truncate text-[10px] text-muted-foreground">{{ $instrument->name }}</p>
                        </div>
                    </div>

                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-[.11em] text-muted-foreground">Market Price</p>
                        <p class="mt-1 text-[13px] font-semibold tabular-nums"
                           data-market-price-instrument="{{ $instrument->id }}"
                           data-marketplace="{{ $marketplace }}">{{ $priceText }}</p>
                        <p class="mt-0.5 text-[10px] text-muted-foreground">{{ strtoupper($marketplace) }} · {{ strtoupper($instrument->asset_class) }}</p>
                    </div>

                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-[.11em] text-muted-foreground">Market</p>
                        <p class="mt-1 text-[13px] font-semibold">{{ $instrument->market ?: '—' }}</p>
                        <p class="mt-0.5 text-[10px] text-muted-foreground">{{ $instrument->base_asset ?: '—' }}{{ $instrument->quote_asset ? ' / '.$instrument->quote_asset : '' }}</p>
                    </div>

                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-[.11em] text-muted-foreground">Precision</p>
                        <p class="mt-1 text-[13px] font-semibold tabular-nums">{{ $instrument->price_precision }}</p>
                        <p class="mt-0.5 text-[10px] text-muted-foreground">Tick / Pip {{ $instrument->pip_size ?: 'auto' }}</p>
                    </div>

                    <div class="flex lg:justify-end">
                        <a href="{{ $viewRoute }}" class="ui-btn ui-btn-secondary whitespace-nowrap">View Asset<i data-lucide="arrow-up-right" class="h-4 w-4"></i></a>
                    </div>
                </div>
            @empty
                <div class="px-4 py-12 text-center">
                    <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl border border-border bg-muted/40"><i data-lucide="scan-line" class="h-4 w-4 text-muted-foreground"></i></div>
                    <p class="mt-3 text-xs font-semibold">No {{ strtolower($scopeLabel) }} registered yet.</p>
                    <p class="mt-1 text-[11px] text-muted-foreground">This workspace is ready and will populate from MarketInstrument when the asset class is seeded.</p>
                </div>
            @endforelse
        </div>
    </section>
</div>
