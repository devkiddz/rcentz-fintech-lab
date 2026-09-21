<x-user-layout>
<x-slot name="header">{{ localize('ui.r2d.copy.my_copied', 'My Copied Strategies') }}</x-slot>
<div class="ui-page max-w-[1440px]">
<section class="ui-page-header">
    <div>
        <p class="ui-kicker text-[10px]">{{ localize('ui.r2d.common.copy_trading', 'Copy Trading') }}</p>
        <h1 class="ui-heading !text-xl">{{ localize('ui.r2d.copy.my_copied', 'My Copied Strategies') }}</h1>
        <p class="ui-lead !text-[13px]">{{ localize('ui.r2d.copy.my_lead', 'Allocation exposure, contract lifecycle and mirrored market activity.') }}</p>
    </div>
    <div class="flex gap-2">
        <a class="ui-btn ui-btn-secondary" href="{{ route('copy-trading.executions') }}">{{ localize('ui.r2d.common.history', 'History') }}</a>
        <a class="ui-btn ui-btn-secondary" href="{{ route('copy-trading.marketplace') }}">{{ localize('ui.r2d.common.marketplace', 'Marketplace') }}</a>
    </div>
</section>

<div class="grid gap-5 xl:grid-cols-2">
@forelse($relationships as $relationship)
@php
    $m=$relationship->performance_metrics;
    $allocation=(float)$relationship->allocation_limit;
    $used=(float)$relationship->used_amount;
    $usedPct=$allocation>0?min(100,($used/$allocation)*100):0;
    $positive=(int)($m['positive_count']??0);
    $negative=(int)($m['negative_count']??0);
    $state=$relationship->contract_state;
    $remaining=$relationship->remaining_seconds;
    $days=intdiv($remaining,86400);
    $hours=intdiv($remaining%86400,3600);
    $minutes=intdiv($remaining%3600,60);
