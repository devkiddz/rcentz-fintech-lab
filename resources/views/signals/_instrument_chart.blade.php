@php
    $analysis = $analysis ?? [];
    $chartHeight = $chartHeight ?? 'h-[300px] md:h-[380px]';
    $symbol = $signal->instrument_symbol;
    $precision = $signal->price_precision;
    $current = (float) ($analysis['current_price'] ?? $signal->entry_min ?? 0);
    $previous = (float) ($analysis['previous_close'] ?? $current);
    $change = $current - $previous;
    $changePercent = $previous > 0 ? ($change / $previous) * 100 : 0;
    $availableFrames = collect($analysis['timeframes'] ?? [])
        ->filter(fn ($rows) => is_array($rows) && count($rows) >= 2)
        ->keys()
        ->all();
    $defaultFrame = $analysis['default_timeframe'] ?? $signal->timeframe ?? '1d';
    $frameLabels = ['5m'=>'5M','15m'=>'15M','1h'=>'1H','4h'=>'4H','1d'=>'1D','1w'=>'1W','1m'=>'1M','3m'=>'3M','1y'=>'1Y'];
@endphp

<section class="ui-panel overflow-hidden">
    <div class="flex flex-col gap-3 border-b border-border px-4 py-3 md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-[9px] uppercase tracking-[.13em] text-muted-foreground">{{ strtoupper($signal->asset_class) }} price analysis</p>
            <div class="mt-1 flex flex-wrap items-baseline gap-2">
                <h2 class="text-sm font-semibold">{{ $symbol }}</h2>
                <span class="text-base font-semibold tabular-nums">{{ number_format($current, $precision) }}</span>
                <span class="text-xs font-semibold {{ $changePercent >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                    {{ $changePercent >= 0 ? '+' : '' }}{{ number_format($changePercent, 2) }}%
                </span>
            </div>
        </div>
        <div class="flex w-full flex-col gap-2 md:w-auto md:items-end">
            <div class="flex max-w-full gap-1 overflow-x-auto rounded-xl border border-border bg-muted/10 p-1 [scrollbar-width:none]">
                @foreach($frameLabels as $frame => $label)
                    @php $enabled = in_array($frame, $availableFrames, true); $active = $frame === $defaultFrame; @endphp
                    <button type="button" data-rcentz-timeframe="{{ $frame }}" @disabled(!$enabled)
                        class="shrink-0 rounded-lg px-2.5 py-1.5 text-[10px] font-medium transition {{ $active ? 'bg-muted text-foreground' : 'text-muted-foreground' }} {{ !$enabled ? 'cursor-not-allowed opacity-35' : 'hover:text-foreground' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
            <div class="flex gap-1 rounded-xl border border-border bg-muted/10 p-1">
                <button type="button" data-rcentz-chart-mode="line" class="rounded-lg bg-muted px-2.5 py-1.5 text-[9px] font-medium text-foreground">Line</button>
                <button type="button" data-rcentz-chart-mode="candles" class="rounded-lg px-2.5 py-1.5 text-[9px] font-medium text-muted-foreground">Candles</button>
                <button type="button" data-rcentz-chart-mode="area" class="rounded-lg px-2.5 py-1.5 text-[9px] font-medium text-muted-foreground">Area</button>
            </div>
        </div>
    </div>

    @if(!empty($analysis['has_chart']))
        <div class="border-b border-border px-4 py-2 text-[9px] text-muted-foreground">
            Stored {{ strtoupper($signal->asset_class) }} market history · {{ str_replace('_', ' ', (string) ($analysis['source'] ?? $analysis['analysis_source'] ?? 'market feed')) }}
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
                <i data-lucide="chart-no-axes-combined" class="mx-auto h-5 w-5"></i>
                <p class="mt-3 text-xs font-medium">Building market history</p>
                <p class="mt-1 text-[10px] leading-4 text-muted-foreground">The chart appears when this instrument has enough verified stored market data.</p>
            </div>
        </div>
    @endif
</section>
