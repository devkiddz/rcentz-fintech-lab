@php
    $isStock = $instrument->asset_class === 'stock';
    $isForex = $instrument->asset_class === 'forex';
    $isCrypto = $instrument->asset_class === 'crypto';

    $source = $isStock
        ? ($instrument->canonicalStock ?: $instrument->stock)
        : ($isForex
            ? ($instrument->canonicalForexPair ?: $instrument->forexPair)
            : ($isCrypto ? $instrument->canonicalCryptoPair : null));

    $updated = $source?->last_updated ?? $instrument->updated_at;
    $precision = max(0, min(8, (int)$instrument->price_precision));
    $prefix = $isStock ? currency_symbol() : '';
    $formatPrice = fn ($value) => $value === null ? '—' : $prefix.number_format((float)$value, $precision);

    $current = (float)($analysis['current_price'] ?? 0);
    $previous = (float)($analysis['previous_close'] ?? $current);
    $change = $current - $previous;
    $changePercent = $previous > 0 ? ($change / $previous) * 100 : 0;
    $trend = (string)($analysis['trend'] ?? 'Unavailable');
    $momentum = (float)($analysis['momentum_percent'] ?? 0);
    $momentumLabel = (string)($analysis['momentum_label'] ?? 'Unavailable');
    $historyPoints = count($analysis['series'] ?? ($analysis['timeframes']['1d'] ?? []));
    $sourceName = str_replace('_', ' ', strtoupper((string)($analysis['source'] ?? 'unavailable')));

    $preferredSessions = $source?->preferred_sessions ?? ($analysis['preferred_sessions'] ?? []);
    $activeSessions = $analysis['active_sessions'] ?? [];
    $preferredSessions = is_array($preferredSessions) ? $preferredSessions : [];
    $activeSessions = is_array($activeSessions) ? $activeSessions : [];

    $backRoute = match($instrument->asset_class) {
        'stock' => $admin ? route('admin.instruments.stocks') : route('instruments.stocks'),
        'forex' => $admin ? route('admin.instruments.forex') : route('instruments.forex'),
        'crypto' => $admin ? route('admin.instruments.crypto') : route('instruments.crypto'),
        default => $admin ? route('admin.instruments.index') : route('instruments.index'),
    };
@endphp

