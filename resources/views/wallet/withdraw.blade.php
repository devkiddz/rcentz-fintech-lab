<x-user-layout>
    <x-slot name="header">Withdraw</x-slot>

    @php
        $helpTitle = \App\Models\Setting::get('withdrawal_help_title', 'Withdrawal process');
        $helpSteps = collect(range(1, 4))->map(function ($number) {
            $defaults = [
                1 => 'Submit request|Enter the amount you want to withdraw.',
                2 => 'Security review|Your request is reviewed before payout details are requested.',
                3 => 'Verify withdrawal|Use the verification code sent to your account.',
                4 => 'Payout processing|Track the request until it is approved or declined.',
            ];
            $raw = \App\Models\Setting::get("withdrawal_step_{$number}", $defaults[$number]);
            [$title, $description] = array_pad(explode('|', $raw, 2), 2, '');
            return ['number' => $number, 'title' => trim($title), 'description' => trim($description)];
        });

        $statusLabel = function ($request) {
            if (!$request) return null;
            return match ($request->status) {
                'pending' => 'Awaiting security review',
                'token_issued' => 'Verification code ready',
                'used' => optional($request->walletTransaction)->status === 'completed'
                    ? 'Completed'
                    : (optional($request->walletTransaction)->status === 'rejected' ? 'Declined' : 'Pending payout'),
                'expired' => 'Verification expired',
                'cancelled' => 'Cancelled',
                default => ucwords(str_replace('_', ' ', $request->status)),
            };
        };
    @endphp

    <div class="ui-page max-w-6xl">
        <section class="ui-page-header">
            <div>
                <p class="ui-kicker">Money · Withdrawal</p>
                <h1 class="ui-heading">Withdraw funds</h1>
                <p class="ui-lead max-w-2xl">Submit a withdrawal request, complete security verification, then track payout approval from one place.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('money.index') }}" class="ui-btn ui-btn-secondary"><i data-lucide="wallet" class="h-4 w-4"></i>Money</a>
                <a href="{{ route('money.activity') }}" class="ui-btn ui-btn-secondary"><i data-lucide="history" class="h-4 w-4"></i>Activity</a>
            </div>
        </section>

        <section class="grid gap-3 sm:grid-cols-3">
            <article class="ui-metric-card">
                <div class="ui-metric-icon"><i data-lucide="wallet" class="h-5 w-5"></i></div>
                <div><p class="ui-label">Balance</p><p class="mt-1 text-2xl font-semibold">{{ format_currency($wallet->balance) }}</p></div>
            </article>
            <article class="ui-metric-card">
                <div class="ui-metric-icon"><i data-lucide="circle-dollar-sign" class="h-5 w-5"></i></div>
                <div><p class="ui-label">Available</p><p class="mt-1 text-2xl font-semibold">{{ format_currency($wallet->available_balance) }}</p></div>
            </article>
            <article class="ui-metric-card">
                <div class="ui-metric-icon"><i data-lucide="lock-keyhole" class="h-5 w-5"></i></div>
                <div><p class="ui-label">Reserved</p><p class="mt-1 text-2xl font-semibold">{{ format_currency($wallet->reserved_balance) }}</p></div>
            </article>
        </section>

        <section class="mt-4 grid gap-4 lg:grid-cols-[minmax(0,1.15fr)_minmax(320px,.85fr)]">
            <article class="ui-surface overflow-hidden">
                <div class="border-b border-border px-5 py-4">
                    <p class="ui-kicker">{{ $activeTokenRequest ? 'Current withdrawal' : 'New withdrawal' }}</p>
                    <h2 class="mt-1 text-lg font-semibold">
                        {{ $activeTokenRequest ? $statusLabel($activeTokenRequest) : 'Request a withdrawal' }}
                    </h2>
                </div>

                <div class="p-5">
                    @if($activeTokenRequest)
                        <div class="rounded-2xl border border-border bg-muted/20 p-5">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="ui-label">Requested amount</p>
                                    <p class="mt-1 text-2xl font-semibold">{{ format_currency($activeTokenRequest->amount) }}</p>
                                    <p class="mt-2 text-xs text-muted-foreground">Requested {{ $activeTokenRequest->created_at->format('M j, Y · h:i A') }}</p>
                                </div>
                                <span class="w-fit rounded-full border border-border bg-background px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[.1em]">
                                    {{ $statusLabel($activeTokenRequest) }}
                                </span>
                            </div>

                            @if($activeTokenRequest->status === 'pending')
                                <div class="mt-5 rounded-xl border border-amber-500/20 bg-amber-500/10 p-4">
                                    <p class="text-sm font-semibold text-amber-700 dark:text-amber-300">Security review in progress</p>
                                    <p class="mt-1 text-xs text-muted-foreground">Your request has been submitted. You will see a private account alert when a verification code is issued.</p>
                                </div>
                            @elseif($activeTokenRequest->status === 'token_issued')
                                <div class="mt-5 rounded-xl border border-emerald-500/20 bg-emerald-500/10 p-4">
                                    <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">Verification code issued</p>
                                    <p class="mt-1 text-xs text-muted-foreground">Complete payout details before the code expires.</p>
                                    <a href="{{ route('money.withdraw.verify', $activeTokenRequest) }}" class="ui-btn ui-btn-primary mt-4">
                                        Continue withdrawal
                                    </a>
                                </div>
                            @endif
                        </div>
                    @else
                        <form method="POST" action="{{ route('money.withdraw.request') }}" class="space-y-5">
                            @csrf
                            <div>
                                <label class="ui-label">Amount</label>
                                <div class="relative mt-1">
                                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground">{{ currency_symbol() }}</span>
                                    <input class="ui-input w-full pl-8 text-lg font-semibold" name="amount" type="number" step="0.01" min="1" max="{{ $wallet->available_balance }}" value="{{ old('amount') }}" required>
                                </div>
                                <p class="mt-1 text-[10px] text-muted-foreground">Available to withdraw: {{ format_currency($wallet->available_balance) }}</p>
                            </div>
                            <div>
                                <label class="ui-label">Note <span class="font-normal text-muted-foreground">(optional)</span></label>
                                <textarea class="ui-input mt-1 w-full" name="note" rows="3" maxlength="1000" placeholder="Add context for this withdrawal request...">{{ old('note') }}</textarea>
                            </div>
                            <button class="ui-btn ui-btn-primary w-full justify-center">
                                <i data-lucide="shield-check" class="h-4 w-4"></i>
                                Submit withdrawal request
                            </button>
                        </form>
                    @endif
                </div>
            </article>

            <aside class="ui-panel p-5">
                <p class="ui-kicker">How it works</p>
                <h2 class="mt-1 text-lg font-semibold">{{ $helpTitle }}</h2>
                <div class="mt-5 space-y-4">
                    @foreach($helpSteps as $step)
                        <div class="flex gap-3">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-border bg-muted text-xs font-semibold">{{ $step['number'] }}</div>
                            <div>
                                <p class="text-sm font-semibold">{{ $step['title'] }}</p>
                                <p class="mt-1 text-xs leading-5 text-muted-foreground">{{ $step['description'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </aside>
        </section>

        <section class="ui-surface mt-4 overflow-hidden">
            <div class="border-b border-border px-5 py-4">
                <p class="ui-kicker">History</p>
                <h2 class="mt-1 text-base font-semibold">Withdrawal requests</h2>
                <p class="mt-1 text-xs text-muted-foreground">Track security review, verification and payout status.</p>
            </div>
            @forelse($withdrawalRequests as $requestItem)
                <div class="flex flex-col gap-3 border-b border-border px-5 py-4 last:border-b-0 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-sm font-semibold">{{ format_currency($requestItem->amount) }}</p>
                            <span class="rounded-full bg-muted px-2 py-1 text-[9px] font-semibold uppercase tracking-[.08em]">{{ $statusLabel($requestItem) }}</span>
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ $requestItem->created_at->format('M j, Y · h:i A') }}
                            @if($requestItem->walletTransaction?->reference_id)
                                · {{ $requestItem->walletTransaction->reference_id }}
                            @endif
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if($requestItem->status === 'token_issued' && $requestItem->token_expires_at && now()->lte($requestItem->token_expires_at))
                            <a href="{{ route('money.withdraw.verify', $requestItem) }}" class="ui-btn ui-btn-primary ui-btn-sm">Verify</a>
                        @endif
                        @if($requestItem->walletTransaction)
                            <a href="{{ route('money.activity', ['type' => 'withdrawal']) }}" class="ui-btn ui-btn-secondary ui-btn-sm">View activity</a>
                        @endif
                    </div>
                </div>
            @empty
                <div class="px-5 py-10 text-center text-sm text-muted-foreground">No withdrawal requests yet.</div>
            @endforelse
        </section>
    </div>
</x-user-layout>
