<x-user-layout>
<x-slot name="header">{{ $categoryTitle ?? localize('ui.r2d.common.investments', 'Investments') }}</x-slot>

@php
    $categoryCards = [
        ['Stocks','investments.stocks','chart-candlestick','Stock-backed private instruments','stock_market'],
        ['Cryptocurrency','investments.crypto','coins','Digital asset baskets with internal valuation','cryptocurrency'],
        ['Real Estate','investments.real-estate','house','Property-backed private investment instruments','real_estate'],
        ['Bonds & Fixed Income','investments.bonds','landmark','Income-oriented private notes and pools','bonds'],
    ];
@endphp

<div class="mx-auto max-w-[1280px] space-y-6 px-3 py-5 sm:px-5 lg:px-6">
    <section class="overflow-hidden rounded-3xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
        <div class="grid gap-6 p-6 lg:grid-cols-[1.2fr_.8fr] lg:p-8">
            <div>
                <div class="flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-red-600"></span>
                    <p class="text-[10px] font-semibold uppercase tracking-[.18em] text-red-600">{{ localize('ui.r2d.investments.market_kicker', 'Private investment market') }}</p>
                </div>
                <h1 class="mt-3 max-w-3xl text-3xl font-semibold tracking-tight text-zinc-950 dark:text-white sm:text-4xl">
                    {{ $categoryTitle ?? localize('ui.r2d.investments.hero', 'One investment market. Multiple asset classes.') }}
                </h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                    {{ localize('ui.r2d.investments.hero_lead', 'Explore private investment opportunities across multiple asset classes. Platform-recorded investment prices remain authoritative for customer units and performance.') }}
                </p>
                <div class="mt-5 flex flex-wrap gap-2">
                    <a href="{{ route('account.investments') }}" class="rounded-xl bg-red-600 px-4 py-2 text-xs font-semibold text-white hover:bg-red-700">{{ localize('ui.r2d.common.my_investments', 'My Investments') }}</a>
                    <a href="{{ route('account.investments.portfolio') }}" class="rounded-xl border border-zinc-200 px-4 py-2 text-xs font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-900">{{ localize('ui.r2d.common.portfolio', 'Portfolio') }}</a>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                @foreach([
                    [localize('ui.r2d.investments.instruments', 'Instruments'),$overview['instruments'] ?? \App\Models\PrivateInvestmentInstrument::where('is_visible',true)->count()],
                    [localize('ui.r2d.investments.asset_classes', 'Asset classes'),$overview['categories'] ?? \App\Models\PrivateInvestmentInstrument::where('is_visible',true)->distinct('category')->count('category')],
                    [localize('ui.r2d.investments.featured', 'Featured'),$overview['featured'] ?? \App\Models\PrivateInvestmentInstrument::where('is_featured',true)->count()],
                    [localize('ui.r2d.investments.underlying', 'Underlying'),currency_symbol().number_format($overview['underlying_valuation'] ?? \App\Models\PrivateInvestmentAsset::where('status','active')->sum('current_valuation'),0)],
                ] as [$label,$value])
                    <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-900">
                        <p class="text-[9px] uppercase tracking-[.12em] text-zinc-400">{{ $label }}</p>
                        <p class="mt-2 text-lg font-semibold tabular-nums text-zinc-950 dark:text-white">{{ $value }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    @php
        $tapeInstruments = \App\Models\PrivateInvestmentInstrument::query()
            ->where('is_visible', true)
            ->whereIn('status', ['active', 'paused'])
            ->orderByDesc('is_featured')
            ->orderBy('symbol')
            ->get();
    @endphp

    @if($tapeInstruments->isNotEmpty())
        <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-zinc-950 shadow-sm dark:border-zinc-800"
                 aria-label="Investment listings tape">
            <div class="flex items-center justify-between border-b border-white/10 px-4 py-2.5">
                <div class="flex items-center gap-2">
                    <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                    <p class="text-[9px] font-semibold uppercase tracking-[.16em] text-zinc-400">{{ localize('ui.r2d.investments.listings', 'Investment listings') }}</p>
                </div>
                <p class="text-[9px] text-zinc-500">{{ localize('ui.r2d.investments.price_authority', 'Private Investment Price Authority') }}</p>
            </div>

            <div class="investment-tape-window relative overflow-hidden">
                <div class="investment-tape-track flex min-w-max items-stretch">
                    @for($pass = 0; $pass < 2; $pass++)
                        @foreach($tapeInstruments as $tapeInstrument)
                            @php $tapeMove = (float) $tapeInstrument->change_percent; @endphp
                            <a href="{{ route('investments.show', $tapeInstrument->slug) }}"
                               class="flex min-w-[210px] items-center justify-between gap-5 border-r border-white/10 px-4 py-3 transition hover:bg-white/[.04]">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-semibold text-white">{{ $tapeInstrument->symbol }}</span>
                                        <span class="rounded-full bg-white/[.06] px-1.5 py-0.5 text-[8px] font-medium uppercase tracking-[.08em] text-zinc-400">
                                            {{ str_replace(['stock_market','cryptocurrency','real_estate','bonds'], ['Stocks','Crypto','Real Estate','Bonds'], $tapeInstrument->category) }}
                                        </span>
                                    </div>
                                    <p class="mt-1 max-w-[120px] truncate text-[9px] text-zinc-500">{{ $tapeInstrument->name }}</p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="text-xs font-semibold tabular-nums text-white">{{ currency_symbol() }}{{ number_format((float)$tapeInstrument->current_price, 2) }}</p>
                                    <p class="mt-0.5 text-[9px] font-semibold {{ $tapeMove >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                                        {{ $tapeMove >= 0 ? '+' : '' }}{{ number_format($tapeMove, 2) }}%
                                    </p>
                                </div>
                            </a>
                        @endforeach
                    @endfor
                </div>
            </div>

            <style>
                @keyframes investmentTapeScroll {
                    from { transform: translateX(0); }
                    to { transform: translateX(-50%); }
                }
                .investment-tape-track {
                    width: max-content;
                    animation: investmentTapeScroll 42s linear infinite;
                }
                .investment-tape-window:hover .investment-tape-track {
                    animation-play-state: paused;
                }
                @media (prefers-reduced-motion: reduce) {
                    .investment-tape-window {
                        overflow-x: auto;
                    }
                    .investment-tape-track {
                        animation: none;
                    }
                }
            </style>
        </section>
    @endif

    <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        @foreach($categoryCards as [$label,$routeName,$icon,$copy,$categoryKey])
            <a href="{{ route($routeName) }}" class="group rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-red-200 dark:border-zinc-800 dark:bg-zinc-950">
                <div class="flex items-center justify-between">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-red-50 text-red-600 dark:bg-red-950/30"><i data-lucide="{{ $icon }}" class="h-4 w-4"></i></span>
                    <span class="text-[10px] font-semibold text-zinc-400">{{ localize('ui.r2d.investments.products_count', ':count products', ['count' => $categoryStats[$categoryKey] ?? \App\Models\PrivateInvestmentInstrument::where('category',$categoryKey)->where('is_visible',true)->count()]) }}</span>
                </div>
                <h2 class="mt-4 text-sm font-semibold text-zinc-950 group-hover:text-red-600 dark:text-white">{{ $label }}</h2>
                <p class="mt-1 text-[11px] leading-5 text-zinc-500">{{ $copy }}</p>
            </a>
        @endforeach
    </section>

    @if(!empty($featured) && $featured->isNotEmpty())
        <section>
            <div class="mb-3 flex items-end justify-between"><div><p class="text-[10px] font-semibold uppercase tracking-[.14em] text-red-600">{{ localize('ui.r2d.investments.featured', 'Featured') }}</p><h2 class="mt-1 text-xl font-semibold">{{ localize('ui.r2d.investments.featured_opportunities', 'Featured opportunities') }}</h2></div></div>
            <div class="grid gap-4 lg:grid-cols-2">
                @foreach($featured as $instrument)
                    @php $fChange=(float)$instrument->change_percent; @endphp
                    <a href="{{ route('investments.show',$instrument->slug) }}" class="group overflow-hidden rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm transition hover:border-red-200 dark:border-zinc-800 dark:bg-zinc-950">
                        <div class="flex items-start justify-between gap-4">
                            <div><span class="rounded-full bg-red-50 px-2 py-1 text-[9px] font-semibold text-red-600 dark:bg-red-950/30">{{ $instrument->symbol }}</span><h3 class="mt-3 text-base font-semibold group-hover:text-red-600">{{ $instrument->name }}</h3><p class="mt-1 text-xs text-zinc-500">{{ ucwords(str_replace('_',' ',$instrument->category)) }}</p></div>
                            <div class="text-right"><p class="text-xl font-semibold">{{ currency_symbol() }}{{ number_format((float)$instrument->current_price,2) }}</p><p class="mt-1 text-xs font-semibold {{ $fChange >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $fChange >= 0 ? '+' : '' }}{{ number_format($fChange,2) }}%</p></div>
                        </div>
                        <p class="mt-4 line-clamp-2 text-xs leading-5 text-zinc-500">{{ $instrument->description }}</p>
                        @php $featuredStory = app(\App\Services\PrivateInvestmentProjectionService::class)->forInstrument($instrument, (float)$instrument->minimum_investment); @endphp
                        <div class="mt-4 flex flex-wrap gap-2 text-[9px]">
                            <span class="rounded-full bg-zinc-100 px-2 py-1 dark:bg-zinc-900">{{ $featuredStory['duration_label'] }}</span>
                            <span class="rounded-full bg-zinc-100 px-2 py-1 dark:bg-zinc-900">{{ $featuredStory['cycle_return_label'] }} / {{ $featuredStory['return_interval_label'] }}</span>
                            <span class="rounded-full bg-zinc-100 px-2 py-1 dark:bg-zinc-900">{{ localize('ui.r2d.investments.profit', 'Profit') }} {{ currency_symbol() }}{{ number_format($featuredStory['net_term_min_profit'],0) }}–{{ currency_symbol() }}{{ number_format($featuredStory['net_term_max_profit'],0) }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <div class="grid gap-5 xl:grid-cols-[1fr_320px]">
        <section>
            <form method="GET" action="{{ url()->current() }}" class="mb-4 flex flex-col gap-3 rounded-2xl border border-zinc-200 bg-white p-3 shadow-sm dark:border-zinc-800 dark:bg-zinc-950 md:flex-row md:items-center">
                <input name="search" value="{{ request('search') }}" placeholder="{{ localize('ui.r2d.investments.search_placeholder', 'Search investments...') }}" class="ui-input flex-1">
                @if(!isset($categoryTitle))
                    <select name="category" class="ui-input md:max-w-[220px]"><option value="">{{ localize('ui.r2d.investments.all_asset_classes', 'All asset classes') }}</option>@foreach($categories as $category)<option value="{{ $category }}" @selected(request('category')===$category)>{{ ucwords(str_replace('_',' ',$category)) }}</option>@endforeach</select>
                @endif
                <select name="risk_level" class="ui-input md:max-w-[180px]"><option value="">{{ localize('ui.r2d.investments.all_risk_levels', 'All risk levels') }}</option>@foreach(['low','medium','high','very_high'] as $risk)<option value="{{ $risk }}" @selected(request('risk_level')===$risk)>{{ ucwords(str_replace('_',' ',$risk)) }}</option>@endforeach</select>
                <button class="rounded-xl bg-red-600 px-5 py-2.5 text-xs font-semibold text-white hover:bg-red-700">{{ localize('ui.r2d.investments.filter', 'Filter') }}</button>
            </form>

            <div class="grid gap-4 md:grid-cols-2">
                @forelse($instruments as $instrument)
                    @php $change=(float)$instrument->change_percent; @endphp
                    <a href="{{ route('investments.show',$instrument->slug) }}" class="group rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-red-200 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-950">
                        <div class="flex items-start justify-between gap-4">
                            <div><span class="rounded-full bg-red-50 px-2 py-1 text-[9px] font-semibold uppercase text-red-600 dark:bg-red-950/30">{{ $instrument->symbol }}</span><h3 class="mt-3 text-sm font-semibold group-hover:text-red-600">{{ $instrument->name }}</h3><p class="mt-1 text-[10px] text-zinc-500">{{ ucwords(str_replace('_',' ',$instrument->category)) }} · {{ ucwords(str_replace('_',' ',$instrument->risk_level)) }} risk</p></div>
                            <i data-lucide="arrow-up-right" class="h-4 w-4 text-zinc-400 group-hover:text-red-600"></i>
                        </div>
                        <p class="mt-4 line-clamp-2 text-xs leading-5 text-zinc-500">{{ $instrument->description }}</p>
                        @php $listingStory = app(\App\Services\PrivateInvestmentProjectionService::class)->forInstrument($instrument, (float)$instrument->minimum_investment); @endphp
                        <div class="mt-4 grid grid-cols-2 border-y border-zinc-100 dark:border-zinc-900">
                            <div class="border-b border-r border-zinc-100 py-3 pr-3 dark:border-zinc-900"><p class="text-[9px] uppercase tracking-[.08em] text-zinc-400">{{ localize('ui.r2d.common.duration', 'Duration') }}</p><p class="mt-1 text-xs font-semibold">{{ $listingStory['duration_label'] }}</p></div>
                            <div class="border-b border-zinc-100 py-3 pl-3 dark:border-zinc-900"><p class="text-[9px] uppercase tracking-[.08em] text-zinc-400">{{ localize('ui.r2d.investments.return_cycle', 'Return cycle') }}</p><p class="mt-1 text-xs font-semibold">{{ $listingStory['cycle_return_label'] }}</p><p class="mt-0.5 text-[9px] text-zinc-400">{{ localize('ui.r2d.investments.every', 'every :period', ['period' => $listingStory['return_interval_label']]) }}</p></div>
                            <div class="border-r border-zinc-100 py-3 pr-3 dark:border-zinc-900"><p class="text-[9px] uppercase tracking-[.08em] text-zinc-400">{{ localize('ui.r2d.investments.potential_min', 'Potential min') }}</p><p class="mt-1 text-xs font-semibold">{{ currency_symbol() }}{{ number_format($listingStory['net_term_min_profit'],2) }}</p></div>
                            <div class="py-3 pl-3"><p class="text-[9px] uppercase tracking-[.08em] text-zinc-400">{{ localize('ui.r2d.investments.potential_max', 'Potential max') }}</p><p class="mt-1 text-xs font-semibold">{{ currency_symbol() }}{{ number_format($listingStory['net_term_max_profit'],2) }}</p></div>
                        </div>
                        <div class="mt-auto grid grid-cols-3 gap-3 border-t border-zinc-100 pt-4 dark:border-zinc-900">
                            <div><p class="text-[9px] text-zinc-400">{{ localize('ui.r2d.investments.price', 'Price') }}</p><p class="mt-1 text-sm font-semibold">{{ currency_symbol() }}{{ number_format((float)$instrument->current_price,2) }}</p></div>
                            <div><p class="text-[9px] text-zinc-400">{{ localize('ui.r2d.investments.move', 'Move') }}</p><p class="mt-1 text-sm font-semibold {{ $change >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $change >= 0 ? '+' : '' }}{{ number_format($change,2) }}%</p></div>
                            <div class="text-right"><p class="text-[9px] text-zinc-400">{{ localize('ui.r2d.investments.minimum', 'Minimum') }}</p><p class="mt-1 text-sm font-semibold">{{ currency_symbol() }}{{ number_format((float)$instrument->minimum_investment,0) }}</p></div>
                        </div>
                    </a>
                @empty
                    <div class="col-span-full rounded-2xl border border-dashed border-zinc-300 bg-white p-10 text-center text-sm text-zinc-500 dark:border-zinc-800 dark:bg-zinc-950">{{ localize('ui.r2d.investments.no_match', 'No investment instruments match this view.') }}</div>
                @endforelse
            </div>
            <div class="mt-5">{{ $instruments->links() }}</div>
        </section>

        <aside class="space-y-4">
            <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                <p class="text-[10px] font-semibold uppercase tracking-[.14em] text-red-600">{{ localize('ui.r2d.investments.market_movement', 'Market movement') }}</p>
                <h2 class="mt-1 text-base font-semibold">{{ localize('ui.r2d.investments.largest_moves', 'Largest current moves') }}</h2>
                <div class="mt-4 space-y-3">
                    @forelse(($movers ?? collect()) as $instrument)
                        @php $m=(float)$instrument->change_percent; @endphp
                        <a href="{{ route('investments.show',$instrument->slug) }}" class="flex items-center justify-between gap-3">
                            <div><p class="text-xs font-semibold">{{ $instrument->symbol }}</p><p class="text-[9px] text-zinc-400">{{ ucwords(str_replace('_',' ',$instrument->category)) }}</p></div>
                            <div class="text-right"><p class="text-xs font-semibold">{{ currency_symbol() }}{{ number_format((float)$instrument->current_price,2) }}</p><p class="text-[10px] font-semibold {{ $m >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $m >= 0 ? '+' : '' }}{{ number_format($m,2) }}%</p></div>
                        </a>
                    @empty
                        <p class="text-xs text-zinc-500">{{ localize('ui.r2d.investments.movement_wait', 'Movement data will appear as valuation history grows.') }}</p>
                    @endforelse
                </div>

                @php
                    $summaryCounts = [
                        ['Stocks', 'stock_market', 'investments.stocks'],
                        ['Crypto', 'cryptocurrency', 'investments.crypto'],
                        ['Real Estate', 'real_estate', 'investments.real-estate'],
                        ['Bonds', 'bonds', 'investments.bonds'],
                    ];
                    $summaryTotal = collect($summaryCounts)->sum(
                        fn ($item) => (int) ($categoryStats[$item[1]] ?? \App\Models\PrivateInvestmentInstrument::where('category', $item[1])->where('is_visible', true)->count())
                    );
                @endphp

                <div class="mt-5 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[9px] font-semibold uppercase tracking-[.14em] text-zinc-400">{{ localize('ui.r2d.investments.listings_summary', 'Listings summary') }}</p>
                            <p class="mt-1 text-sm font-semibold text-zinc-950 dark:text-white">{{ $summaryTotal }} active listings</p>
                        </div>
                        <a href="{{ route('investments.index') }}" class="text-[9px] font-semibold text-red-600 hover:text-red-700">{{ localize('ui.r2d.investments.view_all', 'View all') }}</a>
                    </div>

                    <div class="mt-3 grid grid-cols-2 gap-2">
                        @foreach($summaryCounts as [$summaryLabel, $summaryKey, $summaryRoute])
                            @php
                                $summaryCount = (int) ($categoryStats[$summaryKey] ?? \App\Models\PrivateInvestmentInstrument::where('category', $summaryKey)->where('is_visible', true)->count());
                            @endphp
                            <a href="{{ route($summaryRoute) }}"
                               class="rounded-xl border border-zinc-200 bg-zinc-50 p-3 transition hover:border-red-200 hover:bg-red-50/50 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-red-900 dark:hover:bg-red-950/10">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-[9px] text-zinc-500">{{ $summaryLabel }}</span>
                                    <span class="text-xs font-semibold tabular-nums text-zinc-950 dark:text-white">{{ $summaryCount }}</span>
                                </div>
                            </a>
                        @endforeach
                    </div>

                    <div class="mt-3 flex items-center justify-between rounded-xl bg-zinc-50 px-3 py-2.5 dark:bg-zinc-900">
                        <span class="text-[9px] text-zinc-500">{{ localize('ui.r2d.investments.underlying_valuation', 'Underlying valuation') }}</span>
                        <span class="text-xs font-semibold tabular-nums text-zinc-950 dark:text-white">
                            {{ currency_symbol() }}{{ number_format($overview['underlying_valuation'] ?? \App\Models\PrivateInvestmentAsset::where('status','active')->sum('current_valuation'), 0) }}
                        </span>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-red-100 bg-red-50 p-5 dark:border-red-950 dark:bg-red-950/20">
                <p class="text-[10px] font-semibold uppercase tracking-[.14em] text-red-600">{{ \App\Models\Setting::get('investment_pricing_authority_title', 'Pricing Authority') }}</p>
                <p class="mt-2 text-xs leading-5 text-zinc-700 dark:text-zinc-300">{{ \App\Models\Setting::get('investment_pricing_authority_message', 'Investment prices shown here are recorded by the private Investment Engine. They are not direct live-market quotes.') }}</p>
            </section>
        </aside>
    </div>
</div>
</x-user-layout>
