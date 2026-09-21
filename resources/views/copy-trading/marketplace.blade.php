<x-user-layout>
<x-slot name="header">{{ localize('ui.r2d.common.copy_trading', 'Copy Trading') }}</x-slot>
<div class="ui-page max-w-[1440px]">
<section class="ui-page-header">
    <div>
        <p class="ui-kicker text-[10px]">{{ localize('ui.r2d.common.copy_trading', 'Copy Trading') }}</p>
        <h1 class="ui-heading !text-xl">{{ localize('ui.r2d.copy.strategy_marketplace', 'Strategy Marketplace') }}</h1>
        <p class="ui-lead !text-[13px]">{{ localize('ui.r2d.copy.marketplace_lead', 'Approved provider strategies with transparent allocation, performance and market context.') }}</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('copy-trading.my-copies') }}" class="ui-btn ui-btn-secondary">{{ localize('ui.r2d.copy.my_copies', 'My Copies') }}</a>
        <a href="{{ route('copy-trading.apply') }}" class="ui-btn ui-btn-secondary">{{ localize('ui.r2d.copy.become_provider', 'Become Provider') }}</a>
    </div>
</section>

<div class="grid gap-5 lg:grid-cols-2">
@forelse($strategies as $strategy)
@php $m=$strategy->performance_metrics; @endphp
<article class="ui-panel overflow-hidden border border-border/70">
    <div class="p-5">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-[10px] uppercase tracking-[.12em] text-muted-foreground">{{ localize('ui.r2d.copy.approved_provider', 'Approved provider') }}</p>
                <h2 class="mt-1 text-base font-semibold">{{ $strategy->name }}</h2>
                <p class="mt-1 text-xs text-muted-foreground">{{ $strategy->profile->user->name }}</p>
            </div>
            <div class="flex gap-2">
                <span class="rounded-full border border-amber-500/20 bg-amber-500/10 px-2 py-1 text-[10px] font-semibold text-amber-600">{{ localize('ui.r2d.copy.risk_label', ':level risk', ['level' => ucfirst($strategy->risk_level)]) }}</span>
                <span class="rounded-full border border-border bg-muted px-2 py-1 text-[10px] font-semibold text-muted-foreground">{{ localize('ui.r2d.copy.copiers_count', ':count copiers', ['count' => $strategy->copier_count]) }}</span>
            </div>
        </div>
        <p class="mt-3 line-clamp-2 text-xs leading-5 text-muted-foreground">{{ $strategy->description }}</p>
    </div>

    <div class="border-y border-border/70 bg-muted/10 p-3">
        @if($strategy->market_symbol && $strategy->market_asset_class === 'stock')
            @include('trading.partials.mini-analysis-card',['symbol'=>$strategy->market_symbol,'height'=>'h-[230px] sm:h-[270px]'])
        @elseif($strategy->market_symbol)
            <div class="flex h-[230px] sm:h-[270px] items-center justify-center px-4 text-center">
                <div>
                    <span class="inline-flex rounded-full border border-sky-500/20 bg-sky-500/10 px-2 py-1 text-[9px] font-semibold uppercase tracking-[.12em] text-sky-600">{{ strtoupper($strategy->market_asset_class) }}</span>
                    <p class="mt-3 text-lg font-semibold">{{ $strategy->market_symbol }}</p>
                    <p class="mt-1 text-sm font-semibold">{{ $strategy->market_price_display }}</p>
                    <p class="mt-2 text-[9px] uppercase tracking-[.12em] text-muted-foreground">{{ $strategy->market_marketplace }} · {{ str_replace('_',' ',$strategy->market_status) }}</p>
                    <p class="mt-2 text-[9px] text-muted-foreground">{{ localize('ui.r2d.copy.mirrored_context', 'Multi-asset execution context from the latest successfully mirrored trade.') }}</p>
                </div>
            </div>
        @else
            <div class="flex h-[230px] sm:h-[270px] items-center justify-center px-4 text-center">
                <div>
                    <p class="text-[11px] font-medium">{{ localize('ui.r2d.copy.waiting_first_mirror', 'Waiting for first mirrored execution') }}</p>
                    <p class="mt-1 text-[9px] text-muted-foreground">{{ localize('ui.r2d.copy.market_after_mirror', 'The strategy market context appears after its first successful mirrored trade.') }}</p>
                </div>
            </div>
        @endif
    </div>

    <div class="p-5">
        <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-4">
            @foreach([
                [localize('ui.r2d.common.pnl', 'P/L'), ($m['profit_loss'] > 0 ? '+' : '').format_currency($m['profit_loss'])],
                [localize('ui.r2d.common.return', 'Return'), ($m['return_percent'] > 0 ? '+' : '').number_format($m['return_percent'],2).'%'],
                [localize('ui.r2d.common.executions', 'Executions'), $m['completed_count']],
                [localize('ui.r2d.copy.positive_rate', 'Positive Rate'), number_format($m['positive_execution_rate'] ?? $m['win_rate'],1).'%'],
            ] as [$label,$value])
            <div class="rounded-lg border border-border bg-muted/10 p-3">
                <p class="text-[8px] uppercase tracking-[.11em] text-muted-foreground">{{ $label }}</p>
                <p class="mt-1 text-[12px] font-semibold">{{ $value }}</p>
            </div>
            @endforeach
        </div>

        <form action="{{ route('copy-trading.follow',$strategy) }}" method="POST" class="mt-5 space-y-3 border-t border-border pt-5">
            @csrf
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="ui-label !text-[10px]">{{ localize('ui.r2d.common.allocation', 'Allocation') }}</label>
                    <input name="allocation_limit" class="ui-input" type="number" min="{{ $strategy->minimum_allocation }}" value="{{ $strategy->recommended_allocation ?: $strategy->minimum_allocation }}" required>
                </div>
                <div>
                    <label class="ui-label !text-[10px]">{{ localize('ui.r2d.copy.max_per_trade', 'Max / Trade') }}</label>
                    <input name="max_trade_amount" class="ui-input" type="number" min="10" value="{{ $strategy->minimum_allocation }}" required>
                </div>
                <div>
                    <label class="ui-label !text-[10px]">{{ localize('ui.r2d.copy.copy_percent', 'Copy %') }}</label>
                    <input name="copy_ratio_percent" class="ui-input" type="number" min="1" max="200" value="100" required>
                </div>
                <div>
                    <label class="ui-label !text-[10px]">{{ localize('ui.r2d.common.contract', 'Contract') }}</label>
                    <select name="duration_minutes" class="ui-input" required>
                        <option value="60">{{ localize('ui.r2d.copy.one_hour', '1 hour') }}</option>
                        <option value="240">{{ localize('ui.r2d.copy.four_hours', '4 hours') }}</option>
                        <option value="1440">{{ localize('ui.r2d.copy.one_day', '1 day') }}</option>
                        <option value="10080" selected>{{ localize('ui.r2d.copy.seven_days', '7 days') }}</option>
                        <option value="43200">{{ localize('ui.r2d.copy.thirty_days', '30 days') }}</option>
                    </select>
                </div>
            </div>
            <button class="ui-btn ui-btn-primary w-full">{{ localize('ui.r2d.copy.copy_strategy', 'Copy Strategy') }}</button>
        </form>
    </div>
</article>
@empty
<div class="ui-panel p-8 text-center text-sm text-muted-foreground lg:col-span-2">{{ localize('ui.r2d.copy.no_strategies', 'No approved strategies available.') }}</div>
@endforelse
</div>
</div>
</x-user-layout>
