<x-admin-layout>
<div class="ui-page max-w-[1500px]">
<section class="ui-page-header"><div><p class="ui-kicker">Admin · Investments · {{ $instrument->symbol }}</p><h1 class="ui-heading">{{ $instrument->name }}</h1><p class="ui-lead">{{ ucwords(str_replace('_',' ',$instrument->category)) }} · Current price {{ currency_symbol() }}{{ number_format((float)$instrument->current_price,2) }}</p></div><div class="flex gap-2"><a href="{{ route('admin.investments.control.index') }}" class="ui-btn ui-btn-secondary">Back</a><a href="{{ route('admin.investments.control.preview',$instrument) }}" class="ui-btn ui-btn-secondary">Preview Asset</a></div></section>
@if(session('success'))<div class="mb-4 rounded-xl border border-emerald-500/20 bg-emerald-500/10 p-4 text-xs text-emerald-600">{{ session('success') }}</div>@endif
@if($errors->any())<div class="mb-4 rounded-xl border border-red-500/20 bg-red-500/10 p-4 text-xs text-red-600">{{ $errors->first() }}</div>@endif

<section class="ui-panel mb-4 p-5">
<div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
    <div>
        <p class="ui-kicker">Reserve authority</p>
        <h2 class="mt-1 text-lg font-semibold">Backing & Capacity</h2>
        <p class="mt-1 max-w-2xl text-[10px] leading-4 text-muted-foreground">Reserve value is the authority for how many customer units can be supported. Unsold catalogue supply cannot create reserve backing.</p>
    </div>
    <div class="rounded-full border border-border px-3 py-1 text-[10px] font-semibold {{ $reserveSummary['customer_fully_backed'] ? 'text-emerald-600' : 'text-red-600' }}">
        {{ $reserveSummary['customer_fully_backed'] ? 'CUSTOMER EXPOSURE BACKED' : 'BACKING DEFICIT' }}
    </div>
</div>
<div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
    <div class="rounded-xl border border-border p-3"><p class="text-[9px] uppercase tracking-[.1em] text-muted-foreground">Reserve Value</p><p class="mt-1 text-sm font-semibold">{{ currency_symbol() }}{{ number_format((float)$reserveSummary['reserve_value'],2) }}</p></div>
    <div class="rounded-xl border border-border p-3"><p class="text-[9px] uppercase tracking-[.1em] text-muted-foreground">Customer Exposure</p><p class="mt-1 text-sm font-semibold">{{ currency_symbol() }}{{ number_format((float)$reserveSummary['customer_liability'],2) }}</p></div>
    <div class="rounded-xl border border-border p-3"><p class="text-[9px] uppercase tracking-[.1em] text-muted-foreground">Outstanding Units</p><p class="mt-1 text-sm font-semibold">{{ number_format((float)$reserveSummary['customer_units'],6) }}</p></div>
    <div class="rounded-xl border border-border p-3"><p class="text-[9px] uppercase tracking-[.1em] text-muted-foreground">Backed Capacity</p><p class="mt-1 text-sm font-semibold">{{ number_format((float)$reserveSummary['backed_unit_capacity'],6) }}</p></div>
    <div class="rounded-xl border border-border p-3"><p class="text-[9px] uppercase tracking-[.1em] text-muted-foreground">Sellable Now</p><p class="mt-1 text-sm font-semibold">{{ number_format((float)$reserveSummary['sellable_units'],6) }}</p></div>
    <div class="rounded-xl border border-border p-3"><p class="text-[9px] uppercase tracking-[.1em] text-muted-foreground">Customer Coverage</p><p class="mt-1 text-sm font-semibold">{{ number_format((float)$reserveSummary['customer_coverage_percent'],2) }}%</p></div>
</div>
@if(!$reserveSummary['listed_fully_backed'])
<p class="mt-3 text-[10px] leading-4 text-amber-600">Catalogue supply is not yet normalized to reserve capacity. R4B baseline normalization is required before reserve authority is sealed.</p>
@endif
</section>