@endphp
<article class="ui-panel overflow-hidden border border-border/70">
    <div class="p-5">
        <div class="flex items-start justify-between gap-3">
            <div>
                <div class="flex flex-wrap gap-2">
                    <span class="rounded-full border border-amber-500/20 bg-amber-500/10 px-2 py-1 text-[10px] font-semibold text-amber-600">{{ localize('ui.r2d.copy.risk_label', ':level risk', ['level' => ucfirst($relationship->strategy?->risk_level ?? 'medium')]) }}</span>
                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-[10px] font-semibold {{ $state==='running'?'border border-emerald-500/20 bg-emerald-500/10 text-emerald-600':'border border-border bg-muted text-muted-foreground' }}">
                        @if($state==='running')<span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>@endif
                        {{ ucfirst($state) }}
                    </span>
                </div>
                <h2 class="mt-2.5 text-base font-semibold">{{ $relationship->strategy?->name }}</h2>
                <p class="mt-1 text-xs text-muted-foreground">{{ localize('ui.r2d.copy.provider_line', 'Provider · :name', ['name' => $relationship->provider?->name]) }}</p>
            </div>
            <button type="button"
                    class="ui-btn ui-btn-secondary !h-8 !px-3"
                    onclick="document.getElementById('copy-contract-{{ $relationship->id }}').showModal()">
                {{ $state === 'running' ? localize('ui.r2d.common.manage', 'Manage') : localize('ui.r2d.common.details', 'Details') }}
            </button>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-2.5 sm:grid-cols-4">
            <div class="rounded-lg border border-border bg-muted/10 p-3">
                <p class="text-[8px] uppercase tracking-[.11em] text-muted-foreground">{{ localize('ui.r2d.common.started', 'Started') }}</p>
                <p class="mt-1 text-[11px] font-semibold">{{ $relationship->started_at?->format('M d · H:i') ?? '—' }}</p>
            </div>
            <div class="rounded-lg border border-border bg-muted/10 p-3">
                <p class="text-[8px] uppercase tracking-[.11em] text-muted-foreground">{{ localize('ui.r2d.common.duration', 'Duration') }}</p>
                <p class="mt-1 text-[11px] font-semibold">
                    @if(!$relationship->duration_minutes)
                        {{ localize('ui.r2d.copy.legacy', 'Legacy') }}
                    @elseif($relationship->duration_minutes >= 1440)
                        {{ number_format($relationship->duration_minutes/1440,0) }} day(s)
                    @elseif($relationship->duration_minutes >= 60)
                        {{ number_format($relationship->duration_minutes/60,0) }} hour(s)
                    @else
                        {{ $relationship->duration_minutes }} min
                    @endif
                </p>
            </div>
            <div class="rounded-lg border border-border bg-muted/10 p-3">
                <p class="text-[8px] uppercase tracking-[.11em] text-muted-foreground">{{ localize('ui.r2d.common.ends', 'Ends') }}</p>
                <p class="mt-1 text-[11px] font-semibold">{{ $relationship->ends_at?->format('M d · H:i') ?? localize('ui.r2d.common.open', 'Open') }}</p>
            </div>
            <div class="rounded-lg border border-border bg-muted/10 p-3">
                <p class="text-[8px] uppercase tracking-[.11em] text-muted-foreground">{{ $state==='running' ? localize('ui.r2d.common.remaining', 'Remaining') : localize('ui.r2d.common.contract', 'Contract') }}</p>
                <p class="mt-1 text-[11px] font-semibold">
                    @if($state==='running')
                        {{ $days>0?$days.'d ':'' }}{{ $hours>0?$hours.'h ':'' }}{{ $minutes }}m
                    @else
                        {{ ucfirst($state) }}
                    @endif
                </p>
            </div>
        </div>
    </div>

    <div class="border-y border-border/70 bg-muted/10 p-3">
        @if($relationship->market_symbol && $relationship->market_asset_class === 'stock')
            @include('trading.partials.mini-analysis-card',['symbol'=>$relationship->market_symbol,'height'=>'h-[250px] sm:h-[300px]'])
        @elseif($relationship->market_symbol)
            <div class="flex h-[250px] sm:h-[300px] items-center justify-center p-4 text-center">
                <div>
                    <span class="inline-flex rounded-full border border-sky-500/20 bg-sky-500/10 px-2 py-1 text-[9px] font-semibold uppercase tracking-[.12em] text-sky-600">{{ strtoupper($relationship->market_asset_class) }}</span>
                    <p class="mt-3 text-xl font-semibold">{{ $relationship->market_symbol }}</p>
                    <p class="mt-1 text-sm font-semibold">{{ $relationship->market_price_display }}</p>
                    <p class="mt-2 text-[9px] uppercase tracking-[.12em] text-muted-foreground">{{ $relationship->market_marketplace }} · {{ str_replace('_',' ',$relationship->market_status) }}</p>
                    <p class="mt-2 text-[9px] text-muted-foreground">{{ localize('ui.r2d.copy.copied_context', 'Current copied position context is marked in the same marketplace where the follower execution occurred.') }}</p>
                </div>
            </div>
        @else
            <div class="flex h-[250px] sm:h-[300px] items-center justify-center p-4 text-center">
                <div>
                    <p class="text-[11px] font-medium">{{ localize('ui.r2d.copy.waiting_completed', 'Waiting for the first completed mirror') }}</p>
                    <p class="mt-1 text-[9px] text-muted-foreground">{{ localize('ui.r2d.copy.market_after_provider', "Market context appears after the provider's first successful copied execution.") }}</p>
                </div>
            </div>
        @endif
    </div>

    <div class="p-5">
        <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-4">
            @foreach([
                [localize('ui.r2d.bots.current_pnl', 'Current P/L'),($m['profit_loss']>0?'+':'').format_currency($m['profit_loss'])],
                [localize('ui.r2d.common.return', 'Return'),($m['return_percent']>0?'+':'').number_format($m['return_percent'],2).'%'],
                [localize('ui.r2d.common.positive', 'Positive'),$positive],
                [localize('ui.r2d.common.negative', 'Negative'),$negative],
            ] as [$label,$value])
            <div class="rounded-lg border border-border bg-muted/10 p-3">
                <p class="text-[8px] uppercase tracking-[.11em] text-muted-foreground">{{ $label }}</p>
                <p class="mt-1 text-[12px] font-semibold">{{ $value }}</p>
            </div>
            @endforeach
        </div>

        <div class="mt-3 grid grid-cols-2 gap-2.5 sm:grid-cols-4">
            @foreach([
                [localize('ui.r2d.common.allocation', 'Allocation'),format_currency($allocation)],
                [localize('ui.r2d.common.used', 'Used'),format_currency($used)],
                [localize('ui.r2d.copy.copy_percent', 'Copy %'),number_format((float)$relationship->copy_ratio_percent,0).'%'],
                [localize('ui.r2d.copy.max_per_trade', 'Max / Trade'),format_currency($relationship->max_trade_amount)],
            ] as [$label,$value])
            <div class="rounded-lg border border-border bg-background/60 p-3">
                <p class="text-[8px] uppercase tracking-[.11em] text-muted-foreground">{{ $label }}</p>
                <p class="mt-1 text-[11px] font-semibold">{{ $value }}</p>
            </div>
            @endforeach
        </div>

        <div class="mt-4">
            <div class="flex justify-between text-[10px]">
                <span class="text-muted-foreground">{{ localize('ui.r2d.bots.allocation_used', 'Allocation used') }}</span>
                <span class="font-medium">{{ number_format($usedPct,1) }}%</span>
            </div>
            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-muted">
                <div class="h-full bg-sky-500" style="width:{{ $usedPct }}%"></div>
            </div>
        </div>
    </div>

    <dialog id="copy-contract-{{ $relationship->id }}"
            class="w-[min(92vw,520px)] rounded-2xl border border-border bg-background p-0 text-foreground shadow-2xl backdrop:bg-black/60">
        <div class="border-b border-border px-5 py-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-[9px] font-semibold uppercase tracking-[.14em] text-sky-500">{{ localize('ui.r2d.copy.copy_contract', 'Copy contract') }}</p>
                    <h3 class="mt-1 text-base font-semibold">{{ $relationship->strategy?->name }}</h3>
                    <p class="mt-1 text-xs text-muted-foreground">{{ localize('ui.r2d.copy.provider_line', 'Provider · :name', ['name' => $relationship->provider?->name]) }}</p>
                </div>
                <button type="button"
                        class="rounded-lg border border-border p-2 text-muted-foreground hover:bg-muted hover:text-foreground"
                        onclick="document.getElementById('copy-contract-{{ $relationship->id }}').close()">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>
        </div>

        <div class="space-y-4 p-5">
            @if($state === 'running')
                <div class="rounded-xl border border-amber-500/20 bg-amber-500/5 p-4">
                    <div class="flex gap-3">
                        <i data-lucide="lock-keyhole" class="mt-0.5 h-4 w-4 shrink-0 text-amber-600"></i>
                        <div>
                            <p class="text-sm font-semibold">{{ localize('ui.r2d.copy.running_locked', 'Running contract locked') }}</p>
                            <p class="mt-1 text-xs leading-5 text-muted-foreground">
                                {{ localize('ui.r2d.copy.lock_help', 'Once copying starts, allocation, maximum per trade, copy percentage and duration are fixed until the contract ends. This prevents the execution rules from changing midway through mirrored trades.') }}
                            </p>
                        </div>
                    </div>
                </div>
            @else
                <div class="rounded-xl border border-border bg-muted/10 p-4">
                    <p class="text-sm font-semibold">{{ localize('ui.r2d.copy.state_contract', ':state contract', ['state' => ucfirst($state)]) }}</p>
                    <p class="mt-1 text-xs leading-5 text-muted-foreground">
                        {{ localize('ui.r2d.copy.readonly_help', 'Contract terms are retained as a read-only record after activation and are not rewritten later.') }}
                    </p>
                </div>
            @endif

            <div class="grid grid-cols-2 gap-2.5">
                @foreach([
                    [localize('ui.r2d.common.allocation', 'Allocation'),format_currency($allocation)],
                    [localize('ui.r2d.copy.max_per_trade', 'Max / Trade'),format_currency($relationship->max_trade_amount)],
                    [localize('ui.r2d.copy.copy_percent', 'Copy %'),number_format((float)$relationship->copy_ratio_percent,0).'%'],
                    [localize('ui.r2d.common.used', 'Used'),format_currency($used)],
                ] as [$label,$value])
                    <div class="rounded-xl border border-border bg-background p-3">
                        <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">{{ $label }}</p>
                        <p class="mt-1 text-xs font-semibold">{{ $value }}</p>
                    </div>
                @endforeach
            </div>

            <div class="rounded-xl border border-border bg-muted/10 p-4">
                <div class="grid gap-3 sm:grid-cols-3">
                    <div>
                        <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">{{ localize('ui.r2d.common.started', 'Started') }}</p>
                        <p class="mt-1 text-xs font-medium">{{ $relationship->started_at?->format('M d, Y · H:i') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">{{ localize('ui.r2d.common.ends', 'Ends') }}</p>
                        <p class="mt-1 text-xs font-medium">{{ $relationship->ends_at?->format('M d, Y · H:i') ?? 'Legacy / open' }}</p>
                    </div>
                    <div>
                        <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">{{ localize('ui.r2d.common.state', 'State') }}</p>
                        <p class="mt-1 text-xs font-medium">{{ ucfirst($state) }}</p>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="button"
                        class="ui-btn ui-btn-primary"
                        onclick="document.getElementById('copy-contract-{{ $relationship->id }}').close()">
                    {{ localize('ui.r2d.common.close', 'Close') }}
                </button>
            </div>
        </div>
    </dialog>
</article>
@empty
<div class="ui-panel p-8 text-center text-sm text-muted-foreground xl:col-span-2">{{ localize('ui.r2d.copy.no_copied', 'No copied strategies yet.') }}</div>
@endforelse
</div>
</div>
</x-user-layout>
