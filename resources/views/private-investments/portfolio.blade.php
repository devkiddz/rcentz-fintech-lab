<x-user-layout>
<x-slot name="header">{{ localize('ui.r2d.portfolio.intelligence', 'Portfolio Intelligence') }}</x-slot>

@php
    $money = fn ($value) => currency_symbol().number_format(abs((float) $value), 2);
    $signedMoney = fn ($value) => ((float) $value >= 0 ? '+' : '-').$money($value);
@endphp

<div class="mx-auto max-w-[1180px] px-3 py-5 sm:px-5">
    <section class="rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950 sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-[.16em] text-red-600">{{ localize('ui.r2d.portfolio.intelligence', 'Portfolio Intelligence') }}</p>
                <h1 class="mt-2 text-2xl font-semibold text-zinc-950 dark:text-white">{{ localize('ui.r2d.portfolio.my_portfolio', 'My Portfolio') }}</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-zinc-500">
                    {{ localize('ui.r2d.portfolio.lead', 'Review private investments alongside Trading, Copy Trading and Bot Trading without mixing the financial records of each engine.') }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2 text-xs">
                <a href="{{ route('account.investments') }}" class="rounded-full border border-zinc-200 px-3 py-2 font-medium dark:border-zinc-800">{{ localize('ui.r2d.common.investment_account', 'Investment account') }}</a>
                <a href="{{ route('account.investments.transactions') }}" class="rounded-full border border-zinc-200 px-3 py-2 font-medium dark:border-zinc-800">{{ localize('ui.r2d.common.transactions', 'Transactions') }}</a>
                <a href="{{ route('account.investments.performance') }}" class="rounded-full border border-zinc-200 px-3 py-2 font-medium dark:border-zinc-800">{{ localize('ui.r2d.common.performance', 'Performance') }}</a>
            </div>
        </div>

        @if($isAdmin)
            <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200">
                Admin audit mode: this customer-owned portfolio surface does not calculate an Admin portfolio. Use the Admin control plane for customer inspection.
            </div>
        @else
            <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-800">
                    <p class="text-[10px] font-semibold uppercase tracking-[.14em] text-zinc-400">{{ localize('ui.r2d.common.current_value', 'Current value') }}</p>
                    <p class="mt-2 text-xl font-semibold">{{ currency_symbol() }}{{ number_format($summary['current_value'], 2) }}</p>
                    <p class="mt-1 text-xs text-zinc-500">{{ $summary['active_holdings'] }} {{ $summary['active_holdings'] === 1 ? 'active holding' : 'active holdings' }}</p>
                </div>
                <div class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-800">
                    <p class="text-[10px] font-semibold uppercase tracking-[.14em] text-zinc-400">{{ localize('ui.r2d.common.capital_at_work', 'Capital at work') }}</p>
                    <p class="mt-2 text-xl font-semibold">{{ currency_symbol() }}{{ number_format($summary['capital_at_work'], 2) }}</p>
                    <p class="mt-1 text-xs text-zinc-500">{{ localize('ui.r2d.portfolio.remaining_cost_basis', 'Remaining cost basis') }}</p>
                </div>
                <div class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-800">
                    <p class="text-[10px] font-semibold uppercase tracking-[.14em] text-zinc-400">{{ localize('ui.r2d.common.net_performance', 'Net performance') }}</p>
                    <p class="mt-2 text-xl font-semibold {{ $summary['net_performance'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                        {{ $signedMoney($summary['net_performance']) }}
                    </p>
                    <p class="mt-1 text-xs {{ $summary['return_percent'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                        {{ $summary['return_percent'] >= 0 ? '+' : '' }}{{ number_format($summary['return_percent'], 2) }}% {{ localize('ui.r2d.portfolio.of_subscribed_capital', 'of total subscribed capital') }}
                    </p>
                </div>
                <div class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-800">
                    <p class="text-[10px] font-semibold uppercase tracking-[.14em] text-zinc-400">{{ localize('ui.r2d.common.available_wallet', 'Available wallet') }}</p>
                    <p class="mt-2 text-xl font-semibold">{{ currency_symbol() }}{{ number_format($summary['available_wallet'], 2) }}</p>
                    <p class="mt-1 text-xs text-zinc-500">{{ localize('ui.r2d.portfolio.outside_invested_capital', 'Outside invested capital') }}</p>
                </div>
            </div>

            <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-900">
                    <p class="text-xs text-zinc-500">{{ financial_term('unrealized_pnl') }}</p>
                    <p class="mt-1 font-semibold {{ $summary['unrealized_profit_loss'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $signedMoney($summary['unrealized_profit_loss']) }}</p>
                </div>
                <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-900">
                    <p class="text-xs text-zinc-500">{{ financial_term('realized_pnl') }}</p>
                    <p class="mt-1 font-semibold {{ $summary['realized_profit_loss'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $signedMoney($summary['realized_profit_loss']) }}</p>
                </div>
                <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-900">
                    <p class="text-xs text-zinc-500">{{ localize('ui.r2d.common.distributions', 'Distributions') }}</p>
                    <p class="mt-1 font-semibold text-emerald-600">+{{ $money($summary['distributions']) }}</p>
                </div>
                <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-900">
                    <p class="text-xs text-zinc-500">{{ localize('ui.r2d.common.deductions', 'Deductions') }}</p>
                    <p class="mt-1 font-semibold text-red-600">-{{ $money($summary['deductions']) }}</p>
                </div>
            </div>
        @endif
    </section>

    @if(!$isAdmin)
        <div class="mt-5 grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
            <section class="rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                <div class="flex items-end justify-between gap-3">
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-[.14em] text-red-600">{{ localize('ui.r2d.common.private_investments', 'Private Investments') }}</p>
                        <h2 class="mt-1 text-lg font-semibold">{{ localize('ui.r2d.portfolio.individual_performance', 'Individual performance') }}</h2>
                    </div>
                    <span class="text-xs text-zinc-500">{{ localize('ui.r2d.portfolio.tracked_count', ':count tracked', ['count' => $positions->count()]) }}</span>
                </div>

                @if($positions->isEmpty())
                    <div class="mt-5 rounded-2xl border border-dashed border-zinc-300 p-8 text-center text-sm text-zinc-500 dark:border-zinc-800">
                        {{ localize('ui.r2d.portfolio.no_private_history', 'No Private Investment history yet. Subscribe to an investment and its capital, valuation and lifecycle movements will accumulate here.') }}
                    </div>
                @else
                    <div class="mt-5 grid gap-4">
                        @foreach($positions as $position)
                            @php
                                $holding = $position['holding'];
                                $instrument = $position['instrument'];
                            @endphp

                            <article class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-800">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <a href="{{ route('investments.show', $instrument->slug) }}" class="font-semibold hover:text-red-600">{{ $instrument->name }}</a>
                                            <span class="rounded-full bg-zinc-100 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-zinc-600 dark:bg-zinc-900 dark:text-zinc-300">{{ $position['status'] }}</span>
                                        </div>
                                        <p class="mt-1 text-xs text-zinc-500">
                                            {{ $instrument->symbol }} · {{ number_format((float) $holding->units, 4) }} units · avg {{ currency_symbol() }}{{ number_format((float) $holding->average_entry_price, 2) }}
                                        </p>
                                    </div>
                                    <div class="text-left sm:text-right">
                                        <p class="text-xs text-zinc-500">{{ localize('ui.r2d.common.net_performance', 'Net performance') }}</p>
                                        <p class="mt-1 text-lg font-semibold {{ $position['net_performance'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                            {{ $signedMoney($position['net_performance']) }}
                                        </p>
                                        <p class="text-xs {{ $position['return_percent'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                            {{ $position['return_percent'] >= 0 ? '+' : '' }}{{ number_format($position['return_percent'], 2) }}%
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
                                    <div>
                                        <p class="text-[10px] uppercase tracking-wide text-zinc-400">{{ localize('ui.r2d.common.capital_at_work', 'Capital at work') }}</p>
                                        <p class="mt-1 text-sm font-semibold">{{ currency_symbol() }}{{ number_format($position['capital_at_work'], 2) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] uppercase tracking-wide text-zinc-400">{{ localize('ui.r2d.common.current_value', 'Current value') }}</p>
                                        <p class="mt-1 text-sm font-semibold">{{ currency_symbol() }}{{ number_format($position['current_value'], 2) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] uppercase tracking-wide text-zinc-400">{{ financial_term('unrealized_pnl') }}</p>
                                        <p class="mt-1 text-sm font-semibold {{ $position['unrealized_profit_loss'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $signedMoney($position['unrealized_profit_loss']) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] uppercase tracking-wide text-zinc-400">{{ financial_term('realized_pnl') }}</p>
                                        <p class="mt-1 text-sm font-semibold {{ $position['realized_profit_loss'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $signedMoney($position['realized_profit_loss']) }}</p>
                                    </div>
                                </div>

                                <div class="mt-4 grid grid-cols-2 gap-2 rounded-2xl bg-zinc-50 p-3 text-xs dark:bg-zinc-900 sm:grid-cols-4">
                                    <div><span class="text-zinc-500">{{ localize('ui.r2d.common.subscribed', 'Subscribed') }}</span><p class="mt-1 font-semibold">{{ currency_symbol() }}{{ number_format($position['total_subscribed'], 2) }}</p></div>
                                    <div><span class="text-zinc-500">{{ localize('ui.r2d.common.distributions', 'Distributions') }}</span><p class="mt-1 font-semibold text-emerald-600">+{{ $money($position['distributions']) }}</p></div>
                                    <div><span class="text-zinc-500">{{ localize('ui.r2d.common.deductions', 'Deductions') }}</span><p class="mt-1 font-semibold text-red-600">-{{ $money($position['deductions']) }}</p></div>
                                    <div><span class="text-zinc-500">{{ localize('ui.r2d.common.fees_paid', 'Fees paid') }}</span><p class="mt-1 font-semibold">{{ currency_symbol() }}{{ number_format($position['fees_paid'], 2) }}</p></div>
                                </div>

                                @if($position['recent_activity']->isNotEmpty())
                                    <div class="mt-4 border-t border-zinc-100 pt-3 dark:border-zinc-900">
                                        <p class="text-[10px] font-semibold uppercase tracking-[.12em] text-zinc-400">{{ localize('ui.r2d.common.latest_movements', 'Latest movements') }}</p>
                                        <div class="mt-2 grid gap-2">
                                            @foreach($position['recent_activity']->take(3) as $transaction)
                                                @php
                                                    $positive = in_array($transaction->type, ['distribution', 'redemption'], true);
                                                    $negative = in_array($transaction->type, ['deduction', 'subscription'], true);
                                                    $amount = in_array($transaction->type, ['redemption', 'distribution', 'deduction'], true)
                                                        ? (float) $transaction->net_amount
                                                        : (float) $transaction->gross_amount;
                                                @endphp
                                                <div class="flex items-center justify-between gap-3 text-xs">
                                                    <div>
                                                        <span class="font-medium capitalize">{{ str_replace('_', ' ', $transaction->type) }}</span>
                                                        <span class="ml-1 text-zinc-400">{{ optional($transaction->executed_at)->format('M j, Y H:i') }}</span>
                                                    </div>
                                                    <span class="font-semibold {{ $positive ? 'text-emerald-600' : ($negative ? 'text-red-600' : '') }}">
                                                        {{ $positive ? '+' : ($negative ? '-' : '') }}{{ $money($amount) }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            <aside class="space-y-5">
                <section class="rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                    <p class="text-[10px] font-semibold uppercase tracking-[.14em] text-red-600">{{ localize('ui.r2d.portfolio.why_moved', 'Why did my portfolio move?') }}</p>
                    <h2 class="mt-1 text-lg font-semibold">{{ localize('ui.r2d.common.recent_activity', 'Recent activity') }}</h2>

                    @if($activity->isEmpty())
                        <p class="mt-4 text-sm text-zinc-500">{{ localize('ui.r2d.portfolio.no_investment_activity', 'No investment activity yet.') }}</p>
                    @else
                        <div class="mt-4 grid gap-3">
                            @foreach($activity as $transaction)
                                @php
                                    $positive = in_array($transaction->type, ['distribution', 'redemption'], true);
                                    $negative = in_array($transaction->type, ['deduction', 'subscription'], true);
                                    $amount = in_array($transaction->type, ['redemption', 'distribution', 'deduction'], true)
                                        ? (float) $transaction->net_amount
                                        : (float) $transaction->gross_amount;
                                @endphp
                                <div class="border-b border-zinc-100 pb-3 text-xs last:border-0 last:pb-0 dark:border-zinc-900">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="font-semibold capitalize">{{ str_replace('_', ' ', $transaction->type) }}</p>
                                            <p class="mt-1 text-zinc-500">{{ $transaction->instrument?->name ?? 'Private Investment' }}</p>
                                        </div>
                                        <p class="font-semibold {{ $positive ? 'text-emerald-600' : ($negative ? 'text-red-600' : '') }}">
                                            {{ $positive ? '+' : ($negative ? '-' : '') }}{{ $money($amount) }}
                                        </p>
                                    </div>
                                    <p class="mt-1 text-[10px] text-zinc-400">{{ optional($transaction->executed_at)->format('M j, Y · H:i') }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>

                <section class="rounded-3xl border border-dashed border-zinc-300 p-5 dark:border-zinc-800">
                    <p class="text-[10px] font-semibold uppercase tracking-[.14em] text-zinc-400">{{ localize('ui.r2d.portfolio.sources', 'Portfolio sources') }}</p>
                    <div class="mt-3 space-y-2 text-sm">
                        <div class="flex items-center justify-between"><span>Private Investments</span><span class="font-semibold text-emerald-600">{{ localize('ui.r2d.common.connected', 'Connected') }}</span></div>
                        <div class="flex items-center justify-between"><span>Trading</span><span class="font-semibold text-emerald-600">{{ localize('ui.r2d.common.connected', 'Connected') }}</span></div>
                        <div class="flex items-center justify-between"><span>Copy Trading</span><span class="font-semibold text-emerald-600">{{ localize('ui.r2d.common.connected', 'Connected') }}</span></div>
                        <div class="flex items-center justify-between"><span>Bot Trading</span><span class="font-semibold text-emerald-600">{{ localize('ui.r2d.common.connected', 'Connected') }}</span></div>
                    </div>
                    <p class="mt-4 text-xs leading-5 text-zinc-500">
                        {{ localize('ui.r2d.portfolio.sources_help', 'Track your investments and trading performance in one place.') }}
                    </p>
                </section>
            </aside>
        </div>

        <section class="mt-5 rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-[.14em] text-red-600">{{ localize('ui.r2d.common.trading', 'Trading') }}</p>
                    <h2 class="mt-1 text-lg font-semibold">{{ localize('ui.r2d.portfolio.trading_performance', 'Trading Performance') }}</h2>
                    <p class="mt-1 text-xs text-zinc-500">{{ localize('ui.r2d.portfolio.direct_positions_help', 'Only positions opened directly by the customer are included here.') }}</p>
                </div>
                <div class="text-xs text-zinc-500">{{ $manualTrading['summary']['positions'] }} position{{ $manualTrading['summary']['positions'] === 1 ? '' : 's' }}</div>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-900">
                    <p class="text-xs text-zinc-500">{{ localize('ui.r2d.common.capital_traded', 'Capital traded') }}</p>
                    <p class="mt-1 font-semibold">{{ currency_symbol() }}{{ number_format($manualTrading['summary']['capital_traded'], 2) }}</p>
                </div>
                <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-900">
                    <p class="text-xs text-zinc-500">{{ financial_term('realized_pnl') }}</p>
                    <p class="mt-1 font-semibold {{ $manualTrading['summary']['realized_profit_loss'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $signedMoney($manualTrading['summary']['realized_profit_loss']) }}</p>
                </div>
                <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-900">
                    <p class="text-xs text-zinc-500">{{ financial_term('unrealized_pnl') }}</p>
                    <p class="mt-1 font-semibold {{ $manualTrading['summary']['unrealized_profit_loss'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $signedMoney($manualTrading['summary']['unrealized_profit_loss']) }}</p>
                </div>
                <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-900">
                    <p class="text-xs text-zinc-500">{{ localize('ui.r2d.common.net_pnl', 'Net P/L') }}</p>
                    <p class="mt-1 font-semibold {{ $manualTrading['summary']['net_profit_loss'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $signedMoney($manualTrading['summary']['net_profit_loss']) }}</p>
                    <p class="mt-1 text-[10px] {{ $manualTrading['summary']['return_percent'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $manualTrading['summary']['return_percent'] >= 0 ? '+' : '' }}{{ number_format($manualTrading['summary']['return_percent'], 2) }}%</p>
                </div>
            </div>

            <div class="mt-3 flex flex-wrap gap-2 text-xs">
                <span class="rounded-full border border-zinc-200 px-3 py-1.5 dark:border-zinc-800">Open {{ $manualTrading['summary']['open_positions'] }}</span>
                <span class="rounded-full border border-zinc-200 px-3 py-1.5 dark:border-zinc-800">Closed {{ $manualTrading['summary']['closed_positions'] }}</span>
                <span class="rounded-full border border-zinc-200 px-3 py-1.5 dark:border-zinc-800">Wins {{ $manualTrading['summary']['wins'] }}</span>
                <span class="rounded-full border border-zinc-200 px-3 py-1.5 dark:border-zinc-800">Losses {{ $manualTrading['summary']['losses'] }}</span>
                <span class="rounded-full border border-zinc-200 px-3 py-1.5 dark:border-zinc-800">Open capital {{ currency_symbol() }}{{ number_format($manualTrading['summary']['open_capital'], 2) }}</span>
            </div>

            @if($manualTrading['positions']->isEmpty())
                <div class="mt-5 rounded-2xl border border-dashed border-zinc-300 p-6 text-center text-sm text-zinc-500 dark:border-zinc-800">
                    {{ localize('ui.r2d.portfolio.no_trading_positions', 'No Trading positions yet.') }}
                </div>
            @else
                <div class="mt-5 grid gap-3">
                    @foreach($manualTrading['positions']->take(8) as $item)
                        @php
                            $trade = $item['position'];
                            $stock = $item['stock'];
                        @endphp
                        <article class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-800">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-semibold">{{ $stock?->symbol ?? 'Unknown' }}</p>
                                        <span class="rounded-full bg-zinc-100 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide dark:bg-zinc-900">{{ $trade->direction }}</span>
                                        <span class="rounded-full bg-zinc-100 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide dark:bg-zinc-900">{{ $trade->marketplace }}</span>
                                        <span class="rounded-full bg-zinc-100 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide dark:bg-zinc-900">{{ $trade->status }}</span>
                                    </div>
                                    <p class="mt-1 text-xs text-zinc-500">
                                        {{ number_format((float) $trade->initial_quantity, 4) }} initial · {{ number_format((float) $trade->open_quantity, 4) }} open · entry {{ currency_symbol() }}{{ number_format((float) $trade->entry_price, 2) }}
                                    </p>
                                </div>
                                <div class="text-left sm:text-right">
                                    <p class="text-xs text-zinc-500">{{ localize('ui.r2d.common.net_pnl', 'Net P/L') }}</p>
                                    <p class="mt-1 text-lg font-semibold {{ $item['net_profit_loss'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $signedMoney($item['net_profit_loss']) }}</p>
                                    <p class="text-xs {{ $item['return_percent'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $item['return_percent'] >= 0 ? '+' : '' }}{{ number_format($item['return_percent'], 2) }}%</p>
                                </div>
                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
                                <div><p class="text-[10px] uppercase tracking-wide text-zinc-400">Initial capital</p><p class="mt-1 text-sm font-semibold">{{ currency_symbol() }}{{ number_format($item['initial_capital'], 2) }}</p></div>
                                <div><p class="text-[10px] uppercase tracking-wide text-zinc-400">Open capital</p><p class="mt-1 text-sm font-semibold">{{ currency_symbol() }}{{ number_format($item['open_capital'], 2) }}</p></div>
                                <div><p class="text-[10px] uppercase tracking-wide text-zinc-400">{{ financial_term('realized_pnl') }}</p><p class="mt-1 text-sm font-semibold {{ $item['realized_profit_loss'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $signedMoney($item['realized_profit_loss']) }}</p></div>
                                <div><p class="text-[10px] uppercase tracking-wide text-zinc-400">{{ financial_term('unrealized_pnl') }}</p><p class="mt-1 text-sm font-semibold {{ $item['unrealized_profit_loss'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $signedMoney($item['unrealized_profit_loss']) }}</p></div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif

            @if($manualTrading['activity']->isNotEmpty())
                <div class="mt-5 border-t border-zinc-100 pt-4 dark:border-zinc-900">
                    <p class="text-[10px] font-semibold uppercase tracking-[.12em] text-zinc-400">{{ localize('ui.r2d.portfolio.recent_trading_activity', 'Recent Trading Activity') }}</p>
                    <div class="mt-3 grid gap-2">
                        @foreach($manualTrading['activity']->take(6) as $event)
                            <div class="flex items-center justify-between gap-3 text-xs">
                                <div>
                                    <span class="font-medium">{{ $event->position?->stock?->symbol ?? 'Trade' }}</span>
                                    <span class="ml-1 capitalize text-zinc-500">{{ str_replace('_', ' ', $event->event_type) }}</span>
                                </div>
                                <div class="text-right">
                                    @if((float) $event->profit_loss !== 0.0)
                                        <span class="font-semibold {{ (float) $event->profit_loss >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $signedMoney((float) $event->profit_loss) }}</span>
                                    @endif
                                    <span class="ml-2 text-zinc-400">{{ optional($event->created_at)->format('M j, H:i') }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>

        <section class="mt-5 rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-[.14em] text-red-600">{{ localize('ui.r2d.common.copy_trading', 'Copy Trading') }}</p>
                    <h2 class="mt-1 text-lg font-semibold">{{ localize('ui.r2d.portfolio.copied_performance', 'Copied strategy performance') }}</h2>
                    <p class="mt-1 text-xs text-zinc-500">{{ localize('ui.r2d.portfolio.copy_positions_help', 'Only positions marked by the Copy Trading engine are included here.') }}</p>
                </div>
                <div class="text-xs text-zinc-500">{{ $copyTrading['summary']['strategies'] }} strateg{{ $copyTrading['summary']['strategies'] === 1 ? 'y' : 'ies' }}</div>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-900">
                    <p class="text-xs text-zinc-500">{{ localize('ui.r2d.portfolio.allocation_limit', 'Allocation limit') }}</p>
                    <p class="mt-1 font-semibold">{{ currency_symbol() }}{{ number_format($copyTrading['summary']['allocation_limit'], 2) }}</p>
                </div>
                <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-900">
                    <p class="text-xs text-zinc-500">{{ localize('ui.r2d.portfolio.used_allocation', 'Used allocation') }}</p>
                    <p class="mt-1 font-semibold">{{ currency_symbol() }}{{ number_format($copyTrading['summary']['used_allocation'], 2) }}</p>
                    <p class="mt-1 text-[10px] text-zinc-400">Remaining {{ currency_symbol() }}{{ number_format($copyTrading['summary']['remaining_allocation'], 2) }}</p>
                </div>
                <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-900">
                    <p class="text-xs text-zinc-500">{{ financial_term('realized_pnl') }}</p>
                    <p class="mt-1 font-semibold {{ $copyTrading['summary']['realized_profit_loss'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $signedMoney($copyTrading['summary']['realized_profit_loss']) }}</p>
                </div>
                <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-900">
                    <p class="text-xs text-zinc-500">{{ localize('ui.r2d.common.net_pnl', 'Net P/L') }}</p>
                    <p class="mt-1 font-semibold {{ $copyTrading['summary']['net_profit_loss'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $signedMoney($copyTrading['summary']['net_profit_loss']) }}</p>
                    <p class="mt-1 text-[10px] {{ $copyTrading['summary']['return_percent'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $copyTrading['summary']['return_percent'] >= 0 ? '+' : '' }}{{ number_format($copyTrading['summary']['return_percent'], 2) }}%</p>
                </div>
            </div>

            <div class="mt-3 flex flex-wrap gap-2 text-xs">
                <span class="rounded-full border border-zinc-200 px-3 py-1.5 dark:border-zinc-800">Positions {{ $copyTrading['summary']['positions'] }}</span>
                <span class="rounded-full border border-zinc-200 px-3 py-1.5 dark:border-zinc-800">Open {{ $copyTrading['summary']['open_positions'] }}</span>
                <span class="rounded-full border border-zinc-200 px-3 py-1.5 dark:border-zinc-800">Closed {{ $copyTrading['summary']['closed_positions'] }}</span>
                <span class="rounded-full border border-zinc-200 px-3 py-1.5 dark:border-zinc-800">Wins {{ $copyTrading['summary']['wins'] }}</span>
                <span class="rounded-full border border-zinc-200 px-3 py-1.5 dark:border-zinc-800">Losses {{ $copyTrading['summary']['losses'] }}</span>
            </div>

            @if($copyTrading['positions']->isEmpty())
                <div class="mt-5 rounded-2xl border border-dashed border-zinc-300 p-6 text-center text-sm text-zinc-500 dark:border-zinc-800">
                    {{ localize('ui.r2d.portfolio.no_copy_positions', 'No Copy Trading positions yet.') }}
                </div>
            @else
                <div class="mt-5 grid gap-3">
                    @foreach($copyTrading['positions']->take(8) as $item)
                        @php
                            $trade = $item['position'];
                            $stock = $item['stock'];
                            $strategy = $item['strategy'];
                            $relationship = $item['relationship'];
                        @endphp

                        <article class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-800">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-semibold">{{ $strategy?->name ?? 'Copy Strategy' }}</p>
                                        <span class="rounded-full bg-zinc-100 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide dark:bg-zinc-900">{{ $stock?->symbol ?? 'Unknown' }}</span>
                                        <span class="rounded-full bg-zinc-100 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide dark:bg-zinc-900">{{ $trade->status }}</span>
                                    </div>
                                    <p class="mt-1 text-xs text-zinc-500">
                                        Provider {{ $strategy?->provider?->name ?? $relationship?->provider?->name ?? 'Unavailable' }}
                                        · {{ $trade->direction }} · {{ $trade->marketplace }}
                                    </p>
                                    <p class="mt-1 text-xs text-zinc-500">
                                        {{ number_format((float) $trade->initial_quantity, 4) }} initial · {{ number_format((float) $trade->open_quantity, 4) }} open · entry {{ currency_symbol() }}{{ number_format((float) $trade->entry_price, 2) }}
                                    </p>
                                </div>
                                <div class="text-left sm:text-right">
                                    <p class="text-xs text-zinc-500">{{ localize('ui.r2d.common.net_pnl', 'Net P/L') }}</p>
                                    <p class="mt-1 text-lg font-semibold {{ $item['net_profit_loss'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $signedMoney($item['net_profit_loss']) }}</p>
                                    <p class="text-xs {{ $item['return_percent'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $item['return_percent'] >= 0 ? '+' : '' }}{{ number_format($item['return_percent'], 2) }}%</p>
                                </div>
                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
                                <div><p class="text-[10px] uppercase tracking-wide text-zinc-400">{{ localize('ui.r2d.common.capital_traded', 'Capital traded') }}</p><p class="mt-1 text-sm font-semibold">{{ currency_symbol() }}{{ number_format($item['initial_capital'], 2) }}</p></div>
                                <div><p class="text-[10px] uppercase tracking-wide text-zinc-400">{{ financial_term('realized_pnl') }}</p><p class="mt-1 text-sm font-semibold {{ $item['realized_profit_loss'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $signedMoney($item['realized_profit_loss']) }}</p></div>
                                <div><p class="text-[10px] uppercase tracking-wide text-zinc-400">{{ financial_term('unrealized_pnl') }}</p><p class="mt-1 text-sm font-semibold {{ $item['unrealized_profit_loss'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $signedMoney($item['unrealized_profit_loss']) }}</p></div>
                                <div><p class="text-[10px] uppercase tracking-wide text-zinc-400">Copy contract</p><p class="mt-1 text-sm font-semibold capitalize">{{ $relationship?->contract_state ?? 'historical' }}</p></div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="mt-5 rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-[.14em] text-red-600">{{ localize('ui.r2d.common.bot_trading', 'Bot Trading') }}</p>
                    <h2 class="mt-1 text-lg font-semibold">{{ localize('ui.r2d.portfolio.automated_performance', 'Automated strategy performance') }}</h2>
                    <p class="mt-1 text-xs text-zinc-500">{{ localize('ui.r2d.portfolio.bot_positions_help', 'Bot-owned positions and executions remain isolated from Trading and Copy Trading.') }}</p>
                </div>
                <div class="text-xs text-zinc-500">{{ $botTrading['summary']['bots'] }} bot{{ $botTrading['summary']['bots'] === 1 ? '' : 's' }}</div>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-900">
                    <p class="text-xs text-zinc-500">{{ localize('ui.r2d.common.capital_traded', 'Capital traded') }}</p>
                    <p class="mt-1 font-semibold">{{ currency_symbol() }}{{ number_format($botTrading['summary']['capital_traded'], 2) }}</p>
                </div>
                <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-900">
                    <p class="text-xs text-zinc-500">{{ financial_term('realized_pnl') }}</p>
                    <p class="mt-1 font-semibold {{ $botTrading['summary']['realized_profit_loss'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $signedMoney($botTrading['summary']['realized_profit_loss']) }}</p>
                </div>
                <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-900">
                    <p class="text-xs text-zinc-500">{{ financial_term('unrealized_pnl') }}</p>
                    <p class="mt-1 font-semibold {{ $botTrading['summary']['unrealized_profit_loss'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $signedMoney($botTrading['summary']['unrealized_profit_loss']) }}</p>
                </div>
                <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-900">
                    <p class="text-xs text-zinc-500">{{ localize('ui.r2d.common.net_pnl', 'Net P/L') }}</p>
                    <p class="mt-1 font-semibold {{ $botTrading['summary']['net_profit_loss'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $signedMoney($botTrading['summary']['net_profit_loss']) }}</p>
                    <p class="mt-1 text-[10px] {{ $botTrading['summary']['return_percent'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $botTrading['summary']['return_percent'] >= 0 ? '+' : '' }}{{ number_format($botTrading['summary']['return_percent'], 2) }}%</p>
                </div>
            </div>

            <div class="mt-3 flex flex-wrap gap-2 text-xs">
                <span class="rounded-full border border-zinc-200 px-3 py-1.5 dark:border-zinc-800">{{ localize('ui.r2d.portfolio.active_bots', 'Active bots') }} {{ $botTrading['summary']['active_bots'] }}</span>
                <span class="rounded-full border border-zinc-200 px-3 py-1.5 dark:border-zinc-800">Positions {{ $botTrading['summary']['positions'] }}</span>
                <span class="rounded-full border border-zinc-200 px-3 py-1.5 dark:border-zinc-800">Open {{ $botTrading['summary']['open_positions'] }}</span>
                <span class="rounded-full border border-zinc-200 px-3 py-1.5 dark:border-zinc-800">Closed {{ $botTrading['summary']['closed_positions'] }}</span>
                <span class="rounded-full border border-zinc-200 px-3 py-1.5 dark:border-zinc-800">Executions {{ $botTrading['summary']['executions'] }}</span>
                <span class="rounded-full border border-zinc-200 px-3 py-1.5 dark:border-zinc-800">Wins {{ $botTrading['summary']['wins'] }}</span>
                <span class="rounded-full border border-zinc-200 px-3 py-1.5 dark:border-zinc-800">Losses {{ $botTrading['summary']['losses'] }}</span>
            </div>

            @if($botTrading['bots']->isEmpty())
                <div class="mt-5 rounded-2xl border border-dashed border-zinc-300 p-6 text-center text-sm text-zinc-500 dark:border-zinc-800">
                    {{ localize('ui.r2d.portfolio.no_bot_configs', 'No Bot Trading configurations yet.') }}
                </div>
            @else
                <div class="mt-5 grid gap-3">
                    @foreach($botTrading['bots'] as $item)
                        @php
                            $bot = $item['bot'];
                            $subscription = $item['subscription'];
                        @endphp

                        <article class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-800">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-semibold">{{ $bot->name }}</p>
                                        <span class="rounded-full bg-zinc-100 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide dark:bg-zinc-900">{{ $bot->stock?->symbol ?? 'Multi' }}</span>
                                        <span class="rounded-full bg-zinc-100 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide dark:bg-zinc-900">{{ $bot->status }}</span>
                                    </div>
                                    <p class="mt-1 text-xs text-zinc-500">
                                        {{ $bot->strategy }} · {{ $bot->action }}
                                        @if($subscription)
                                            · subscription {{ $subscription->status }}
                                        @endif
                                    </p>
                                    <p class="mt-1 text-xs text-zinc-500">
                                        Spend {{ currency_symbol() }}{{ number_format((float) $bot->spent_total, 2) }}
                                        @if((float) $bot->max_total_spend > 0)
                                            / {{ currency_symbol() }}{{ number_format((float) $bot->max_total_spend, 2) }}
                                        @endif
                                    </p>
                                </div>
                                <div class="text-left sm:text-right">
                                    <p class="text-xs text-zinc-500">{{ localize('ui.r2d.common.net_pnl', 'Net P/L') }}</p>
                                    <p class="mt-1 text-lg font-semibold {{ $item['net_profit_loss'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $signedMoney($item['net_profit_loss']) }}</p>
                                    <p class="text-xs {{ $item['return_percent'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $item['return_percent'] >= 0 ? '+' : '' }}{{ number_format($item['return_percent'], 2) }}%</p>
                                </div>
                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
                                <div><p class="text-[10px] uppercase tracking-wide text-zinc-400">{{ localize('ui.r2d.common.capital_traded', 'Capital traded') }}</p><p class="mt-1 text-sm font-semibold">{{ currency_symbol() }}{{ number_format($item['capital_traded'], 2) }}</p></div>
                                <div><p class="text-[10px] uppercase tracking-wide text-zinc-400">Open capital</p><p class="mt-1 text-sm font-semibold">{{ currency_symbol() }}{{ number_format($item['open_capital'], 2) }}</p></div>
                                <div><p class="text-[10px] uppercase tracking-wide text-zinc-400">Positions</p><p class="mt-1 text-sm font-semibold">{{ $item['positions']->count() }}</p></div>
                                <div><p class="text-[10px] uppercase tracking-wide text-zinc-400">Executions</p><p class="mt-1 text-sm font-semibold">{{ $item['executions']->count() }}</p></div>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-2 text-[10px] text-zinc-500">
                                <span>Realized {{ $signedMoney($item['realized_profit_loss']) }}</span>
                                <span>·</span>
                                <span>Unrealized {{ $signedMoney($item['unrealized_profit_loss']) }}</span>
                                <span>·</span>
                                <span>Wins {{ $item['wins'] }}</span>
                                <span>·</span>
                                <span>Losses {{ $item['losses'] }}</span>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif

            @if($botTrading['activity']->isNotEmpty())
                <div class="mt-5 border-t border-zinc-100 pt-4 dark:border-zinc-900">
                    <p class="text-[10px] font-semibold uppercase tracking-[.12em] text-zinc-400">{{ localize('ui.r2d.portfolio.recent_bot_activity', 'Recent Bot Activity') }}</p>
                    <div class="mt-3 grid gap-2">
                        @foreach($botTrading['activity']->take(6) as $execution)
                            <div class="flex items-center justify-between gap-3 text-xs">
                                <div>
                                    <span class="font-medium">{{ $execution->bot?->name ?? 'Bot' }}</span>
                                    <span class="ml-1 capitalize text-zinc-500">{{ $execution->action }}</span>
                                    @if($execution->bot?->stock)
                                        <span class="ml-1 text-zinc-400">{{ $execution->bot->stock->symbol }}</span>
                                    @endif
                                </div>
                                <div class="text-right">
                                    <span class="font-semibold">{{ currency_symbol() }}{{ number_format((float) $execution->amount, 2) }}</span>
                                    <span class="ml-2 text-zinc-400">{{ optional($execution->executed_at)->format('M j, H:i') }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>
    @endif
</div>
</x-user-layout>