<section class="ui-panel mb-4 p-4" data-r4c-reference-authority>
    @php
        $instrument->loadMissing(['assets.marketInstrument', 'assets.privateMarketReference']);

    $referenceCandidates = $instrument->assets
            ->filter(fn ($asset) => $asset->status === 'active' && (bool) $asset->is_reserve_backing)
            ->values();
    @endphp
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="ui-kicker">Market reference source</p>
            <h2 class="mt-1 text-sm font-semibold">Select the one reference shown beneath the investment chart</h2>
            <p class="mt-1 max-w-3xl text-[10px] leading-4 text-muted-foreground">All active reserve assets still contribute to backing. This selection only controls the single price reference customers and administrators see beside the chart.</p>
        </div>
        <form method="POST" action="{{ route('admin.investments.control.reference.update',$instrument) }}" class="flex min-w-0 flex-col gap-2 sm:flex-row sm:items-center">
            @csrf
            @method('PATCH')
            <select class="ui-input min-w-[260px]" name="reference_asset_id" required @disabled($referenceCandidates->isEmpty())>
                <option value="">Select reference asset</option>
                @foreach($referenceCandidates as $asset)
                    <option value="{{ $asset->id }}" @selected((int)$instrument->reference_asset_id === (int)$asset->id)>
                        {{ $asset->name }}{{ $asset->marketInstrument ? ' · '.$asset->marketInstrument->display_symbol : ($asset->privateMarketReference ? ' · '.$asset->privateMarketReference->symbol : ' · LEGACY') }}
                    </option>
                @endforeach
            </select>
            <button class="ui-btn ui-btn-primary whitespace-nowrap" @disabled($referenceCandidates->isEmpty())>Set Reference</button>
        </form>
    </div>
</section>

