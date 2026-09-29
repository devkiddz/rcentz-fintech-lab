<x-admin-layout>
    <x-slot name="header">KYC review</x-slot>

    @php
        $activeStatus = $status ?? request('status');
        $statusClass = fn ($value) => match($value) {
            'approved' => 'bg-emerald-500/10 text-emerald-600',
            'rejected' => 'bg-red-500/10 text-red-600',
            default => 'bg-amber-500/10 text-amber-600',
        };
        $statusIcon = fn ($value) => match($value) {
            'approved' => 'badge-check',
            'rejected' => 'circle-x',
            default => 'clock-3',
        };
    @endphp

    <div class="ui-page max-w-[1500px] space-y-5">
        <section class="flex flex-col gap-4 border-b border-border/70 pb-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg border border-border bg-muted/40"><i data-lucide="scan-face" class="h-4 w-4"></i></span>
                    <p class="ui-kicker">Compliance review</p>
                </div>
                <h1 class="mt-3 text-2xl font-semibold tracking-tight">Identity verification queue</h1>
                <p class="mt-1 max-w-2xl text-xs leading-5 text-muted-foreground">Review customer identity details, document validity and submitted images before approving access to KYC-protected features.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.kyc.by-status', 'pending') }}" class="ui-btn ui-btn-primary"><i data-lucide="inbox" class="h-4 w-4"></i>Review pending</a>
            </div>
        </section>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach([
                ['Total applications', $stats['total'], 'files', 'text-foreground', route('admin.kyc.index')],
                ['Pending review', $stats['pending'], 'clock-3', 'text-amber-500', route('admin.kyc.by-status','pending')],
                ['Approved', $stats['approved'], 'badge-check', 'text-emerald-500', route('admin.kyc.by-status','approved')],
                ['Rejected', $stats['rejected'], 'circle-x', 'text-red-500', route('admin.kyc.by-status','rejected')],
            ] as $metric)
                <a href="{{ $metric[4] }}" class="group rounded-2xl border border-border bg-card p-4 transition hover:-translate-y-0.5 hover:border-foreground/15">
                    <div class="flex items-start justify-between">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-muted/50 {{ $metric[3] }}"><i data-lucide="{{ $metric[2] }}" class="h-4 w-4"></i></span>
                        <i data-lucide="arrow-up-right" class="h-4 w-4 text-muted-foreground transition group-hover:text-foreground"></i>
                    </div>
                    <p class="mt-4 text-[9px] font-semibold uppercase tracking-[.13em] text-muted-foreground">{{ $metric[0] }}</p>
                    <p class="mt-1 text-2xl font-semibold tabular-nums">{{ number_format($metric[1]) }}</p>
                </a>
            @endforeach
        </section>

        <section class="overflow-hidden rounded-2xl border border-border bg-card">
            <div class="flex flex-col gap-3 border-b border-border px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                <div>
                    <h2 class="text-sm font-semibold">{{ $activeStatus ? ucfirst($activeStatus).' applications' : 'All applications' }}</h2>
                    <p class="mt-0.5 text-[10px] text-muted-foreground">{{ $kycApplications->total() }} record{{ $kycApplications->total() === 1 ? '' : 's' }} in this view</p>
                </div>
                <div class="flex flex-wrap gap-1.5">
                    @foreach([
                        [null,'All',$stats['total']],
                        ['pending','Pending',$stats['pending']],
                        ['approved','Approved',$stats['approved']],
                        ['rejected','Rejected',$stats['rejected']],
                    ] as $tab)
                        <a href="{{ $tab[0] ? route('admin.kyc.by-status',$tab[0]) : route('admin.kyc.index') }}"
                           class="rounded-lg px-3 py-2 text-[10px] font-semibold transition {{ $activeStatus === $tab[0] || (!$activeStatus && !$tab[0]) ? 'bg-foreground text-background' : 'bg-muted/55 text-muted-foreground hover:text-foreground' }}">
                            {{ $tab[1] }} <span class="ml-1 opacity-70">{{ $tab[2] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            @forelse($kycApplications as $kyc)
                <a href="{{ route('admin.kyc.show',$kyc) }}" class="group grid gap-3 border-b border-border px-4 py-4 transition last:border-b-0 hover:bg-muted/20 sm:px-5 lg:grid-cols-[minmax(240px,1.2fr)_minmax(170px,.8fr)_minmax(180px,.9fr)_140px_32px] lg:items-center">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-muted text-xs font-semibold text-foreground">{{ strtoupper(mb_substr($kyc->user?->name ?: $kyc->full_name,0,1)) }}</span>
                        <div class="min-w-0">
                            <p class="truncate text-xs font-semibold text-foreground">{{ $kyc->user?->name ?: $kyc->full_name }}</p>
                            <p class="mt-0.5 truncate text-[10px] text-muted-foreground">{{ $kyc->user?->email ?: 'No account email' }}</p>
                        </div>
                    </div>

                    <div>
                        <p class="text-[9px] font-semibold uppercase tracking-[.12em] text-muted-foreground">Identity</p>
                        <p class="mt-1 truncate text-[11px] font-medium">{{ $kyc->document_type_label }} · {{ $kyc->nationality }}</p>
                    </div>

                    <div>
                        <p class="text-[9px] font-semibold uppercase tracking-[.12em] text-muted-foreground">Submitted</p>
                        <p class="mt-1 text-[11px] font-medium">{{ $kyc->formatted_submitted_at ?: $kyc->created_at?->format('M j, Y g:i A') }}</p>
                    </div>

                    <div>
                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[9px] font-semibold {{ $statusClass($kyc->status) }}">
                            <i data-lucide="{{ $statusIcon($kyc->status) }}" class="h-3 w-3"></i>{{ $kyc->status_label }}
                        </span>
                    </div>

                    <i data-lucide="chevron-right" class="h-4 w-4 text-muted-foreground transition group-hover:translate-x-0.5 group-hover:text-foreground"></i>
                </a>
            @empty
                <div class="px-5 py-14 text-center">
                    <span class="mx-auto flex h-11 w-11 items-center justify-center rounded-xl bg-muted text-muted-foreground"><i data-lucide="scan-face" class="h-5 w-5"></i></span>
                    <h3 class="mt-3 text-sm font-semibold">No KYC applications in this view</h3>
                    <p class="mt-1 text-[11px] text-muted-foreground">New customer submissions will appear here automatically.</p>
                </div>
            @endforelse

            @if($kycApplications->hasPages())
                <div class="border-t border-border px-5 py-4">{{ $kycApplications->links() }}</div>
            @endif
        </section>

        <section class="rounded-2xl border border-border bg-muted/15 p-4">
            <div class="flex items-start gap-3">
                <i data-lucide="shield-alert" class="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground"></i>
                <p class="text-[10px] leading-5 text-muted-foreground"><span class="font-semibold text-foreground">Review discipline:</span> confirm the applicant's identity details, expiry date and all three submitted images before deciding. Rejections should include a clear reason the customer can act on.</p>
            </div>
        </section>
    </div>
</x-admin-layout>
