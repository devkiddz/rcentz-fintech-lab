@php
    $admin = (bool) ($admin ?? false);
    $allAssetsRoute = $admin
        ? route('admin.investments.control.index')
        : route('investments.index');

    $reserveAssets = $instrument->assets
        ->filter(fn ($asset) => (bool) $asset->is_reserve_backing && $asset->status === 'active')
        ->values();

    $referenceReserveTotal = (float) $reserveAssets->sum('current_valuation');
    $referenceAsset = $instrument->reference_asset_id
        ? $reserveAssets->firstWhere('id', (int) $instrument->reference_asset_id)
        : null;

    if ($referenceAsset) {
        $referenceAsset->loadMissing(['marketInstrument', 'privateMarketReference']);
    }

    $privateReference = $referenceAsset?->privateMarketReference;
    $referenceMarket = $privateReference ? null : $referenceAsset?->marketInstrument;

    $referenceName = $privateReference?->name
        ?: $referenceMarket?->name
        ?: $referenceAsset?->name;

    $referenceSymbol = $privateReference
        ? collect([
            $privateReference->location,
            ucwords(str_replace('_', ' ', (string) $privateReference->category)),
        ])->filter()->implode(' · ')
        : ($referenceMarket?->display_symbol
            ?: ($referenceAsset ? ucwords(str_replace('_',' ',(string)$referenceAsset->asset_type)) : null));

    $referenceCode = $privateReference?->symbol
        ?: $referenceMarket?->symbol
        ?: ($referenceAsset ? strtoupper(substr((string)$referenceAsset->asset_type,0,10)) : null);

    $referencePrice = $privateReference
        ? (float) $privateReference->current_price
        : ($referenceAsset ? (float) ($referenceAsset->current_unit_price ?? 0) : 0.0);

    $previousPrice = $privateReference && (float) $privateReference->previous_price > 0
        ? (float) $privateReference->previous_price
        : null;

    $updatedAt = $privateReference?->last_valued_at
        ?: $referenceAsset?->last_valued_at
        ?: $referenceAsset?->updated_at;

    $precision = $referenceMarket ? max(0,min(8,(int)$referenceMarket->price_precision)) : 2;
    $quoteAsset = $referenceMarket ? trim((string)$referenceMarket->quote_asset) : '';
    $privateCurrency = strtoupper(trim((string) ($privateReference?->currency ?? 'USD')));
    $privateCurrencyPrefix = $privateCurrency === 'USD'
        ? currency_symbol()
        : ($privateCurrency !== '' ? $privateCurrency.' ' : '');
    $marketplace = null;

    if ($referenceAsset && $referenceMarket) {
        try {
            $marketRouter = app(\App\Services\MarketPriceRouter::class);
            $marketplace = $marketRouter->activeMarketplace();
            $marketAnalysis = app(\App\Services\MarketInstrumentAnalysisService::class)
                ->forInstrument($referenceMarket, $marketplace);

            $marketCurrent = (float) ($marketAnalysis['current_price'] ?? 0);
            $marketPrevious = (float) ($marketAnalysis['previous_close'] ?? 0);

            if ($marketCurrent > 0) {
                $referencePrice = $marketCurrent;
            }
            if ($marketPrevious > 0) {
                $previousPrice = $marketPrevious;
            }

            if (!empty($marketAnalysis['captured_at'])) {
                $updatedAt = \Carbon\Carbon::parse($marketAnalysis['captured_at']);
            } else {
                $referenceMarket->loadMissing([
                    'canonicalStock',
                    'canonicalForexPair',
                    'canonicalCryptoPair',
                    'canonicalCommodityInstrument',
                    'controlledMarketInstrument',
                ]);

                $marketSource = match($referenceMarket->asset_class) {
                    \App\Models\MarketInstrument::ASSET_STOCK => $referenceMarket->canonicalStock ?: $referenceMarket->stock,
                    \App\Models\MarketInstrument::ASSET_FOREX => $referenceMarket->canonicalForexPair ?: $referenceMarket->forexPair,
                    \App\Models\MarketInstrument::ASSET_CRYPTO => $referenceMarket->canonicalCryptoPair,
                    \App\Models\MarketInstrument::ASSET_COMMODITY => $referenceMarket->canonicalCommodityInstrument,
                    default => null,
                };

                $updatedAt = $marketplace === 'controlled'
                    ? ($referenceMarket->controlledMarketInstrument?->last_moved_at ?: $updatedAt)
                    : ($marketSource?->last_updated ?: $marketSource?->updated_at ?: $updatedAt);
            }
        } catch (\Throwable) {
            // Fall back to the last validated reserve reference without inventing a price.
        }
    }

    // Legacy local reserve records remain readable until they are deliberately
    // linked to a first-class Private Market Reference.
    if ($referenceAsset && !$referenceMarket && !$privateReference) {
        $latestReferenceEvent = $referenceAsset->reserveEvents()
            ->whereNotNull('previous_unit_price')
            ->whereNotNull('new_unit_price')
            ->where('previous_unit_price', '>', 0)
            ->latest('effective_at')
            ->latest('id')
            ->first();

        if ($latestReferenceEvent) {
            $previousPrice = (float) $latestReferenceEvent->previous_unit_price;
            $updatedAt = $latestReferenceEvent->effective_at ?: $updatedAt;
        }
    }

    if ($referenceAsset && $referencePrice <= 0 && (float)$referenceAsset->reserve_quantity > 0) {
        $referencePrice = (float)$referenceAsset->current_valuation / (float)$referenceAsset->reserve_quantity;
    }

    if ($previousPrice !== null && $previousPrice <= 0) {
        $previousPrice = null;
    }

    $changeAmount = $previousPrice !== null ? $referencePrice - $previousPrice : null;
    $changePercent = ($changeAmount !== null && $previousPrice > 0)
        ? ($changeAmount / $previousPrice) * 100
        : null;

    $movement = $changeAmount === null || abs($changeAmount) < 0.00000001
        ? 'flat'
        : ($changeAmount > 0 ? 'up' : 'down');

    $movementClass = match($movement) {
        'up' => 'text-emerald-500',
        'down' => 'text-red-500',
        default => 'text-zinc-400',
    };

    $movementBg = match($movement) {
        'up' => 'bg-emerald-500/10',
        'down' => 'bg-red-500/10',
        default => 'bg-zinc-500/10',
    };

    $referencePriceText = $referenceAsset
        ? ($privateReference
            ? $privateCurrencyPrefix.number_format($referencePrice,2)
            : ($referenceMarket
                ? number_format($referencePrice,$precision).($quoteAsset !== '' ? ' '.$quoteAsset : '')
                : currency_symbol().number_format($referencePrice,2)))
        : '—';

    $previousPriceText = $previousPrice !== null
        ? ($privateReference
            ? $privateCurrencyPrefix.number_format($previousPrice,2)
            : ($referenceMarket
                ? number_format($previousPrice,$precision).($quoteAsset !== '' ? ' '.$quoteAsset : '')
                : currency_symbol().number_format($previousPrice,2)))
        : '—';

    $changeAmountText = $changeAmount !== null
        ? ($privateReference
            ? $privateCurrencyPrefix.number_format(abs($changeAmount),2)
            : number_format(abs($changeAmount),$precision))
        : null;

    $reserveUnit = $referenceAsset && trim((string)$referenceAsset->reserve_unit) !== ''
        ? (string)$referenceAsset->reserve_unit
        : ($privateReference && trim((string)$privateReference->reference_unit) !== ''
            ? (string)$privateReference->reference_unit
            : 'unit');