<section class="grid gap-4 xl:grid-cols-[1fr_1fr]">
<div class="ui-panel p-5"><p class="ui-kicker">Instrument settings</p>
<form method="POST" action="{{ route('admin.investments.control.instruments.update',$instrument) }}" class="mt-4 grid gap-3 sm:grid-cols-2">@csrf @method('PATCH')
<div><label class="mb-1.5 block text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">Instrument Name</label><input class="ui-input w-full" name="name" value="{{ $instrument->name }}" required></div>
<div><label class="mb-1.5 block text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">Asset Class</label><select class="ui-input w-full" name="category">@foreach(['stock_market','forex','cryptocurrency','real_estate','bonds','hedge_assets'] as $c)<option value="{{ $c }}" @selected($instrument->category===$c)>{{ ucwords(str_replace('_',' ',$c)) }}</option>@endforeach</select></div>
<div><label class="mb-1.5 block text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">Risk Level</label><select class="ui-input w-full" name="risk_level">@foreach(['low','medium','high','very_high'] as $r)<option @selected($instrument->risk_level===$r)>{{ $r }}</option>@endforeach</select></div>
<div><label class="mb-1.5 block text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">Minimum Investment</label><input class="ui-input w-full" type="number" step="0.01" name="minimum_investment" value="{{ $instrument->minimum_investment }}" required></div>
<div><label class="mb-1.5 block text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">Maximum Investment</label><input class="ui-input w-full" type="number" step="0.01" name="maximum_investment" value="{{ $instrument->maximum_investment }}"></div>
<div><label class="mb-1.5 block text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">Management Fee (%)</label><input class="ui-input w-full" type="number" step="0.0001" name="management_fee_percent" value="{{ $instrument->management_fee_percent }}" required></div>
<div><label class="mb-1.5 block text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">Lock Period (Days)</label><input class="ui-input w-full" type="number" min="0" max="3650" name="lock_period_days" value="{{ $instrument->lock_period_days }}" required></div>
<div><label class="mb-1.5 block text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">Investment Duration (Days)</label><input class="ui-input w-full" type="number" min="1" max="3650" name="duration_days" value="{{ $instrument->duration_days }}" required><p class="mt-1 text-[9px] text-muted-foreground">Full lifespan of the investment.</p></div>
<div><label class="mb-1.5 block text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">Return Cycle (Days)</label><input class="ui-input w-full" type="number" min="1" max="3650" name="return_interval_days" value="{{ $instrument->return_interval_days }}" required><p class="mt-1 text-[9px] text-muted-foreground">Configured return interval; may be 3 days, weekly, fortnightly, monthly, etc.</p></div>
<div><label class="mb-1.5 block text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">Projected Minimum Return / Cycle (%)</label><input class="ui-input w-full" type="number" step="0.0001" min="0" max="100" name="projected_return_min_percent" value="{{ $instrument->projected_return_min_percent }}" required></div>
<div><label class="mb-1.5 block text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">Projected Maximum Return / Cycle (%)</label><input class="ui-input w-full" type="number" step="0.0001" min="0" max="100" name="projected_return_max_percent" value="{{ $instrument->projected_return_max_percent }}" required></div>
<div><label class="mb-1.5 block text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">Subscription Fee (%)</label><input class="ui-input w-full" type="number" step="0.0001" min="0" max="100" name="subscription_fee_percent" value="{{ $instrument->subscription_fee_percent }}" required></div>
<div><label class="mb-1.5 block text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">Redemption Fee (%)</label><input class="ui-input w-full" type="number" step="0.0001" min="0" max="100" name="redemption_fee_percent" value="{{ $instrument->redemption_fee_percent }}" required></div>
<div><label class="mb-1.5 block text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">Reserve-Managed Unit Supply</label><div class="ui-input flex w-full items-center">{{ number_format((float)$instrument->unit_supply,6) }}</div><p class="mt-1 text-[9px] text-muted-foreground">Controlled by reserve authority, not manual instrument settings.</p></div>
<div><label class="mb-1.5 block text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">Reserve-Managed Available Units</label><div class="ui-input flex w-full items-center">{{ number_format((float)$instrument->available_units,6) }}</div><p class="mt-1 text-[9px] text-muted-foreground">Cannot exceed reserve-backed capacity after outstanding holdings.</p></div>
<div class="sm:col-span-2"><label class="mb-1.5 block text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">Description</label><textarea class="ui-input w-full" name="description" rows="3">{{ $instrument->description }}</textarea></div>
<label class="inline-flex items-center gap-2 text-xs"><input type="checkbox" name="is_featured" value="1" @checked($instrument->is_featured)><span>Feature on marketplace</span></label>
<label class="inline-flex items-center gap-2 text-xs"><input type="checkbox" name="is_visible" value="1" @checked($instrument->is_visible)><span>Visible to customers</span></label>
<button class="ui-btn ui-btn-primary sm:col-span-2">Save Instrument</button>
</form>
<form method="POST" action="{{ route('admin.investments.control.instruments.toggle',$instrument) }}" class="mt-3">@csrf @method('PATCH')<button class="ui-btn ui-btn-secondary">{{ $instrument->status==='active'?'Pause Instrument':'Activate from Reserve' }}</button></form>
<p class="mt-2 text-[9px] leading-4 text-muted-foreground">Activation revalidates reserve value, customer exposure and reserve-authorized supply. A zero or under-backed reserve cannot be activated.</p>
</div>

