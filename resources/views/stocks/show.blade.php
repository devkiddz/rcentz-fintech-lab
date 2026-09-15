<x-user-layout>
<x-slot name="header">{{ $stock->symbol }} · {{ $stock->company_name }}</x-slot>

<div class="ui-page max-w-[1500px]">
    <section class="ui-page-header">
        <div class="flex min-w-0 items-center gap-3">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-border bg-muted">
                @if($stock->logo_url)
                    <img src="{{ $stock->logo_url }}" alt="{{ $stock->symbol }}" class="h-full w-full object-cover">
                @else
                    <span class="text-sm font-semibold">{{ substr($stock->symbol,0,2) }}</span>
                @endif
            </div>
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <p class="ui-kicker !mb-0 text-[9px]">Stocks</p>
                    <span class="rounded-full border border-sky-500/20 bg-sky-500/10 px-2 py-0.5 text-[9px] font-semibold text-sky-600">{{ $stock->symbol }}</span>
                </div>
                <h1 class="mt-1 truncate text-xl font-semibold md:text-2xl">{{ $stock->company_name }}</h1>
                <p class="mt-1 text-[10px] text-muted-foreground md:text-xs">{{ $stock->sector }} · {{ $stock->industry }}</p>
            </div>
        </div>

        <div class="flex gap-2">
            @if($isInWatchlist)
                <form action="{{ route('trading.watchlist.remove',$stock) }}" method="POST">
                    @csrf @method('DELETE')
                    <button class="ui-btn ui-btn-secondary"><i data-lucide="bookmark-check" class="h-4 w-4"></i><span class="hidden sm:inline">Watching</span></button>
                </form>
            @else
                <form action="{{ route('trading.watchlist.add',$stock) }}" method="POST">
                    @csrf
                    <button class="ui-btn ui-btn-secondary"><i data-lucide="bookmark-plus" class="h-4 w-4"></i><span class="hidden sm:inline">Watchlist</span></button>
                </form>
            @endif
        </div>
    </section>

    <div class="grid grid-cols-2 gap-2.5 md:grid-cols-4">
        @foreach([
            ['Current Price',$stock->formatted_current_price,'circle-dollar-sign','text-sky-500'],
            ['Day Change',$stock->formatted_change_amount.' · '.$stock->formatted_change_percentage,$stock->change_percentage>=0?'trending-up':'trending-down',$stock->change_percentage>=0?'text-emerald-600':'text-red-600'],
            ['Volume',$stock->formatted_volume,'chart-no-axes-column-increasing','text-sky-500'],
            ['Market Cap',$stock->formatted_market_cap,'pie-chart','text-violet-500'],
        ] as [$label,$value,$icon,$class])
            <div class="ui-panel p-3 md:p-3.5">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="text-[8px] uppercase tracking-[.11em] text-muted-foreground md:text-[9px]">{{ $label }}</p>
                        <p class="mt-1 text-sm font-semibold tabular-nums md:text-lg {{ $class }}">{{ $value }}</p>
                    </div>
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border border-border bg-muted/30">
                        <i data-lucide="{{ $icon }}" class="h-3.5 w-3.5 {{ $class }}"></i>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-4 grid gap-4 xl:grid-cols-[minmax(0,1fr)_340px]">
        <div class="space-y-4">
            @include('trading.partials.analysis-chart',['stock'=>$stock,'analysis'=>$analysis,'chartHeight'=>'h-[300px] sm:h-[360px] lg:h-[430px]'])

            <section class="ui-panel overflow-hidden">
                <div class="border-b border-border px-4 py-3">
                    <p class="text-[9px] uppercase tracking-[.13em] text-muted-foreground">Stock details</p>
                    <h2 class="mt-1 text-sm font-semibold">Key Metrics & Company Information</h2>
                </div>

                <div class="grid gap-0 md:grid-cols-2">
                    <div class="border-b border-border p-4 md:border-b-0 md:border-r">
                        <p class="text-[10px] font-semibold">Key metrics</p>
                        <div class="mt-3 space-y-2.5">
                            @foreach([
                                ['Previous Close',$stock->formatted_previous_close],
                                ['P/E Ratio',$stock->formatted_pe_ratio],
                                ['Dividend Yield',$stock->formatted_dividend_yield],
                                ['52W High',$stock->formatted_fifty_two_week_high],
                                ['52W Low',$stock->formatted_fifty_two_week_low],
                            ] as [$label,$value])
                                <div class="flex items-center justify-between gap-3 text-[11px]">
                                    <span class="text-muted-foreground">{{ $label }}</span>
                                    <span class="font-semibold tabular-nums">{{ $value }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="p-4">
                        <p class="text-[10px] font-semibold">Company information</p>
                        <div class="mt-3 space-y-2.5">
                            @foreach([
                                ['Symbol',$stock->symbol],
                                ['Company',$stock->company_name],
                                ['Sector',$stock->sector],
                                ['Industry',$stock->industry],
                                ['Last Updated',$stock->formatted_last_updated],
                            ] as [$label,$value])
                                <div class="flex items-start justify-between gap-3 text-[11px]">
                                    <span class="shrink-0 text-muted-foreground">{{ $label }}</span>
                                    <span class="text-right font-semibold">{{ $value }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>

            @php
                $articles = $newsFromDb->isNotEmpty() ? $newsFromDb : $newsFromApi;
            @endphp
            @if($articles->isNotEmpty())
                <section class="ui-panel overflow-hidden">
                    <div class="border-b border-border px-4 py-3">
                        <p class="text-[9px] uppercase tracking-[.13em] text-muted-foreground">Market intelligence</p>
                        <h2 class="mt-1 text-sm font-semibold">Latest {{ $stock->symbol }} News</h2>
                    </div>
                    <div class="divide-y divide-border">
                        @foreach($articles->take(4) as $article)
                            @php
                                $headline = is_array($article) ? ($article['headline'] ?? '') : ($article->headline ?? '');
                                $source = is_array($article) ? ($article['source'] ?? '') : ($article->source ?? '');
                                $url = is_array($article) ? ($article['url'] ?? '#') : ($article->url ?? '#');
                            @endphp
                            <a href="{{ $url }}" target="_blank" rel="noopener" class="block px-4 py-3 transition hover:bg-muted/10">
                                <p class="text-xs font-semibold leading-5">{{ $headline }}</p>
                                <p class="mt-1 text-[9px] text-muted-foreground">{{ $source }}</p>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>

        <aside class="space-y-4">
            <section class="ui-panel p-4">
                <div class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-xl border border-emerald-500/20 bg-emerald-500/10 text-emerald-600">
                        <i data-lucide="zap" class="h-4 w-4"></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold">Trading Actions</p>
                        <p class="text-[10px] text-muted-foreground">Buy or sell {{ $stock->symbol }}</p>
                    </div>
                </div>

                <div class="mt-4 grid gap-2">
                    <a href="{{ route('trading.buy',$stock) }}" class="ui-btn ui-btn-primary h-10 w-full"><i data-lucide="plus" class="h-4 w-4"></i> Buy {{ $stock->symbol }}</a>
                    @if($userHolding)
                        <a href="{{ route('trading.sell',$stock) }}" class="ui-btn h-10 w-full border border-red-500/40 text-red-600 hover:bg-red-500/10"><i data-lucide="minus" class="h-4 w-4"></i> Sell {{ $stock->symbol }}</a>
                    @endif
                </div>
            </section>

            <section class="ui-panel p-4">
                <p class="text-sm font-semibold">Watchlist & Alerts</p>
                <p class="mt-1 text-[10px] text-muted-foreground">Track this stock and set a price level.</p>

                <form action="{{ route('trading.watchlist.add',$stock) }}" method="POST" class="mt-4 space-y-3">
                    @csrf
                    <div>
                        <label class="ui-label !text-[10px]">Alert price</label>
                        <input name="alert_price" type="number" step="0.01" min="0" value="{{ old('alert_price',$watchlistItem?->alert_price) }}" class="ui-input" placeholder="0.00">
                    </div>
                    <div>
                        <label class="ui-label !text-[10px]">Alert type</label>
                        <select name="alert_type" class="ui-input">
                            <option value="">No alert</option>
                            <option value="above" @selected($watchlistItem?->alert_type==='above')>Price moves above</option>
                            <option value="below" @selected($watchlistItem?->alert_type==='below')>Price moves below</option>
                        </select>
                    </div>
                    <button class="ui-btn ui-btn-secondary w-full"><i data-lucide="bell" class="h-4 w-4"></i> Save Alert</button>
                </form>
            </section>

            <section class="ui-panel p-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-[9px] uppercase tracking-[.12em] text-muted-foreground">Analyst sentiment</p>
                        <p class="mt-1 text-lg font-semibold">{{ $analysis['analyst']['label'] ?: 'No rating' }}</p>
                    </div>
                    @if($analysis['analyst']['percentage'])
                        <span class="rounded-full border border-violet-500/20 bg-violet-500/10 px-2 py-1 text-[9px] font-semibold text-violet-600">{{ number_format($analysis['analyst']['percentage'],1) }}% buy</span>
                    @endif
                </div>

                @if($analysis['analyst']['total'] > 0)
                    @php
                        $buyCount = $analysis['analyst']['strong_buy'] + $analysis['analyst']['buy'];
                        $totalRatings = max(1,$analysis['analyst']['total']);
                    @endphp
                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-muted">
                        <div class="h-full bg-emerald-500" style="width:{{ min(100,($buyCount/$totalRatings)*100) }}%"></div>
                    </div>
                    <div class="mt-3 grid grid-cols-3 gap-2 text-center">
                        <div><p class="text-[9px] text-muted-foreground">Buy</p><p class="mt-1 text-xs font-semibold text-emerald-600">{{ $buyCount }}</p></div>
                        <div><p class="text-[9px] text-muted-foreground">Hold</p><p class="mt-1 text-xs font-semibold">{{ $analysis['analyst']['hold'] }}</p></div>
                        <div><p class="text-[9px] text-muted-foreground">Sell</p><p class="mt-1 text-xs font-semibold text-red-600">{{ $analysis['analyst']['sell'] + $analysis['analyst']['strong_sell'] }}</p></div>
                    </div>
                @else
                    <p class="mt-3 text-[10px] text-muted-foreground">No stored analyst recommendation is available for this symbol yet.</p>
                @endif
            </section>

            <section class="ui-panel p-4">
                <p class="text-[9px] uppercase tracking-[.12em] text-muted-foreground">Analysis & signal</p>
                <div class="mt-3 grid grid-cols-2 gap-2">
                    @foreach([
                        ['Trend',$analysis['trend']],
                        ['Momentum',$analysis['momentum_label']],
                        ['Risk / Reward',$analysis['risk_reward']],
                        ['Period Return',($analysis['momentum_percent']>=0?'+':'').number_format($analysis['momentum_percent'],2).'%'],
                    ] as [$label,$value])
                        <div class="rounded-xl border border-border bg-muted/10 p-2.5">
                            <p class="text-[8px] uppercase tracking-[.1em] text-muted-foreground">{{ $label }}</p>
                            <p class="mt-1 text-[11px] font-semibold">{{ $value }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        </aside>
    </div>
</div>
</x-user-layout>
