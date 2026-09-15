<x-user-layout>
<x-slot name="header">Edit Copy Settings</x-slot>
<div class="ui-page max-w-4xl">
<section class="ui-page-header">
    <div>
        <p class="ui-kicker">Copy Trading</p>
        <h1 class="ui-heading">{{ $relationship->strategy?->name }}</h1>
        <p class="ui-lead">Update your own allocation and copy behaviour without changing the provider strategy.</p>
    </div>
    <a href="{{ route('copy-trading.my-copies') }}" class="ui-btn ui-btn-secondary">Back to My Copies</a>
</section>

<form method="POST" action="{{ route('copy-trading.relationships.update',$relationship) }}" class="ui-panel p-6 space-y-6">
    @csrf @method('PATCH')

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-border p-4"><p class="text-xs text-muted-foreground">Provider</p><p class="mt-1 font-semibold">{{ $relationship->provider->name }}</p></div>
        <div class="rounded-xl border border-border p-4"><p class="text-xs text-muted-foreground">Minimum Amount</p><p class="mt-1 font-semibold">{{ format_currency($relationship->strategy?->minimum_allocation ?? 0) }}</p></div>
        <div class="rounded-xl border border-border p-4"><p class="text-xs text-muted-foreground">Used Amount</p><p class="mt-1 font-semibold">{{ format_currency($relationship->used_amount) }}</p></div>
    </div>

    <div class="grid gap-5 md:grid-cols-2">
        <div>
            <label class="ui-label">Allocation Amount</label>
            <input name="allocation_limit" type="number" step="0.01" class="ui-input" min="{{ $relationship->strategy?->minimum_allocation ?? 50 }}" value="{{ old('allocation_limit',$relationship->allocation_limit) }}" required>
        </div>
        <div>
            <label class="ui-label">Maximum Per Trade</label>
            <input name="max_trade_amount" type="number" step="0.01" min="10" class="ui-input" value="{{ old('max_trade_amount',$relationship->max_trade_amount) }}" required>
        </div>
        <div>
            <label class="ui-label">Copy Percentage</label>
            <input name="copy_ratio_percent" type="number" step="0.01" min="1" max="200" class="ui-input" value="{{ old('copy_ratio_percent',$relationship->copy_ratio_percent) }}" required>
        </div>
        <div>
            <label class="ui-label">Status</label>
            <select name="status" class="ui-input">
                @foreach(['active','paused','stopped'] as $status)
                    <option value="{{ $status }}" @selected(old('status',$relationship->status)===$status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="flex justify-end gap-3 border-t border-border pt-5">
        <a href="{{ route('copy-trading.my-copies') }}" class="ui-btn ui-btn-secondary">Cancel</a>
        <button class="ui-btn ui-btn-primary">Save Copy Settings</button>
    </div>
</form>
</div>
</x-user-layout>