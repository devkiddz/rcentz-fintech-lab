<x-admin-layout>
@php
    $previous = (float) ($reference->previous_price ?? 0);
    $current = (float) $reference->current_price;
    $change = $previous > 0 ? $current - $previous : null;
    $percent = $previous > 0 ? ($change / $previous) * 100 : null;
    $prefix = strtoupper($reference->currency) === 'USD' ? currency_symbol() : strtoupper($reference->currency).' ';
@endphp

<div class="ui-page max-w-[1500px]">
    <section class="ui-page-header">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <p class="ui-kicker">Private Base Reference Asset</p>
                <span class="rounded-full border border-border px-2 py-1 text-[9px] font-semibold {{ $reference->status === 'active' ? 'text-emerald-600' : 'text-amber-600' }}">{{ ucfirst($reference->status) }}</span>
            </div>
            <h1 class="ui-heading">{{ $reference->name }}</h1>
            <p class="ui-lead">{{ $reference->symbol }} · {{ ucwords(str_replace('_',' ',$reference->category)) }}@if($reference->location) · {{ $reference->location }}@endif</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.investments.instruments.base-assets.index') }}" class="ui-btn ui-btn-secondary">Base Reference Engine</a>
            <form method="POST" action="{{ route('admin.investments.instruments.base-assets.private.status', $reference) }}">@csrf @method('PATCH')
                <button class="ui-btn ui-btn-secondary">{{ $reference->status === 'active' ? 'Pause Reference' : 'Activate Reference' }}</button>
            </form>
        </div>
    </section>

    @if(session('success'))<div class="mb-4 rounded-xl border border-emerald-500/20 bg-emerald-500/10 p-4 text-xs text-emerald-600">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="mb-4 rounded-xl border border-red-500/20 bg-red-500/10 p-4 text-xs text-red-600">{{ $errors->first() }}</div>@endif

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
        <div class="ui-panel p-4"><p class="ui-kicker">Current Price</p><p class="mt-2 text-xl font-semibold tabular-nums">{{ $prefix }}{{ number_format($current,2) }}</p></div>
        <div class="ui-panel p-4"><p class="ui-kicker">Previous Price</p><p class="mt-2 text-xl font-semibold tabular-nums">{{ $previous > 0 ? $prefix.number_format($previous,2) : '—' }}</p></div>
        <div class="ui-panel p-4"><p class="ui-kicker">Movement</p><p class="mt-2 text-xl font-semibold {{ $change === null ? 'text-muted-foreground' : ($change >= 0 ? 'text-emerald-600' : 'text-red-600') }}">@if($percent !== null){{ $percent >= 0 ? '+' : '' }}{{ number_format($percent,2) }}%@else—@endif</p></div>
        <div class="ui-panel p-4"><p class="ui-kicker">Reference Unit</p><p class="mt-2 text-xl font-semibold">{{ $reference->reference_unit }}</p></div>
        <div class="ui-panel p-4"><p class="ui-kicker">Last Valued</p><p class="mt-2 text-sm font-semibold">{{ optional($reference->last_valued_at)->format('M j, Y · H:i') ?: '—' }}</p></div>
    </section>

    <section class="mt-4 grid gap-4 xl:grid-cols-[.8fr_1.2fr]">
        <div class="ui-panel p-5">
            <p class="ui-kicker">Valuation Authority</p>
            <h2 class="mt-1 text-lg font-semibold">Update Base Price</h2>
            <p class="mt-2 text-xs leading-5 text-muted-foreground">This private reference is maintained inside RCENTZ. Updating it preserves the old price, writes valuation history, and revalues every reserve asset that selected this reference.</p>
            <form method="POST" action="{{ route('admin.investments.instruments.base-assets.private.price.update', $reference) }}" class="mt-5 space-y-4">
                @csrf @method('PATCH')
                <div><label class="ui-label">New Reference Price</label><input class="ui-input w-full" type="number" step="0.00000001" min="0.00000001" name="price" value="{{ $reference->current_price }}" required></div>
                <div><label class="ui-label">Valuation Reason</label><textarea class="ui-input w-full" rows="4" name="reason" placeholder="Appraisal, verified transaction benchmark, approved valuation review..." required></textarea></div>
                <button class="ui-btn ui-btn-primary w-full justify-center">Apply Private Valuation</button>
            </form>
        </div>

        <div class="ui-panel overflow-hidden">
            <div class="border-b border-border p-5">
                <p class="ui-kicker">Reference Usage</p>
                <h2 class="mt-1 text-lg font-semibold">Used By Assets</h2>
                <p class="mt-2 text-xs leading-5 text-muted-foreground">Read-only usage. Assets choose their reference from Investment Control; references do not attach themselves to assets.</p>
            </div>
            <div class="divide-y divide-border">
                @forelse($reference->assets as $asset)
                    <div class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold">{{ $asset->name }}</p>
                            <p class="mt-1 text-[10px] text-muted-foreground">{{ $asset->instrument?->symbol }} · {{ $asset->instrument?->name }} · {{ number_format((float)$asset->reserve_quantity,8) }} {{ $asset->reserve_unit ?: 'unit' }}</p>
                            <p class="mt-1 text-[10px] text-muted-foreground">Reserve value {{ currency_symbol() }}{{ number_format((float)$asset->current_valuation,2) }}</p>
                        </div>
                        @if($asset->instrument)
                            <a class="ui-btn ui-btn-secondary !h-8" href="{{ route('admin.investments.control.show', $asset->instrument) }}">Manage Investment</a>
                        @endif
                    </div>
                @empty
                    <div class="p-8 text-center text-xs text-muted-foreground">No reserve asset has selected this private reference yet.</div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="ui-panel mt-4 overflow-hidden">
        <div class="border-b border-border p-5"><p class="ui-kicker">Reference History</p><h2 class="mt-1 text-lg font-semibold">Base Price History</h2></div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left">
                <thead class="border-b border-border bg-muted/20 text-[9px] uppercase tracking-[.1em] text-muted-foreground">
                    <tr><th class="px-5 py-3">Time</th><th>Previous</th><th>Price</th><th>Change</th><th>Reason</th></tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @foreach($reference->prices as $price)
                        <tr class="text-xs">
                            <td class="px-5 py-3 text-muted-foreground">{{ optional($price->recorded_at)->format('M j, Y · H:i') }}</td>
                            <td>{{ $price->previous_price !== null ? $prefix.number_format((float)$price->previous_price,2) : '—' }}</td>
                            <td class="font-semibold">{{ $prefix }}{{ number_format((float)$price->price,2) }}</td>
                            <td class="{{ (float)$price->change_amount > 0 ? 'text-emerald-600' : ((float)$price->change_amount < 0 ? 'text-red-600' : 'text-muted-foreground') }}">{{ (float)$price->change_amount >= 0 ? '+' : '' }}{{ number_format((float)$price->change_percent,2) }}%</td>
                            <td class="max-w-[420px] py-3 pr-5 text-muted-foreground">{{ $price->reason }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
</x-admin-layout>
