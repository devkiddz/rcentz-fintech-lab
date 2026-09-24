<x-admin-layout>
<div class="ui-page max-w-[1500px]">
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker">Investments · Instruments · Base Assets</p>
            <h1 class="ui-heading">Private Base Assets</h1>
            <p class="ui-lead max-w-3xl">Create private underlying assets here. Manage each Base Asset from its dedicated management page and study its movement from Performance.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.investments.instruments.base-assets.index') }}" class="ui-btn ui-btn-secondary">Base Assets</a>
            <a href="{{ route('admin.investments.instruments.public.base-assets.index') }}" class="ui-btn ui-btn-secondary">Public Base Assets</a>
        </div>
    </section>

    @if(session('success'))<div class="mb-4 rounded-xl border border-emerald-500/20 bg-emerald-500/10 p-4 text-xs text-emerald-600">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="mb-4 rounded-xl border border-red-500/20 bg-red-500/10 p-4 text-xs text-red-600">{{ $errors->first() }}</div>@endif

    <section class="ui-panel p-5">
        <p class="ui-kicker">Private · RCENTZ Valuation</p>
        <h2 class="mt-1 text-lg font-semibold">Create Private Base Asset</h2>
        <p class="mt-2 text-xs leading-5 text-muted-foreground">The opening price becomes the first valuation point. The new Base Asset can then be managed, moved and connected to Investment Products.</p>

        <form method="POST" action="{{ route('admin.investments.instruments.private.base-assets.store') }}" class="mt-5 grid gap-3 md:grid-cols-2">
            @csrf
            <div><label class="ui-label">Symbol</label><input class="ui-input mt-1 w-full" name="symbol" value="{{ old('symbol') }}" placeholder="LEKKI-SA" required></div>
            <div><label class="ui-label">Currency</label><input class="ui-input mt-1 w-full" name="currency" value="{{ old('currency','USD') }}" maxlength="3" required></div>
            <div class="md:col-span-2"><label class="ui-label">Base Asset Name</label><input class="ui-input mt-1 w-full" name="name" value="{{ old('name') }}" placeholder="Lekki Serviced Apartments" required></div>
            <div>
                <label class="ui-label">Asset Class</label>
                <select class="ui-input mt-1 w-full" name="category" required>
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
            <div><label class="ui-label">Location</label><input class="ui-input mt-1 w-full" name="location" value="{{ old('location') }}" placeholder="Lekki, Lagos"></div>
            <div><label class="ui-label">Reference Unit</label><input class="ui-input mt-1 w-full" name="reference_unit" value="{{ old('reference_unit','property') }}" required></div>
            <div><label class="ui-label">Opening Base Price</label><input class="ui-input mt-1 w-full" type="number" step="0.00000001" min="0.00000001" name="current_price" value="{{ old('current_price') }}" required></div>
            <div class="md:col-span-2"><label class="ui-label">Description</label><textarea class="ui-input mt-1 w-full" rows="3" name="description" placeholder="What this private Base Asset represents.">{{ old('description') }}</textarea></div>
            <div class="md:col-span-2"><button class="ui-btn ui-btn-primary w-full justify-center">Create Private Base Asset</button></div>
        </form>
    </section>

    <section class="ui-panel mt-4 overflow-hidden">
        <div class="border-b border-border p-5">
            <p class="ui-kicker">Private Registry</p>
            <h2 class="mt-1 text-lg font-semibold">Private Base Assets</h2>
            <p class="mt-2 text-xs text-muted-foreground">Registry cards stay compact. Management and performance each have their own page.</p>
        </div>

        <div class="grid gap-3 p-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse($references as $reference)
                @php
                    $current = (float)$reference->current_price;
                    $previous = (float)($reference->previous_price ?? $reference->current_price);
                    $change = $current - $previous;
                    $percent = $previous > 0 ? ($change / $previous) * 100 : 0;
                    $prefix = strtoupper($reference->currency) === 'USD' ? currency_symbol() : strtoupper($reference->currency).' ';
                @endphp
                <article class="rounded-2xl border border-border p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full bg-red-50 px-2 py-1 text-[9px] font-semibold uppercase text-red-600 dark:bg-red-950/30">{{ $reference->symbol }}</span>
                                <span class="rounded-full border border-border px-2 py-1 text-[8px] font-semibold">PRIVATE BASE</span>
                            </div>
                            <h3 class="mt-3 text-sm font-semibold">{{ $reference->name }}</h3>
                            <p class="mt-1 text-[10px] text-muted-foreground">{{ ucwords(str_replace('_',' ',$reference->category)) }}@if($reference->location) · {{ $reference->location }}@endif</p>
                        </div>
                        <span class="text-[9px] font-semibold {{ $reference->status === 'active' ? 'text-emerald-600' : 'text-amber-600' }}">{{ strtoupper($reference->status) }}</span>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3 border-y border-border py-3">
                        <div>
                            <p class="text-[8px] uppercase text-muted-foreground">Current</p>
                            <p class="mt-1 text-sm font-semibold">{{ $prefix }}{{ number_format($current,2) }}</p>
                            <p class="text-[9px] text-muted-foreground">per {{ $reference->reference_unit }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[8px] uppercase text-muted-foreground">Latest Move</p>
                            <p class="mt-1 text-sm font-semibold {{ $change >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $percent >= 0 ? '+' : '' }}{{ number_format($percent,2) }}%</p>
                            <p class="text-[9px] text-muted-foreground">{{ $reference->assets_count }} linked reserve{{ $reference->assets_count === 1 ? '' : 's' }}</p>
                        </div>
                    </div>

                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <a href="{{ route('admin.investments.instruments.private.base-assets.performance',$reference) }}" class="ui-btn ui-btn-secondary !h-9 !px-2 !text-[11px] whitespace-nowrap justify-center">View Performance</a>
                        <a href="{{ route('admin.investments.instruments.private.base-assets.show',$reference) }}" class="ui-btn ui-btn-primary !h-9 !px-2 !text-[11px] whitespace-nowrap justify-center">Manage Base Asset</a>
                    </div>
                </article>
            @empty
                <div class="col-span-full rounded-xl border border-dashed border-border p-8 text-center text-xs text-muted-foreground">No Private Base Assets yet.</div>
            @endforelse
        </div>
    </section>
</div>
</x-admin-layout>
