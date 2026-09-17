<x-user-layout>
    <x-slot name="header">Support</x-slot>

    <div class="ui-page max-w-6xl">
        <section class="ui-page-header">
            <div>
                <p class="ui-kicker">Help & support</p>
                <h1 class="ui-heading">How can we help?</h1>
                <p class="ui-lead">Send a support request for your account, Money, investments, trading, or another product question.</p>
            </div>
            <div class="ui-header-actions">
                <a href="{{ route('dashboard') }}" class="ui-btn ui-btn-secondary">
                    <i data-lucide="layout-dashboard" class="h-4 w-4"></i>
                    Overview
                </a>
                <a href="{{ route('account.history') }}" class="ui-btn ui-btn-secondary">
                    <i data-lucide="history" class="h-4 w-4"></i>
                    Audit history
                </a>
            </div>
        </section>

        <section class="grid gap-4 lg:grid-cols-[minmax(0,1.35fr)_minmax(280px,.65fr)]">
            <article class="ui-panel overflow-hidden">
                <div class="border-b border-border px-5 py-4 sm:px-6">
                    <p class="ui-kicker">New request</p>
                    <h2 class="text-lg font-semibold text-foreground">Contact support</h2>
                    <p class="mt-1 text-xs text-muted-foreground">Give us enough detail to understand what you need help with.</p>
                </div>

                <form action="{{ route('support.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5 p-5 sm:p-6">
                    @csrf

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="ui-label">Name</label>
                            <input type="text" value="{{ auth()->user()->name }}" disabled class="ui-input opacity-70" />
                        </div>
                        <div>
                            <label class="ui-label">Email</label>
                            <input type="email" value="{{ auth()->user()->email }}" disabled class="ui-input opacity-70" />
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="ui-label" for="category">Category</label>
                            <select id="category" name="category" class="ui-input" required>
                                <option value="" disabled {{ old('category') ? '' : 'selected' }}>Select a category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category }}" @selected(old('category') === $category)>{{ $category }}</option>
                                @endforeach
                            </select>
                            @error('category')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="ui-label" for="subject">Subject</label>
                            <input id="subject" type="text" name="subject" value="{{ old('subject') }}" class="ui-input" placeholder="Brief summary" required />
                            @error('subject')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label class="ui-label" for="message">Message</label>
                        <textarea id="message" name="message" rows="7" class="ui-input min-h-40 resize-y" placeholder="Describe the issue or question" required>{{ old('message') }}</textarea>
                        @error('message')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="ui-label" for="attachment">Attachment <span class="font-normal text-muted-foreground">(optional)</span></label>
                        <input id="attachment" type="file" name="attachment" accept=".jpg,.jpeg,.png,.pdf" class="block w-full rounded-lg border border-border bg-background px-3 py-2.5 text-sm text-foreground file:mr-3 file:rounded-md file:border-0 file:bg-muted file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-foreground" />
                        <p class="mt-1.5 text-xs text-muted-foreground">JPG, PNG or PDF up to 5 MB.</p>
                        @error('attachment')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-end border-t border-border pt-5">
                        <button type="submit" class="ui-btn ui-btn-primary w-full sm:w-auto">
                            <i data-lucide="send" class="h-4 w-4"></i>
                            Send request
                        </button>
                    </div>
                </form>
            </article>

            <aside class="space-y-4">
                <article class="ui-panel p-5">
                    <div class="ui-metric-icon"><i data-lucide="life-buoy" class="h-4 w-4"></i></div>
                    <h2 class="mt-4 text-base font-semibold text-foreground">Common topics</h2>
                    <div class="mt-4 divide-y divide-border rounded-xl border border-border">
                        @foreach([
                            ['shield-check', 'Identity verification'],
                            ['wallet-cards', 'Money and withdrawals'],
                            ['chart-no-axes-combined', 'Investments and trading'],
                            ['settings', 'Account and technical help'],
                        ] as [$icon, $label])
                            <div class="flex items-center gap-3 px-4 py-3 text-sm text-foreground">
                                <i data-lucide="{{ $icon }}" class="h-4 w-4 text-muted-foreground"></i>
                                <span>{{ $label }}</span>
                            </div>
                        @endforeach
                    </div>
                </article>

                <article class="ui-panel p-5">
                    <p class="ui-kicker">Response time</p>
                    <h2 class="text-base font-semibold text-foreground">Usually within 24 hours</h2>
                    <p class="mt-2 text-xs leading-5 text-muted-foreground">Requests are reviewed on weekdays. Keep any reference number or transaction details in your message when they are relevant.</p>
                </article>
            </aside>
        </section>
    </div>
</x-user-layout>
