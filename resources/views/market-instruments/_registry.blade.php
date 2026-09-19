@php
    $routeNames = $admin
        ? ['index' => 'admin.instruments.index', 'stock' => 'admin.instruments.stocks', 'forex' => 'admin.instruments.forex', 'crypto' => 'admin.instruments.crypto']
        : ['index' => 'instruments.index', 'stock' => 'instruments.stocks', 'forex' => 'instruments.forex', 'crypto' => 'instruments.crypto'];

    $tabs = [
        [null, 'Overview', 'layout-grid'],
        ['stock', 'Stocks', 'chart-no-axes-combined'],
        ['forex', 'Forex', 'arrow-left-right'],
        ['crypto', 'Crypto', 'bitcoin'],
    ];

    // Product order is intentional: Stocks → Forex → Crypto.
    // Do not alphabetically sort these groups; the customer market surface
    // follows the platform's liquid-instrument hierarchy.
    $groups = $scope
        ? collect([$scope => $instruments])
        : collect([
            'stock' => $instruments->where('asset_class', 'stock')->values(),
            'forex' => $instruments->where('asset_class', 'forex')->values(),
            'crypto' => $instruments->where('asset_class', 'crypto')->values(),
        ])->filter(fn ($group) => $group->isNotEmpty());

    $groupLabels = ['stock' => 'Stocks', 'forex' => 'Forex', 'crypto' => 'Crypto'];
    $groupIcons = ['stock' => 'chart-no-axes-combined', 'forex' => 'arrow-left-right', 'crypto' => 'bitcoin'];

    $formatPrice = function ($instrument, $price) {
        if ($price === null) return '—';
        $precision = max(0, min(8, (int) $instrument->price_precision));
        $prefix = $instrument->asset_class === 'stock' ? currency_symbol() : '';
        $suffix = $instrument->asset_class === 'stock' ? '' : ' '.($instrument->quote_asset ?: '');
        return $prefix.number_format((float)$price, $precision).$suffix;
    };
@endphp

