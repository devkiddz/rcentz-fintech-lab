<x-user-layout>
<x-slot name="header">{{ \App\Services\BasketDisplay::symbol($instrument, $marketplace) }} Market</x-slot>
<style>
    .instrument-scroll-pane:focus-visible { outline: 2px solid currentColor; outline-offset: 3px; }
    @media (min-width: 1280px) {
        .instrument-scroll-pane {
            height: clamp(420px, 76vh, 980px);
            height: clamp(420px, 76dvh, 980px);
            min-width: 0;
            min-height: 0;
            overflow-y: auto;
            overscroll-behavior-y: contain;
            scrollbar-gutter: stable;
            scrollbar-width: thin;
        }
        .instrument-chart-scroll { padding-right: 8px; }
        .instrument-order-scroll { position: static; }
    }
</style>
@php
    $precision = max(0, min(8, (int) $instrument->price_precision));
    $mark = (float) ($analysis['current_price'] ?? 0);
    $previous = (float) ($analysis['previous_close'] ?? $mark);
    $move = $mark - $previous;
    $movePct = $previous > 0 ? ($move / $previous) * 100 : 0;
    $walletCurrency = strtoupper((string) ($wallet?->currency ?: 'USD'));
    $holdingQty = (float) ($holding?->quantity ?? 0);
    $marketListRoute = match($instrument->asset_class) { 'stock' => route('instruments.stocks'), 'forex' => route('instruments.forex'), 'crypto' => route('instruments.crypto'), 'commodity' => route('instruments.commodities'), default => route('instruments.index') };
    $chartPositions=$positions->map(fn($position)=>[
        'id'=>$position->id, 'direction'=>$position->direction ?: 'long',
        'entry'=>(float)$position->entry_price,
        'stop'=>$position->stop_loss_price!==null ? (float)$position->stop_loss_price : null,
        'target'=>$position->take_profit_price!==null ? (float)$position->take_profit_price : null,
    ])->values();
    // Execution arrows describe active positions only; closed trade history stays in Orders.
    $chartExecutions=$positions->filter(fn($position)=>$position->entryMarketExecutionTransaction?->status === 'completed')
        ->map(fn($position)=>['id'=>$position->id,'side'=>$position->direction === 'short' ? 'sell' : 'buy',
            'time'=>($position->entryMarketExecutionTransaction->executed_at ?? $position->opened_at)->timestamp])->values();
    $chartData=['positions'=>$chartPositions,'executions'=>$chartExecutions];
@endphp

<div class="ui-page max-w-[1500px]" data-market-runtime data-trade-workstation data-trade-chart-data='@json($chartData)'>
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker text-[10px]">Trading Â· {{ strtoupper($instrument->asset_class) }}</p>
            <div class="mt-1 flex flex-wrap items-center gap-2">
                <h1 class="ui-heading !text-2xl">{{ \App\Services\BasketDisplay::symbol($instrument, $marketplace) }}</h1>

                @if (!$executionReady)
    <span class="rounded-full border border-amber-500/25 bg-amber-500/10 px-2 py-1 text-[10px] font-semibold text-amber-600">Execution unavailable</span>
