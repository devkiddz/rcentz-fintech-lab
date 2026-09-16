<x-admin-layout>
<div class="ui-page max-w-[1500px]">
<section class="ui-page-header"><div><p class="ui-kicker">Admin · Investments · {{ $instrument->symbol }}</p><h1 class="ui-heading">{{ $instrument->name }}</h1><p class="ui-lead">{{ ucwords(str_replace('_',' ',$instrument->category)) }} · Current price {{ currency_symbol() }}{{ number_format((float)$instrument->current_price,2) }}</p></div><div class="flex gap-2"><a href="{{ route('admin.investments.control.index') }}" class="ui-btn ui-btn-secondary">Back</a><a href="{{ route('investments.show',$instrument->slug) }}" class="ui-btn ui-btn-secondary">Customer View</a></div></section>
@if(session('success'))<div class="mb-4 rounded-xl border border-emerald-500/20 bg-emerald-500/10 p-4 text-xs text-emerald-600">{{ session('success') }}</div>@endif
@if($errors->any())<div class="mb-4 rounded-xl border border-red-500/20 bg-red-500/10 p-4 text-xs text-red-600">{{ $errors->first() }}</div>@endif

<section class="grid gap-4 xl:grid-cols-[1fr_1fr]">
<div class="ui-panel p-5"><p class="ui-kicker">Instrument settings</p>
<form method="POST" action="{{ route('admin.investments.control.instruments.update',$instrument) }}" class="mt-4 grid gap-3 sm:grid-cols-2">@csrf @method('PATCH')
<div><label class="mb-1.5 block text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">Instrument Name</label><input class="ui-input w-full" name="name" value="{{ $instrument->name }}" required></div>
<div><label class="mb-1.5 block text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">Asset Class</label><select class="ui-input w-full" name="category">@foreach(['stock_market','cryptocurrency','real_estate','bonds'] as $c)<option value="{{ $c }}" @selected($instrument->category===$c)>{{ ucwords(str_replace('_',' ',$c)) }}</option>@endforeach</select></div>
<div><label class="mb-1.5 block text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">Risk Level</label><select class="ui-input w-full" name="risk_level">@foreach(['low','medium','high','very_high'] as $r)<option @selected($instrument->risk_level===$r)>{{ $r }}</option>@endforeach</select></div>
<div><label class="mb-1.5 block text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">Minimum Investment</label><input class="ui-input w-full" type="number" step="0.01" name="minimum_investment" value="{{ $instrument->minimum_investment }}" required></div>
<div><label class="mb-1.5 block text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">Maximum Investment</label><input class="ui-input w-full" type="number" step="0.01" name="maximum_investment" value="{{ $instrument->maximum_investment }}"></div>
<div><label class="mb-1.5 block text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">Management Fee (%)</label><input class="ui-input w-full" type="number" step="0.0001" name="management_fee_percent" value="{{ $instrument->management_fee_percent }}" required></div>
<div><label class="mb-1.5 block text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">Lock Period (Days)</label><input class="ui-input w-full" type="number" name="lock_period_days" value="{{ $instrument->lock_period_days }}" required></div>
<div><label class="mb-1.5 block text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">Total Unit Supply</label><input class="ui-input w-full" type="number" step="0.000001" name="unit_supply" value="{{ $instrument->unit_supply }}" required></div>
<div><label class="mb-1.5 block text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">Available Units</label><input class="ui-input w-full" type="number" step="0.000001" name="available_units" value="{{ $instrument->available_units }}" required></div>
<div class="sm:col-span-2"><label class="mb-1.5 block text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">Description</label><textarea class="ui-input w-full" name="description" rows="3">{{ $instrument->description }}</textarea></div>
<label class="text-xs"><input type="checkbox" name="is_featured" value="1" @checked($instrument->is_featured)> Featured</label>
<label class="text-xs"><input type="checkbox" name="is_visible" value="1" @checked($instrument->is_visible)> Visible</label>
<button class="ui-btn ui-btn-primary sm:col-span-2">Save Instrument</button>
</form>
<form method="POST" action="{{ route('admin.investments.control.instruments.toggle',$instrument) }}" class="mt-3">@csrf @method('PATCH')<button class="ui-btn ui-btn-secondary">{{ $instrument->status==='active'?'Pause Instrument':'Resume Instrument' }}</button></form>
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
<div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
    <div><p class="ui-kicker">Customer Account Operation</p><h2 class="mt-1 text-lg font-semibold">Subscribe or Redeem for Customer</h2><p class="mt-1 text-[10px] text-muted-foreground">Admin mutation authority is explicit: select the customer, instrument action and amount/units.</p></div>
    <p class="text-[10px] text-muted-foreground">Current price: {{ currency_symbol() }}{{ number_format((float)$instrument->current_price,2) }}</p>
