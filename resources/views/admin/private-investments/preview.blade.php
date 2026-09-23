<x-admin-layout>
<div class="ui-page max-w-[1500px]">
@php
    $change = (float) $instrument->change_percent;
    $assetTotal = (float) $instrument->assets->sum('current_valuation');
    $story = app(\App\Services\PrivateInvestmentProjectionService::class)->forInstrument(
        $instrument,
        (float) $instrument->minimum_investment
    );
@endphp

<section class="ui-page-header">
    <div>
        <p class="ui-kicker">Admin · Investment Asset Preview · {{ $instrument->symbol }}</p>
        <h1 class="ui-heading">{{ $instrument->name }}</h1>
        <p class="ui-lead">Read-only product inspection inside the admin control plane.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.investments.control.show',$instrument) }}" class="ui-btn ui-btn-primary">Manage Asset</a>
        <a href="{{ route('admin.investments.control.index') }}" class="ui-btn ui-btn-secondary"><i data-lucide="layout-grid" class="h-4 w-4"></i>View All Assets</a>
    </div>
</section>

<section class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
    <div class="ui-panel p-4"><p class="ui-kicker">Status</p><p class="mt-2 text-lg font-semibold">{{ strtoupper($instrument->status) }}</p></div>
    <div class="ui-panel p-4"><p class="ui-kicker">Unit Price</p><p class="mt-2 text-lg font-semibold">{{ currency_symbol() }}{{ number_format((float)$instrument->current_price,2) }}</p><p class="mt-1 text-[10px] {{ $change >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $change >= 0 ? '+' : '' }}{{ number_format($change,2) }}%</p></div>
    <div class="ui-panel p-4"><p class="ui-kicker">Reserve Value</p><p class="mt-2 text-lg font-semibold">{{ currency_symbol() }}{{ number_format((float)$reserveSummary['reserve_value'],2) }}</p></div>
    <div class="ui-panel p-4"><p class="ui-kicker">Backed Capacity</p><p class="mt-2 text-lg font-semibold">{{ number_format((float)$reserveSummary['backed_unit_capacity'],6) }}</p></div>
    <div class="ui-panel p-4"><p class="ui-kicker">Available</p><p class="mt-2 text-lg font-semibold">{{ number_format((float)$instrument->available_units,6) }}</p></div>
    <div class="ui-panel p-4"><p class="ui-kicker">Customer Units</p><p class="mt-2 text-lg font-semibold">{{ number_format((float)$reserveSummary['customer_units'],6) }}</p></div>
</section>

<section class="mt-5">
    @include('private-investments.partials.chart', ['instrument'=>$instrument,'analysis'=>$analysis,'attachReference'=>true])
    @include('private-investments.partials.market-reference', ['instrument'=>$instrument,'admin'=>true])
</section>

<section class="mt-5 grid gap-4 xl:grid-cols-[1.05fr_.95fr]">
    <div class="ui-panel overflow-hidden">
        <div class="border-b border-border px-5 py-4">
            <p class="ui-kicker">Reserve Composition</p>
            <h2 class="mt-1 text-lg font-semibold">{{ $instrument->assets->count() }} active backing assets</h2>
            <p class="mt-1 text-[10px] text-muted-foreground">Total current valuation {{ currency_symbol() }}{{ number_format($assetTotal,2) }}.</p>
        </div>
        <div class="divide-y divide-border">
            @forelse($instrument->assets as $asset)
                <div class="p-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold">{{ $asset->name }}</p>
                            <p class="mt-1 text-[10px] uppercase tracking-[.1em] text-muted-foreground">{{ ucwords(str_replace('_',' ',$asset->asset_type)) }} · {{ ucwords(str_replace('_',' ',$asset->valuation_mode)) }}</p>
                            @if($asset->description)<p class="mt-2 max-w-2xl text-[10px] leading-4 text-muted-foreground">{{ $asset->description }}</p>@endif
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold">{{ currency_symbol() }}{{ number_format((float)$asset->current_valuation,2) }}</p>
                            <p class="mt-1 text-[10px] text-muted-foreground">{{ number_format((float)$asset->reserve_quantity,8) }} {{ $asset->reserve_unit }}</p>
                        </div>
                    </div>

                    @if($asset->marketInstrument)
                        <div class="mt-3 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-border bg-muted/15 p-3">
                            <div>
                                <p class="text-[9px] font-semibold uppercase tracking-[.11em] text-muted-foreground">Public Market Reference</p>
                                <p class="mt-1 text-xs font-semibold">{{ $asset->marketInstrument->display_symbol }} · {{ strtoupper($asset->marketInstrument->asset_class) }}</p>
                            </div>
                            <a href="{{ route('admin.instruments.show',$asset->marketInstrument) }}" class="ui-btn ui-btn-secondary ui-btn-sm">View Market Reference</a>
                        </div>
                    @endif
                </div>
            @empty
                <div class="p-8 text-center text-xs text-muted-foreground">No active reserve asset is configured.</div>
            @endforelse
        </div>
    </div>

    <div class="ui-panel p-5">
        <p class="ui-kicker">Product Terms</p>
        <h2 class="mt-1 text-lg font-semibold">Investment configuration</h2>
        <div class="mt-4 divide-y divide-border text-xs">
            @foreach([
                ['Category', ucwords(str_replace('_',' ',$instrument->category))],
                ['Risk', ucwords(str_replace('_',' ',$instrument->risk_level))],
                ['Minimum', currency_symbol().number_format((float)$instrument->minimum_investment,2)],
                ['Maximum', $instrument->maximum_investment ? currency_symbol().number_format((float)$instrument->maximum_investment,2) : 'No configured maximum'],
                ['Duration', $story['duration_label']],
                ['Lock period', $instrument->lock_period_days.' days'],
                ['Return interval', 'Every '.$story['return_interval_label']],
                ['Projected cycle', $story['cycle_return_label']],
                ['Subscription fee', number_format((float)$instrument->subscription_fee_percent,2).'%'],
                ['Management fee', number_format((float)$instrument->management_fee_percent,2).'%'],
                ['Redemption fee', number_format((float)$instrument->redemption_fee_percent,2).'%'],
            ] as [$label,$value])
                <div class="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0"><span class="text-muted-foreground">{{ $label }}</span><strong class="text-right">{{ $value }}</strong></div>
            @endforeach
        </div>
        @if($instrument->description)
            <div class="mt-5 rounded-xl border border-border bg-muted/15 p-4 text-[10px] leading-5 text-muted-foreground">{{ $instrument->description }}</div>
        @endif
    </div>
</section>
</div>
</x-admin-layout>
