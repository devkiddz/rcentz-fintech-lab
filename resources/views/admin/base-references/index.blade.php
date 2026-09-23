<x-admin-layout>
@php
    $scope = request('scope');
@endphp
<div class="ui-page max-w-[1500px]" data-base-reference-engine>
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker">Markets · Investment Authority</p>
            <h1 class="ui-heading">Base Reference Engine</h1>
            <p class="ui-lead max-w-3xl">The base owns the price. Public references come from live market authority and are read-only. Private references are created and maintained inside RCENTZ.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.investments.control.index') }}" class="ui-btn ui-btn-secondary">Investment Control</a>
            <a href="{{ route('admin.instruments.index') }}" class="ui-btn ui-btn-secondary">Public Market Registry</a>
        </div>
    </section>

    @if(session('success'))<div class="mb-4 rounded-xl border border-emerald-500/20 bg-emerald-500/10 p-4 text-xs text-emerald-600">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="mb-4 rounded-xl border border-red-500/20 bg-red-500/10 p-4 text-xs text-red-600">{{ $errors->first() }}</div>@endif

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['Public references',$stats['public']],
            ['Public active',$stats['public_active']],
            ['Private references',$stats['private']],
            ['Private active',$stats['private_active']],
        ] as [$label,$value])
            <div class="ui-panel p-4"><p class="ui-kicker">{{ $label }}</p><p class="mt-2 text-xl font-semibold">{{ $value }}</p></div>
        @endforeach
    </section>

    <section class="ui-panel mt-4 p-5" id="add-public-base-reference">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <div class="max-w-3xl">
                <p class="ui-kicker">Public · Live · Read-only after registration</p>
                <h2 class="mt-1 text-lg font-semibold">Add Public Base Reference</h2>
                <p class="mt-2 text-xs leading-5 text-muted-foreground">Choose the public asset class and enter its market symbol. RCENTZ verifies the live feed and creates the canonical base reference automatically. No starting price is entered manually.</p>
            </div>

            <form method="POST" action="{{ route('admin.investments.instruments.base-assets.public.store') }}" class="grid w-full gap-3 sm:grid-cols-[180px_1fr_auto] xl:max-w-3xl">
                @csrf
                <div>
                    <label class="ui-label">Asset Class</label>
                    <select class="ui-input mt-1 w-full" name="public_asset_class" required>
                        @foreach(['stock' => 'Stock', 'forex' => 'Forex', 'crypto' => 'Crypto', 'commodity' => 'Commodity'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('public_asset_class','stock')===$value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="ui-label">Public Symbol / Pair</label>
                    <input class="ui-input mt-1 w-full" name="public_symbol" value="{{ old('public_symbol') }}" placeholder="AAPL · EUR/USD · BTC/USD · XAU/USD" required>
                </div>
                <button class="ui-btn ui-btn-primary self-end justify-center whitespace-nowrap">Verify & Add Public Reference</button>
            </form>
        </div>
    </section>

    <section class="mt-4 grid gap-4 xl:grid-cols-[420px_1fr]" id="private-base-references">
        <div class="ui-panel p-5">
            <p class="ui-kicker">Private · Editable</p>
            <h2 class="mt-1 text-lg font-semibold">Create Private Base Reference</h2>
            <p class="mt-2 text-xs leading-5 text-muted-foreground">Create the underlying asset first. Investment products can then select it as their base reference.</p>

            <form method="POST" action="{{ route('admin.investments.instruments.base-assets.private.store') }}" class="mt-5 space-y-4">
                @csrf
                <div class="grid gap-3 sm:grid-cols-2">
                    <div><label class="ui-label">Symbol</label><input class="ui-input w-full" name="symbol" value="{{ old('symbol') }}" placeholder="LEKKI-SA" required></div>
                    <div><label class="ui-label">Currency</label><input class="ui-input w-full" name="currency" value="{{ old('currency','USD') }}" maxlength="3" required></div>
                </div>
                <div><label class="ui-label">Base Asset Name</label><input class="ui-input w-full" name="name" value="{{ old('name') }}" placeholder="Lekki Serviced Apartments" required></div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="ui-label">Asset Class</label>
                        <select class="ui-input w-full" name="category" required>
                            @foreach([
                                'real_estate' => 'Real Estate',
                                'private_business' => 'Private Business',
                                'infrastructure' => 'Infrastructure',
                                'private_debt' => 'Private Debt',
                                'cash_reserve' => 'Cash Reserve',
                                'commodity' => 'Commodity',
                                'other' => 'Other'
                            ] as $value => $label)
                                <option value="{{ $value }}" @selected(old('category')===$value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label class="ui-label">Location</label><input class="ui-input w-full" name="location" value="{{ old('location') }}" placeholder="Lekki, Lagos"></div>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div><label class="ui-label">Reference Unit</label><input class="ui-input w-full" name="reference_unit" value="{{ old('reference_unit','property') }}" placeholder="property / plot / sqm" required></div>
                    <div><label class="ui-label">Opening Base Price</label><input class="ui-input w-full" type="number" step="0.00000001" min="0.00000001" name="current_price" value="{{ old('current_price') }}" required></div>
                </div>
                <div><label class="ui-label">Description</label><textarea class="ui-input w-full" rows="4" name="description" placeholder="What this base reference asset represents.">{{ old('description') }}</textarea></div>
                <button class="ui-btn ui-btn-primary w-full justify-center">Create Private Base Reference</button>
            </form>
        </div>

        <div class="ui-panel overflow-hidden">
            <div class="border-b border-border p-5">
                <p class="ui-kicker">Private · Editable</p>
                <h2 class="mt-1 text-lg font-semibold">Private Base Reference Assets</h2>
                <p class="mt-2 text-xs text-muted-foreground">RCENTZ owns the valuation authority for these references.</p>
            </div>

            <div class="grid gap-3 p-4 md:grid-cols-2">
                @forelse($privateReferences as $reference)
                    @php
                        $current = (float)$reference->current_price;
                        $previous = (float)($reference->previous_price ?? 0);
                        $change = $previous > 0 ? $current - $previous : null;
                        $percent = $previous > 0 ? ($change / $previous) * 100 : null;
                        $prefix = strtoupper($reference->currency) === 'USD' ? currency_symbol() : strtoupper($reference->currency).' ';
                    @endphp
                    <article class="rounded-2xl border border-border p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-red-50 px-2 py-1 text-[9px] font-semibold uppercase text-red-600 dark:bg-red-950/30">{{ $reference->symbol }}</span>
                                    <span class="rounded-full border border-border px-2 py-1 text-[9px] font-semibold {{ $reference->status === 'active' ? 'text-emerald-600' : 'text-amber-600' }}">{{ ucfirst($reference->status) }}</span>
                                </div>
                                <h3 class="mt-3 truncate text-sm font-semibold">{{ $reference->name }}</h3>
                                <p class="mt-1 text-[10px] text-muted-foreground">{{ ucwords(str_replace('_',' ',$reference->category)) }}@if($reference->location) · {{ $reference->location }}@endif</p>
                            </div>
                            <span class="rounded-full border border-border px-2 py-1 text-[8px] font-semibold text-muted-foreground">PRIVATE</span>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-3 border-y border-border py-3">
                            <div>
                                <p class="text-[8px] uppercase tracking-[.1em] text-muted-foreground">Base Price</p>
                                <p class="mt-1 text-sm font-semibold tabular-nums">{{ $prefix }}{{ number_format($current,2) }}</p>
                                <p class="mt-0.5 text-[9px] text-muted-foreground">per {{ $reference->reference_unit }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[8px] uppercase tracking-[.1em] text-muted-foreground">Movement</p>
                                <p class="mt-1 text-sm font-semibold {{ $change === null ? 'text-muted-foreground' : ($change >= 0 ? 'text-emerald-600' : 'text-red-600') }}">@if($percent !== null){{ $percent >= 0 ? '+' : '' }}{{ number_format($percent,2) }}%@else—@endif</p>
                                <p class="mt-0.5 text-[9px] text-muted-foreground">{{ $reference->assets_count }} linked asset{{ $reference->assets_count === 1 ? '' : 's' }}</p>
                            </div>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-2">
                            <a href="{{ route('admin.investments.instruments.base-assets.private.show',$reference) }}" class="ui-btn ui-btn-secondary !h-8">Price & History</a>
                            <details class="min-w-0 flex-1">
                                <summary class="ui-btn ui-btn-secondary !h-8 cursor-pointer justify-center">Edit Base Asset</summary>
                                <form method="POST" action="{{ route('admin.investments.instruments.base-assets.private.identity.update',$reference) }}" class="mt-3 space-y-2 rounded-xl border border-border p-3">
                                    @csrf @method('PATCH')
                                    <div class="grid gap-2 sm:grid-cols-2">
                                        <input class="ui-input" name="symbol" value="{{ $reference->symbol }}" required>
                                        <input class="ui-input" name="currency" value="{{ $reference->currency }}" maxlength="3" required>
                                    </div>
                                    <input class="ui-input w-full" name="name" value="{{ $reference->name }}" required>
                                    <div class="grid gap-2 sm:grid-cols-2">
                                        <input class="ui-input" name="category" value="{{ $reference->category }}" required>
                                        <input class="ui-input" name="location" value="{{ $reference->location }}">
                                    </div>
                                    <input class="ui-input w-full" name="reference_unit" value="{{ $reference->reference_unit }}" required>
                                    <textarea class="ui-input w-full" rows="2" name="description">{{ $reference->description }}</textarea>
                                    <button class="ui-btn ui-btn-primary w-full justify-center">Save Base Asset</button>
                                </form>
                            </details>
                        </div>
                    </article>
                @empty
                    <div class="col-span-full rounded-xl border border-dashed border-border p-8 text-center text-xs text-muted-foreground">No private base references yet.</div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="ui-panel mt-4 overflow-hidden" id="public-base-references">
        <div class="border-b border-border p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="ui-kicker">Public · Live · Read-only</p>
                    <h2 class="mt-1 text-lg font-semibold">Public Base Reference Assets</h2>
                    <p class="mt-2 text-xs text-muted-foreground">These references are supplied by live market authority. Add new symbols above; once registered, their prices are not edited here.</p>
                </div>
                <a href="{{ route('admin.instruments.index') }}" class="ui-btn ui-btn-secondary">Open Public Registry</a>
            </div>
        </div>

        <div class="grid gap-3 p-4 sm:grid-cols-2 xl:grid-cols-3">
            @forelse($publicReferences as $reference)
                <article class="rounded-2xl border border-border p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full bg-zinc-100 px-2 py-1 text-[9px] font-semibold uppercase text-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">{{ $reference['display_symbol'] }}</span>
                                <span class="rounded-full border border-border px-2 py-1 text-[8px] font-semibold text-muted-foreground">LIVE</span>
                            </div>
                            <h3 class="mt-3 truncate text-sm font-semibold">{{ $reference['name'] }}</h3>
                            <p class="mt-1 text-[10px] text-muted-foreground">{{ strtoupper($reference['asset_class']) }}@if($reference['market']) · {{ $reference['market'] }}@endif</p>
                        </div>
                        <span class="text-[9px] font-semibold {{ $reference['is_active'] ? 'text-emerald-600' : 'text-zinc-400' }}">{{ $reference['is_active'] ? 'ACTIVE' : 'INACTIVE' }}</span>
                    </div>
                    <div class="mt-4 border-t border-border pt-3">
                        <p class="text-[8px] uppercase tracking-[.1em] text-muted-foreground">Live Base Price</p>
                        @if($reference['price'] !== null)
                            <p class="mt-1 text-sm font-semibold tabular-nums">{{ number_format((float)$reference['price'], $reference['precision']) }}{{ $reference['quote_asset'] ? ' '.$reference['quote_asset'] : '' }}</p>
                        @else
                            <p class="mt-1 text-sm font-semibold text-muted-foreground">Price unavailable</p>
                        @endif
                        <p class="mt-0.5 text-[9px] text-muted-foreground">per {{ $reference['unit'] }}</p>
                    </div>
                </article>
            @empty
                <div class="col-span-full rounded-xl border border-dashed border-border p-8 text-center text-xs text-muted-foreground">No public market references registered.</div>
            @endforelse
        </div>
    </section>
</div>
</x-admin-layout>
