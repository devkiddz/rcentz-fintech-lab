<x-admin-layout>
@php
    $current = (float) $reference->current_price;
    $previous = (float) ($reference->previous_price ?? $reference->current_price);
    $change = $current - $previous;
    $percent = $previous > 0 ? ($change / $previous) * 100 : 0;
    $linked = $reference->assets->isNotEmpty();
    $prefix = strtoupper($reference->currency) === 'USD'
        ? currency_symbol()
        : strtoupper($reference->currency).' ';
@endphp

<div class="ui-page max-w-[1500px]"
     data-private-management-runtime="{{ route('admin.investments.instruments.private.base-assets.movement.runtime', $reference) }}"
     data-private-auto="{{ $movement['mode'] === 'auto' ? '1' : '0' }}"
     data-base-currency="{{ strtoupper($reference->currency) }}">
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker">Private Base Asset · Management</p>
            <div class="mt-1 flex flex-wrap items-center gap-2">
                <h1 class="ui-heading">{{ $reference->name }}</h1>
                <span class="rounded-full border border-border px-2 py-1 text-[9px] font-semibold">{{ $reference->symbol }}</span>
                <span class="rounded-full border border-border px-2 py-1 text-[9px] font-semibold {{ $reference->status === 'active' ? 'text-emerald-600' : 'text-amber-600' }}">{{ strtoupper($reference->status) }}</span>
            </div>
            <p class="ui-lead">{{ ucwords(str_replace('_',' ',$reference->category)) }}@if($reference->location) · {{ $reference->location }}@endif · {{ $reference->reference_unit }}</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.investments.instruments.private.base-assets.index') }}" class="ui-btn ui-btn-secondary">Private Base Assets</a>
            <a href="{{ route('admin.investments.instruments.private.base-assets.performance',$reference) }}" class="ui-btn ui-btn-primary">View Performance</a>
        </div>
    </section>

    @if(session('success'))<div class="mb-4 rounded-xl border border-emerald-500/20 bg-emerald-500/10 p-4 text-xs text-emerald-600">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="mb-4 rounded-xl border border-red-500/20 bg-red-500/10 p-4 text-xs text-red-600">{{ $errors->first() }}</div>@endif

    <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6">
        <div class="ui-panel p-4"><p class="ui-kicker">Current</p><p class="mt-2 text-lg font-semibold" data-management-current>{{ $prefix }}{{ number_format($current,2) }}</p></div>
        <div class="ui-panel p-4"><p class="ui-kicker">Previous</p><p class="mt-2 text-lg font-semibold" data-management-previous>{{ $prefix }}{{ number_format($previous,2) }}</p></div>
        <div class="ui-panel p-4"><p class="ui-kicker">Latest Move</p><p class="mt-2 text-lg font-semibold {{ $change >= 0 ? 'text-emerald-600' : 'text-red-600' }}" data-management-change>{{ $change >= 0 ? '+' : '' }}{{ number_format($change,2) }}</p><p class="mt-1 text-[10px]" data-management-percent>{{ $percent >= 0 ? '+' : '' }}{{ number_format($percent,2) }}%</p></div>
        <div class="ui-panel p-4"><p class="ui-kicker">Movement Mode</p><p class="mt-2 text-lg font-semibold" data-management-mode>{{ strtoupper($movement['mode']) }}</p><p class="mt-1 text-[10px] text-muted-foreground">{{ ucwords($movement['behavior']) }}</p></div>
        <div class="ui-panel p-4"><p class="ui-kicker">Linked Reserves</p><p class="mt-2 text-lg font-semibold">{{ $reference->assets->count() }}</p></div>
        <div class="ui-panel p-4"><p class="ui-kicker">History</p><p class="mt-2 text-lg font-semibold" data-management-history>{{ $reference->prices->count() }}</p><p class="mt-1 text-[10px] text-muted-foreground">recent points loaded</p></div>
    </section>

    <section class="grid gap-4 mt-4 xl:grid-cols-2">
        <div class="ui-panel overflow-hidden">
            <div class="border-b border-border p-5">
                <p class="ui-kicker">Asset Identity</p>
                <h2 class="mt-1 text-lg font-semibold">Base Asset Details</h2>
                <p class="mt-2 text-xs text-muted-foreground">Structural identity locks after an investment reserve depends on this Base Asset.</p>
            </div>

            <form method="POST" action="{{ route('admin.investments.instruments.private.base-assets.identity.update',$reference) }}" class="grid gap-3 p-5 sm:grid-cols-2">
                @csrf @method('PATCH')

                @if($linked)
                    <div class="sm:col-span-2 rounded-xl border border-amber-500/20 bg-amber-500/10 p-3 text-[10px] text-amber-600">
                        Symbol, asset class, currency and reference unit are locked because this Base Asset is in use.
                    </div>
                    <input type="hidden" name="symbol" value="{{ $reference->symbol }}">
                    <input type="hidden" name="category" value="{{ $reference->category }}">
                    <input type="hidden" name="currency" value="{{ $reference->currency }}">
                    <input type="hidden" name="reference_unit" value="{{ $reference->reference_unit }}">
                    <div><label class="ui-label">Symbol</label><div class="mt-1 rounded-xl border border-border px-3 py-2.5 text-xs">{{ $reference->symbol }}</div></div>
                    <div><label class="ui-label">Currency</label><div class="mt-1 rounded-xl border border-border px-3 py-2.5 text-xs">{{ $reference->currency }}</div></div>
                    <div><label class="ui-label">Asset Class</label><div class="mt-1 rounded-xl border border-border px-3 py-2.5 text-xs">{{ ucwords(str_replace('_',' ',$reference->category)) }}</div></div>
                    <div><label class="ui-label">Reference Unit</label><div class="mt-1 rounded-xl border border-border px-3 py-2.5 text-xs">{{ $reference->reference_unit }}</div></div>
                @else
                    <div><label class="ui-label">Symbol</label><input class="ui-input mt-1 w-full" name="symbol" value="{{ $reference->symbol }}" required></div>
                    <div><label class="ui-label">Currency</label><input class="ui-input mt-1 w-full" name="currency" value="{{ $reference->currency }}" maxlength="3" required></div>
                    <div><label class="ui-label">Asset Class</label><input class="ui-input mt-1 w-full" name="category" value="{{ $reference->category }}" required></div>
                    <div><label class="ui-label">Reference Unit</label><input class="ui-input mt-1 w-full" name="reference_unit" value="{{ $reference->reference_unit }}" required></div>
                @endif

                <div class="sm:col-span-2"><label class="ui-label">Name</label><input class="ui-input mt-1 w-full" name="name" value="{{ $reference->name }}" required></div>
                <div class="sm:col-span-2"><label class="ui-label">Location</label><input class="ui-input mt-1 w-full" name="location" value="{{ $reference->location }}"></div>
                <div class="sm:col-span-2"><label class="ui-label">Description</label><textarea class="ui-input mt-1 w-full" rows="4" name="description">{{ $reference->description }}</textarea></div>
                <div class="sm:col-span-2"><button class="ui-btn ui-btn-primary w-full justify-center">Save Base Asset Details</button></div>
            </form>
        </div>

        <div class="ui-panel overflow-hidden">
            <div class="border-b border-border p-5">
                <p class="ui-kicker">Movement Authority</p>
                <h2 class="mt-1 text-lg font-semibold">Movement Control</h2>
                <p class="mt-2 text-xs text-muted-foreground">Control how this Private Base Asset moves. Auto continues from its configured behavior and interval; Manual keeps movement under direct control.</p>
            </div>

            <div class="p-5">
                <form method="POST" action="{{ route('admin.investments.instruments.private.base-assets.movement.update',$reference) }}" class="grid gap-3 sm:grid-cols-2">
                    @csrf @method('PATCH')
                    <div>
                        <label class="ui-label">Mode</label>
                        <select class="ui-input mt-1 w-full" name="movement_mode">
                            <option value="manual" @selected($movement['mode']==='manual')>Manual</option>
                            <option value="auto" @selected($movement['mode']==='auto')>Auto</option>
                        </select>
                    </div>
                    <div>
                        <label class="ui-label">Behavior</label>
                        <select class="ui-input mt-1 w-full" name="movement_behavior">
                            <option value="smart" @selected($movement['behavior']==='smart')>Smart</option>
                            <option value="up" @selected($movement['behavior']==='up')>Up Bias</option>
                            <option value="down" @selected($movement['behavior']==='down')>Down Bias</option>
                            <option value="range" @selected($movement['behavior']==='range')>Range</option>
                        </select>
                    </div>
                    <div><label class="ui-label">Strength</label><input class="ui-input mt-1 w-full" type="number" min="0.1" max="3" step="0.1" name="movement_strength" value="{{ $movement['strength'] }}" required></div>
                    <div><label class="ui-label">Volatility %</label><input class="ui-input mt-1 w-full" type="number" min="0.001" max="5" step="0.001" name="movement_volatility_percent" value="{{ $movement['volatility_percent'] }}" required></div>
                    <div class="sm:col-span-2">
                        <label class="ui-label">Interval</label>
                        <select class="ui-input mt-1 w-full" name="movement_tick_seconds">
                            @foreach([5,10,15,30,60,120,300] as $seconds)
                                <option value="{{ $seconds }}" @selected((int)$movement['tick_seconds']===$seconds)>{{ $seconds < 60 ? $seconds.' sec' : (($seconds/60).' min') }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2"><button class="ui-btn ui-btn-primary w-full justify-center">Save Movement Control</button></div>
                </form>

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <form method="POST" action="{{ route('admin.investments.instruments.private.base-assets.movement.move',$reference) }}" class="rounded-xl border border-border p-4">
                        @csrf
                        <p class="text-xs font-semibold">Move Now</p>
                        <p class="mt-1 text-[10px] text-muted-foreground">Record an immediate movement point.</p>
                        <select class="ui-input mt-3 w-full" name="behavior">
                            <option value="smart">Smart Move</option>
                            <option value="up">Move Up</option>
                            <option value="down">Move Down</option>
                            <option value="range">Range Move</option>
                        </select>
                        <button class="ui-btn ui-btn-secondary mt-3 w-full justify-center">Record Movement Now</button>
                    </form>

                    <form method="POST" action="{{ route('admin.investments.instruments.private.base-assets.price.update',$reference) }}" class="rounded-xl border border-border p-4">
                        @csrf @method('PATCH')
                        <p class="text-xs font-semibold">Exact Valuation</p>
                        <p class="mt-1 text-[10px] text-muted-foreground">Set a specific approved valuation and record the reason.</p>
                        <input class="ui-input mt-3 w-full" type="number" step="0.00000001" min="0.00000001" name="price" value="{{ $reference->current_price }}" required>
                        <textarea class="ui-input mt-2 w-full" rows="2" name="reason" placeholder="Valuation reason" required></textarea>
                        <button class="ui-btn ui-btn-secondary mt-3 w-full justify-center">Update Valuation</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section class="grid gap-4 mt-4 xl:grid-cols-2">
        <div class="ui-panel overflow-hidden">
            <div class="border-b border-border p-5">
                <p class="ui-kicker">Investment Usage</p>
                <h2 class="mt-1 text-lg font-semibold">Linked Reserves</h2>
            </div>
            <div class="divide-y divide-border">
                @forelse($reference->assets as $asset)
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

        <div class="ui-panel overflow-hidden">
            <div class="border-b border-border p-5">
                <p class="ui-kicker">Valuation History</p>
                <h2 class="mt-1 text-lg font-semibold">Recent Valuation Points</h2>
            </div>
            <div class="max-h-[520px] overflow-auto divide-y divide-border">
                @forelse($reference->prices as $price)
                    <div class="grid grid-cols-[1fr_auto] gap-4 p-4">
                        <div>
                            <p class="text-xs font-semibold">{{ $prefix }}{{ number_format((float)$price->price,2) }}</p>
                            <p class="mt-1 text-[10px] text-muted-foreground">{{ $price->reason }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] font-semibold {{ (float)$price->change_amount >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ (float)$price->change_percent >= 0 ? '+' : '' }}{{ number_format((float)$price->change_percent,3) }}%</p>
                            <p class="mt-1 text-[9px] text-muted-foreground">{{ optional($price->recorded_at)->format('M j, Y H:i:s') }}</p>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-xs text-muted-foreground">No valuation history yet.</div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="ui-panel mt-4 p-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="ui-kicker">Base Asset Status</p>
                <h2 class="mt-1 text-lg font-semibold">{{ ucfirst($reference->status) }}</h2>
                <p class="mt-2 text-xs text-muted-foreground">Created {{ optional($reference->created_at)->format('M j, Y H:i') }} · Last valued {{ optional($reference->last_valued_at)->format('M j, Y H:i:s') ?: '—' }}</p>
            </div>
            <form method="POST" action="{{ route('admin.investments.instruments.private.base-assets.status',$reference) }}">
                @csrf @method('PATCH')
                <button class="ui-btn ui-btn-secondary">{{ $reference->status === 'active' ? 'Pause Base Asset' : 'Activate Base Asset' }}</button>
            </form>
        </div>
    </section>
</div>

<script>
(() => {
    const root = document.querySelector('[data-private-management-runtime]');
    if (!root || root.dataset.privateAuto !== '1') return;

    const url = root.dataset.privateManagementRuntime;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const currency = root.dataset.baseCurrency || 'USD';
    let busy = false;

    const num = (value, digits = 2) => Number(value || 0).toLocaleString(undefined, {
        minimumFractionDigits: digits,
        maximumFractionDigits: digits,
    });

    const apply = (analysis) => {
        const current = Number(analysis.current_price || 0);
        const previous = Number(analysis.previous_close || current);
        const change = Number(analysis.change_amount || 0);
        const percent = Number(analysis.change_percent || 0);

        const map = {
            '[data-management-current]': `${num(current)} ${currency}`,
            '[data-management-previous]': `${num(previous)} ${currency}`,
            '[data-management-change]': `${change >= 0 ? '+' : ''}${num(change)}`,
            '[data-management-percent]': `${percent >= 0 ? '+' : ''}${num(percent,2)}%`,
            '[data-management-history]': String(analysis.history_points || 0),
        };

        Object.entries(map).forEach(([selector, value]) => {
            const el = root.querySelector(selector);
            if (el) el.textContent = value;
        });
    };

    const poll = async () => {
        if (busy || document.hidden) return;
        busy = true;
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                credentials: 'same-origin',
                cache: 'no-store',
            });
            if (!response.ok) return;
            const payload = await response.json();
            if (payload.analysis) apply(payload.analysis);
        } catch (_) {
        } finally {
            busy = false;
        }
    };

    window.setInterval(poll, 3000);
    poll();
})();
</script>
</x-admin-layout>
