<x-user-layout>
<x-slot name="header">Bot Performance</x-slot>
<div class="ui-page max-w-[1500px]">
<section class="ui-page-header">
    <div>
        <p class="ui-kicker text-[10px]">AI Trading Bots</p>
        <h1 class="ui-heading !text-xl">Performance & Executions</h1>
        <p class="ui-lead !text-[13px]">Live bot performance, multi-asset market movement and execution activity.</p>
    </div>
</section>

<div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
    @foreach([
        ['activity','Total Trades',$summary['trade_count']],
        ['wallet-minimal','Executed Volume',format_currency($summary['volume'])],
        ['badge-dollar-sign','Profit / Loss',($summary['profit_loss']>0?'+':'').format_currency($summary['profit_loss'])],
        ['trending-up','Return',($summary['return_percent']>0?'+':'').number_format($summary['return_percent'],2).'%'],
        ['target','Positive Rate',number_format($summary['win_rate'],1).'%'],
    ] as [$icon,$label,$value])
        <div class="ui-panel p-3.5">
            <div class="flex items-center gap-2 text-[10px] uppercase tracking-[.13em] text-muted-foreground">
                <i data-lucide="{{ $icon }}" class="h-3.5 w-3.5"></i>{{ $label }}
            </div>
            <p class="mt-2 text-lg font-semibold">{{ $value }}</p>
        </div>
    @endforeach
</div>


