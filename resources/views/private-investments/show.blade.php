<x-user-layout>
<x-slot name="header">{{ $instrument->name }}</x-slot>

@php
    $change = (float)$instrument->change_percent;
    $assetTotal = (float)$instrument->assets->sum('current_valuation');
@endphp

<div class="mx-auto max-w-[1280px] space-y-5 px-3 py-5 sm:px-5 lg:px-6">
    <div class="flex items-center gap-2 text-xs">
        <a href="{{ route('investments.index') }}" class="text-zinc-500 hover:text-red-600">Investments</a>
        <span class="text-zinc-300">/</span>
        <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $instrument->symbol }}</span>
    </div>

    <section class="rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950 md:p-7">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="max-w-3xl">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-red-50 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[.14em] text-red-600 dark:bg-red-950/30">{{ $instrument->symbol }}</span>
                    <span class="rounded-full border border-zinc-200 px-2.5 py-1 text-[10px] font-medium text-zinc-500 dark:border-zinc-800">{{ ucwords(str_replace('_',' ', $instrument->category)) }}</span>
                    <span class="rounded-full border border-zinc-200 px-2.5 py-1 text-[10px] font-medium text-zinc-500 dark:border-zinc-800">{{ ucwords(str_replace('_',' ', $instrument->risk_level)) }} risk</span>
                </div>
                <h1 class="mt-4 text-3xl font-semibold tracking-tight text-zinc-950 dark:text-white">{{ $instrument->name }}</h1>
                <p class="mt-3 text-sm leading-6 text-zinc-600 dark:text-zinc-400">{{ $instrument->description }}</p>
            </div>

            <div class="min-w-[230px] rounded-2xl border border-red-100 bg-red-50 p-4 dark:border-red-950 dark:bg-red-950/20">
                <p class="text-[9px] font-semibold uppercase tracking-[.14em] text-red-600">Current price</p>
                <div class="mt-1 flex items-baseline gap-2">
                    <span class="text-3xl font-semibold tabular-nums text-zinc-950 dark:text-white">{{ currency_symbol() }}{{ number_format((float)$instrument->current_price,2) }}</span>
                    <span class="text-xs font-semibold {{ $change >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $change >= 0 ? '+' : '' }}{{ number_format($change,2) }}%</span>
                </div>
                <p class="mt-2 text-[10px] text-zinc-500">System-authoritative valuation</p>
            </div>
        </div>
    </section>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1.65fr)_minmax(340px,.65fr)] xl:items-stretch">
        <div class="min-w-0">
            @include('private-investments.partials.chart', ['instrument'=>$instrument,'analysis'=>$analysis])
        </div>
        <div class="min-h-[470px]">
            @include('private-investments.partials.subscription-controls')
        </div>
    </div>

    @php
        $story = app(\App\Services\PrivateInvestmentProjectionService::class)->forInstrument($instrument, (float)$instrument->minimum_investment);
        $watchItem = auth()->user()->isAdmin()
            ? null
            : \App\Models\PrivateInvestmentWatchlist::query()
                ->where('user_id',auth()->id())
                ->where('instrument_id',$instrument->id)
                ->first();
    @endphp

    <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
        <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-800">
            <p class="text-[10px] font-semibold uppercase tracking-[.16em] text-red-600">Investment Overview</p>
            <h2 class="mt-1 text-lg font-semibold text-zinc-950 dark:text-white">Terms, return model and access</h2>
            <p class="mt-1 max-w-3xl text-[10px] leading-4 text-zinc-500">Projection values are illustrative and derived from configured terms, internal unit pricing and fees.</p>
        </div>

        <div class="grid divide-y divide-zinc-200 dark:divide-zinc-800 lg:grid-cols-3 lg:divide-x lg:divide-y-0">
            <div class="min-h-[260px] p-5">
                <p class="text-[9px] font-semibold uppercase tracking-[.14em] text-zinc-400">Capital & Access</p>
                <div class="mt-4 divide-y divide-zinc-100 text-xs dark:divide-zinc-900">
                    @foreach([
                        ['Minimum investment', currency_symbol().number_format((float)$instrument->minimum_investment,0)],
                        ['Listing price / unit', currency_symbol().number_format((float)$instrument->current_price,2)],
                        ['Available units', number_format((float)$instrument->available_units,0)],
                        ['Lock period', $instrument->lock_period_days.' days'],
                        ['Status', ucfirst($instrument->status)],
                    ] as [$label,$value])
                        <div class="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0">
                            <span class="text-zinc-500">{{ $label }}</span>
                            <span class="text-right font-semibold text-zinc-950 dark:text-white">{{ $value }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="min-h-[260px] p-5">
                <p class="text-[9px] font-semibold uppercase tracking-[.14em] text-zinc-400">Return Model</p>
                <div class="mt-4 divide-y divide-zinc-100 text-xs dark:divide-zinc-900">
                    <div class="flex items-center justify-between gap-4 pb-3"><span class="text-zinc-500">Duration</span><strong>{{ $story['duration_label'] }}</strong></div>
                    <div class="flex items-center justify-between gap-4 py-3"><span class="text-zinc-500">Projected cycle</span><strong>{{ $story['cycle_return_label'] }}</strong></div>
                    <div class="flex items-center justify-between gap-4 py-3"><span class="text-zinc-500">Return interval</span><strong>Every {{ $story['return_interval_label'] }}</strong></div>
                    <div class="flex items-center justify-between gap-4 py-3"><span class="text-zinc-500">Profit on minimum</span><strong>{{ currency_symbol() }}{{ number_format($story['net_term_min_profit'],2) }} – {{ currency_symbol() }}{{ number_format($story['net_term_max_profit'],2) }}</strong></div>
                    <div class="flex items-center justify-between gap-4 pt-3"><span class="text-zinc-500">Daily equivalent</span><strong>{{ currency_symbol() }}{{ number_format($story['daily_min_profit'],2) }} – {{ currency_symbol() }}{{ number_format($story['daily_max_profit'],2) }}</strong></div>
                </div>
            </div>

            <div class="min-h-[260px] p-5">
                <p class="text-[9px] font-semibold uppercase tracking-[.14em] text-zinc-400">Fees & Estimated Value</p>
                <div class="mt-4 divide-y divide-zinc-100 text-xs dark:divide-zinc-900">
                    <div class="flex items-center justify-between gap-4 pb-3"><span class="text-zinc-500">Subscription fee</span><strong>{{ number_format((float)$instrument->subscription_fee_percent,2) }}%</strong></div>
                    <div class="flex items-center justify-between gap-4 py-3"><span class="text-zinc-500">Management fee</span><strong>{{ number_format((float)$instrument->management_fee_percent,2) }}%</strong></div>
                    <div class="flex items-center justify-between gap-4 py-3"><span class="text-zinc-500">Redemption fee</span><strong>{{ number_format((float)$instrument->redemption_fee_percent,2) }}%</strong></div>
                    <div class="flex items-center justify-between gap-4 py-3"><span class="text-zinc-500">Min estimated maturity</span><strong>{{ currency_symbol() }}{{ number_format($story['minimum_maturity_value'],2) }}</strong></div>
                    <div class="flex items-center justify-between gap-4 pt-3"><span class="text-zinc-500">Max estimated maturity</span><strong>{{ currency_symbol() }}{{ number_format($story['maximum_maturity_value'],2) }}</strong></div>
                </div>
            </div>
        </div>
    </section>

    <div class="grid gap-5 lg:grid-cols-2">
        <section class="flex min-h-[420px] max-h-[520px] flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
            <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-4 dark:border-zinc-800">
                <div><p class="text-[10px] font-semibold uppercase tracking-[.16em] text-red-600">Underlying Assets</p><h2 class="mt-1 text-base font-semibold text-zinc-950 dark:text-white">{{ $instrument->assets->count() }} active assets</h2></div>
                <p class="text-xs font-medium text-zinc-500">{{ currency_symbol() }}{{ number_format($assetTotal,0) }}</p>
            </div>
            <div class="min-h-0 flex-1 overflow-y-auto">
                @forelse($instrument->assets as $asset)
                    <div class="border-b border-zinc-100 px-5 py-4 last:border-b-0 dark:border-zinc-900">
                        <div class="flex items-start justify-between gap-5">
                            <div class="min-w-0"><p class="truncate text-sm font-semibold text-zinc-950 dark:text-white">{{ $asset->name }}</p><p class="mt-1 text-[9px] uppercase tracking-[.12em] text-zinc-400">{{ ucwords(str_replace('_',' ',$asset->asset_type)) }}</p></div>
                            <div class="shrink-0 text-right"><p class="text-sm font-semibold tabular-nums">{{ currency_symbol() }}{{ number_format((float)$asset->current_valuation,0) }}</p><p class="mt-1 text-[9px] text-zinc-400">{{ number_format((float)$asset->ownership_percentage,1) }}% weight</p></div>
                        </div>
                        @if($asset->description)<p class="mt-2 text-[10px] leading-4 text-zinc-500">{{ $asset->description }}</p>@endif
                    </div>
                @empty
                    <div class="flex h-full items-center justify-center p-8 text-xs text-zinc-500">No underlying assets recorded.</div>
                @endforelse
            </div>
        </section>

        <section class="flex min-h-[420px] max-h-[520px] flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
            <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-800">
                <p class="text-[10px] font-semibold uppercase tracking-[.16em] text-red-600">Valuation Timeline</p>
                <h2 class="mt-1 text-base font-semibold text-zinc-950 dark:text-white">Recent price-driving events</h2>
            </div>
            <div class="min-h-0 flex-1 overflow-y-auto">
                @forelse($instrument->events as $event)
                    <div class="grid grid-cols-[12px_1fr_auto] gap-3 border-b border-zinc-100 px-5 py-4 last:border-b-0 dark:border-zinc-900">
                        <span class="mt-1.5 h-2 w-2 rounded-full {{ $event->direction === 'negative' ? 'bg-red-600' : ($event->direction === 'positive' ? 'bg-emerald-500' : 'bg-zinc-400') }}"></span>
                        <div><p class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">{{ ucwords(str_replace('_',' ',$event->event_type)) }}</p><p class="mt-1 text-[10px] leading-4 text-zinc-500">{{ $event->reason }}</p></div>
                        <p class="text-[9px] text-zinc-400">{{ optional($event->effective_at)->format('M j, Y') }}</p>
                    </div>
                @empty
                    <div class="flex h-full items-center justify-center p-8 text-xs text-zinc-500">No valuation events recorded.</div>
                @endforelse
            </div>
        </section>
    </div>

    @if(!auth()->user()->isAdmin())
        <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
            <div class="grid lg:grid-cols-[.65fr_1.35fr]">
                <div class="border-b border-zinc-200 p-5 dark:border-zinc-800 lg:border-b-0 lg:border-r">
                    <p class="text-[10px] font-semibold uppercase tracking-[.16em] text-red-600">Watchlist</p>
                    <h2 class="mt-1 text-base font-semibold">{{ $watchItem ? 'You are watching this investment' : 'Track this investment' }}</h2>
                    <p class="mt-2 text-[10px] leading-4 text-zinc-500">Keep a personal target, priority and review note without changing the investment itself.</p>
                </div>
                <div class="p-5">
                    @if($watchItem)
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="text-xs text-zinc-500">Target: <strong class="text-zinc-950 dark:text-white">{{ $watchItem->target_price ? currency_symbol().number_format((float)$watchItem->target_price,2) : 'Not set' }}</strong><span class="mx-2 text-zinc-300">·</span>Priority: <strong class="text-zinc-950 dark:text-white">{{ ucfirst($watchItem->priority) }}</strong></div>
                            <a href="{{ route('account.investments.watchlist') }}" class="text-xs font-semibold text-red-600">Manage watchlist</a>
                        </div>
                    @else
                        <form method="POST" action="{{ route('account.investments.watchlist.store',$instrument) }}" class="grid gap-2 md:grid-cols-[1fr_180px_1.3fr_auto]">
                            @csrf
                            <input class="ui-input" type="number" step="0.000001" min="0.000001" name="target_price" placeholder="Optional target price">
                            <select class="ui-input" name="priority"><option value="normal">Normal priority</option><option value="high">High priority</option><option value="low">Low priority</option></select>
                            <input class="ui-input" name="note" placeholder="Optional note">
                            <button class="rounded-xl bg-zinc-950 px-4 text-xs font-semibold text-white dark:bg-white dark:text-zinc-950">Watch</button>
                        </form>
                    @endif
                </div>
            </div>
        </section>
    @endif

</div>
</x-user-layout>
