<x-user-layout>
<x-slot name="header">Bot Subscriptions</x-slot>
<div class="ui-page max-w-[1440px]">
<section class="ui-page-header">
    <div>
        <p class="ui-kicker text-[10px]">AI Trading Bots</p>
        <h1 class="ui-heading !text-xl">Subscriptions</h1>
        <p class="ui-lead !text-[13px]">Subscription term, runtime status and expiry history.</p>
    </div>
</section>

<div class="grid gap-3 lg:grid-cols-2">
@forelse($subscriptions as $s)
    @php
        $expired = $s->is_expired;
        $statusClass = $expired
            ? 'border-red-500/20 bg-red-500/10 text-red-600'
            : ($s->status === 'active'
                ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-600'
                : 'border-amber-500/20 bg-amber-500/10 text-amber-600');
    @endphp
    <article class="ui-panel p-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full border border-sky-500/20 bg-sky-500/10 px-2 py-1 text-[10px] font-semibold text-sky-600">{{ $s->product?->stock?->symbol }}</span>
                    <span class="rounded-full border px-2 py-1 text-[10px] font-semibold {{ $statusClass }}">{{ ucfirst($expired ? 'expired' : $s->status) }}</span>
                </div>
                <h2 class="mt-2 text-sm font-semibold">{{ $s->product?->name }}</h2>
                <p class="mt-1 text-[11px] text-muted-foreground">{{ ucfirst($s->product?->billing_period ?? 'monthly') }} subscription</p>
            </div>
            <div class="text-right">
                <p class="text-[9px] uppercase tracking-[.12em] text-muted-foreground">Paid</p>
                <p class="mt-1 text-sm font-semibold">{{ format_currency($s->price_paid) }}</p>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-3 gap-2">
            <div class="rounded-lg border border-border bg-muted/10 p-2.5">
                <p class="text-[9px] uppercase tracking-[.12em] text-muted-foreground">Started</p>
                <p class="mt-1 text-[11px] font-semibold">{{ optional($s->starts_at)->format('M d, Y') }}</p>
            </div>
            <div class="rounded-lg border border-border bg-muted/10 p-2.5">
                <p class="text-[9px] uppercase tracking-[.12em] text-muted-foreground">Ends</p>
                <p class="mt-1 text-[11px] font-semibold">{{ optional($s->ends_at)->format('M d, Y') ?: '—' }}</p>
            </div>
            <div class="rounded-lg border border-border bg-muted/10 p-2.5">
                <p class="text-[9px] uppercase tracking-[.12em] text-muted-foreground">Remaining</p>
                <p class="mt-1 text-[11px] font-semibold">
                    @if($expired)
                        Expired
                    @elseif($s->days_remaining !== null)
                        {{ $s->days_remaining }} day{{ $s->days_remaining === 1 ? '' : 's' }}
                    @else
                        —
                    @endif
                </p>
            </div>
        </div>

        <div class="mt-3 flex items-center justify-between border-t border-border pt-3">
            <p class="text-[10px] text-muted-foreground">
                @if($expired)
                    Runtime paused automatically when the subscription term ended.
                @elseif($s->status === 'cancelled')
                    Cancelled {{ optional($s->cancelled_at)->diffForHumans() }}.
                @else
                    Runtime access remains usable until the end date.
                @endif
            </p>

            @if(!in_array($s->status,['cancelled','expired']))
                <form method="POST" action="{{ route('ai-bots.cancel',$s) }}">
                    @csrf @method('DELETE')
                    <button class="ui-btn ui-btn-secondary !h-8 !px-3 !text-[11px]">Cancel</button>
                </form>
            @endif
        </div>
    </article>
@empty
    <div class="ui-panel p-8 text-center text-sm text-muted-foreground lg:col-span-2">No bot subscriptions yet.</div>
@endforelse
</div>

<div class="mt-4">{{ $subscriptions->links() }}</div>
</div>
</x-user-layout>