@endphp

<section class="-mt-px overflow-hidden rounded-b-2xl border border-t-0 border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950" data-r4c-r115-market-reference>
    <div class="flex min-h-12 items-center justify-between gap-3 border-t border-zinc-100 px-4 py-2.5 dark:border-zinc-900">
        <div class="flex min-w-0 items-center gap-3">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-zinc-50 dark:bg-zinc-900">
                <i data-lucide="radar" class="h-3.5 w-3.5 text-red-500"></i>
            </div>
            <div class="min-w-0">
                <p class="text-[9px] font-semibold uppercase tracking-[.14em] text-red-600">Market Reference</p>
                <p class="mt-0.5 truncate text-[10px] text-zinc-500">Underlying reserve reference · Total reserve {{ currency_symbol() }}{{ number_format($referenceReserveTotal,2) }}</p>
            </div>
        </div>
        <a href="{{ $allAssetsRoute }}" class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-zinc-200 px-3 py-2 text-[9px] font-semibold text-zinc-700 transition hover:border-red-200 hover:text-red-600 dark:border-zinc-800 dark:text-zinc-200 dark:hover:border-red-950 dark:hover:text-red-400">
            View All Assets
            <i data-lucide="layout-grid" class="h-3 w-3"></i>
        </a>
    </div>

    @if($referenceAsset)
        <div class="overflow-x-auto border-t border-zinc-100 dark:border-zinc-900 [scrollbar-width:thin]">
            <article style="display:grid;grid-template-columns:minmax(255px,1.35fr) minmax(205px,1fr) minmax(175px,.8fr) minmax(185px,.9fr) minmax(220px,1fr);min-width:1040px;align-items:stretch;">
                <div class="flex min-w-0 items-center gap-3 px-4 py-3.5">
                    <div class="flex h-10 min-w-14 items-center justify-center rounded-xl border border-zinc-200 bg-zinc-50 px-2 text-[9px] font-semibold dark:border-zinc-800 dark:bg-zinc-900">{{ $referenceCode }}</div>
                    <div class="min-w-0">
                        <p class="truncate text-[11px] font-semibold text-zinc-950 dark:text-white">{{ $referenceName }}</p>
                        <p class="mt-0.5 truncate text-[9px] text-zinc-500">{{ $referenceSymbol }}</p>
                    </div>
                </div>

                <div class="border-l border-zinc-100 px-4 py-3.5 dark:border-zinc-900">
                    <p class="text-[8px] font-semibold uppercase tracking-[.11em] text-zinc-400">Reference Price</p>
                    <div class="mt-1 flex items-center gap-2">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $movementBg }} {{ $movementClass }}">
                            <i data-lucide="chart-no-axes-combined" class="h-4 w-4"></i>
                        </span>
                        <div class="min-w-0">
                            <p class="text-[13px] font-semibold tabular-nums {{ $movementClass }}">{{ $referencePriceText }}</p>
                            <p class="mt-0.5 text-[9px] font-semibold tabular-nums {{ $movementClass }}">
                                @if($changeAmount !== null && $changePercent !== null)
                                    {{ $changeAmount >= 0 ? '+' : '-' }}{{ $changeAmountText }}
                                    ({{ $changePercent >= 0 ? '+' : '' }}{{ number_format($changePercent,2) }}%)
                                @else
                                    —
                                @endif
                            </p>
                        </div>
                    </div>
                    <p class="mt-1 text-[8px] text-zinc-500">per {{ $reserveUnit }}</p>
                </div>

                <div class="border-l border-zinc-100 px-4 py-3.5 dark:border-zinc-900">
                    <p class="text-[8px] font-semibold uppercase tracking-[.11em] text-zinc-400">Reserve Quantity</p>
                    <p class="mt-1 text-[12px] font-semibold tabular-nums text-zinc-950 dark:text-white">{{ number_format((float)$referenceAsset->reserve_quantity,8) }}</p>
                    <p class="mt-0.5 text-[8px] text-zinc-500">{{ $reserveUnit }}</p>
                </div>

                <div class="border-l border-zinc-100 px-4 py-3.5 dark:border-zinc-900">
                    <p class="text-[8px] font-semibold uppercase tracking-[.11em] text-zinc-400">Reserve Value</p>
                    <p class="mt-1 text-[12px] font-semibold tabular-nums text-zinc-950 dark:text-white">{{ currency_symbol() }}{{ number_format((float)$referenceAsset->current_valuation,2) }}</p>
                    <p class="mt-0.5 text-[8px] text-zinc-500">Backing value</p>
                </div>

                <div class="border-l border-zinc-100 px-4 py-3.5 dark:border-zinc-900">
                    <p class="text-[8px] font-semibold uppercase tracking-[.11em] text-zinc-400">Last Updated</p>
                    <p class="mt-1 text-[10px] font-semibold text-zinc-950 dark:text-white">{{ $updatedAt ? $updatedAt->format('M j, Y · H:i') : '—' }}</p>
                    <p class="mt-1 text-[8px] uppercase tracking-[.08em] text-zinc-400">Previous Price</p>
                    <p class="mt-0.5 text-[10px] font-semibold tabular-nums text-zinc-700 dark:text-zinc-200">{{ $previousPriceText }}</p>
                </div>
            </article>
        </div>
    @else
        <div class="border-t border-zinc-100 px-4 py-4 dark:border-zinc-900">
            <p class="text-[10px] font-semibold text-zinc-700 dark:text-zinc-200">Market reference source has not been selected.</p>
            <p class="mt-1 text-[9px] text-zinc-500">Reserve backing remains intact. An administrator must select the single reference asset used beside the chart.</p>
        </div>
    @endif
</section>