<div class="ui-page max-w-[1600px]" data-market-runtime>
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker text-[10px]">Market Instrument · {{ strtoupper($instrument->asset_class) }}</p>
            <div class="mt-1 flex flex-wrap items-center gap-2">
                <h1 class="ui-heading !text-2xl">{{ $instrument->display_symbol }}</h1>
                <span class="inline-flex rounded-full border border-border px-2 py-1 text-[10px] font-semibold uppercase tracking-[.11em]">{{ strtoupper($instrument->asset_class) }}</span>
                <span class="inline-flex rounded-full border border-border bg-muted/20 px-2 py-1 text-[10px] font-semibold uppercase tracking-[.11em] text-muted-foreground">{{ strtoupper($marketplace) }} PRICE</span>
            </div>
            <p class="ui-lead !max-w-3xl !text-[13px]">{{ $instrument->name }}</p>
        </div>
        <a href="{{ $backRoute }}" class="ui-btn ui-btn-secondary"><i data-lucide="arrow-left" class="h-4 w-4"></i>Back to {{ ucfirst($instrument->asset_class) }}</a>
    </section>

    <section class="mt-5 grid grid-cols-2 gap-3 lg:grid-cols-3 xl:grid-cols-6">
        <div class="ui-panel p-4">
            <p class="text-[10px] font-semibold uppercase tracking-[.11em] text-muted-foreground">Market Price</p>
            <p class="mt-2 text-lg font-semibold tabular-nums"
               data-market-price-instrument="{{ $instrument->id }}"
               data-marketplace="{{ $marketplace }}">{{ $formatPrice($current) }}</p>
        </div>
        <div class="ui-panel p-4">
            <p class="text-[10px] font-semibold uppercase tracking-[.11em] text-muted-foreground">Previous</p>
            <p class="mt-2 text-lg font-semibold tabular-nums"
               data-market-previous-instrument="{{ $instrument->id }}"
               data-marketplace="{{ $marketplace }}">{{ $formatPrice($previous) }}</p>
        </div>
        <div class="ui-panel p-4">
            <p class="text-[10px] font-semibold uppercase tracking-[.11em] text-muted-foreground">Price Move</p>
            <p class="mt-2 text-lg font-semibold tabular-nums {{ $change >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                {{ $change >= 0 ? '+' : '-' }}{{ $formatPrice(abs($change)) }}
            </p>
            <p class="mt-1 text-[11px] font-semibold {{ $changePercent >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $changePercent >= 0 ? '+' : '' }}{{ number_format($changePercent,2) }}%</p>
        </div>
        <div class="ui-panel p-4">
            <p class="text-[10px] font-semibold uppercase tracking-[.11em] text-muted-foreground">Trend</p>
            <p class="mt-2 text-lg font-semibold">{{ $trend }}</p>
            <p class="mt-1 text-[11px] text-muted-foreground">{{ $momentumLabel }}</p>
        </div>
        <div class="ui-panel p-4">
            <p class="text-[10px] font-semibold uppercase tracking-[.11em] text-muted-foreground">Momentum</p>
            <p class="mt-2 text-lg font-semibold tabular-nums {{ $momentum >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $momentum >= 0 ? '+' : '' }}{{ number_format($momentum,2) }}%</p>
            <p class="mt-1 text-[11px] text-muted-foreground">Stored period</p>
        </div>
        <div class="ui-panel p-4">
            <p class="text-[10px] font-semibold uppercase tracking-[.11em] text-muted-foreground">State</p>
            <p class="mt-2 text-lg font-semibold">{{ $instrument->is_active ? 'ACTIVE' : 'INACTIVE' }}</p>
            <p class="mt-1 text-[11px] text-muted-foreground">{{ number_format($historyPoints) }} history points</p>
        </div>
    </section>

    @if(!empty($analysis['analysis_error']))
        <div class="mt-4 rounded-xl border border-amber-500/25 bg-amber-500/10 px-4 py-3 text-[11px] text-amber-700 dark:text-amber-300">
            Analysis adapter status: {{ $analysis['analysis_error'] }}
        </div>
    @endif

    <section class="mt-5">
        @include('trading.partials.analysis-chart', [
            'instrument' => $instrument,
            'analysis' => $analysis,
            'chartHeight' => 'h-[320px] md:h-[420px]',
        ])
    </section>

    <section class="mt-5 grid gap-4 xl:grid-cols-[1.15fr_.85fr]">
        <div class="ui-panel overflow-hidden">
            <div class="border-b border-border/70 px-5 py-4">
                <p class="ui-kicker">Market Intelligence</p>
                <h2 class="mt-1 text-[15px] font-semibold">Technical profile</h2>
                <p class="mt-1 text-[11px] text-muted-foreground">Shared analysis contract resolved for this MarketInstrument.</p>
            </div>
            <div class="grid gap-px bg-border/60 sm:grid-cols-2 lg:grid-cols-3">
                @foreach([
                    ['Support',$formatPrice($analysis['support'] ?? null)],
                    ['Resistance',$formatPrice($analysis['resistance'] ?? null)],
                    ['SMA 20',$formatPrice($analysis['sma20'] ?? null)],
                    ['SMA 50',$formatPrice($analysis['sma50'] ?? null)],
                    ['SMA 200',$formatPrice($analysis['sma200'] ?? null)],
                    ['Risk / Reward',$analysis['risk_reward'] ?? '—'],
                    ['Analysis Source',$sourceName],
                    ['History Points',number_format($historyPoints)],
                    ['Runtime Source',strtoupper($marketplace)],
                ] as [$label,$value])
                    <div class="bg-card px-5 py-4">
                        <p class="text-[10px] font-semibold uppercase tracking-[.11em] text-muted-foreground">{{ $label }}</p>
                        <p class="mt-1 text-[12px] font-semibold">{{ $value }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="ui-panel overflow-hidden">
            <div class="border-b border-border/70 px-5 py-4">
                <p class="ui-kicker">Instrument Authority</p>
                <h2 class="mt-1 text-[15px] font-semibold">Canonical identity</h2>
                <p class="mt-1 text-[11px] text-muted-foreground">Canonical instrument identity used by pricing, charts, Signals and future execution adapters.</p>
            </div>
            <div class="divide-y divide-border/70">
                @foreach([
                    ['Instrument ID',$instrument->id],
                    ['Canonical Symbol',$instrument->symbol],
                    ['Display Symbol',$instrument->display_symbol],
                    ['Asset Class',strtoupper($instrument->asset_class)],
                    ['Market',$instrument->market ?: '—'],
                    ['Base Asset',$instrument->base_asset ?: '—'],
                    ['Quote Asset',$instrument->quote_asset ?: '—'],
                    ['Price Precision',$instrument->price_precision],
                    ['Tick / Pip',$instrument->pip_size ?: 'Auto'],
                ] as [$label,$value])
                    <div class="flex items-start justify-between gap-4 px-5 py-3.5 text-[11px]">
                        <span class="text-muted-foreground">{{ $label }}</span>
                        <span class="text-right font-semibold">{{ $value }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    @if($isForex)
        <section class="ui-panel mt-5 overflow-hidden">
            <div class="border-b border-border/70 px-5 py-4">
                <p class="ui-kicker">Forex Profile</p>
                <h2 class="mt-1 text-[15px] font-semibold">{{ $instrument->display_symbol }} market context</h2>
                <p class="mt-1 text-[11px] text-muted-foreground">FX-specific metadata layered underneath the shared MarketInstrument runtime.</p>
            </div>

            <div class="grid gap-px bg-border/60 sm:grid-cols-2 xl:grid-cols-4">
                @foreach([
                    ['Base Currency',$source?->base_currency ?: $instrument->base_asset ?: '—'],
                    ['Quote Currency',$source?->quote_currency ?: $instrument->quote_asset ?: '—'],
                    ['Pip Size',$source?->pip_size ?: $instrument->pip_size ?: '—'],
                    ['Precision',$source?->price_precision ?? $instrument->price_precision],
                    ['Market Session',$analysis['market_session'] ?? 'Not resolved'],
                    ['Preferred Session Active',!empty($analysis['preferred_session_active']) ? 'YES' : 'NO'],
                    ['External Feed',isset($source->external_feed_enabled) ? ($source->external_feed_enabled ? 'ENABLED' : 'DISABLED') : '—'],
                    ['Last Feed Update',$updated ? $updated->format('M j, Y g:i A') : 'Not available'],
                ] as [$label,$value])
                    <div class="bg-card px-5 py-4">
                        <p class="text-[10px] font-semibold uppercase tracking-[.11em] text-muted-foreground">{{ $label }}</p>
                        <p class="mt-1 text-[12px] font-semibold">{{ $value }}</p>
                    </div>
                @endforeach
            </div>

            <div class="grid gap-4 border-t border-border/70 p-5 lg:grid-cols-2">
                <div class="rounded-xl border border-border bg-muted/10 p-4">
                    <p class="text-[10px] font-semibold uppercase tracking-[.11em] text-muted-foreground">Preferred Sessions</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @forelse($preferredSessions as $session)
                            <span class="rounded-full border border-border bg-background px-2.5 py-1 text-[10px] font-semibold">{{ str_replace('_',' ',strtoupper((string)$session)) }}</span>
                        @empty
                            <span class="text-[11px] text-muted-foreground">No preferred session metadata.</span>
                        @endforelse
                    </div>
                </div>
                <div class="rounded-xl border border-border bg-muted/10 p-4">
                    <p class="text-[10px] font-semibold uppercase tracking-[.11em] text-muted-foreground">Active Sessions</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @forelse($activeSessions as $session)
                            <span class="rounded-full border border-emerald-500/25 bg-emerald-500/10 px-2.5 py-1 text-[10px] font-semibold text-emerald-600">{{ str_replace('_',' ',strtoupper((string)$session)) }}</span>
                        @empty
                            <span class="text-[11px] text-muted-foreground">No preferred session currently active.</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>
    @elseif($isCrypto)
        <section class="ui-panel mt-5 overflow-hidden">
            <div class="border-b border-border/70 px-5 py-4">
                <p class="ui-kicker">Crypto Profile</p>
                <h2 class="mt-1 text-[15px] font-semibold">{{ $instrument->display_symbol }} · 24/7 market context</h2>
                <p class="mt-1 text-[11px] text-muted-foreground">Crypto-specific market data layered underneath the same MarketInstrument price, analysis and chart runtime used by Stocks and Forex.</p>
            </div>
            <div class="grid gap-px bg-border/60 sm:grid-cols-2 xl:grid-cols-4">
                @foreach([
                    ['Base Asset',$source?->base_asset ?: $instrument->base_asset ?: '—'],
                    ['Quote Asset',$source?->quote_asset ?: $instrument->quote_asset ?: '—'],
                    ['Market Hours','24 / 7'],
                    ['Precision',$source?->price_precision ?? $instrument->price_precision],
                    ['Minimum Tick',$source?->minimum_tick ?: $instrument->pip_size ?: '—'],
                    ['External Feed',isset($source->external_feed_enabled) ? ($source->external_feed_enabled ? 'ENABLED' : 'DISABLED') : '—'],
                    ['Daily History',number_format($historyPoints).' points'],
                    ['Last Feed Update',$updated ? $updated->format('M j, Y g:i A') : 'Not available'],
                ] as [$label,$value])
                    <div class="bg-card px-5 py-4">
                        <p class="text-[10px] font-semibold uppercase tracking-[.11em] text-muted-foreground">{{ $label }}</p>
                        <p class="mt-1 text-[12px] font-semibold">{{ $value }}</p>
                    </div>
                @endforeach
            </div>
            <div class="border-t border-border/70 p-5">
                <div class="rounded-xl border border-border bg-muted/10 p-4">
                    <p class="text-[10px] font-semibold uppercase tracking-[.11em] text-muted-foreground">Session Authority</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span class="rounded-full border border-emerald-500/25 bg-emerald-500/10 px-2.5 py-1 text-[10px] font-semibold text-emerald-600">24 / 7 ACTIVE</span>
                        <span class="rounded-full border border-border bg-background px-2.5 py-1 text-[10px] font-semibold">NO FOREX SESSION GATING</span>
                        <span class="rounded-full border border-border bg-background px-2.5 py-1 text-[10px] font-semibold">NO STOCK EXCHANGE HOURS</span>
                    </div>
                </div>
            </div>
        </section>
    @endif

    <section class="ui-panel mt-5 p-5">
        <div class="flex items-start gap-3">
            <div class="mt-0.5 rounded-lg border border-border bg-muted/30 p-2"><i data-lucide="shield-check" class="h-4 w-4"></i></div>
            <div>
                <p class="text-[13px] font-semibold">Execution boundary</p>
                <p class="mt-1 max-w-4xl text-[11px] leading-5 text-muted-foreground">This workspace proves market identity, routed price and analysis truth. Stock execution remains on the established Stock trading ledger. Forex and Crypto execution stay disabled until their holdings, transaction and position contracts are migrated to the multi-asset execution layer.</p>
            </div>
        </div>
    </section>
</div>
