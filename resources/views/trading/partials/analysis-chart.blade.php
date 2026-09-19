@php
    $analysis = $analysis ?? [];
    $chartHeight = $chartHeight ?? 'h-[300px] md:h-[380px]';
    $stockModel = $stock ?? null;
    $marketInstrument = $instrument ?? null;

    if (! $marketInstrument && $stockModel) {
        $marketInstrument = $stockModel->marketInstrument ?: $stockModel->legacyMarketInstrument;
    }

    $assetClass = $marketInstrument?->asset_class ?: 'stock';
    $displaySymbol = $marketInstrument?->display_symbol ?: ($stockModel?->symbol ?: ($analysis['display_symbol'] ?? $analysis['symbol'] ?? 'MARKET'));
    $precision = max(0, min(8, (int) ($marketInstrument?->price_precision ?? $analysis['price_precision'] ?? 2)));
    $analysisMarketplace = $analysis['marketplace'] ?? (str_starts_with((string)($analysis['source'] ?? ''), 'controlled_') ? 'controlled' : 'live');
    $fallbackCurrent = $stockModel?->current_price ?? 0;
    $analysisCurrent = (float)($analysis['current_price'] ?? $fallbackCurrent);
    $analysisPrevious = (float)($analysis['previous_close'] ?? $analysisCurrent);
    $analysisChange = $analysisCurrent - $analysisPrevious;
    $analysisChangePercent = $analysisPrevious > 0 ? ($analysisChange / $analysisPrevious) * 100 : 0;
    $pricePrefix = $assetClass === 'stock' ? currency_symbol() : '';
    $formatMarketPrice = fn ($value) => $pricePrefix.number_format((float)$value, $precision);
@endphp

