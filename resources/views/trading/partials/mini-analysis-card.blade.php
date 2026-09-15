@php
    $miniSymbol = strtoupper((string)($symbol ?? ''));
    $miniStock = $miniSymbol ? \App\Models\Stock::where('symbol', $miniSymbol)->first() : null;
    $miniAnalysis = $miniStock ? app(\App\Services\StockAnalysisService::class)->forStock($miniStock) : [];
    $miniFrames = collect($miniAnalysis['timeframes'] ?? [])
        ->filter(fn ($rows) => is_array($rows) && count($rows) >= 2)
        ->keys()
        ->all();
    $miniDefault = $miniAnalysis['default_timeframe'] ?? '1d';
    if (!in_array($miniDefault, $miniFrames, true)) {
        $miniDefault = in_array('1d', $miniFrames, true) ? '1d' : ($miniFrames[0] ?? null);
    }
    $miniHeight = $height ?? 'h-[150px]';
@endphp

<div class="overflow-hidden rounded-xl border border-border/70 bg-background/30">
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-border/70 px-3 py-2">
        <div>
            <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Market story</p>
            <p class="mt-0.5 text-[10px] font-semibold">{{ $miniSymbol ?: 'Waiting for symbol' }}</p>
        </div>

        @if($miniDefault)
            <div class="flex gap-1">
                @foreach(['1d'=>'1D','1w'=>'1W','1m'=>'1M','3m'=>'3M'] as $frame=>$label)
                    @if(in_array($frame,$miniFrames,true))
                        <button type="button"
                                data-rcentz-timeframe="{{ $frame }}"
                                class="rounded-md px-2 py-1 text-[8px] font-medium {{ $frame===$miniDefault?'bg-muted text-foreground':'text-muted-foreground' }}">
                            {{ $label }}
                        </button>
                    @endif
                @endforeach
            </div>
        @endif
    </div>

    @if($miniStock && $miniDefault)
        <div class="{{ $miniHeight }} p-2">
            <div class="h-full w-full"
                 data-rcentz-analysis
                 data-compact="true"
                 data-default-timeframe="{{ $miniDefault }}"
                 data-analysis='@json($miniAnalysis)'></div>
        </div>
    @else
        <div class="{{ $miniHeight }} flex items-center justify-center p-3 text-center">
            <div>
                <p class="text-[10px] font-medium">Historical chart unavailable</p>
                <p class="mt-1 text-[9px] text-muted-foreground">A chart appears when this card resolves to a listed stock symbol.</p>
            </div>
        </div>
    @endif
</div>
