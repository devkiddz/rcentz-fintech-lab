@php
    $analysis = $analysis ?? [];
    $chartHeight = $chartHeight ?? 'h-[300px] md:h-[380px]';
@endphp

<section class="ui-panel overflow-hidden">
    <div class="flex flex-col gap-3 border-b border-border px-4 py-3 md:flex-row md:items-center md:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <p class="text-[9px] uppercase tracking-[.13em] text-muted-foreground">Price analysis</p>
                @if(!empty($analysis['source']))
                    <span class="rounded-full border border-border bg-muted px-2 py-0.5 text-[8px] font-semibold uppercase tracking-[.1em] text-muted-foreground">
                        {{ str_replace('_',' ',$analysis['source']) }}
                    </span>
                @endif
            </div>
            <div class="mt-1 flex flex-wrap items-baseline gap-2">
                <h2 class="text-sm font-semibold">{{ $stock->symbol }}</h2>
                <span class="text-base font-semibold tabular-nums">{{ $stock->formatted_current_price }}</span>
                <span class="text-xs font-semibold {{ $stock->change_percentage >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                    {{ $stock->formatted_change_percentage }}
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
                            title="{{ $enabled ? $label.' timeframe' : $label.' will unlock when enough stored market data exists' }}"
                            class="shrink-0 rounded-lg px-2.5 py-1.5 text-[10px] font-medium transition {{ $active ? 'bg-muted text-foreground' : 'text-muted-foreground' }} {{ !$enabled ? 'cursor-not-allowed opacity-35' : 'hover:text-foreground' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <div class="flex gap-1 rounded-xl border border-border bg-muted/10 p-1">
                <button type="button" data-rcentz-chart-mode="line" class="rounded-lg bg-muted px-2.5 py-1.5 text-[9px] font-medium text-foreground transition">
                    Line
                </button>
                <button type="button" data-rcentz-chart-mode="candles" class="rounded-lg px-2.5 py-1.5 text-[9px] font-medium text-muted-foreground transition hover:text-foreground">
                    Candles
                </button>
                <button type="button" data-rcentz-chart-mode="area" class="rounded-lg px-2.5 py-1.5 text-[9px] font-medium text-muted-foreground transition hover:text-foreground">
                    Area
                </button>
            </div>
        </div>
    </div>

    @if(!empty($analysis['has_chart']))
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-border px-4 py-2">
            <p class="text-[9px] text-muted-foreground">
                Historical: Alpha Vantage daily OHLCV · Intraday: persisted live feed
            </p>
            <p class="text-[9px] text-muted-foreground">
                Grey timeframes unlock as enough real observations accumulate.
            </p>
        </div>

        <div class="border-b border-border px-4 py-2">
            <div class="flex flex-wrap gap-x-4 gap-y-1 text-[9px] text-muted-foreground">
                @if(!empty($analysis['sma20']))
                    <span><i class="mr-1 inline-block h-1.5 w-1.5 rounded-full bg-sky-500"></i>SMA 20 {{ currency_symbol() }}{{ number_format($analysis['sma20'],2) }}</span>
                @endif
                @if(!empty($analysis['sma50']))
                    <span><i class="mr-1 inline-block h-1.5 w-1.5 rounded-full bg-violet-500"></i>SMA 50 {{ currency_symbol() }}{{ number_format($analysis['sma50'],2) }}</span>
                @endif
                @if(!empty($analysis['sma200']))
                    <span><i class="mr-1 inline-block h-1.5 w-1.5 rounded-full bg-purple-500"></i>SMA 200 {{ currency_symbol() }}{{ number_format($analysis['sma200'],2) }}</span>
                @endif
                @if(!empty($analysis['support']))
                    <span>Support {{ currency_symbol() }}{{ number_format($analysis['support'],2) }}</span>
                @endif
                @if(!empty($analysis['resistance']))
                    <span>Resistance {{ currency_symbol() }}{{ number_format($analysis['resistance'],2) }}</span>
                @endif
            </div>
        </div>

        <div class="{{ $chartHeight }} p-2 md:p-3">
            <div class="h-full w-full"
                 data-rcentz-analysis
                 data-default-timeframe="{{ $defaultFrame }}"
                 data-analysis='@json($analysis)'></div>
        </div>
    @else
        <div class="flex {{ $chartHeight }} items-center justify-center p-5">
            <div class="max-w-sm rounded-2xl border border-border bg-muted/10 px-5 py-4 text-center">
                <i data-lucide="chart-no-axes-combined" class="mx-auto h-5 w-5 text-sky-500"></i>
                <p class="mt-3 text-xs font-medium">Building market history</p>
                <p class="mt-1 text-[10px] leading-4 text-muted-foreground">The analysis chart appears when stored stock history or live candles are available.</p>
            </div>
        </div>
    @endif
</section>
