<x-admin-layout>
@php
    $linked = (int)$baseAsset->reserve_assets_count > 0;
@endphp

<div class="ui-page max-w-[1500px]">
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker">Public Base Asset · Management</p>
            <div class="mt-1 flex flex-wrap items-center gap-2">
                <h1 class="ui-heading">{{ $instrument->name }}</h1>
                <span class="rounded-full border border-border px-2 py-1 text-[9px] font-semibold">{{ $instrument->display_symbol ?: $instrument->symbol }}</span>
                <span class="rounded-full border border-border px-2 py-1 text-[9px] font-semibold {{ $baseAsset->status === 'active' ? 'text-emerald-600' : 'text-amber-600' }}">{{ strtoupper($baseAsset->status) }}</span>
            </div>
            <p class="ui-lead">{{ strtoupper($instrument->asset_class) }}@if($instrument->market) · {{ $instrument->market }}@endif</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.investments.instruments.public.base-assets.index') }}" class="ui-btn ui-btn-secondary">Public Base Assets</a>
            <a href="{{ route('admin.investments.instruments.public.base-assets.performance',$baseAsset) }}" class="ui-btn ui-btn-primary">View Performance</a>
        </div>
    </section>

    @if(session('success'))<div class="mb-4 rounded-xl border border-emerald-500/20 bg-emerald-500/10 p-4 text-xs text-emerald-600">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="mb-4 rounded-xl border border-red-500/20 bg-red-500/10 p-4 text-xs text-red-600">{{ $errors->first() }}</div>@endif

    <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="ui-panel p-4"><p class="ui-kicker">Live Price</p><p class="mt-2 text-lg font-semibold">@if($livePrice !== null){{ number_format((float)$livePrice,max(0,min(8,(int)$instrument->price_precision))) }} {{ $instrument->quote_asset }}@else—@endif</p><p class="mt-1 text-[10px] text-muted-foreground">per {{ $unit }}</p></div>
        <div class="ui-panel p-4"><p class="ui-kicker">Usage</p><p class="mt-2 text-lg font-semibold">{{ $baseAsset->reserve_assets_count }}</p><p class="mt-1 text-[10px] text-muted-foreground">linked reserve{{ $baseAsset->reserve_assets_count === 1 ? '' : 's' }}</p></div>
        <div class="ui-panel p-4"><p class="ui-kicker">Market Status</p><p class="mt-2 text-lg font-semibold">{{ $instrument->is_active ? 'ACTIVE' : 'INACTIVE' }}</p></div>
        <div class="ui-panel p-4"><p class="ui-kicker">Base Asset Status</p><p class="mt-2 text-lg font-semibold">{{ strtoupper($baseAsset->status) }}</p></div>
    </section>

    <section class="grid gap-4 mt-4 xl:grid-cols-2">
        <div class="ui-panel overflow-hidden">
            <div class="border-b border-border p-5">
                <p class="ui-kicker">Underlying Authority</p>
                <h2 class="mt-1 text-lg font-semibold">Market Instrument</h2>
                <p class="mt-2 text-xs text-muted-foreground">Markets owns the public instrument and live price. Investments owns this Base Asset designation.</p>
            </div>

            <div class="grid gap-3 p-5 sm:grid-cols-2">
                <div><p class="text-[9px] uppercase text-muted-foreground">Symbol</p><p class="mt-1 text-xs font-semibold">{{ $instrument->display_symbol ?: $instrument->symbol }}</p></div>
                <div><p class="text-[9px] uppercase text-muted-foreground">Asset Class</p><p class="mt-1 text-xs font-semibold">{{ strtoupper($instrument->asset_class) }}</p></div>
                <div><p class="text-[9px] uppercase text-muted-foreground">Base</p><p class="mt-1 text-xs font-semibold">{{ $instrument->base_asset ?: '—' }}</p></div>
                <div><p class="text-[9px] uppercase text-muted-foreground">Quote</p><p class="mt-1 text-xs font-semibold">{{ $instrument->quote_asset ?: '—' }}</p></div>
                <div><p class="text-[9px] uppercase text-muted-foreground">Market</p><p class="mt-1 text-xs font-semibold">{{ $instrument->market ?: '—' }}</p></div>
                <div><p class="text-[9px] uppercase text-muted-foreground">Price Precision</p><p class="mt-1 text-xs font-semibold">{{ $instrument->price_precision }}</p></div>
            </div>

            <div class="border-t border-border p-5">
                @if($linked)
                    <div class="rounded-xl border border-amber-500/20 bg-amber-500/10 p-3 text-[10px] leading-4 text-amber-600">
                        The underlying Market Instrument is locked because {{ $baseAsset->reserve_assets_count }} investment reserve{{ $baseAsset->reserve_assets_count === 1 ? '' : 's' }} already depend on this Base Asset.
                    </div>
                @else
                    <form method="POST" action="{{ route('admin.investments.instruments.public.base-assets.update',$baseAsset) }}">
                        @csrf @method('PATCH')
                        <label class="ui-label">Change Underlying Market Instrument</label>
                        <select class="ui-input mt-1 w-full" name="market_instrument_id" required>
                            @foreach($candidates as $candidate)
                                <option value="{{ $candidate->id }}" @selected($candidate->id === $instrument->id)>{{ strtoupper($candidate->asset_class) }} · {{ $candidate->display_symbol ?: $candidate->symbol }} · {{ $candidate->name }}</option>
                            @endforeach
                        </select>
                        <button class="ui-btn ui-btn-primary mt-3 w-full justify-center">Save Public Base Asset</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="ui-panel overflow-hidden">
            <div class="border-b border-border p-5">
                <p class="ui-kicker">Investment Usage</p>
                <h2 class="mt-1 text-lg font-semibold">Linked Reserves</h2>
            </div>
            <div class="divide-y divide-border">
                @forelse($baseAsset->reserveAssets as $asset)
                    <div class="flex items-start justify-between gap-4 p-5">
                        <div>
                            <p class="text-xs font-semibold">{{ $asset->name }}</p>
                            <p class="mt-1 text-[10px] text-muted-foreground">{{ $asset->instrument?->name ?: 'Investment Product' }} · {{ number_format((float)$asset->reserve_quantity,8) }} {{ $asset->reserve_unit }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-semibold">{{ currency_symbol() }}{{ number_format((float)$asset->current_valuation,2) }}</p>
                            <p class="mt-1 text-[10px] text-muted-foreground">{{ strtoupper($asset->status) }}</p>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-xs text-muted-foreground">No investment reserve is linked yet.</div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="ui-panel mt-4 p-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="ui-kicker">Base Asset Authority</p>
                <h2 class="mt-1 text-lg font-semibold">Management Actions</h2>
                <p class="mt-2 text-xs text-muted-foreground">Created {{ optional($baseAsset->created_at)->format('M j, Y H:i') }} · Market Instrument #{{ $instrument->id }} · Base Asset #{{ $baseAsset->id }}</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <form method="POST" action="{{ route('admin.investments.instruments.public.base-assets.status',$baseAsset) }}">
                    @csrf @method('PATCH')
                    <button class="ui-btn ui-btn-secondary">{{ $baseAsset->status === 'active' ? 'Pause Base Asset' : 'Activate Base Asset' }}</button>
                </form>

                @unless($linked)
                    <form method="POST" action="{{ route('admin.investments.instruments.public.base-assets.destroy',$baseAsset) }}" onsubmit="return confirm('Remove this Public Base Asset designation? The Market Instrument itself remains untouched.')">
                        @csrf @method('DELETE')
                        <button class="ui-btn ui-btn-secondary text-red-600">Remove Base Asset</button>
                    </form>
                @endunless
            </div>
        </div>
    </section>
</div>
</x-admin-layout>