<div class="ui-page" data-market-runtime>
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker text-[10px]">Markets</p>
            <h1 class="ui-heading !text-2xl">{{ $title }}</h1>
            <p class="ui-lead !max-w-3xl !text-[13px]">{{ $description }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if(!$admin)
                <a href="{{ route('broker.portfolio') }}" class="ui-btn ui-btn-secondary"><i data-lucide="briefcase-business" class="h-4 w-4"></i>Portfolio</a>
                <a href="{{ route('broker.orders') }}" class="ui-btn ui-btn-secondary"><i data-lucide="list-ordered" class="h-4 w-4"></i>Orders</a>
            @endif
            <div class="rounded-xl border border-border bg-muted/15 px-3 py-2 text-right">
                <p class="text-[10px] font-semibold uppercase tracking-[.11em] text-muted-foreground">Price environment</p>
                <p class="mt-1 text-[11px] font-semibold uppercase">{{ $marketplace === 'controlled' ? 'Internal / Controlled' : 'External / Live' }}</p>
            </div>
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

    <section class="mt-5 grid grid-cols-2 gap-3 md:grid-cols-5">
        @foreach([
            ['Total',$counts['total'],'layers-3'],
            ['Active',$counts['active'],'activity'],
            ['Stocks',$counts['stock'],'chart-no-axes-combined'],
            ['Forex',$counts['forex'],'arrow-left-right'],
            ['Crypto',$counts['crypto'],'bitcoin'],
        ] as [$label,$value,$icon])
            <div class="ui-panel p-4">
                <div class="flex items-center justify-between gap-3">
                    <div><p class="text-[10px] font-semibold uppercase tracking-[.11em] text-muted-foreground">{{ $label }}</p><p class="mt-2 text-xl font-semibold tabular-nums">{{ number_format($value) }}</p></div>
                    <div class="rounded-lg border border-border bg-muted/40 p-2"><i data-lucide="{{ $icon }}" class="h-4 w-4"></i></div>
                </div>
            </div>
        @endforeach
    </section>

    <div class="mt-5 space-y-5">
        @forelse($groups as $assetClass => $group)
            @php
                $assetLabel = $groupLabels[$assetClass] ?? ucfirst((string)$assetClass);
                $assetIcon = $groupIcons[$assetClass] ?? 'scan-line';
            @endphp
            <section class="ui-panel overflow-hidden">
                <div class="border-b border-border/70 px-5 py-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="rounded-xl border border-border bg-muted/40 p-2.5"><i data-lucide="{{ $assetIcon }}" class="h-4 w-4"></i></div>
                            <div><p class="ui-kicker">{{ $assetLabel }}</p><h2 class="mt-1 text-[15px] font-semibold">{{ number_format($group->count()) }} listed {{ strtolower($assetLabel) }} instruments</h2></div>
                        </div>
                        <p class="max-w-lg text-right text-[10px] leading-5 text-muted-foreground">Displayed prices are market marks. A customer order receives a separately validated executable quote before capital can move.</p>
                    </div>
                </div>

                <div class="divide-y divide-border/70">
                    @foreach($group as $instrument)
                        @php
                            $stock = $instrument->canonicalStock ?: $instrument->stock;
                            $viewRoute = match($instrument->asset_class) {
                                'stock' => $stock
                                    ? ($admin ? route('admin.instruments.stocks.show', ['stock'=>$stock->symbol]) : route('instruments.stocks.show', ['stock'=>$stock->symbol]))
                                    : ($admin ? route('admin.instruments.show',$instrument) : route('instruments.show',$instrument)),
                                'forex' => $admin ? route('admin.instruments.forex.show',['symbol'=>$instrument->symbol]) : route('instruments.forex.show',['symbol'=>$instrument->symbol]),
                                'crypto' => $admin ? route('admin.instruments.crypto.show',['symbol'=>$instrument->symbol]) : route('instruments.crypto.show',['symbol'=>$instrument->symbol]),
                                default => $admin ? route('admin.instruments.show',$instrument) : route('instruments.show',$instrument),
                            };
                            $current = $instrument->runtime_price;
                            $previous = $instrument->runtime_previous;
                            $movePct = ($current !== null && $previous !== null && (float)$previous > 0)
                                ? (((float)$current - (float)$previous) / (float)$previous) * 100
                                : null;
                            $ready = (bool) $instrument->runtime_executable;
                        @endphp

                        <div class="grid gap-4 px-5 py-4 transition-colors hover:bg-muted/20 lg:grid-cols-[minmax(260px,1.45fr)_minmax(150px,.75fr)_minmax(120px,.6fr)_minmax(150px,.75fr)_minmax(150px,.75fr)_auto] lg:items-center">
                            <div class="flex min-w-0 items-center gap-3">
                                <div class="flex h-11 min-w-14 items-center justify-center rounded-xl border border-border bg-muted/60 px-2 text-[11px] font-semibold">{{ $instrument->symbol }}</div>
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-full border border-border bg-muted/20 px-2 py-1 text-[9px] font-semibold uppercase tracking-[.1em]">{{ strtoupper($instrument->asset_class) }}</span>
                                        @if($instrument->is_featured)<span class="text-[9px] font-semibold uppercase tracking-[.1em] text-sky-500">Featured</span>@endif
                                    </div>
                                    <p class="mt-1 text-[13px] font-semibold">{{ $instrument->display_symbol }}</p>
                                    <p class="mt-0.5 truncate text-[10px] text-muted-foreground">{{ $instrument->name }}</p>
                                </div>
                            </div>

                            <div><p class="text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">Market price</p><p class="mt-1 text-[13px] font-semibold tabular-nums" data-market-price-instrument="{{ $instrument->id }}" data-marketplace="{{ $marketplace }}">{{ $formatPrice($instrument,$current) }}</p><p class="mt-0.5 text-[9px] text-muted-foreground">{{ strtoupper($marketplace) }} mark</p></div>
                            <div><p class="text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">Move</p><p class="mt-1 text-[13px] font-semibold {{ $movePct===null?'text-muted-foreground':($movePct>=0?'text-emerald-600':'text-red-600') }}">{{ $movePct===null?'—':(($movePct>=0?'+':'').number_format($movePct,2).'%') }}</p><p class="mt-0.5 text-[9px] text-muted-foreground">vs previous</p></div>
                            <div><p class="text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">Market</p><p class="mt-1 text-[12px] font-semibold">{{ $instrument->market ?: 'Global' }}</p><p class="mt-0.5 text-[9px] text-muted-foreground">{{ $instrument->base_asset ?: '—' }}{{ $instrument->quote_asset ? ' / '.$instrument->quote_asset : '' }}</p></div>
                            <div><p class="text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">Trading</p><p class="mt-1 text-[11px] font-semibold {{ $ready?'text-emerald-600':'text-amber-600' }}">{{ $ready ? 'READY' : 'UNAVAILABLE' }}</p><p class="mt-0.5 truncate text-[9px] text-muted-foreground">{{ $ready ? 'Order routing available' : ($instrument->runtime_execution_reason ?: 'Execution adapter unavailable') }}</p></div>
                            <div class="flex flex-wrap gap-2 lg:justify-end">
                                <a href="{{ $viewRoute }}" class="ui-btn ui-btn-secondary ui-btn-sm whitespace-nowrap">View Asset</a>
                                @if(!$admin && $ready)
                                    <a href="{{ route('broker.workstation',['assetClass'=>$instrument->asset_class,'symbol'=>$instrument->symbol]) }}" class="ui-btn ui-btn-ghost ui-btn-sm whitespace-nowrap">Trade<i data-lucide="arrow-up-right" class="h-3.5 w-3.5"></i></a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @empty
            <section class="ui-panel px-5 py-12 text-center"><p class="text-sm font-semibold">No instruments registered.</p><p class="mt-1 text-[11px] text-muted-foreground">The market registry is currently empty.</p></section>
        @endforelse
    </div>
</div>