@endif
            </div>
            <p class="ui-lead !max-w-3xl !text-[13px]">{{ \App\Services\BasketDisplay::name($instrument, $marketplace) }} Â· {{ $instrument->base_asset }}{{ $instrument->quote_asset ? ' / '.$instrument->quote_asset : '' }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('broker.portfolio') }}" class="ui-btn ui-btn-secondary">Portfolio</a>
            <a href="{{ route('broker.orders') }}" class="ui-btn ui-btn-secondary">Orders</a>
            <a href="{{ route('broker.positions') }}" class="ui-btn ui-btn-secondary">Positions</a>
            <a href="{{ $marketListRoute }}" class="ui-btn ui-btn-secondary">Markets</a>
        </div>
    </section>

    <section class="ui-panel mb-5 p-4" aria-label="Switch trading instrument">
        <form class="flex flex-col gap-3 sm:flex-row sm:items-end" onsubmit="event.preventDefault(); const picker=this.querySelector('select'); if(picker.value) window.location.assign(picker.value);">
            <div class="min-w-0 flex-1">
                <label for="instrument-picker" class="ui-label">{{ match($instrument->asset_class) { 'forex'=>'Switch Forex pair', 'crypto'=>'Switch crypto instrument', 'stock'=>'Switch stock', 'commodity'=>'Switch commodity', default=>'Switch instrument' } }}</label>
                <select id="instrument-picker" class="ui-input w-full !rounded-full" aria-describedby="instrument-picker-help">
                    @foreach($instrumentOptions->groupBy('asset_class') as $optionAsset => $options)
                        <optgroup label="{{ match($optionAsset) { 'stock'=>'Stocks', 'forex'=>'Forex', 'crypto'=>'Crypto', default=>'Other' } }}">
                            @foreach($options as $option)
                                <option value="{{ route('broker.workstation', ['assetClass'=>$option->asset_class, 'symbol'=>$option->symbol]) }}" @selected($option->id === $instrument->id)>{{ \App\Services\BasketDisplay::symbol($option, $marketplace) }} Â· {{ \App\Services\BasketDisplay::name($option, $marketplace) }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="ui-btn ui-btn-primary !rounded-full">Open instrument <i data-lucide="arrow-right" class="h-4 w-4" aria-hidden="true"></i></button>
        </form>
        <p id="instrument-picker-help" class="mt-2 text-[11px] text-muted-foreground">Choose another {{ match($instrument->asset_class) { 'forex'=>'Forex pair', 'crypto'=>'crypto instrument', 'stock'=>'stock', 'commodity'=>'commodity', default=>'instrument' } }}. Switching pages keeps your open positions intact.</p>
        <noscript><p class="mt-2 text-xs"><a class="underline" href="{{ route('instruments.index') }}">Browse instruments</a></p></noscript>
    </section>

    @if(session('success'))
        <div class="mb-4 rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-xs text-emerald-700 dark:text-emerald-400">{{ session('success') }} @if(session('last_trade_order'))<a class="ml-2 font-semibold underline" href="{{ route('broker.orders.show', ['publicId'=>session('last_trade_order')]) }}">View receipt</a>@endif</div>
    @endif
    @if($errors->any())
        <div class="mb-4 rounded-xl border border-red-500/20 bg-red-500/10 px-4 py-3 text-xs text-red-700 dark:text-red-400">{{ $errors->first() }}</div>
    @endif

    <div data-trade-feedback role="status" aria-live="polite" tabindex="-1" hidden></div>

    <section data-trade-metrics class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="ui-panel p-4">
            <p class="text-[10px] font-semibold uppercase tracking-[.11em] text-muted-foreground">Market mark</p>
            <p class="mt-2 text-xl font-semibold tabular-nums" data-market-price-instrument="{{ $instrument->id }}" data-marketplace="{{ $marketplace }}">
                {{ number_format($mark, $precision) }} {{ $instrument->quote_asset }}
            </p>
            <p class="mt-1 text-[10px] text-muted-foreground">Display mark, not a guaranteed fill.</p>
        </div>
        <div class="ui-panel p-4">
            <p class="text-[10px] font-semibold uppercase tracking-[.11em] text-muted-foreground">Move</p>
            <p class="mt-2 text-xl font-semibold tabular-nums {{ $move >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $move >= 0 ? '+' : '' }}{{ number_format($movePct, 2) }}%</p>
            <p class="mt-1 text-[10px] text-muted-foreground">From previous close.</p>
        </div>
        <div class="ui-panel p-4">
            <p class="text-[10px] font-semibold uppercase tracking-[.11em] text-muted-foreground">Available funds</p>
            <p data-trade-funds class="mt-2 text-xl font-semibold tabular-nums">{{ $walletCurrency }} {{ number_format((float) ($wallet?->available_balance ?? 0), 2) }}</p>
            <p class="mt-1 text-[10px] text-muted-foreground">Available after reserved balance.</p>
        </div>
        <div class="ui-panel p-4">
            <p class="text-[10px] font-semibold uppercase tracking-[.11em] text-muted-foreground">{{ config('paper_trading.enabled') ? 'Open exposure' : 'Owned exposure' }}</p>
            <p class="mt-2 text-xl font-semibold tabular-nums">{{ number_format(config('paper_trading.enabled') ? (float)$positions->sum('open_quantity') : $holdingQty, 8) }}</p>
            <p class="mt-1 text-[10px] text-muted-foreground">{{ $instrument->base_asset ?: $instrument->symbol }} units.</p>
        </div>
    </section>

    <div class="mt-5 grid gap-5 xl:grid-cols-[minmax(0,1.35fr)_minmax(340px,.65fr)]">
        <div class="space-y-5 instrument-scroll-pane instrument-chart-scroll" role="region" aria-label="Chart and open positions" tabindex="0">
            <section>
                @include('trading.partials.analysis-chart', [
                    'instrument' => $instrument,
                    'analysis' => $analysis,
                    'chartHeight' => 'h-[340px] md:h-[440px]',
                ])
            </section>

            <details class="ui-panel p-4">
                <summary class="cursor-pointer text-sm font-semibold">Asset details</summary>
                <dl class="mt-4 grid grid-cols-2 gap-4 text-xs sm:grid-cols-3">
                    <div><dt class="text-muted-foreground">Name</dt><dd class="mt-1 font-semibold">{{ \App\Services\BasketDisplay::name($instrument, $marketplace) }}</dd></div>
                    <div><dt class="text-muted-foreground">Category</dt><dd class="mt-1 font-semibold">{{ ucfirst($instrument->asset_class) }}</dd></div>
                    <div><dt class="text-muted-foreground">Market</dt><dd class="mt-1 font-semibold">{{ $instrument->market ?: 'Global' }}</dd></div>
                    <div><dt class="text-muted-foreground">Base / Quote</dt><dd class="mt-1 font-semibold">{{ $instrument->base_asset }} / {{ $instrument->quote_asset }}</dd></div>
                    <div><dt class="text-muted-foreground">Quantity</dt><dd class="mt-1 font-semibold">{{ $instrument->isCommodity() ? 'Troy ounces' : ($instrument->isStock() ? 'Shares' : 'Asset units') }}</dd></div>
                    <div><dt class="text-muted-foreground">Price precision</dt><dd class="mt-1 font-semibold">{{ $precision }} decimals</dd></div>
                </dl>
            </details>

            <p class="text-[10px] text-muted-foreground">Chart: blue = Long entry Â· orange = Short entry Â· red = Stop loss Â· green = Take profit. Execution arrows show open trades only, within stored chart history.</p>

            <section class="ui-panel overflow-hidden" data-trade-positions aria-label="Open positions for this instrument">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border/70 px-5 py-4"><h2 class="text-sm font-semibold">Open positions Â· {{ \App\Services\BasketDisplay::symbol($instrument, $marketplace) }}</h2><span class="rounded-full border border-border px-3 py-1 text-xs">{{ $positions->count() }} open</span></div>
                @forelse($positions as $position)
                    @include('broker.partials.instrument-position', ['position' => $position])
                @empty
                    <div class="p-5"><p class="text-sm font-semibold">No open positions for {{ \App\Services\BasketDisplay::symbol($instrument, $marketplace) }}.</p><p class="mt-2 text-xs leading-5 text-muted-foreground">{{ config('paper_trading.enabled') ? 'Use the order ticket to open a Long or Short position.' : 'Buy creates exposure; Sell only reduces exposure you already own. Short entry is currently unavailable.' }}</p><a href="{{ route('broker.orders') }}" class="ui-btn ui-btn-secondary mt-3">Review order history</a></div>
                @endforelse
            </section>
        </div>

        <aside class="ui-panel self-start instrument-scroll-pane instrument-order-scroll" aria-label="Market order ticket" tabindex="0">
            <div class="border-b border-border/70 px-5 py-4">
                <p class="ui-kicker">Order Ticket</p>
                <h2 class="mt-1 text-[15px] font-semibold">Market order</h2>
                <p class="mt-1 text-[11px] leading-5 text-muted-foreground">{{ config('paper_trading.enabled') ? 'Buy opens a long position. Sell opens a short position. Use the position panel below the chart to close existing trades.' : 'Buy creates exposure. Sell reduces owned exposure; it does not open a short position in the current mode.' }}</p>
            </div>

            <form method="POST" action="{{ route('broker.orders.submit', ['assetClass' => $instrument->asset_class, 'symbol' => $instrument->symbol]) }}" class="space-y-4 p-5" data-trade-ajax="open">
                @csrf
                <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', $idempotencyKey) }}">

                <div>
                    <label class="ui-label">Side</label>
                    <select name="side" class="ui-input w-full" required>
                        <option value="buy" @selected(old('side', 'buy') === 'buy')>{{ config('paper_trading.enabled') ? 'Buy / Long' : 'Buy' }}</option>
                        <option value="sell" @selected(old('side') === 'sell')>{{ config('paper_trading.enabled') ? 'Sell / Short' : 'Sell / Reduce owned exposure' }}</option>
                    </select>
                </div>

                @if($copyStrategies->isNotEmpty())
                    <div>
                        <label class="ui-label">Execution purpose</label>
                        <select name="copy_strategy_id" class="ui-input w-full">
                            <option value="">Personal trade â€” do not copy</option>
                            @foreach($copyStrategies as $strategy)
                                <option value="{{ $strategy->id }}" @selected((string) old('copy_strategy_id') === (string) $strategy->id)>
                                    Copy Strategy Â· {{ $strategy->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-[9px] leading-4 text-muted-foreground">
                            Only orders assigned to a Copy Strategy are mirrored to its active followers. Manage existing strategy positions from the Positions desk.
                        </p>
                    </div>
                @endif

                <div>
                    <label class="ui-label">Quantity type</label>
                    <select name="quantity_mode" class="ui-input w-full" required>
                        @foreach($quantityModes as $mode => $label)
                            <option value="{{ $mode }}" @selected(old('quantity_mode', array_key_first($quantityModes)) === $mode)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-[9px] text-muted-foreground">
                        @if($instrument->isStock()) Shares only.
                        @elseif($instrument->isForex()) Base units or standard 100,000-unit lots.
                        @else Asset units or an amount in your account settlement currency.
                        @endif
                    </p>
                </div>

                <div>
                    <label class="ui-label">Quantity / amount</label>
                    <input name="quantity" type="number" step="0.00000001" min="0.00000001" value="{{ old('quantity') }}" class="ui-input w-full" placeholder="Enter order size" required>
                    @error('quantity')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
                </div>

                @if(config('paper_trading.enabled'))
                <section data-entry-calculator class="rounded-xl border border-border bg-muted/10 p-3" aria-label="Position cost estimate">
                    <p class="text-xs font-semibold">Position calculator</p>
                    <dl class="mt-3 space-y-2 text-xs">
                        @foreach(['available'=>'Available balance','units'=>'Position units','price'=>'Estimated entry price','collateral'=>'Position collateral','entry_fee'=>'Entry fee','required'=>'Total required','remaining'=>'Available after opening'] as $key=>$label)
                        <div class="flex items-center justify-between gap-3"><dt class="text-muted-foreground">{{ $label }}</dt><dd data-entry-value="{{ $key }}" class="font-semibold tabular-nums">â€”</dd></div>
                        @endforeach
                    </dl>
                    <p data-entry-status class="mt-3 text-[11px] leading-5" role="status" aria-live="polite">Enter a quantity to estimate your position.</p>
                    <p class="mt-2 text-[10px] leading-4 text-muted-foreground">Collateral is reserved while the position is open. Estimates refresh automatically; the final price and funds are checked when you submit.</p>
                </section>
                @endif

                @include('broker.partials.risk-inputs', ['riskPosition'=>null, 'allowPrices'=>(bool)config('paper_trading.enabled')])

                <div>
                    <label class="ui-label">Trade duration (minutes)</label>
                    <input name="duration_minutes" type="number" min="1" max="43200" value="{{ old('duration_minutes') }}" class="ui-input w-full" placeholder="Optional">
                </div>

                <div class="rounded-xl border border-border bg-muted/10 p-3 text-[10px] leading-5 text-muted-foreground">
                    Market orders are immediate-or-fail. The selected marketplace must have an executable quote. Final fill prices may differ from the displayed mark. {{ config('paper_trading.enabled') ? 'Risk controls apply to long and short positions.' : 'Sell requires existing exposure in this market.' }}
                </div>

                <button class="ui-btn ui-btn-primary w-full" @disabled(!$executionReady)>
                    {{ $executionReady ? 'Execute Market Order' : 'Execution Currently Unavailable' }}
                </button>
            </form>
        </aside>
    </div>
</div>
</x-user-layout>