<section class="ui-panel overflow-hidden" data-market-runtime>
    <div class="flex flex-col gap-3 border-b border-border px-4 py-3 md:flex-row md:items-center md:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <p class="text-[9px] uppercase tracking-[.13em] text-muted-foreground">Price analysis</p>
                <span class="rounded-full border border-border bg-muted/20 px-2 py-0.5 text-[8px] font-semibold uppercase tracking-[.11em] text-muted-foreground">
                    {{ strtoupper($analysisMarketplace) }} · {{ strtoupper($assetClass) }}
                </span>
            </div>
            <div class="mt-1 flex flex-wrap items-baseline gap-2">
                <h2 class="text-sm font-semibold">{{ $displaySymbol }}</h2>
                <span class="text-base font-semibold tabular-nums"
                      @if($marketInstrument)
                          data-market-price-instrument="{{ $marketInstrument->id }}"
                      @elseif($stockModel)
                          data-market-price-symbol="{{ $stockModel->symbol }}"
                      @endif
                      data-marketplace="{{ $analysisMarketplace }}">{{ $formatMarketPrice($analysisCurrent) }}</span>
                <span class="text-xs font-semibold {{ $analysisChangePercent >= 0 ? 'text-emerald-600' : 'text-red-600' }}"
                      @if($marketInstrument)
                          data-market-change-percent-instrument="{{ $marketInstrument->id }}"
                      @elseif($stockModel)
                          data-market-change-percent-symbol="{{ $stockModel->symbol }}"
                      @endif
                      data-marketplace="{{ $analysisMarketplace }}">
                    {{ $analysisChangePercent >= 0 ? '+' : '' }}{{ number_format($analysisChangePercent,2) }}%
                </span>
            </div>
        </div>

        @php
            $availableFrames = collect($analysis['timeframes'] ?? [])
                ->filter(fn ($rows) => is_array($rows) && count($rows) >= 2)
                ->keys()
                ->all();

            $defaultFrame = $analysis['default_timeframe'] ?? '15m';

            $frameLabels = [
                '5m' => '5M',
                '15m' => '15M',
                '1h' => '1H',
                '4h' => '4H',
                '1d' => '1D',
                '1w' => '1W',
                '1m' => '1M',
                '3m' => '3M',
                '1y' => '1Y',
            ];
        @endphp

        <div class="flex w-full flex-col gap-2 md:w-auto md:items-end">
            <div class="flex max-w-full gap-1 overflow-x-auto rounded-xl border border-border bg-muted/10 p-1 [scrollbar-width:none]">
                @foreach($frameLabels as $frame => $label)
                    @php
                        $enabled = in_array($frame, $availableFrames, true);
                        $active = $frame === $defaultFrame;
                    @endphp
                    <button type="button"
                            data-rcentz-timeframe="{{ $frame }}"
                            @disabled(!$enabled)
                            title="{{ $enabled ? $label.' timeframe' : $label.' will unlock when enough stored market observations exist' }}"
                            class="shrink-0 rounded-lg px-2.5 py-1.5 text-[10px] font-medium transition {{ $active ? 'bg-muted text-foreground' : 'text-muted-foreground' }} {{ !$enabled ? 'cursor-not-allowed opacity-35' : 'hover:text-foreground' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <div class="flex gap-1 rounded-xl border border-border bg-muted/10 p-1">
                <button type="button" data-rcentz-chart-mode="line" class="rounded-lg bg-muted px-2.5 py-1.5 text-[9px] font-medium text-foreground transition">Line</button>
                <button type="button" data-rcentz-chart-mode="candles" class="rounded-lg px-2.5 py-1.5 text-[9px] font-medium text-muted-foreground transition hover:text-foreground">Candles</button>
                <button type="button" data-rcentz-chart-mode="area" class="rounded-lg px-2.5 py-1.5 text-[9px] font-medium text-muted-foreground transition hover:text-foreground">Area</button>
            </div>
        </div>
    </div>

    @if(!empty($analysis['has_chart']))
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-border px-4 py-2">
            <p class="text-[9px] text-muted-foreground">Market price history · automatic runtime updates</p>
            <p class="text-[9px] text-muted-foreground">Grey timeframes unlock as enough stored observations accumulate.</p>
        </div>

        <div class="border-b border-border px-4 py-2">
            <div class="flex flex-wrap gap-x-4 gap-y-1 text-[9px] text-muted-foreground">
                @if(!empty($analysis['sma20']))
                    <span><i class="mr-1 inline-block h-1.5 w-1.5 rounded-full bg-sky-500"></i>SMA 20 {{ $formatMarketPrice($analysis['sma20']) }}</span>
                @endif
                @if(!empty($analysis['sma50']))
                    <span><i class="mr-1 inline-block h-1.5 w-1.5 rounded-full bg-violet-500"></i>SMA 50 {{ $formatMarketPrice($analysis['sma50']) }}</span>
                @endif
                @if(!empty($analysis['sma200']))
                    <span><i class="mr-1 inline-block h-1.5 w-1.5 rounded-full bg-purple-500"></i>SMA 200 {{ $formatMarketPrice($analysis['sma200']) }}</span>
                @endif
                @if(!empty($analysis['support']))
                    <span>Support {{ $formatMarketPrice($analysis['support']) }}</span>
                @endif
                @if(!empty($analysis['resistance']))
                    <span>Resistance {{ $formatMarketPrice($analysis['resistance']) }}</span>
                @endif
            </div>
        </div>

        <div class="{{ $chartHeight }} p-2 md:p-3">
            <div class="h-full w-full"
                 data-rcentz-analysis
                 @if($marketInstrument)
                     data-market-analysis-instrument="{{ $marketInstrument->id }}"
                 @elseif($stockModel)
                     data-market-analysis-symbol="{{ $stockModel->symbol }}"
                 @endif
                 data-marketplace="{{ $analysisMarketplace }}"
                 data-default-timeframe="{{ $defaultFrame }}"
                 data-analysis='@json($analysis)'></div>
        </div>
    @else
        <div class="flex {{ $chartHeight }} items-center justify-center p-5">
            <div class="max-w-sm rounded-2xl border border-border bg-muted/10 px-5 py-4 text-center">
                <i data-lucide="chart-no-axes-combined" class="mx-auto h-5 w-5 text-sky-500"></i>
                <p class="mt-3 text-xs font-medium">Building market history</p>
                <p class="mt-1 text-[10px] leading-4 text-muted-foreground">The chart appears when enough stored market history or controlled observations are available.</p>
            </div>
        </div>
    @endif
</section>
