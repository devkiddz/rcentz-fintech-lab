<x-user-layout>
<x-slot name="header">Stock Marketplace</x-slot>

<div class="ui-page max-w-[1440px]">
    <section class="ui-page-header">
        <div>
            <div class="flex items-center gap-2">
                <p class="ui-kicker text-[10px]">Trading</p>
                <span class="inline-flex items-center gap-1 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2 py-1 text-[9px] font-semibold uppercase tracking-[.12em] text-emerald-600">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Live
                </span>
            </div>
            <h1 class="ui-heading !text-2xl">Stock Marketplace</h1>
            <p class="ui-lead !text-[13px]">Live market discovery, watchlists and direct stock execution from one workspace.</p>
        </div>

        <div class="ui-panel flex min-w-[220px] items-center justify-between p-3.5">
            <div>
                <p class="text-[9px] uppercase tracking-[.13em] text-muted-foreground">Active stocks</p>
                <p class="mt-1 text-xl font-semibold">{{ $stocks->total() }}</p>
            </div>
            <div class="grid grid-cols-2 gap-2 text-right">
                <div>
                    <p class="text-[9px] text-muted-foreground">Gainers</p>
                    <p class="text-xs font-semibold text-emerald-600">{{ $gainers->count() }}</p>
                </div>
                <div>
                    <p class="text-[9px] text-muted-foreground">Losers</p>
                    <p class="text-xs font-semibold text-red-600">{{ $losers->count() }}</p>
                </div>
            </div>
        </div>
    </section>

    <div class="grid gap-3 md:grid-cols-3">
        <a href="{{ route('stocks.gainers') }}" class="ui-panel group p-3.5 transition hover:border-emerald-500/30">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[9px] uppercase tracking-[.12em] text-muted-foreground">Top gainer</p>
                    <p class="mt-1 text-lg font-semibold text-emerald-600">
                        +{{ $gainers->first() ? number_format($gainers->first()->change_percentage, 2) : '0.00' }}%
                    </p>
                    <p class="mt-1 text-[10px] text-muted-foreground">{{ $gainers->first()?->symbol ?: '—' }}</p>
                </div>
                <i data-lucide="trending-up" class="h-5 w-5 text-emerald-600"></i>
            </div>
        </a>

        <a href="{{ route('stocks.losers') }}" class="ui-panel group p-3.5 transition hover:border-red-500/30">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[9px] uppercase tracking-[.12em] text-muted-foreground">Top loser</p>
                    <p class="mt-1 text-lg font-semibold text-red-600">
                        {{ $losers->first() ? number_format($losers->first()->change_percentage, 2) : '0.00' }}%
                    </p>
                    <p class="mt-1 text-[10px] text-muted-foreground">{{ $losers->first()?->symbol ?: '—' }}</p>
                </div>
                <i data-lucide="trending-down" class="h-5 w-5 text-red-600"></i>
            </div>
        </a>

        <a href="{{ route('stocks.most-active') }}" class="ui-panel group p-3.5 transition hover:border-sky-500/30">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[9px] uppercase tracking-[.12em] text-muted-foreground">Most active</p>
                    <p class="mt-1 text-lg font-semibold">{{ $mostActive->first()?->formatted_volume ?: '0' }}</p>
                    <p class="mt-1 text-[10px] text-muted-foreground">{{ $mostActive->first()?->symbol ?: '—' }}</p>
                </div>
                <i data-lucide="activity" class="h-5 w-5 text-sky-500"></i>
            </div>
        </a>
    </div>

    <section class="ui-panel mt-4 p-4">
        <form method="GET" class="grid gap-3 md:grid-cols-2 xl:grid-cols-[1.35fr_.9fr_.9fr_.9fr_auto]">
            <div>
                <label for="search" class="ui-label !text-[10px]">Search market</label>
                <div class="relative">
                    <i data-lucide="search" class="absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-muted-foreground"></i>
                    <input id="search" name="search" value="{{ request('search') }}" placeholder="Symbol or company name" class="ui-input !pl-9">
                </div>
            </div>

            <div>
                <label for="sector" class="ui-label !text-[10px]">Sector</label>
                <select id="sector" name="sector" class="ui-input">
                    <option value="">All sectors</option>
                    @foreach($sectors as $sector)
                        <option value="{{ $sector }}" @selected(request('sector') == $sector)>{{ $sector }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="industry" class="ui-label !text-[10px]">Industry</label>
                <select id="industry" name="industry" class="ui-input">
                    <option value="">All industries</option>
                    @foreach($industries as $industry)
                        <option value="{{ $industry }}" @selected(request('industry') == $industry)>{{ $industry }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="sort" class="ui-label !text-[10px]">Sort by</label>
                <select id="sort" name="sort" class="ui-input">
                    <option value="symbol" @selected(request('sort') == 'symbol')>Symbol</option>
                    <option value="price" @selected(request('sort') == 'price')>Price</option>
                    <option value="change" @selected(request('sort') == 'change')>Change</option>
                    <option value="volume" @selected(request('sort') == 'volume')>Volume</option>
                    <option value="market_cap" @selected(request('sort') == 'market_cap')>Market Cap</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button class="ui-btn ui-btn-primary !h-10 !px-4">Apply</button>
                <a href="{{ route('stocks.index') }}" class="ui-btn ui-btn-secondary !h-10 !px-4">Reset</a>
            </div>
        </form>
    </section>

    @if($featuredStocks->count())
    <section class="mt-4">
        <div class="mb-2 flex items-end justify-between">
            <div>
                <p class="text-[9px] uppercase tracking-[.13em] text-muted-foreground">Featured</p>
                <h2 class="mt-1 text-sm font-semibold">Market leaders</h2>
            </div>
            <p class="text-[10px] text-muted-foreground">Live price snapshots</p>
        </div>

        <div class="grid gap-3 md:grid-cols-3">
            @foreach($featuredStocks->take(3) as $stock)
                <article class="ui-panel p-3.5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-border bg-muted">
                                @if($stock->logo_url)
                                    <img src="{{ $stock->logo_url }}" alt="{{ $stock->symbol }}" class="h-full w-full object-cover">
                                @else
                                    <span class="text-[10px] font-semibold">{{ substr($stock->symbol,0,2) }}</span>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold">{{ $stock->symbol }}</p>
                                <p class="truncate text-[10px] text-muted-foreground">{{ $stock->company_name }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold tabular-nums">{{ $stock->formatted_current_price }}</p>
                            <p class="mt-1 text-[10px] font-medium {{ $stock->change_percentage >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ $stock->change_percentage >= 0 ? '+' : '' }}{{ number_format($stock->change_percentage,2) }}%
                            </p>
                        </div>
                    </div>

                    <div class="mt-3 flex items-center justify-between border-t border-border pt-3">
                        <span class="text-[10px] text-muted-foreground">{{ $stock->sector }}</span>
                        <div class="flex gap-2">
                            <a href="{{ route('stocks.show',$stock) }}" class="ui-btn ui-btn-secondary !h-8 !px-3 !text-[10px]">Details</a>
                            <a href="{{ route('trading.buy',$stock) }}" class="ui-btn ui-btn-primary !h-8 !px-3 !text-[10px]">Buy</a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
    @endif

    <section class="ui-panel mt-5 overflow-hidden">
        <div class="flex items-center justify-between border-b border-border px-4 py-3">
            <div>
                <p class="text-[9px] uppercase tracking-[.13em] text-muted-foreground">Market universe</p>
                <h2 class="mt-1 text-sm font-semibold">All Stocks</h2>
            </div>
            <span class="rounded-full border border-border bg-muted px-2 py-1 text-[9px] font-semibold text-muted-foreground">{{ $stocks->total() }} listed</span>
        </div>

        @if($stocks->count())
            <div class="overflow-x-auto">
                <table class="w-full min-w-[860px]">
                    <thead class="bg-muted/20">
                        <tr class="text-left text-[9px] uppercase tracking-[.12em] text-muted-foreground">
                            <th class="px-4 py-3 font-medium">Stock</th>
                            <th class="px-4 py-3 font-medium">Price</th>
                            <th class="px-4 py-3 font-medium">Day</th>
                            <th class="px-4 py-3 font-medium">Volume</th>
                            <th class="px-4 py-3 font-medium">Market cap</th>
                            <th class="px-4 py-3 text-right font-medium">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach($stocks as $stock)
                            <tr class="transition hover:bg-muted/10">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-8 w-8 items-center justify-center overflow-hidden rounded-lg border border-border bg-muted">
                                            @if($stock->logo_url)
                                                <img src="{{ $stock->logo_url }}" alt="{{ $stock->symbol }}" class="h-full w-full object-cover">
                                            @else
                                                <span class="text-[9px] font-semibold">{{ substr($stock->symbol,0,2) }}</span>
                                            @endif
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold">{{ $stock->symbol }}</p>
                                            <p class="mt-0.5 text-[9px] text-muted-foreground">{{ $stock->company_name }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-xs font-semibold tabular-nums">{{ $stock->formatted_current_price }}</td>
                                <td class="px-4 py-3">
                                    <p class="text-xs font-semibold {{ $stock->change_percentage >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                        {{ $stock->change_percentage >= 0 ? '+' : '' }}{{ number_format($stock->change_percentage,2) }}%
                                    </p>
                                    <p class="mt-0.5 text-[9px] text-muted-foreground">{{ $stock->formatted_change_amount }}</p>
                                </td>
                                <td class="px-4 py-3 text-xs tabular-nums">{{ $stock->formatted_volume }}</td>
                                <td class="px-4 py-3 text-xs tabular-nums">{{ $stock->formatted_market_cap }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('stocks.show',$stock) }}" class="ui-btn ui-btn-secondary !h-8 !w-8 !p-0" title="View details">
                                            <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                                        </a>
                                        <a href="{{ route('trading.buy',$stock) }}" class="ui-btn ui-btn-primary !h-8 !px-3 !text-[10px]">
                                            <i data-lucide="arrow-up-right" class="h-3.5 w-3.5"></i> Buy
                                        </a>
                                        @if(auth()->check())
                                            @php($isInWatchlist = auth()->user()->stockWatchlist()->where('stock_id',$stock->id)->exists())
                                            @if($isInWatchlist)
                                                <form action="{{ route('trading.watchlist.remove',$stock) }}" method="POST">
                                                    @csrf @method('DELETE')
                                                    <button class="ui-btn ui-btn-secondary !h-8 !w-8 !p-0" title="Remove from watchlist">
                                                        <i data-lucide="bookmark-check" class="h-3.5 w-3.5"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <form action="{{ route('trading.watchlist.add',$stock) }}" method="POST">
                                                    @csrf
                                                    <button class="ui-btn ui-btn-secondary !h-8 !w-8 !p-0" title="Add to watchlist">
                                                        <i data-lucide="bookmark-plus" class="h-3.5 w-3.5"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-border px-4 py-3">{{ $stocks->links() }}</div>
        @else
            <div class="p-10 text-center">
                <i data-lucide="search-x" class="mx-auto h-7 w-7 text-muted-foreground"></i>
                <p class="mt-3 text-sm font-medium">No stocks found</p>
                <p class="mt-1 text-xs text-muted-foreground">Try a different search or filter combination.</p>
            </div>
        @endif
    </section>
</div>
</x-user-layout>
