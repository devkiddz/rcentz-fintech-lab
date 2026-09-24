@php
    $analysis = $analysis ?? [];
    $precision = max(
        0,
        min(8, (int) ($analysis['price_precision'] ?? 2))
    );
    $current = (float) ($analysis['current_price'] ?? 0);
    $previous = (float) ($analysis['previous_close'] ?? $current);
    $change = (float) ($analysis['change_amount'] ?? ($current - $previous));
    $changePercent = (float) (
        $analysis['change_percent']
        ?? ($previous > 0 ? (($change / $previous) * 100) : 0)
    );
    $frames = [
        '1w' => '1W',
        '1m' => '1M',
        '3m' => '3M',
        '1y' => '1Y',
        'all' => 'ALL',
    ];
    $defaultFrame = $analysis['default_timeframe'] ?? 'all';
    $currency = strtoupper(
        (string) ($analysis['quote_asset'] ?? 'USD')
    );
@endphp

<section class="ui-panel overflow-hidden">
    <div class="flex flex-col gap-3 border-b border-border px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="ui-kicker">Base Asset Performance</p>
            <div class="mt-1 flex flex-wrap items-baseline gap-2">
                <h2 class="text-sm font-semibold">{{ $symbol }}</h2>
                <span class="text-xl font-semibold tabular-nums"
                      data-base-chart-current>
                    {{ number_format($current, $precision) }} {{ $currency }}
                </span>
                <span class="text-xs font-semibold {{ $changePercent >= 0 ? 'text-emerald-600' : 'text-red-600' }}"
                      data-base-chart-change>
                    {{ $changePercent >= 0 ? '+' : '' }}{{ number_format($changePercent,2) }}%
                </span>
            </div>
            <p class="mt-1 text-[10px] text-muted-foreground">
                {{ $kind === 'public'
                    ? 'Public market price history · sourced from the approved market authority'
                    : 'Private valuation history · every approved RCENTZ valuation becomes a performance point' }}
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <div class="flex max-w-full gap-1 overflow-x-auto rounded-xl border border-border bg-muted/10 p-1">
                @foreach($frames as $frame => $label)
                    @php
                        $enabled = count(
                            $analysis['timeframes'][$frame] ?? []
                        ) >= 2;
                    @endphp
                    <button type="button"
                            data-rcentz-timeframe="{{ $frame }}"
                            @disabled(!$enabled)
                            class="rounded-lg px-2.5 py-1.5 text-[10px] font-medium {{ $frame === $defaultFrame ? 'bg-muted text-foreground' : 'text-muted-foreground' }} {{ !$enabled ? 'cursor-not-allowed opacity-35' : '' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <div class="flex gap-1 rounded-xl border border-border bg-muted/10 p-1">
                <button type="button" data-rcentz-chart-mode="line" class="rounded-lg bg-muted px-2.5 py-1.5 text-[10px] font-medium">Line</button>
                @if(!empty($analysis['supports_candles']))
                    <button type="button" data-rcentz-chart-mode="candles" class="rounded-lg px-2.5 py-1.5 text-[10px] font-medium text-muted-foreground">Candles</button>
                @endif
                <button type="button" data-rcentz-chart-mode="area" class="rounded-lg px-2.5 py-1.5 text-[10px] font-medium text-muted-foreground">Area</button>
            </div>
        </div>
    </div>

    <div class="relative p-3"
         style="height:430px; min-height:430px;">
        <div class="h-full w-full"
             data-rcentz-analysis
             data-default-timeframe="{{ $defaultFrame }}"
             data-analysis='@json($analysis)'></div>

        @if(empty($analysis['has_chart']))
            <div class="absolute inset-3 flex items-center justify-center"
                 data-base-chart-empty>
                <div class="max-w-md rounded-2xl border border-border bg-card/95 px-5 py-4 text-center shadow-sm">
                    <i data-lucide="chart-no-axes-combined" class="mx-auto h-5 w-5 text-sky-500"></i>
                    <p class="mt-3 text-xs font-semibold">Performance history is still being established</p>
                    <p class="mt-1 text-[10px] leading-4 text-muted-foreground">
                        @if($kind === 'private')
                            Use Move Now or enable Auto movement. The next price point will open the live performance chart.
                        @else
                            The chart appears when at least two public-market history observations are available.
                        @endif
                    </p>
                </div>
            </div>
        @endif
    </div>
</section>
