<x-user-layout>
    <x-slot name="header">Transfer</x-slot>

    <div class="app-page max-w-7xl mx-auto space-y-5 sm:space-y-6">
        <section class="wallet-shell-card">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-medium text-muted-foreground">Available balance</p>
                    <div class="mt-1 flex items-end gap-2">
                        <h1 class="text-3xl font-semibold tracking-tight text-foreground">{{ format_currency($wallet->balance) }}</h1>
                        <span class="pb-1 text-xs font-medium uppercase tracking-wide text-muted-foreground">{{ $wallet->currency }}</span>
                    </div>
                </div>
                <a href="{{ route('wallet.index') }}" class="ui-button-secondary self-start sm:self-auto">
                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                    Wallet overview
                </a>
            </div>
        </section>

        <div class="grid gap-5 xl:grid-cols-2 xl:items-start">
            <section class="ui-panel overflow-hidden shadow-none">
                <div class="flex items-center gap-3 border-b border-border px-5 py-4 sm:px-6">
                    <div class="wallet-metric-icon !mt-0 !h-9 !w-9">
                        <i data-lucide="arrow-right-left" class="h-4 w-4"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-semibold tracking-tight text-foreground">Send funds</h2>
                        <p class="mt-0.5 text-xs text-muted-foreground">Transfer to another customer account.</p>
                    </div>
                </div>

                <div class="p-5 sm:p-6">
                    @if($errors->any())
                        <div class="mb-5 rounded-lg border border-red-500/20 bg-red-500/10 p-3 text-sm text-red-600 dark:text-red-300">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('wallet.process-transfer') }}" class="space-y-5">
                        @csrf
                        <div>
                            <label class="ui-label" for="recipient">Recipient email</label>
                            <input class="ui-input" id="recipient" name="recipient" type="email" value="{{ old('recipient') }}" placeholder="customer@example.com" required>
                            @error('recipient')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="ui-label" for="amount">Amount</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground">$</span>
                                <input class="ui-input pl-8" id="amount" name="amount" type="number" min="1" max="100000" step="0.01" value="{{ old('amount') }}" placeholder="250.00" required>
                            </div>
                            @error('amount')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="ui-label" for="note">Note <span class="font-normal text-muted-foreground">(optional)</span></label>
                            <textarea class="ui-input min-h-24 resize-none" id="note" name="note" maxlength="255" placeholder="What is this transfer for?">{{ old('note') }}</textarea>
                        </div>

                        <div class="flex items-start gap-2.5 rounded-lg border border-border bg-muted/30 p-3 text-xs leading-5 text-muted-foreground">
                            <i data-lucide="shield-check" class="mt-0.5 h-4 w-4 shrink-0 text-foreground"></i>
                            <p>Transfers are processed immediately and recorded in both account histories.</p>
                        </div>

                        <div class="flex justify-end border-t border-border pt-5">
                            <button class="ui-button-primary w-full sm:w-auto" type="submit">
                                Send funds
                                <i data-lucide="arrow-right" class="h-4 w-4"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </section>

            <section class="ui-panel overflow-hidden shadow-none">
                <div class="flex items-center justify-between gap-4 border-b border-border px-5 py-4 sm:px-6">
                    <div>
                        <h2 class="text-base font-semibold tracking-tight text-foreground">Recent transfers</h2>
                        <p class="mt-0.5 text-xs text-muted-foreground">Incoming and outgoing transfers.</p>
                    </div>
                    <a href="{{ route('wallet.transactions') }}" class="ui-button-secondary !h-8 !px-2.5 !py-0 text-xs">View all</a>
                </div>

                <div>
                    @forelse($recentTransfers as $transfer)
                        @php($outgoing = $transfer->sender_id === auth()->id())
                        <div class="wallet-activity-row">
                            <div class="wallet-activity-icon {{ $outgoing ? '' : 'wallet-activity-icon-in' }}">
                                <i data-lucide="{{ $outgoing ? 'arrow-up-right' : 'arrow-down-left' }}" class="h-4 w-4"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-medium text-foreground">
                                    {{ $outgoing ? $transfer->recipient->name : $transfer->sender->name }}
                                </div>
                                <div class="mt-0.5 truncate text-xs text-muted-foreground">
                                    {{ $outgoing ? 'Sent to ' . $transfer->recipient->email : 'Received from ' . $transfer->sender->email }}
                                </div>
                            </div>
                            <div class="shrink-0 text-right">
                                <div class="text-sm font-semibold {{ $outgoing ? 'text-foreground' : 'text-emerald-500' }}">
                                    {{ $outgoing ? '-' : '+' }}{{ format_currency($transfer->amount) }}
                                </div>
                                <div class="mt-0.5 text-[11px] text-muted-foreground">{{ $transfer->created_at->diffForHumans() }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="m-4 app-empty-state min-h-64 border-0 bg-muted/20">
                            <div class="wallet-metric-icon !mt-0 !h-10 !w-10">
                                <i data-lucide="arrow-right-left" class="h-4 w-4"></i>
                            </div>
                            <div>
                                <h3 class="font-medium text-foreground">No transfers yet</h3>
                                <p class="mt-1 text-xs text-muted-foreground">Your transfer history will appear here.</p>
                            </div>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-user-layout>
