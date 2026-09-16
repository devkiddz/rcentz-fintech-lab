@php
    $current = (float) ($analysis['current_price'] ?? 0);
    $previous = (float) ($analysis['previous_close'] ?? $current);
    $change = $current - $previous;
    $changePercent = $previous > 0 ? ($change / $previous) * 100 : 0;
    $defaultFrame = $analysis['default_timeframe'] ?? '1m';
    $frames = ['1w'=>'1W','1m'=>'1M','3m'=>'3M','all'=>'ALL'];
@endphp

<section class="flex min-h-[470px] flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
    <div class="flex flex-col gap-4 border-b border-zinc-200 px-5 py-4 dark:border-zinc-800 lg:flex-row lg:items-center lg:justify-between">
        <div class="min-w-0">
            <p class="text-[10px] font-semibold uppercase tracking-[.16em] text-red-600">Investment price history</p>
            <div class="mt-1 flex flex-wrap items-baseline gap-2">
                <h2 class="text-sm font-semibold text-zinc-950 dark:text-white">{{ $instrument->symbol }}</h2>
                <span class="text-2xl font-semibold tabular-nums text-zinc-950 dark:text-white">{{ currency_symbol() }}{{ number_format($current,2) }}</span>
                <span class="text-xs font-semibold {{ $changePercent >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $changePercent >= 0 ? '+' : '' }}{{ number_format($changePercent,2) }}%</span>
            </div>
        </div>

        <div class="flex w-fit max-w-full items-center overflow-x-auto rounded-xl border border-zinc-200 bg-zinc-50 p-1 dark:border-zinc-800 dark:bg-zinc-900">
            @foreach($frames as $frame => $label)
                @php $enabled = count($analysis['timeframes'][$frame] ?? []) >= 2; @endphp
                <button type="button"
                        data-rcentz-timeframe="{{ $frame }}"
                        @disabled(!$enabled)
                        class="shrink-0 rounded-lg px-3 py-1.5 text-[10px] font-semibold transition {{ $frame === $defaultFrame ? 'bg-white text-zinc-950 shadow-sm dark:bg-zinc-800 dark:text-white' : 'text-zinc-500 hover:text-zinc-950 dark:hover:text-white' }} {{ !$enabled ? 'cursor-not-allowed opacity-35' : '' }}">
                    {{ $label }}
                </button>
            @endforeach

            <span class="mx-1 h-5 w-px shrink-0 bg-zinc-200 dark:bg-zinc-700"></span>

            <button type="button" data-rcentz-chart-mode="line" class="shrink-0 rounded-lg bg-white px-3 py-1.5 text-[10px] font-semibold text-zinc-950 shadow-sm dark:bg-zinc-800 dark:text-white">Line</button>
            <button type="button" data-rcentz-chart-mode="candles" class="shrink-0 rounded-lg px-3 py-1.5 text-[10px] font-semibold text-zinc-500">Candles</button>
            <button type="button" data-rcentz-chart-mode="area" class="shrink-0 rounded-lg px-3 py-1.5 text-[10px] font-semibold text-zinc-500">Area</button>
        </div>
    </div>

    @if(!empty($analysis['has_chart']))
        <div class="border-b border-zinc-100 px-5 py-2 text-[10px] text-zinc-500 dark:border-zinc-900">
            System-authoritative valuation history · Private Investment Engine
        </div>
        <div class="min-h-0 flex-1 p-3">
            <div class="h-[355px] w-full lg:h-[390px]"
                 data-rcentz-analysis
                 data-default-timeframe="{{ $defaultFrame }}"
                 data-analysis='@json($analysis)'></div>
        </div>
    @else
        <div class="flex min-h-[390px] flex-1 items-center justify-center px-5 text-center text-sm text-zinc-500">
            Price history is still being established.
        </div>
    @endif
</section>
