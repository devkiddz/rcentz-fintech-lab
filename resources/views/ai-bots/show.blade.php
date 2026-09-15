<x-user-layout>
<x-slot name="header">{{ $product->name }}</x-slot>
<div class="ui-page max-w-[1300px]">
<section class="ui-page-header">
    <div>
        <p class="ui-kicker text-[10px]">AI Trading Bots</p>
        <h1 class="ui-heading !text-xl">{{ $product->name }}</h1>
        <p class="ui-lead !text-[13px]">{{ $product->description }}</p>
    </div>
    <a href="{{ route('ai-bots.marketplace') }}" class="ui-btn ui-btn-secondary">Marketplace</a>
</section>

<div class="grid gap-4 xl:grid-cols-[1.3fr_.7fr]">
    <section class="ui-panel overflow-hidden">
        <div class="flex items-center justify-between gap-3 border-b border-border/70 px-4 py-3">
            <div>
                <div class="flex flex-wrap gap-2">
                    <span class="rounded-full border border-sky-500/20 bg-sky-500/10 px-2 py-1 text-[10px] font-semibold text-sky-600">{{ $product->stock->symbol }}</span>
                    <span class="rounded-full border border-amber-500/20 bg-amber-500/10 px-2 py-1 text-[10px] font-semibold text-amber-600">{{ ucfirst($product->risk_level) }} risk</span>
                    <span class="rounded-full border border-border bg-muted px-2 py-1 text-[10px] font-semibold">{{ strtoupper($product->strategy) }}</span>
                </div>
                <div class="mt-2 flex items-center gap-2">
                    <h2 class="text-sm font-semibold">Live {{ $product->stock->symbol }} Market</h2>
                    <span class="rounded-full border border-border bg-muted px-2 py-1 text-[9px] font-semibold uppercase tracking-[.12em] text-muted-foreground">
                        {{ str_replace('_', ' ', $marketStatus ?? 'closed') }}
                    </span>
                </div>
            </div>
            <div class="text-right">
                <p class="text-[10px] text-muted-foreground">Current</p>
                <p class="mt-1 text-lg font-semibold">{{ format_currency($product->stock->current_price) }}</p>
                <p class="text-[10px] font-medium {{ (float)$product->stock->change_percentage >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                    {{ (float)$product->stock->change_percentage >= 0 ? '+' : '' }}{{ number_format((float)$product->stock->change_percentage,2) }}%
                </p>
            </div>
        </div>

        <div class="relative h-[320px] p-3">
            @if(count($quoteHistory ?? []) >= 2)
                <div class="h-full w-full"
                     data-rcentz-candles
                     data-quotes='@json($quoteHistory)'></div>
            @else
                <div class="absolute inset-0 flex items-center justify-center p-6">
                    <div class="max-w-sm rounded-2xl border border-border bg-background/80 px-5 py-4 text-center shadow-sm backdrop-blur">
                        <div class="mx-auto flex h-8 w-8 items-center justify-center rounded-full border border-sky-500/20 bg-sky-500/10 text-sky-500">
                            <i data-lucide="chart-no-axes-combined" class="h-4 w-4"></i>
                        </div>
                        <p class="mt-3 text-xs font-medium">Building live price history</p>
                        <p class="mt-1 text-[10px] leading-4 text-muted-foreground">
                            The chart appears as regular-session market quotes are collected.
                        </p>
                    </div>
                </div>
            @endif
        </div>

        <div class="grid grid-cols-2 gap-2 border-t border-border/70 p-3 sm:grid-cols-4">
            @foreach([
                ['Open',$product->stock->open],
                ['High',$product->stock->high],
                ['Low',$product->stock->low],
                ['Prev. Close',$product->stock->previous_close],
            ] as [$label,$value])
                <div class="rounded-lg border border-border bg-muted/10 p-2.5">
                    <p class="text-[9px] uppercase tracking-[.12em] text-muted-foreground">{{ $label }}</p>
                    <p class="mt-1 text-[13px] font-semibold">{{ format_currency($value) }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <aside class="space-y-4">
        <section class="ui-panel p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[10px] uppercase tracking-[.13em] text-muted-foreground">Bot subscription</p>
                    <p class="mt-1 text-2xl font-semibold">{{ $product->price>0?format_currency($product->price):'Free' }}</p>
                    <p class="mt-1 text-[11px] text-muted-foreground">{{ ucfirst(str_replace('_',' ',$product->billing_period)) }}</p>
                </div>
                <i data-lucide="bot" class="h-5 w-5 text-sky-500"></i>
            </div>

            @if($existing)
                <div class="mt-4 rounded-lg border border-emerald-500/20 bg-emerald-500/5 p-3 text-xs">You already have access to this bot.</div>
                <a class="ui-btn ui-btn-primary mt-3 w-full" href="{{ route('ai-bots.my-bots') }}">Open My Bots</a>
            @else
                <form class="mt-4" method="POST" action="{{ route('ai-bots.subscribe',$product) }}">
                    @csrf
                    <button class="ui-btn ui-btn-primary w-full">Subscribe</button>
                </form>
            @endif
        </section>

        <section class="ui-panel p-4">
            <p class="text-[10px] font-semibold uppercase tracking-[.13em] text-muted-foreground">Live bot details</p>
            <div class="mt-3 grid grid-cols-2 gap-2">
                @foreach([
                    ['Strategy',strtoupper($product->strategy)],
                    ['Action',ucfirst($product->action)],
                    ['Minimum',format_currency($product->minimum_balance)],
                    ['Trades',$metrics['completed_count']],
                    ['P/L',($metrics['profit_loss']>0?'+':'').format_currency($metrics['profit_loss'])],
                    ['Return',($metrics['return_percent']>0?'+':'').number_format($metrics['return_percent'],2).'%'],
                ] as [$label,$value])
                    <div class="rounded-lg border border-border bg-muted/10 p-2.5">
                        <p class="text-[9px] uppercase tracking-[.12em] text-muted-foreground">{{ $label }}</p>
                        <p class="mt-1 text-[12px] font-semibold">{{ $value }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    </aside>
</div>

@if($latestNews->count())
<section class="mt-4 ui-panel overflow-hidden">
    <div class="border-b border-border/70 px-4 py-3">
        <p class="text-[10px] font-semibold uppercase tracking-[.13em] text-muted-foreground">Related market intelligence</p>
    </div>
    <div class="grid md:grid-cols-2">
        @foreach($latestNews as $news)
            <article class="border-b border-border/70 p-4 md:border-r">
                <div class="flex items-center gap-2 text-[9px] text-muted-foreground">
                    <span>{{ $news->source ?: 'Market news' }}</span><span>•</span><span>{{ optional($news->published_at)->diffForHumans() }}</span>
                </div>
                <h3 class="mt-2 text-xs font-semibold leading-5">{{ $news->headline }}</h3>
                @if($news->summary)<p class="mt-1 line-clamp-2 text-[10px] leading-4 text-muted-foreground">{{ $news->summary }}</p>@endif
            </article>
        @endforeach
    </div>
</section>
@endif
</div>


@include('ai-bots.partials.lightweight-charts')
</x-user-layout>