<div class="ui-panel p-5"><p class="ui-kicker">Valuation authority</p><h2 class="mt-1 text-lg font-semibold">Apply approved event</h2>
<form method="POST" action="{{ route('admin.investments.control.valuation.apply',$instrument) }}" class="mt-4 grid gap-3 sm:grid-cols-2">@csrf
<div><label class="mb-1.5 block text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">Event Type</label><input class="ui-input w-full" name="event_type" placeholder="e.g. maintenance_expense" required></div>
<div><label class="mb-1.5 block text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">Direction</label><select class="ui-input w-full" name="direction"><option value="positive">Positive</option><option value="negative">Negative</option><option value="neutral">Neutral</option></select></div>
<div><label class="mb-1.5 block text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">Adjustment Method</label><select class="ui-input w-full" name="adjustment_type"><option value="percentage">Percentage</option><option value="fixed">Fixed amount</option><option value="set">Set exact price</option></select></div>
<div><label class="mb-1.5 block text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">Adjustment Value</label><input class="ui-input w-full" type="number" step="0.000001" min="0.000001" name="adjustment_value" placeholder="Adjustment value" required></div>
<div class="sm:col-span-2"><label class="mb-1.5 block text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">Affected Asset</label><select class="ui-input w-full" name="asset_id"><option value="">Whole instrument</option>@foreach($instrument->assets->where('status','active') as $asset)<option value="{{ $asset->id }}">{{ $asset->name }}</option>@endforeach</select></div>
<div class="sm:col-span-2"><label class="mb-1.5 block text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">Reason / Audit Explanation</label><textarea class="ui-input w-full" name="reason" rows="4" placeholder="Explain why this valuation event is being applied" required></textarea></div>
<button class="ui-btn ui-btn-primary sm:col-span-2">Apply Valuation Event</button>
</form></div>
</section>

<section class="ui-panel mt-4 p-5">
<div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
    <div>
        <p class="ui-kicker">Investment lifecycle</p>
        <h2 class="mt-1 text-lg font-semibold">Distribution or Deduction</h2>
        <p class="mt-1 text-[10px] text-muted-foreground">Apply one auditable cash-flow rule to every active holding in this instrument. This does not change the authoritative unit price.</p>
    </div>

    <a href="{{ route('admin.investments.control.lifecycle.history',$instrument) }}" class="ui-btn ui-btn-secondary">
        <i data-lucide="history" class="h-4 w-4"></i>
        View lifecycle history
    </a>
</div>

<form method="POST" action="{{ route('admin.investments.control.lifecycle.apply',$instrument) }}" class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
    @csrf
    <div>
        <label class="ui-label">Lifecycle Type</label>
        <select class="ui-input mt-1 w-full" name="type" required>
            <option value="distribution">Distribution — credit customers</option>
            <option value="deduction">Deduction — debit customers</option>
        </select>
    </div>
    <div>
        <label class="ui-label">Calculation Method</label>
        <select class="ui-input mt-1 w-full" name="calculation_mode" required>
            <option value="fixed_per_unit">Fixed amount per unit</option>
            <option value="percent_current_value">Percentage of current holding value</option>
        </select>
    </div>
    <div>
        <label class="ui-label">Value</label>
        <input class="ui-input mt-1 w-full" type="number" step="0.000001" min="0.000001" name="value" placeholder="e.g. 0.50 or 2.5" required>
    </div>
    <div class="md:col-span-2 xl:col-span-1">
        <label class="ui-label">Reason / Audit Explanation</label>
        <input class="ui-input mt-1 w-full" name="reason" maxlength="2000" placeholder="Why is this lifecycle event being applied?" required>
    </div>
    <button class="ui-btn ui-btn-primary md:col-span-2 xl:col-span-4">Apply Lifecycle Event</button>
</form>

