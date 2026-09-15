<x-admin-layout>
<x-slot name="header">Copy Trading Applications</x-slot>

<div class="ui-page max-w-[1600px]">
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker text-[10px]">Copy Trading</p>
            <h1 class="ui-heading !text-2xl">Provider applications</h1>
            <p class="ui-lead !max-w-3xl !text-[13px]">Review the applicant, submitted trading profile and approval history before granting provider access.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.copy-trading.providers') }}" class="ui-btn ui-btn-secondary">Providers</a>
            <a href="{{ route('admin.copy-trading.strategies') }}" class="ui-btn ui-btn-secondary">Strategies</a>
        </div>
    </section>

    <section class="ui-panel mt-5 overflow-hidden">
        <div class="divide-y divide-border/70">
            @forelse($applications as $a)
                <article class="grid gap-4 px-4 py-4 lg:grid-cols-[1.2fr_.8fr_.4fr_.45fr_auto] lg:items-center">
                    <div class="min-w-0">
                        <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Applicant</p>
                        <p class="mt-1 truncate text-[12px] font-semibold">{{ $a->user->name }}</p>
                        <p class="mt-0.5 truncate text-[9px] text-muted-foreground">{{ $a->display_name }}</p>
                    </div>

                    <div>
                        <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Submitted</p>
                        <p class="mt-1 text-[10px] font-medium">{{ optional($a->created_at)->format('M d, Y · H:i') }}</p>
                    </div>

                    <div>
                        <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Risk</p>
                        <span class="mt-1 inline-flex rounded-full border border-amber-500/20 bg-amber-500/10 px-2 py-1 text-[8px] font-semibold uppercase tracking-[.11em] text-amber-600">{{ ucfirst($a->risk_level) }}</span>
                    </div>

                    <div>
                        <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Status</p>
                        <span class="mt-1 inline-flex rounded-full px-2 py-1 text-[8px] font-semibold uppercase tracking-[.11em]
                            {{ $a->status === 'pending' ? 'border border-sky-500/20 bg-sky-500/10 text-sky-600' : ($a->status === 'approved' ? 'border border-emerald-500/20 bg-emerald-500/10 text-emerald-600' : 'border border-red-500/20 bg-red-500/10 text-red-600') }}">
                            {{ ucfirst($a->status) }}
                        </span>
                    </div>

                    <div class="flex justify-end gap-2">
                        <button type="button" class="ui-btn ui-btn-secondary !h-8 !px-3"
                                onclick="document.getElementById('application-{{ $a->id }}').showModal()">
                            <i data-lucide="eye" class="h-3.5 w-3.5"></i> Review
                        </button>

                        @if($a->status === 'approved' && $a->user?->copyTraderProfile)
                            <a href="{{ route('admin.copy-trading.providers.show',$a->user->copyTraderProfile) }}"
                               class="ui-btn ui-btn-secondary !h-8 !px-3">
                                <i data-lucide="settings-2" class="h-3.5 w-3.5"></i> Manage
                            </a>
                        @endif
                    </div>
                </article>

                <dialog id="application-{{ $a->id }}" class="w-[min(94vw,680px)] rounded-2xl border border-border bg-background p-0 text-foreground shadow-2xl backdrop:bg-black/60">
                    <div class="flex items-start justify-between border-b border-border px-5 py-4">
                        <div>
                            <p class="text-[9px] font-semibold uppercase tracking-[.14em] text-sky-500">Provider application</p>
                            <h3 class="mt-1 text-base font-semibold">{{ $a->display_name }}</h3>
                            <p class="mt-1 text-xs text-muted-foreground">{{ $a->user->name }}</p>
                        </div>
                        <button type="button" class="rounded-lg border border-border p-2 text-muted-foreground hover:bg-muted"
                                onclick="document.getElementById('application-{{ $a->id }}').close()">
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </button>
                    </div>

                    <div class="space-y-4 p-5">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="rounded-xl border border-border bg-muted/10 p-4">
                                <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Experience</p>
                                <p class="mt-2 whitespace-pre-line text-[11px] leading-5">{{ $a->experience ?: 'Not provided.' }}</p>
                            </div>
                            <div class="rounded-xl border border-border bg-muted/10 p-4">
                                <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Strategy summary</p>
                                <p class="mt-2 whitespace-pre-line text-[11px] leading-5">{{ $a->strategy_summary ?: 'Not provided.' }}</p>
                            </div>
                        </div>

                        @if($a->admin_notes)
                            <div class="rounded-xl border border-border p-4">
                                <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Admin notes</p>
                                <p class="mt-2 text-[11px] leading-5">{{ $a->admin_notes }}</p>
                            </div>
                        @endif

                        @if($a->status === 'pending')
                            <div class="grid gap-3 sm:grid-cols-2">
                                <form method="POST" action="{{ route('admin.copy-trading.applications.approve',$a) }}">
                                    @csrf
                                    <button class="ui-btn ui-btn-primary w-full">
                                        <i data-lucide="badge-check" class="h-4 w-4"></i> Approve provider
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('admin.copy-trading.applications.reject',$a) }}" class="space-y-2">
                                    @csrf
                                    <textarea name="admin_notes" class="ui-input min-h-20" placeholder="Reason for rejection" required></textarea>
                                    <button class="ui-btn ui-btn-secondary w-full">Reject application</button>
                                </form>
                            </div>
                        @elseif($a->user?->copyTraderProfile)
                            <a href="{{ route('admin.copy-trading.providers.show',$a->user->copyTraderProfile) }}" class="ui-btn ui-btn-primary w-full">
                                Open provider management
                            </a>
                        @endif
                    </div>
                </dialog>
            @empty
                <div class="p-10 text-center text-[10px] text-muted-foreground">No provider applications found.</div>
            @endforelse
        </div>
    </section>

    <div class="mt-4">{{ $applications->links() }}</div>
</div>
</x-admin-layout>
