<x-user-layout>
    <x-slot name="header">Connected wallets</x-slot>

    <div class="app-page max-w-7xl mx-auto space-y-5 sm:space-y-6">
        <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="app-eyebrow">Money</p>
                <h1 class="app-title">Connected wallets</h1>
                <p class="app-subtitle">Manage the external wallet addresses associated with your account.</p>
            </div>
            <a href="{{ route('money.index') }}" class="ui-button-secondary self-start sm:self-auto">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                Money overview
            </a>
        </section>

        <div class="grid gap-5 xl:grid-cols-2 xl:items-start">
            <section class="ui-panel overflow-hidden shadow-none">
                <div class="flex items-center gap-3 border-b border-border px-5 py-4 sm:px-6">
                    <div class="wallet-metric-icon !mt-0 !h-9 !w-9">
                        <i data-lucide="link" class="h-4 w-4"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-semibold tracking-tight text-foreground">Link a wallet</h2>
                        <p class="mt-0.5 text-xs text-muted-foreground">Add an address to your account.</p>
                    </div>
                </div>

                <div class="p-5 sm:p-6">
                    @if ($errors->any())
                        <div class="mb-5 rounded-lg border border-red-500/20 bg-red-500/10 p-3 text-sm text-red-600 dark:text-red-300">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('money.connections.store') }}" class="space-y-5">
                        @csrf

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="ui-label" for="provider">Wallet provider</label>
                                <select id="provider" name="provider" class="ui-input" required>
                                    <option value="walletconnect" @selected(old('provider') === 'walletconnect')>WalletConnect</option>
                                    <option value="metamask" @selected(old('provider') === 'metamask')>MetaMask</option>
                                    <option value="coinbase" @selected(old('provider') === 'coinbase')>Coinbase Wallet</option>
                                    <option value="trust" @selected(old('provider') === 'trust')>Trust Wallet</option>
                                    <option value="other" @selected(old('provider') === 'other')>Other wallet</option>
                                </select>
                            </div>

                            <div>
                                <label class="ui-label" for="network">Network</label>
                                <select id="network" name="network" class="ui-input" required>
                                    <option value="ethereum" @selected(old('network') === 'ethereum')>Ethereum</option>
                                    <option value="bnb" @selected(old('network') === 'bnb')>BNB Smart Chain</option>
                                    <option value="polygon" @selected(old('network') === 'polygon')>Polygon</option>
                                    <option value="solana" @selected(old('network') === 'solana')>Solana</option>
                                    <option value="bitcoin" @selected(old('network') === 'bitcoin')>Bitcoin</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="ui-label" for="address">Wallet address</label>
                            <input id="address" name="address" value="{{ old('address') }}" class="ui-input font-mono" placeholder="Enter wallet address" required>
                            <p class="mt-1.5 text-xs text-muted-foreground">Confirm the network and address before saving.</p>
                        </div>

                        <div>
                            <label class="ui-label" for="label">Label <span class="font-normal text-muted-foreground">(optional)</span></label>
                            <input id="label" name="label" value="{{ old('label') }}" class="ui-input" placeholder="e.g. Primary wallet">
                        </div>

                        <div class="flex justify-end border-t border-border pt-5">
                            <button class="ui-button-primary w-full sm:w-auto" type="submit">
                                <i data-lucide="link" class="h-4 w-4"></i>
                                Connect wallet
                            </button>
                        </div>
                    </form>
                </div>
            </section>

            <section class="ui-panel overflow-hidden shadow-none">
                <div class="flex items-center justify-between gap-4 border-b border-border px-5 py-4 sm:px-6">
                    <div>
                        <h2 class="text-base font-semibold tracking-tight text-foreground">Your wallets</h2>
                        <p class="mt-0.5 text-xs text-muted-foreground">{{ $wallets->count() }} {{ \Illuminate\Support\Str::plural('wallet', $wallets->count()) }} connected</p>
                    </div>
                    <span class="wallet-status-badge wallet-status-completed !mt-0">{{ $wallets->count() }}</span>
                </div>

                <div class="p-4 sm:p-5">
                    <div class="space-y-3">
                        @forelse($wallets as $linked)
                            <article class="rounded-xl border border-border bg-background p-4 transition-colors hover:bg-muted/20">
                                <div class="flex items-start gap-3">
                                    <div class="wallet-metric-icon !mt-0 !h-9 !w-9">
                                        <i data-lucide="wallet-cards" class="h-4 w-4"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="truncate text-sm font-semibold text-foreground">{{ $linked->label ?: ucfirst($linked->provider) }}</h3>
                                            @if($linked->is_primary)
                                                <span class="wallet-status-badge wallet-status-completed !mt-0">Primary</span>
                                            @endif
                                        </div>
                                        <p class="mt-1 text-xs text-muted-foreground">{{ ucfirst($linked->network) }} · {{ ucfirst($linked->provider) }}</p>
                                        <p class="mt-3 break-all font-mono text-xs text-foreground sm:text-sm">{{ $linked->masked_address }}</p>
                                    </div>
                                </div>

                                <div class="mt-4 flex items-center gap-2 border-t border-border pt-3">
                                    @unless($linked->is_primary)
                                        <form method="POST" action="{{ route('money.connections.primary', $linked) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="inline-flex items-center gap-1.5 text-xs font-medium text-foreground hover:text-primary" type="submit">
                                                <i data-lucide="star" class="h-3.5 w-3.5"></i>
                                                Set primary
                                            </button>
                                        </form>
                                    @endunless

                                    <form method="POST" action="{{ route('money.connections.destroy', $linked) }}" class="ml-auto">
                                        @csrf
                                        @method('DELETE')
                                        <button class="inline-flex items-center gap-1.5 text-xs font-medium text-red-600 hover:text-red-500" type="submit">
                                            <i data-lucide="unlink" class="h-3.5 w-3.5"></i>
                                            Disconnect
                                        </button>
                                    </form>
                                </div>
                            </article>
                        @empty
                            <div class="app-empty-state min-h-72 border-0 bg-muted/20">
                                <div class="wallet-metric-icon !mt-0 !h-10 !w-10">
                                    <i data-lucide="link-2-off" class="h-4 w-4"></i>
                                </div>
                                <div>
                                    <h3 class="font-medium text-foreground">No wallets connected</h3>
                                    <p class="mt-1 text-xs text-muted-foreground">Linked wallets will appear here.</p>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-user-layout>
