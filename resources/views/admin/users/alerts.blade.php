<x-admin-layout>
    <x-slot name="header">
        <div>
            <p class="ui-kicker">Private dashboard communication</p>
            <h1 class="text-xl font-semibold">{{ $user->name }}</h1>
            <p class="mt-1 text-xs text-muted-foreground">{{ $user->email }}</p>
        </div>
    </x-slot>

    @php
        $withdrawalRequest = request('withdrawal_request')
            ? \App\Models\WithdrawalTokenRequest::query()
                ->where('user_id',$user->id)
                ->find(request('withdrawal_request'))
            : null;
    @endphp

    <div class="ui-page max-w-6xl">
        @if(session('success'))
            <div class="mb-4 rounded-xl border border-emerald-500/20 bg-emerald-500/10 p-4 text-sm text-emerald-700 dark:text-emerald-300">{{ session('success') }}</div>
        @endif

        @if($withdrawalRequest)
            <section class="mb-4 rounded-2xl border border-blue-500/20 bg-blue-500/[.06] p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="ui-kicker">Withdrawal verification notice</p>
                        <h2 class="mt-1 text-base font-semibold">{{ format_currency($withdrawalRequest->amount,'USD',$user->currency,$user) }} withdrawal</h2>
                        <p class="mt-1 text-xs text-muted-foreground">Paste the code you copied from Withdrawal Requests into the message below. Sending remains a deliberate admin action.</p>
                    </div>
                    <a href="{{ route('admin.withdrawal-token-requests.index',['user'=>$user->id]) }}" class="ui-btn ui-btn-secondary ui-btn-sm">Back to request</a>
                </div>
            </section>
        @endif

        <div class="grid gap-4 lg:grid-cols-[minmax(0,.9fr)_minmax(0,1.1fr)]">
            <section class="ui-surface overflow-hidden">
                <div class="border-b border-border px-5 py-4">
                    <p class="ui-kicker">New notice</p>
                    <h2 class="mt-1 text-lg font-semibold">Push to customer dashboard</h2>
                    <p class="mt-1 text-xs text-muted-foreground">This is a private dashboard notice, not chat or email.</p>
                </div>

                <form method="POST" action="{{ route('admin.users.alerts.store',$user) }}" class="space-y-4 p-5">
                    @csrf
                    @if($withdrawalRequest)
                        <input type="hidden" name="withdrawal_token_request_id" value="{{ $withdrawalRequest->id }}">
                    @endif

                    <div>
                        <label class="ui-label">Title</label>
                        <input class="ui-input mt-1 w-full" name="title" value="{{ old('title',$withdrawalRequest ? 'Withdrawal verification code' : '') }}" required>
                    </div>

                    <div>
                        <label class="ui-label">Message</label>
                        <textarea class="ui-input mt-1 w-full" name="message" rows="6" required>{{ old('message',$withdrawalRequest ? 'Your withdrawal verification code is [PASTE CODE HERE]. It expires 30 minutes after it was generated.' : '') }}</textarea>
                    </div>

                    <div>
                        <label class="ui-label">Priority</label>
                        <select class="ui-input mt-1 w-full" name="priority">
                            <option value="normal">Normal</option>
                            <option value="important" @selected($withdrawalRequest)>Important</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="ui-label">Action label</label>
                            <input class="ui-input mt-1 w-full" name="action_label" value="{{ old('action_label',$withdrawalRequest ? 'Continue withdrawal' : '') }}" placeholder="Optional">
                        </div>
                        <div>
                            <label class="ui-label">Action URL</label>
                            <input class="ui-input mt-1 w-full" name="action_url" value="{{ old('action_url',$withdrawalRequest ? route('money.withdraw.verify',$withdrawalRequest) : '') }}" placeholder="Optional">
                        </div>
                    </div>

                    <div>
                        <label class="ui-label">Expires</label>
                        <input class="ui-input mt-1 w-full" name="expires_at" type="datetime-local" value="{{ old('expires_at',$withdrawalRequest?->token_expires_at?->format('Y-m-d\TH:i')) }}">
                    </div>

                    <button class="ui-btn ui-btn-primary w-full justify-center">
                        <i data-lucide="send" class="h-4 w-4"></i>Push to dashboard
                    </button>
                </form>
            </section>

            <section class="ui-surface overflow-hidden">
                <div class="border-b border-border px-5 py-4">
                    <p class="ui-kicker">Delivery history</p>
                    <h2 class="mt-1 text-lg font-semibold">Private dashboard notices</h2>
                </div>
                <div class="divide-y divide-border">
                    @forelse($alerts as $alert)
                        <article class="p-5">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-semibold">{{ $alert->title }}</p>
                                    <p class="mt-1 whitespace-pre-line text-sm leading-5 text-muted-foreground">{{ $alert->message }}</p>
                                </div>
                                <span class="rounded-full bg-muted px-2 py-1 text-[9px] font-semibold uppercase">{{ $alert->priority }}</span>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-x-3 gap-y-1 text-[10px] text-muted-foreground">
                                <span>{{ $alert->created_at->format('M j, Y · H:i') }}</span>
                                <span>{{ $alert->read_at ? 'Read' : 'Unread' }}</span>
                                @if($alert->dismissed_at)<span>Dismissed</span>@endif
                                @if($alert->type === 'withdrawal_token')<span>Withdrawal verification</span>@endif
                            </div>
                        </article>
                    @empty
                        <div class="p-8 text-center text-sm text-muted-foreground">No private dashboard notices yet.</div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-admin-layout>
