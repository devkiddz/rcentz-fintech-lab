@php
    $story = app(\App\Services\PrivateInvestmentProjectionService::class)->forInstrument($instrument, (float)$instrument->minimum_investment);
@endphp

<div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
    <div class="rounded-xl bg-zinc-50 p-3 dark:bg-zinc-900">
        <p class="text-[9px] uppercase tracking-[.1em] text-zinc-400">Duration</p>
        <p class="mt-1 text-xs font-semibold">{{ $story['duration_label'] }}</p>
    </div>
    <div class="rounded-xl bg-zinc-50 p-3 dark:bg-zinc-900">
        <p class="text-[9px] uppercase tracking-[.1em] text-zinc-400">Return cycle</p>
        <p class="mt-1 text-xs font-semibold">{{ $story['cycle_return_label'] }}</p>
        <p class="mt-0.5 text-[9px] text-zinc-400">every {{ $story['return_interval_label'] }}</p>
    </div>
    <div class="rounded-xl bg-zinc-50 p-3 dark:bg-zinc-900">
        <p class="text-[9px] uppercase tracking-[.1em] text-zinc-400">Profit on minimum</p>
        <p class="mt-1 text-xs font-semibold">{{ currency_symbol() }}{{ number_format($story['net_term_min_profit'],2) }} – {{ currency_symbol() }}{{ number_format($story['net_term_max_profit'],2) }}</p>
    </div>
    <div class="rounded-xl bg-zinc-50 p-3 dark:bg-zinc-900">
        <p class="text-[9px] uppercase tracking-[.1em] text-zinc-400">Daily equivalent</p>
        <p class="mt-1 text-xs font-semibold">{{ currency_symbol() }}{{ number_format($story['daily_min_profit'],2) }} – {{ currency_symbol() }}{{ number_format($story['daily_max_profit'],2) }}</p>
    </div>
</div>