<section class="mt-4 overflow-hidden rounded-2xl border border-border/70 bg-background/35">
    <div class="flex flex-col gap-3 border-b border-border/70 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <i data-lucide="chart-candlestick" class="h-4 w-4 text-sky-500"></i>
                <p class="text-[10px] font-semibold uppercase tracking-[.15em] text-muted-foreground">Multi-Asset Performance</p>
            </div>
            <div class="mt-1 flex items-center gap-2">
            <h2 class="text-sm font-semibold">Live market performance & related intelligence</h2>
            <span class="rounded-full border border-border bg-muted px-2 py-1 text-[9px] font-semibold uppercase tracking-[.12em] text-muted-foreground">
                MULTI ASSET
            </span>
        </div>
        </div>

        @if($marketContext->count())
            @php
                $leadStock = $marketContext->first();
            @endphp
            <div class="flex flex-wrap items-center gap-3 text-[10px]">
                <span class="rounded-full border border-sky-500/20 bg-sky-500/10 px-2 py-1 font-semibold text-sky-600">{{ $leadStock['symbol'] }}</span>
                <span class="font-semibold">{{ $leadStock['current_display'] ?? format_currency($leadStock['current']) }}</span>
                <span class="{{ $leadStock['change_percentage'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                    {{ $leadStock['change_percentage'] >= 0 ? '+' : '' }}{{ number_format($leadStock['change_percentage'],2) }}%
                </span>
            </div>
        @endif
    </div>

    <div class="grid gap-0 xl:grid-cols-[minmax(0,1.35fr)_minmax(340px,.65fr)]">
        <div class="min-w-0 border-b border-border/70 xl:border-b-0 xl:border-r">
            @if($botCards->count())
                @php
                    $primaryBot = $botCards->first();
                @endphp
                <div class="border-b border-border/70 px-4 py-3">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="rounded-full border border-sky-500/20 bg-sky-500/10 px-2 py-1 text-[10px] font-semibold text-sky-600">{{ $primaryBot['symbol'] }}</span>
                                <span class="rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2 py-1 text-[10px] font-semibold text-emerald-600">{{ ucfirst($primaryBot['status'] ?? 'paused') }}</span>
                            </div>
                            <h3 class="mt-2 text-sm font-semibold">{{ $primaryBot['name'] }}</h3>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] text-muted-foreground">Current</p>
                            <p class="mt-1 text-sm font-semibold">{{ $primaryBot['current_display'] ?? format_currency($primaryBot['current']) }}</p>
                        </div>
                    </div>
                </div>

                <div class="relative h-[300px] px-3 py-3">
                    @if(count($primaryBot['quotes'] ?? []) >= 2)
                        <div class="h-full w-full"
                             data-rcentz-candles
                             data-compact="true"
                             data-quotes='@json($primaryBot["quotes"] ?? [])'></div>
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

                <div class="grid grid-cols-3 gap-2 border-t border-border/70 p-3">
                    <div class="rounded-lg border border-border bg-muted/15 p-2.5">
                        <p class="text-[9px] uppercase tracking-[.12em] text-muted-foreground">P/L</p>
                        <p class="mt-1 text-[13px] font-semibold {{ $primaryBot['metrics']['profit_loss']>=0?'text-emerald-600':'text-red-600' }}">
                            {{ $primaryBot['metrics']['profit_loss']>0?'+':'' }}{{ format_currency($primaryBot['metrics']['profit_loss']) }}
                        </p>
                    </div>
                    <div class="rounded-lg border border-border bg-muted/15 p-2.5">
                        <p class="text-[9px] uppercase tracking-[.12em] text-muted-foreground">Return</p>
                        <p class="mt-1 text-[13px] font-semibold">{{ $primaryBot['metrics']['return_percent']>0?'+':'' }}{{ number_format($primaryBot['metrics']['return_percent'],2) }}%</p>
                    </div>
                    <div class="rounded-lg border border-border bg-muted/15 p-2.5">
                        <p class="text-[9px] uppercase tracking-[.12em] text-muted-foreground">Executions</p>
                        <p class="mt-1 text-[13px] font-semibold">{{ $primaryBot['metrics']['completed_count'] }}</p>
                    </div>
                </div>
            @else
                <div class="flex h-[390px] items-center justify-center p-6 text-sm text-muted-foreground">No bot performance available.</div>
            @endif
        </div>

        <aside class="flex min-h-0 flex-col">
            <div class="shrink-0 border-b border-border/70 px-3.5 py-3">
                <div class="flex items-center gap-2">
                    <i data-lucide="newspaper" class="h-4 w-4 text-violet-500"></i>
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-[.14em] text-muted-foreground">Market Intelligence</p>
                        <h3 class="mt-0.5 text-sm font-semibold">Related News</h3>
                    </div>
                </div>
            </div>

            <div class="h-[390px] divide-y divide-border/70 overflow-y-auto overscroll-contain">
                @forelse($marketNews as $news)
                    @php
                        $sentiment = (float)$news->sentiment_score;
                        $sentimentClass = $sentiment > .1 ? 'text-emerald-600 bg-emerald-500/10 border-emerald-500/20' : ($sentiment < -.1 ? 'text-red-600 bg-red-500/10 border-red-500/20' : 'text-muted-foreground bg-muted border-border');
                    @endphp
                    <article class="p-3.5">
                        <div class="flex items-center gap-2">
                            <span class="rounded-full border border-sky-500/20 bg-sky-500/10 px-2 py-1 text-[9px] font-semibold text-sky-600">{{ $news->symbol }}</span>
                            <span class="rounded-full border px-2 py-1 text-[9px] font-semibold {{ $sentimentClass }}">{{ $news->sentiment_label }}</span>
                            <span class="ml-auto text-[9px] text-muted-foreground">{{ optional($news->published_at)->diffForHumans() }}</span>
                        </div>
                        <h4 class="mt-2 text-[11px] font-semibold leading-4">{{ $news->headline }}</h4>
                        @if($news->summary)
                            <p class="mt-1 line-clamp-3 text-[9px] leading-4 text-muted-foreground">{{ $news->summary }}</p>
                        @endif
                        <div class="mt-2 flex items-center justify-between gap-3 text-[9px] text-muted-foreground">
                            <span>{{ $news->source ?: 'Market news' }}</span>
                            @if($news->url)
                                <a href="{{ $news->url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 font-medium text-foreground hover:underline">
                                    Read <i data-lucide="external-link" class="h-3 w-3"></i>
                                </a>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="p-4">
                        <p class="text-xs font-medium">No related news cached yet.</p>
                        <p class="mt-1 text-[10px] leading-4 text-muted-foreground">Related market news will appear here when fetched.</p>
                    </div>
                @endforelse
            </div>
        </aside>
    </div>

    @if($marketContext->count() > 1)
        <div class="border-t border-border/70 p-3">
            <div class="flex gap-2 overflow-x-auto pb-1">
                @foreach($marketContext as $stock)
                    <div class="min-w-[150px] rounded-lg border border-border bg-muted/10 p-2.5">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[10px] font-semibold">{{ $stock['symbol'] }}</span>
                            <span class="text-[9px] {{ $stock['change_percentage'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ $stock['change_percentage'] >= 0 ? '+' : '' }}{{ number_format($stock['change_percentage'],2) }}%
                            </span>
                        </div>
                        <p class="mt-1 text-xs font-semibold">{{ $stock['current_display'] ?? format_currency($stock['current']) }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</section>

<section class="mt-5">
    <div class="mb-2 flex items-end justify-between gap-3">
        <div>
            <p class="text-[10px] font-semibold uppercase tracking-[.14em] text-muted-foreground">Rcentz Executions</p>
            <h2 class="mt-1 text-sm font-semibold">Recent Bot Activity</h2>
        </div>
        <span class="text-[10px] text-muted-foreground">{{ $executions->total() }} records</span>
    </div>

    <div class="space-y-2.5">
    @foreach($executions as $e)
        <article class="ui-panel p-3.5">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full border border-sky-500/20 bg-sky-500/10 px-2 py-1 text-[10px] font-semibold text-sky-600">{{ $e->marketInstrument?->display_symbol ?? $e->bot?->marketInstrument?->display_symbol ?? $e->bot?->stock?->symbol ?? '—' }}</span>
                        <span class="rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2 py-1 text-[10px] font-semibold text-emerald-600">{{ ucfirst($e->status) }}</span>
                    </div>
                    <h3 class="mt-2 text-sm font-semibold">{{ $e->subscription?->product?->name ?? $e->bot?->name }}</h3>
                    <p class="mt-1 text-[11px] text-muted-foreground">{{ ucfirst($e->action) }} · {{ optional($e->executed_at)->format('M d · H:i') }}</p>
                </div>

                <div class="grid flex-1 grid-cols-3 gap-2 lg:max-w-xl">
                    <div class="rounded-lg border border-border bg-muted/15 p-2.5"><p class="text-[9px] uppercase tracking-[.12em] text-muted-foreground">Entry</p><p class="mt-1 text-[13px] font-semibold">{{ format_currency($e->price) }}</p></div>
                    <div class="rounded-lg border border-border bg-muted/15 p-2.5"><p class="text-[9px] uppercase tracking-[.12em] text-muted-foreground">Amount</p><p class="mt-1 text-[13px] font-semibold">{{ format_currency($e->amount) }}</p></div>
                    <div class="rounded-lg border border-border bg-muted/15 p-2.5"><p class="text-[9px] uppercase tracking-[.12em] text-muted-foreground">Qty</p><p class="mt-1 text-[13px] font-semibold">{{ number_format((float)$e->quantity,4) }}</p></div>
                </div>

                <a href="{{ route('ai-bots.executions.show',$e) }}" class="ui-btn ui-btn-secondary !h-8 !px-3 !text-[11px]">
                    <i data-lucide="square-arrow-out-up-right" class="h-3.5 w-3.5"></i> View
                </a>
            </div>
        </article>
    @endforeach
    </div>

    <div class="mt-4">{{ $executions->links() }}</div>
</section>

</div>
</div>


@include('ai-bots.partials.lightweight-charts')
</x-user-layout>