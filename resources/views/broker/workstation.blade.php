<x-user-layout>
<x-slot name="header">{{ $instrument->display_symbol }} Trading</x-slot>
@php
    $precision = max(0, min(8, (int) $instrument->price_precision));
    $mark = (float) ($analysis['current_price'] ?? 0);
    $previous = (float) ($analysis['previous_close'] ?? $mark);
    $move = $mark - $previous;
    $movePct = $previous > 0 ? ($move / $previous) * 100 : 0;
    $walletCurrency = strtoupper((string) ($wallet?->currency ?: 'USD'));
    $holdingQty = (float) ($holding?->quantity ?? 0);
    $marketListRoute = match($instrument->asset_class) { 'stock' => route('instruments.stocks'), 'forex' => route('instruments.forex'), 'crypto' => route('instruments.crypto'), default => route('instruments.index') };
@endphp

<div class="ui-page max-w-[1500px]" data-market-runtime>
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker text-[10px]">Trading · {{ strtoupper($instrument->asset_class) }}</p>
            <div class="mt-1 flex flex-wrap items-center gap-2">
                <h1 class="ui-heading !text-2xl">{{ $instrument->display_symbol }}</h1>
                <span class="rounded-full border border-border bg-muted/20 px-2 py-1 text-[10px] font-semibold uppercase tracking-[.11em]">{{ strtoupper($marketplace) }}</span>
                <span class="rounded-full border px-2 py-1 text-[10px] font-semibold uppercase tracking-[.11em] {{ $executionReady ? 'border-emerald-500/25 bg-emerald-500/10 text-emerald-600' : 'border-amber-500/25 bg-amber-500/10 text-amber-600' }}">
                    {{ $executionReady ? 'Execution ready' : 'Execution unavailable' }}
                </span>
            </div>
            <p class="ui-lead !max-w-3xl !text-[13px]">{{ $instrument->name }} · {{ $instrument->base_asset }}{{ $instrument->quote_asset ? ' / '.$instrument->quote_asset : '' }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('broker.portfolio') }}" class="ui-btn ui-btn-secondary">Portfolio</a>
            <a href="{{ route('broker.orders') }}" class="ui-btn ui-btn-secondary">Orders</a>
            <a href="{{ route('broker.positions') }}" class="ui-btn ui-btn-secondary">Positions</a>
            <a href="{{ $marketListRoute }}" class="ui-btn ui-btn-secondary">Markets</a>
        </div>
    </section>

    @if(session('success'))
        <div class="mb-4 rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-xs text-emerald-700 dark:text-emerald-400">{{ session('success') }}</div>
    @endif
    @if($errors->has('order'))
        <div class="mb-4 rounded-xl border border-red-500/20 bg-red-500/10 px-4 py-3 text-xs text-red-700 dark:text-red-400">{{ $errors->first('order') }}</div>
    @endif

    <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
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
            <p class="mt-1 text-[10px] text-muted-foreground">From stored previous close.</p>
        </div>
        <div class="ui-panel p-4">
            <p class="text-[10px] font-semibold uppercase tracking-[.11em] text-muted-foreground">Account balance</p>
            <p class="mt-2 text-xl font-semibold tabular-nums">{{ $walletCurrency }} {{ number_format((float) ($wallet?->available_balance ?? 0), 2) }}</p>
            <p class="mt-1 text-[10px] text-muted-foreground">Available after reserved balance.</p>
        </div>
        <div class="ui-panel p-4">
            <p class="text-[10px] font-semibold uppercase tracking-[.11em] text-muted-foreground">Owned exposure</p>
            <p class="mt-2 text-xl font-semibold tabular-nums">{{ number_format($holdingQty, 8) }}</p>
            <p class="mt-1 text-[10px] text-muted-foreground">{{ $instrument->base_asset ?: $instrument->symbol }} units in {{ strtoupper($marketplace) }}.</p>
        </div>
    </section>

    <div class="mt-5 grid gap-5 xl:grid-cols-[minmax(0,1.35fr)_minmax(340px,.65fr)]">
        <div class="space-y-5">
            <section>
                @include('trading.partials.analysis-chart', [
                    'instrument' => $instrument,
                    'analysis' => $analysis,
                    'chartHeight' => 'h-[340px] md:h-[440px]',
                ])
            </section>

            <section class="ui-panel overflow-hidden">
                <div class="border-b border-border/70 px-5 py-4">
                    <p class="ui-kicker">Exposure</p>
                    <h2 class="mt-1 text-[15px] font-semibold">Current position context</h2>
                </div>
                <div class="grid gap-px bg-border/60 sm:grid-cols-3">
                    <div class="bg-card p-4"><p class="text-[10px] uppercase tracking-[.11em] text-muted-foreground">Holding quantity</p><p class="mt-1 text-sm font-semibold">{{ number_format($holdingQty, 8) }}</p></div>
                    <div class="bg-card p-4"><p class="text-[10px] uppercase tracking-[.11em] text-muted-foreground">Open positions</p><p class="mt-1 text-sm font-semibold">{{ $positions->count() }}</p></div>
                    <div class="bg-card p-4"><p class="text-[10px] uppercase tracking-[.11em] text-muted-foreground">Recent orders</p><p class="mt-1 text-sm font-semibold">{{ $recentOrders->count() }}</p></div>
                </div>
            </section>
        </div>

        <aside class="ui-panel self-start overflow-hidden xl:sticky xl:top-4">
            <div class="border-b border-border/70 px-5 py-4">
                <p class="ui-kicker">Order Ticket</p>
                <h2 class="mt-1 text-[15px] font-semibold">Market order</h2>
                <p class="mt-1 text-[11px] leading-5 text-muted-foreground">A durable order record is created first. Capital moves only after the asset execution adapter validates the order and receives an executable quote.</p>
            </div>

            <form method="POST" action="{{ route('broker.orders.submit', ['assetClass' => $instrument->asset_class, 'symbol' => $instrument->symbol]) }}" class="space-y-4 p-5">
                @csrf
                <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', $idempotencyKey) }}">

                <div>
                    <label class="ui-label">Side</label>
                    <select name="side" class="ui-input w-full" required>
                        <option value="buy" @selected(old('side', 'buy') === 'buy')>Buy</option>
                        <option value="sell" @selected(old('side') === 'sell')>Sell</option>
                    </select>
                </div>

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

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="ui-label">Stop loss %</label>
                        <input name="stop_loss_percent" type="number" step="0.01" min="0.01" max="100" value="{{ old('stop_loss_percent') }}" class="ui-input w-full" placeholder="Optional">
                    </div>
                    <div>
                        <label class="ui-label">Take profit %</label>
                        <input name="take_profit_percent" type="number" step="0.01" min="0.01" max="100" value="{{ old('take_profit_percent') }}" class="ui-input w-full" placeholder="Optional">
                    </div>
                </div>

                <div>
                    <label class="ui-label">Trade duration (minutes)</label>
                    <input name="duration_minutes" type="number" min="1" max="43200" value="{{ old('duration_minutes') }}" class="ui-input w-full" placeholder="Optional">
                </div>

                <div class="rounded-xl border border-border bg-muted/10 p-3 text-[10px] leading-5 text-muted-foreground">
                    Market orders are immediate-or-fail. Stock execution follows the selected stock market session, Forex follows 24/5 session authority, and Crypto uses 24/7 spot execution. Final fill price comes from the execution adapter, not this displayed mark.
                </div>

                <button class="ui-btn ui-btn-primary w-full" @disabled(!$executionReady)>
                    {{ $executionReady ? 'Review & Execute Market Order' : 'Execution Currently Unavailable' }}
                </button>
            </form>
        </aside>
    </div>
</div>
</x-user-layout>
