<x-user-layout>
    <x-slot name="header">Rewards</x-slot>

    <div class="ui-page max-w-[1200px] space-y-5">
        <section class="ui-page-header">
            <div>
                <p class="ui-kicker">Rewards & Bonuses</p>
                <h1 class="ui-heading">Your reward history</h1>
                <p class="ui-lead">Cash rewards settle directly into your wallet. Non-cash rewards remain recorded as fulfilled benefits.</p>
            </div>
        </section>

        <section class="ui-metric-grid md:grid-cols-3">
            <div class="ui-metric-card"><p class="ui-kicker">Cash rewards</p><p class="mt-2 text-2xl font-semibold">${{ number_format($summary['cash_total'], 2) }}</p></div>
            <div class="ui-metric-card"><p class="ui-kicker">Cash grants</p><p class="mt-2 text-2xl font-semibold">{{ number_format($summary['cash_grants']) }}</p></div>
            <div class="ui-metric-card"><p class="ui-kicker">Non-cash grants</p><p class="mt-2 text-2xl font-semibold">{{ number_format($summary['non_cash_grants']) }}</p></div>
        </section>

        @if($campaigns->isNotEmpty())
            <section class="ui-panel p-5 sm:p-6">
                <p class="ui-kicker">Available campaigns</p>
                <h2 class="mt-1 text-lg font-semibold">Current reward programs</h2>
                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    @foreach($campaigns as $campaign)
                        <div class="rounded-xl border border-border bg-muted/20 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div><p class="text-sm font-semibold">{{ $campaign->name }}</p><p class="mt-1 text-xs text-muted-foreground">{{ $campaign->description }}</p></div>
                                <span class="rounded-full border border-border px-2 py-1 text-[9px] font-semibold uppercase">{{ $campaign->campaign_type }}</span>
                            </div>
                            <p class="mt-3 text-xs font-semibold text-foreground">
                                @if($campaign->reward_kind === 'cash')
                                    {{ strtoupper($campaign->currency) }} {{ number_format((float)$campaign->cash_amount, 2) }}
                                @else
                                    {{ $campaign->non_cash_label }}
                                @endif
                            </p>
                            @if($campaign->eligibility_key)<p class="mt-1 text-[10px] text-muted-foreground">Eligibility is verified by the platform before fulfillment.</p>@endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="ui-panel overflow-hidden">
            <div class="border-b border-border px-5 py-4"><p class="ui-kicker">Grant ledger</p><h2 class="mt-1 text-lg font-semibold">Fulfilled rewards</h2></div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[850px] text-left">
                    <thead class="border-b border-border bg-muted/20"><tr class="text-[9px] uppercase tracking-[.1em] text-muted-foreground"><th class="px-5 py-3">Campaign</th><th>Type</th><th>Reward</th><th>Source</th><th>Status</th><th class="px-5 py-3">Date</th></tr></thead>
                    <tbody class="divide-y divide-border">
                        @forelse($grants as $grant)
                            <tr>
                                <td class="px-5 py-3"><p class="text-xs font-semibold">{{ $grant->campaign?->name ?? 'Reward' }}</p><p class="mt-1 font-mono text-[9px] text-muted-foreground">{{ $grant->reference }}</p></td>
                                <td class="text-xs">{{ ucfirst(str_replace('_',' ', $grant->campaign?->campaign_type ?? 'reward')) }}</td>
                                <td class="text-xs font-semibold">{{ $grant->reward_kind === 'cash' ? strtoupper($grant->currency).' '.number_format((float)$grant->amount,2) : data_get($grant->non_cash_payload,'label','Non-cash reward') }}</td>
                                <td class="text-[10px] text-muted-foreground">{{ ucfirst(str_replace('_',' ', $grant->source_type)) }}</td>
                                <td><span class="rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2 py-1 text-[9px] font-semibold text-emerald-600">{{ ucfirst($grant->status) }}</span></td>
                                <td class="px-5 py-3 text-[10px] text-muted-foreground">{{ $grant->granted_at?->format('M j, Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-10 text-center text-xs text-muted-foreground">No rewards have been granted to this account yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $grants->links() }}</div>
        </section>
    </div>
</x-user-layout>