<div class="mt-5">
    <div class="mb-2 flex items-center justify-between gap-3">
        <p class="text-[10px] font-medium text-muted-foreground">Latest 5 lifecycle events</p>
        <a href="{{ route('admin.investments.control.lifecycle.history',$instrument) }}" class="text-[10px] font-semibold underline underline-offset-4">View more</a>
    </div>

    <div class="overflow-x-auto">
    <table class="w-full min-w-[760px] text-left text-[10px]">
        <thead class="text-muted-foreground"><tr><th class="pb-2">When</th><th class="pb-2">Type</th><th class="pb-2">Method</th><th class="pb-2">Configured Value</th><th class="pb-2">Customers</th><th class="pb-2">Total</th><th class="pb-2">Reason</th></tr></thead>
        <tbody class="divide-y divide-border">
        @forelse($instrument->lifecycleEvents as $event)
            <tr>
                <td class="py-2">{{ $event->effective_at?->format('M j, Y H:i') }}</td>
                <td class="py-2 font-semibold">{{ ucfirst($event->type) }}</td>
                <td class="py-2">{{ $event->calculation_mode === 'fixed_per_unit' ? 'Per unit' : '% of value' }}</td>
                <td class="py-2">{{ $event->calculation_mode === 'percent_current_value' ? number_format((float)$event->value,4).'%' : currency_symbol().number_format((float)$event->value,4) }}</td>
                <td class="py-2">{{ $event->affected_holdings }}</td>
                <td class="py-2 font-semibold">{{ currency_symbol() }}{{ number_format((float)$event->total_amount,2) }}</td>
                <td class="py-2 text-muted-foreground">{{ $event->reason }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="py-4 text-muted-foreground">No lifecycle distributions or deductions have been applied yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
</div>
</section>

<section class="ui-panel mt-4 p-5">
<div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
    <div><p class="ui-kicker">Customer Account Operation</p><h2 class="mt-1 text-lg font-semibold">Subscribe or Redeem for Customer</h2><p class="mt-1 text-[10px] text-muted-foreground">Admin mutation authority is explicit: select the customer, instrument action and amount/units.</p></div>
    <p class="text-[10px] text-muted-foreground">Current price: {{ currency_symbol() }}{{ number_format((float)$instrument->current_price,2) }}</p>
</div>

<div class="mt-4 grid gap-4 lg:grid-cols-2">
    <form method="POST" action="{{ route('admin.investments.account-operations.subscribe',$instrument) }}" class="rounded-xl border border-border p-4">
        @csrf
        <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
        <p class="text-xs font-semibold">Admin Subscription</p>
        <label class="ui-label mt-3">Customer Account</label>
        <select class="ui-input mt-1 w-full" name="user_id" required>
            <option value="">Select customer</option>
            @foreach($customers as $customer)
                <option value="{{ $customer->id }}">{{ $customer->name }} · {{ $customer->email }} · {{ currency_symbol() }}{{ number_format((float)($customer->wallet?->available_balance ?? 0),2) }}</option>
            @endforeach
        </select>
        <label class="ui-label mt-3">Investment Amount</label>
        <input class="ui-input mt-1 w-full" type="number" step="0.01" min="{{ max(.01,(float)$instrument->minimum_investment) }}" name="amount" placeholder="Minimum {{ currency_symbol() }}{{ number_format((float)$instrument->minimum_investment,2) }}" required>
        <p class="mt-1 text-[9px] text-muted-foreground">Subscription fee {{ number_format((float)$instrument->subscription_fee_percent,2) }}% · Current unit price {{ currency_symbol() }}{{ number_format((float)$instrument->current_price,2) }}</p>
        <button class="ui-btn ui-btn-primary mt-3 w-full justify-center">Subscribe Customer</button>
    </form>

    <form method="POST" action="{{ route('admin.investments.account-operations.redeem',$instrument) }}" class="rounded-xl border border-border p-4">
        @csrf
        <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
        <p class="text-xs font-semibold">Admin Redemption</p>
        <label class="ui-label mt-3">Customer Account</label>
        <select class="ui-input mt-1 w-full" name="user_id" required>
            <option value="">Select customer</option>
            @foreach($customers as $customer)
                <option value="{{ $customer->id }}">{{ $customer->name }} · {{ $customer->email }}</option>
            @endforeach
        </select>
        <label class="ui-label mt-3">Units to Redeem</label>
        <input class="ui-input mt-1 w-full" type="number" step="0.000001" min="0.000001" name="units" placeholder="Enter units" required>
        <p class="mt-1 text-[9px] text-muted-foreground">Redemption fee {{ number_format((float)$instrument->redemption_fee_percent,2) }}% · Lock period {{ $instrument->lock_period_days }} days</p>
        <button class="ui-btn ui-btn-secondary mt-3 w-full justify-center">Redeem Customer Units</button>
    </form>
</div>
</section>

<section class="mt-4 grid gap-4 xl:grid-cols-[1fr_1fr]">
<div class="ui-panel p-5">
<div class="flex items-start justify-between gap-4">
    <div><p class="ui-kicker">Reserve management</p><h2 class="mt-1 text-lg font-semibold">Backing Assets</h2><p class="mt-1 text-[10px] leading-4 text-muted-foreground">Reserve quantity × authoritative unit price establishes backing value. Capacity is synchronized automatically; catalogue supply is never typed manually.</p></div>
    <span class="rounded-full border border-border px-2.5 py-1 text-[9px] font-semibold">{{ $reserveSummary['reserve_assets'] }} ACTIVE RESERVE{{ $reserveSummary['reserve_assets'] === 1 ? '' : 'S' }}</span>
</div>

@php
    $privateMarketReferences = \App\Models\PrivateMarketReference::query()
        ->where('status', 'active')
        ->orderBy('name')
        ->get();
@endphp
<form method="POST" action="{{ route('admin.investments.control.assets.store',$instrument) }}" class="mt-4 grid gap-3 sm:grid-cols-2">@csrf
    <div class="sm:col-span-2">
        <label class="ui-label">Base Reference Asset</label>
        <select class="ui-input mt-1 w-full" name="reference_authority" required>
            <option value="">Select the asset's reference...</option>
            <optgroup label="Public Market — automatic pricing">
                @foreach($marketInstruments as $market)
                    <option value="public:{{ $market->id }}">{{ strtoupper($market->asset_class) }} · {{ $market->symbol }} · {{ $market->name }}</option>
                @endforeach
            </optgroup>
            <optgroup label="Private Market — RCENTZ maintained">
                @foreach($privateMarketReferences as $privateReference)
                    <option value="private:{{ $privateReference->id }}">{{ $privateReference->symbol }} · {{ $privateReference->name }}@if($privateReference->location) · {{ $privateReference->location }}@endif</option>
                @endforeach
            </optgroup>
        </select>
        <p class="mt-1 text-[9px] leading-4 text-muted-foreground">Public references supply price automatically from the market registry. Private references are created in RCENTZ first, then selected here. The reserve asset never invents its own market price.</p>
    </div>
    <div><label class="ui-label">Asset Type</label><input class="ui-input mt-1 w-full" name="asset_type" placeholder="e.g. allocated_gold, property, private_equity" required></div>
    <div><label class="ui-label">Reserve Asset Name</label><input class="ui-input mt-1 w-full" name="name" placeholder="e.g. Lekki Serviced Apartments" required></div>
    <div><label class="ui-label">Reserve Quantity</label><input class="ui-input mt-1 w-full" type="number" step="0.00000001" min="0.00000001" name="reserve_quantity" placeholder="Actual quantity held" required></div>
    <div><label class="ui-label">Reserve Unit</label><input class="ui-input mt-1 w-full" name="reserve_unit" maxlength="32" placeholder="e.g. property, oz, shares" required></div>
    <div class="sm:col-span-2"><label class="ui-label">Acquisition Unit Price</label><input class="ui-input mt-1 w-full" type="number" step="0.00000001" min="0" name="acquisition_unit_price" placeholder="Optional acquisition cost per reserve unit"></div>
    <div class="sm:col-span-2"><label class="ui-label">Asset Description</label><textarea class="ui-input mt-1 w-full" name="description" rows="2" placeholder="What this reserve represents"></textarea></div>
    <div class="sm:col-span-2"><label class="ui-label">Audit Notes</label><textarea class="ui-input mt-1 w-full" name="notes" rows="2" placeholder="Optional internal reserve notes"></textarea></div>
    <button class="ui-btn ui-btn-primary sm:col-span-2">Add Reserve Asset</button>
</form>

<div class="mt-5 space-y-3">
@foreach($instrument->assets as $asset)
<div class="rounded-xl border border-border p-3">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
        <div><p class="text-xs font-semibold">{{ $asset->name }}</p><p class="mt-1 text-[9px] text-muted-foreground">{{ strtoupper(str_replace('_',' ',$asset->valuation_mode ?? 'manual')) }} · {{ number_format((float)$asset->reserve_quantity,8) }} {{ $asset->reserve_unit ?: 'units' }} @if($asset->marketInstrument) · {{ $asset->marketInstrument->symbol }} @elseif($asset->privateMarketReference) · {{ $asset->privateMarketReference->symbol }} @else · LEGACY @endif · {{ $asset->status }}</p></div>
        <div class="text-right"><p class="text-xs font-semibold">{{ currency_symbol() }}{{ number_format((float)$asset->current_valuation,2) }}</p><p class="text-[9px] text-muted-foreground">{{ number_format((float)$asset->ownership_percentage,2) }}% of active reserve</p></div>
    </div>

    @if($asset->status==='active')
    @php
    $assetAuthorityValue = $asset->private_market_reference_id
        ? 'private:'.$asset->private_market_reference_id
        : ($asset->market_instrument_id ? 'public:'.$asset->market_instrument_id : 'legacy');
@endphp
<form method="POST" action="{{ route('admin.investments.control.assets.update',[$instrument,$asset]) }}" class="mt-3 grid gap-2 border-t border-border pt-3 sm:grid-cols-2">@csrf @method('PATCH')
    <div class="sm:col-span-2">
        <label class="ui-label">Base Reference Asset</label>
        <select class="ui-input mt-1 w-full" name="reference_authority" required>
            @if($assetAuthorityValue === 'legacy')
                <option value="legacy" selected>Legacy manual reserve — select a reference when ready</option>
            @endif
            <optgroup label="Public Market — automatic pricing">
                @foreach($marketInstruments as $market)
                    <option value="public:{{ $market->id }}" @selected($assetAuthorityValue === 'public:'.$market->id)>{{ strtoupper($market->asset_class) }} · {{ $market->symbol }} · {{ $market->name }}</option>
                @endforeach
            </optgroup>
            <optgroup label="Private Market — RCENTZ maintained">
                @foreach($privateMarketReferences as $privateReference)
                    <option value="private:{{ $privateReference->id }}" @selected($assetAuthorityValue === 'private:'.$privateReference->id)>{{ $privateReference->symbol }} · {{ $privateReference->name }}@if($privateReference->location) · {{ $privateReference->location }}@endif</option>
                @endforeach
            </optgroup>
        </select>
    </div>
    <div><label class="ui-label">Asset Type</label><input class="ui-input mt-1 w-full" name="asset_type" value="{{ $asset->asset_type }}" required></div>
    <div><label class="ui-label">Name</label><input class="ui-input mt-1 w-full" name="name" value="{{ $asset->name }}" required></div>
    <div><label class="ui-label">Reserve Quantity</label><input class="ui-input mt-1 w-full" type="number" step="0.00000001" min="0.00000001" name="reserve_quantity" value="{{ $asset->reserve_quantity }}" required></div>
    <div><label class="ui-label">Reserve Unit</label><input class="ui-input mt-1 w-full" name="reserve_unit" maxlength="32" value="{{ $asset->reserve_unit }}" required></div>
    <div><label class="ui-label">Acquisition Unit Price</label><input class="ui-input mt-1 w-full" type="number" step="0.00000001" min="0" name="acquisition_unit_price" value="{{ $asset->acquisition_unit_price }}"></div>
    <div><label class="ui-label">Applied Reference Price</label><div class="mt-1 rounded-xl border border-border bg-muted/20 px-3 py-2.5 text-xs font-semibold tabular-nums">{{ currency_symbol() }}{{ number_format((float)$asset->current_unit_price,2) }}</div><p class="mt-1 text-[9px] text-muted-foreground">Read-only here. Change the public/private reference authority to change the source; update a Private Reference from its own registry.</p></div>
    <div class="sm:col-span-2"><label class="ui-label">Description</label><textarea class="ui-input mt-1 w-full" name="description" rows="2">{{ $asset->description }}</textarea></div>
    <div class="sm:col-span-2"><label class="ui-label">Audit Notes</label><textarea class="ui-input mt-1 w-full" name="notes" rows="2">{{ $asset->notes }}</textarea></div>
    <button class="ui-btn ui-btn-secondary sm:col-span-2">Update Reserve & Recalculate Capacity</button>
</form>
    <form method="POST" action="{{ route('admin.investments.control.assets.destroy',[$instrument,$asset]) }}" class="mt-2" onsubmit="return confirm('Remove this reserve asset? The operation is blocked automatically if customer holdings would become under-backed.')">@csrf @method('DELETE')<button class="text-[9px] font-semibold text-red-600">Remove Reserve Asset</button></form>
    @endif
</div>
@endforeach
</div>

<div class="mt-5 border-t border-border pt-4">
    <div class="flex items-center justify-between"><div><p class="ui-kicker">Reserve audit</p><h3 class="mt-1 text-sm font-semibold">Latest Authority Events</h3></div><span class="text-[9px] text-muted-foreground">Immutable operational trail</span></div>
    <div class="mt-3 space-y-2">
    @forelse($instrument->reserveEvents->take(10) as $event)
        <div class="rounded-lg border border-border p-2.5"><div class="flex justify-between gap-3"><div><p class="text-[10px] font-semibold">{{ ucwords(str_replace('_',' ',$event->action)) }}</p><p class="mt-0.5 text-[9px] text-muted-foreground">{{ $event->reason }}</p></div><p class="shrink-0 text-[9px] text-muted-foreground">{{ optional($event->effective_at)->format('M j, H:i') }}</p></div></div>
    @empty
        <p class="text-[10px] text-muted-foreground">No reserve authority events recorded yet.</p>
    @endforelse
    </div>
</div>
</div>

<div class="ui-panel p-5">
<div class="flex items-start justify-between gap-4">
    <div><p class="ui-kicker">Audit history</p><h2 class="mt-1 text-lg font-semibold">Recent valuation events</h2></div>
    <form method="POST" action="{{ route('admin.investments.control.valuation.reset-test',$instrument) }}" onsubmit="return confirm('Clear admin test valuation events and restore the last non-admin baseline price?')">
        @csrf @method('DELETE')
        <button class="ui-btn ui-btn-secondary !h-8 text-[10px]">Clear Test Events</button>
    </form>
</div>
<p class="mt-2 text-[10px] leading-4 text-muted-foreground">Sandbox maintenance only. Seeded/baseline history is preserved; admin-created test valuation events are removed and active holdings are revalued to the restored price.</p>
<div class="mt-4 space-y-3">@foreach($instrument->events as $event)<div class="rounded-xl border border-border p-3"><div class="flex justify-between gap-3"><div><p class="text-xs font-semibold">{{ ucwords(str_replace('_',' ',$event->event_type)) }}</p><p class="mt-1 text-[10px] text-muted-foreground">{{ $event->reason }}</p></div><div class="text-right"><p class="text-xs">{{ currency_symbol() }}{{ number_format((float)$event->previous_price,2) }} → {{ currency_symbol() }}{{ number_format((float)$event->new_price,2) }}</p><p class="mt-1 text-[9px] text-muted-foreground">{{ optional($event->effective_at)->format('M j, Y H:i') }}</p></div></div></div>@endforeach</div></div>
</section>
</div>
</x-admin-layout>