<x-admin-layout>
<div class="ui-page max-w-[1500px]">
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker">Investments · Instruments · Base Assets</p>
            <h1 class="ui-heading">Public Base Assets</h1>
            <p class="ui-lead max-w-3xl">Create investment-facing public underlyings from existing Market Instruments. Management and performance are kept on dedicated pages.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.investments.instruments.base-assets.index') }}" class="ui-btn ui-btn-secondary">Base Assets</a>
            <a href="{{ route('admin.investments.instruments.private.base-assets.index') }}" class="ui-btn ui-btn-secondary">Private Base Assets</a>
            <a href="{{ route('admin.instruments.index') }}" class="ui-btn ui-btn-secondary">Market Instruments</a>
        </div>
    </section>

    @if(session('success'))<div class="mb-4 rounded-xl border border-emerald-500/20 bg-emerald-500/10 p-4 text-xs text-emerald-600">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="mb-4 rounded-xl border border-red-500/20 bg-red-500/10 p-4 text-xs text-red-600">{{ $errors->first() }}</div>@endif

    <section class="ui-panel p-5">
        <p class="ui-kicker">Public · Market Priced</p>
        <h2 class="mt-1 text-lg font-semibold">Create Public Base Asset</h2>
        <p class="mt-2 text-xs leading-5 text-muted-foreground">Choose an existing active Market Instrument. This creates the Investment Base Asset designation only; pricing remains owned by Markets.</p>

        <form method="POST" action="{{ route('admin.investments.instruments.public.base-assets.store') }}" class="mt-5 grid gap-3 sm:grid-cols-[1fr_auto]">
            @csrf
            <div>
                <label class="ui-label">Underlying Market Instrument</label>
                <select class="ui-input mt-1 w-full" name="market_instrument_id" required>
                    <option value="">Select an active Market Instrument...</option>
                    @foreach($candidates as $instrument)
                        <option value="{{ $instrument->id }}">{{ strtoupper($instrument->asset_class) }} · {{ $instrument->display_symbol ?: $instrument->symbol }} · {{ $instrument->name }}</option>
                    @endforeach
                </select>
            </div>
            <button class="ui-btn ui-btn-primary self-end justify-center">Create Public Base Asset</button>
        </form>
    </section>

    <section class="ui-panel mt-4 overflow-hidden">
        <div class="border-b border-border p-5">
            <p class="ui-kicker">Public Registry</p>
            <h2 class="mt-1 text-lg font-semibold">Public Base Assets</h2>
            <p class="mt-2 text-xs text-muted-foreground">Only deliberately created Investment Base Assets appear here.</p>
        </div>

        <div class="grid gap-3 p-4 sm:grid-cols-2 xl:grid-cols-3">
            @forelse($baseAssets as $baseAsset)
                @php $instrument = $baseAsset->marketInstrument; @endphp
                <article class="rounded-2xl border border-border p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full bg-zinc-100 px-2 py-1 text-[9px] font-semibold dark:bg-zinc-900">{{ $instrument?->display_symbol ?: $instrument?->symbol }}</span>
                                <span class="rounded-full border border-border px-2 py-1 text-[8px] font-semibold">PUBLIC BASE</span>
                            </div>
                            <h3 class="mt-3 text-sm font-semibold">{{ $instrument?->name ?: 'Missing Market Instrument' }}</h3>
                            <p class="mt-1 text-[10px] text-muted-foreground">{{ strtoupper($instrument?->asset_class ?: 'unknown') }}@if($instrument?->market) · {{ $instrument->market }}@endif</p>
                        </div>
                        <span class="text-[9px] font-semibold {{ $baseAsset->status === 'active' ? 'text-emerald-600' : 'text-amber-600' }}">{{ strtoupper($baseAsset->status) }}</span>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3 border-y border-border py-3">
                        <div>
                            <p class="text-[8px] uppercase text-muted-foreground">Live Price</p>
                            <p class="mt-1 text-sm font-semibold">@if($baseAsset->resolved_price !== null){{ number_format((float)$baseAsset->resolved_price, max(0,min(8,(int)($instrument?->price_precision ?? 2)))) }} {{ $instrument?->quote_asset }}@else—@endif</p>
                            <p class="text-[9px] text-muted-foreground">per {{ $baseAsset->resolved_unit }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[8px] uppercase text-muted-foreground">Usage</p>
                            <p class="mt-1 text-sm font-semibold">{{ $baseAsset->reserve_assets_count }}</p>
                            <p class="text-[9px] text-muted-foreground">linked reserve{{ $baseAsset->reserve_assets_count === 1 ? '' : 's' }}</p>
                        </div>
                    </div>

                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <a href="{{ route('admin.investments.instruments.public.base-assets.performance', $baseAsset) }}" class="ui-btn ui-btn-secondary !h-9 !px-2 !text-[11px] whitespace-nowrap justify-center">View Performance</a>
                        <a href="{{ route('admin.investments.instruments.public.base-assets.show', $baseAsset) }}" class="ui-btn ui-btn-primary !h-9 !px-2 !text-[11px] whitespace-nowrap justify-center">Manage Base Asset</a>
                    </div>
                </article>
            @empty
                <div class="col-span-full rounded-xl border border-dashed border-border p-8 text-center text-xs text-muted-foreground">No Public Investment Base Assets have been created yet.</div>
            @endforelse
        </div>
    </section>
</div>
</x-admin-layout>