</div>

<div class="mt-4 grid gap-4 lg:grid-cols-2">
    <form method="POST" action="{{ route('admin.investments.account-operations.subscribe',$instrument) }}" class="rounded-xl border border-border p-4">
        @csrf
        <p class="text-xs font-semibold">Admin Subscription</p>
        <select class="ui-input mt-3" name="user_id" required>
            <option value="">Select customer</option>
            @foreach($customers as $customer)
                <option value="{{ $customer->id }}">{{ $customer->name }} · {{ $customer->email }} · {{ currency_symbol() }}{{ number_format((float)($customer->wallet?->available_balance ?? 0),2) }}</option>
            @endforeach
        </select>
        <input class="ui-input mt-2" type="number" step="0.01" min="{{ max(.01,(float)$instrument->minimum_investment) }}" name="amount" placeholder="Subscription amount" required>
        <button class="ui-btn ui-btn-primary mt-3 w-full justify-center">Subscribe Customer</button>
    </form>

    <form method="POST" action="{{ route('admin.investments.account-operations.redeem',$instrument) }}" class="rounded-xl border border-border p-4">
        @csrf
        <p class="text-xs font-semibold">Admin Redemption</p>
        <select class="ui-input mt-3" name="user_id" required>
            <option value="">Select customer</option>
            @foreach($customers as $customer)
                <option value="{{ $customer->id }}">{{ $customer->name }} · {{ $customer->email }}</option>
            @endforeach
        </select>
        <input class="ui-input mt-2" type="number" step="0.000001" min="0.000001" name="units" placeholder="Units to redeem" required>
        <button class="ui-btn ui-btn-secondary mt-3 w-full justify-center">Redeem Customer Units</button>
    </form>
</div>
</section>

<section class="mt-4 grid gap-4 xl:grid-cols-[1fr_1fr]">
<div class="ui-panel p-5"><p class="ui-kicker">Underlying composition</p><h2 class="mt-1 text-lg font-semibold">Assets</h2>
<form method="POST" action="{{ route('admin.investments.control.assets.store',$instrument) }}" class="mt-4 grid gap-2 sm:grid-cols-2">@csrf
<input class="ui-input" name="asset_type" placeholder="Asset type" required><input class="ui-input" name="name" placeholder="Asset name" required>
<input class="ui-input" type="number" step="0.01" name="acquisition_value" placeholder="Acquisition value" required><input class="ui-input" type="number" step="0.01" name="current_valuation" placeholder="Current valuation" required>
<input class="ui-input" type="number" step="0.0001" min="0" max="100" name="ownership_percentage" placeholder="Weight %" required><textarea class="ui-input" name="description" placeholder="Description"></textarea>
<textarea class="ui-input sm:col-span-2" name="notes" placeholder="Notes"></textarea><button class="ui-btn ui-btn-primary sm:col-span-2">Add Asset</button>
</form>
<div class="mt-4 space-y-2">@foreach($instrument->assets as $asset)<div class="rounded-xl border border-border p-3"><div class="flex justify-between gap-3"><div><p class="text-xs font-semibold">{{ $asset->name }}</p><p class="text-[9px] text-muted-foreground">{{ $asset->asset_type }} · {{ $asset->status }}</p></div><div class="text-right"><p class="text-xs font-semibold">{{ currency_symbol() }}{{ number_format((float)$asset->current_valuation,0) }}</p>@if($asset->status==='active')<form method="POST" action="{{ route('admin.investments.control.assets.destroy',[$instrument,$asset]) }}">@csrf @method('DELETE')<button class="mt-1 text-[9px] text-red-600">Remove</button></form>@endif</div></div></div>@endforeach</div>
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