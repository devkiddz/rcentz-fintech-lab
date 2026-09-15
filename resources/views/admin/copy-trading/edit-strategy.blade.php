<x-admin-layout>
    <x-slot name="header">Edit Copy Strategy</x-slot>

    <div class="mx-auto max-w-4xl space-y-6">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-muted-foreground">Copy Trading</p>
            <h1 class="mt-2 text-2xl font-semibold">Edit strategy</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Provider: {{ $strategy->profile->user->name }}
            </p>
        </div>

        <form method="POST" action="{{ route('admin.copy-trading.strategies.update', $strategy) }}" class="ui-panel space-y-5 p-6">
            @csrf
            @method('PATCH')

            <div>
                <label class="ui-label">Strategy name</label>
                <input name="name" class="ui-input" value="{{ old('name', $strategy->name) }}" required>
            </div>

            <div>
                <label class="ui-label">Description</label>
                <textarea name="description" class="ui-input min-h-28">{{ old('description', $strategy->description) }}</textarea>
            </div>

            <div class="grid gap-5 md:grid-cols-3">
                <div>
                    <label class="ui-label">Risk level</label>
                    <select name="risk_level" class="ui-input">
                        @foreach(['low','medium','high'] as $risk)
                            <option value="{{ $risk }}" @selected(old('risk_level', $strategy->risk_level) === $risk)>{{ ucfirst($risk) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="ui-label">Minimum allocation</label>
                    <input name="minimum_allocation" type="number" step="0.01" min="50" class="ui-input" value="{{ old('minimum_allocation', $strategy->minimum_allocation) }}">
                </div>
                <div>
                    <label class="ui-label">Recommended allocation</label>
                    <input name="recommended_allocation" type="number" step="0.01" min="50" class="ui-input" value="{{ old('recommended_allocation', $strategy->recommended_allocation) }}">
                </div>
            </div>

            <div class="rounded-2xl border border-border bg-muted/20 p-5 space-y-4">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold">Presentation Performance Override</p>
                        <p class="mt-1 text-xs text-muted-foreground">Optional preview P/L for the strategy card. It does not create trades or alter any customer balance.</p>
                    </div>
                    <span class="rounded-md border border-border bg-background px-2 py-1 text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">Presentation only</span>
                </div>
                <div class="rounded-xl border border-border bg-background px-4 py-3 text-xs text-muted-foreground">Calculation base: <strong id="performance-calculation-base" class="text-foreground">—</strong></div>

                <label class="flex items-center gap-3 rounded-xl border border-border bg-background p-4">
                    <input type="checkbox" name="use_manual_performance" value="1" @checked(old('use_manual_performance', $strategy->use_manual_performance))>
                    <span class="text-sm font-medium">Use manual P/L and return on presentation cards</span>
                </label>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="ui-label">Manual Profit / Loss</label>
                        <input name="manual_profit_loss" type="number" step="0.01" class="ui-input" value="{{ old('manual_profit_loss', $strategy->manual_profit_loss) }}" placeholder="e.g. 1240.00 or -180.00">
                    </div>
                    <div>
                        <label class="ui-label">Manual Return %</label>
                        <input name="manual_return_percent" type="number" step="0.01" class="ui-input" value="{{ old('manual_return_percent', $strategy->manual_return_percent) }}" placeholder="e.g. 12.40">
                    </div>
                </div>

                <div>
                    <label class="ui-label">Customer-facing performance label</label>
                    <input name="manual_performance_label" class="ui-input" value="{{ old('manual_performance_label', $strategy->manual_performance_label) }}" placeholder="e.g. Illustrative Performance, Backtest Snapshot, Model Projection">
                    <p class="mt-1 text-xs text-muted-foreground">Shown to customers when manual performance is enabled.</p>
                </div>

                <div>
                    <label class="ui-label">Internal presentation note</label>
                    <input name="manual_performance_note" class="ui-input" value="{{ old('manual_performance_note', $strategy->manual_performance_note) }}" placeholder="Why this preview override is being used">
                    <input type="hidden" name="manual_performance_source" id="manual_performance_source" value="">
                </div>
            </div>

            <div class="flex flex-wrap gap-4">
                <label class="flex items-center gap-3 rounded-xl border border-border p-4">
                    <input type="checkbox" name="is_public" value="1" @checked(old('is_public', $strategy->is_public))>
                    <span class="text-sm">Visible in marketplace</span>
                </label>
                <label class="flex items-center gap-3 rounded-xl border border-border p-4">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $strategy->is_active))>
                    <span class="text-sm">Accepting copies</span>
                </label>
            </div>

            <div class="flex justify-end gap-3 border-t border-border pt-5">
                <a href="{{ route('admin.copy-trading.strategies') }}" class="ui-btn ui-btn-secondary">Cancel</a>
                <button class="ui-btn ui-btn-primary">Save strategy</button>
            </div>
        </form>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const pnl = document.querySelector('[name="manual_profit_loss"]');
    const ret = document.querySelector('[name="manual_return_percent"]');
    const source = document.getElementById('manual_performance_source');
    const baseLabel = document.getElementById('performance-calculation-base');

    function numberValue(name) {
        const el = document.querySelector('[name="' + name + '"]');
        if (!el || el.value === '') return 0;
        const value = Number(el.value);
        return Number.isFinite(value) ? value : 0;
    }

    function baseAmount() {
        const recommended = numberValue('recommended_allocation');
            const minimum = numberValue('minimum_allocation');
            return recommended || minimum || 0;
    }

    function refreshBase() {
        const base = baseAmount();
        if (baseLabel) {
            baseLabel.textContent = base > 0 ? '$' + base.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) : 'Set allocation/base amount';
        }
    }

    if (pnl && ret) {
        pnl.addEventListener('input', function () {
            const base = baseAmount();
            if (source) source.value = 'profit_loss';
            if (base > 0 && pnl.value !== '') {
                ret.value = ((Number(pnl.value) / base) * 100).toFixed(2);
            }
        });

        ret.addEventListener('input', function () {
            const base = baseAmount();
            if (source) source.value = 'return_percent';
            if (base > 0 && ret.value !== '') {
                pnl.value = (base * (Number(ret.value) / 100)).toFixed(2);
            }
        });
    }

    ['max_user_allocation','minimum_balance','default_trade_amount','recommended_allocation','minimum_allocation'].forEach(function(name) {
        const el = document.querySelector('[name="' + name + '"]');
        if (el) el.addEventListener('input', refreshBase);
    });

    refreshBase();
});
</script>

</x-admin-layout>
