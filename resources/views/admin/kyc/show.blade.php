<x-admin-layout>
    <x-slot name="header">KYC application</x-slot>

    @php
        $statusTone = match($kyc->status) {
            'approved' => 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20',
            'rejected' => 'bg-red-500/10 text-red-600 border-red-500/20',
            default => 'bg-amber-500/10 text-amber-600 border-amber-500/20',
        };
        $statusIcon = match($kyc->status) {
            'approved' => 'badge-check',
            'rejected' => 'circle-x',
            default => 'clock-3',
        };
        $age = $kyc->date_of_birth?->age;
        $isExpired = $kyc->isExpired();
    @endphp

    <div class="ui-page max-w-[1500px] space-y-5">
        <section class="flex flex-col gap-4 border-b border-border/70 pb-5 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('admin.kyc.index') }}" class="inline-flex items-center gap-1.5 text-[10px] font-medium text-muted-foreground hover:text-foreground"><i data-lucide="arrow-left" class="h-3.5 w-3.5"></i>KYC queue</a>
                    <span class="text-muted-foreground/40">/</span>
                    <span class="text-[10px] text-muted-foreground">Application #{{ $kyc->id }}</span>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-semibold tracking-tight">{{ $kyc->full_name }}</h1>
                    <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[9px] font-semibold {{ $statusTone }}"><i data-lucide="{{ $statusIcon }}" class="h-3 w-3"></i>{{ $kyc->status_label }}</span>
                </div>
                <p class="mt-1 text-xs text-muted-foreground">{{ $kyc->user?->email }} · submitted {{ $kyc->formatted_submitted_at ?: 'date unavailable' }}</p>
            </div>

            @if($kyc->status === 'pending')
                <div class="flex flex-wrap gap-2">
                    <button type="button" onclick="openRejectModal()" class="ui-btn ui-btn-secondary text-red-600"><i data-lucide="circle-x" class="h-4 w-4"></i>Reject</button>
                    <form method="POST" action="{{ route('admin.kyc.approve',$kyc) }}">@csrf
                        <button class="ui-btn ui-btn-primary"><i data-lucide="badge-check" class="h-4 w-4"></i>Approve identity</button>
                    </form>
                </div>
            @endif
        </section>

        @if($isExpired)
            <section class="rounded-2xl border border-red-500/20 bg-red-500/[.05] p-4">
                <div class="flex items-start gap-3"><i data-lucide="calendar-x" class="mt-0.5 h-4 w-4 text-red-500"></i><div><p class="text-xs font-semibold text-red-600">Identity document is expired</p><p class="mt-1 text-[10px] text-muted-foreground">The submitted document expired on {{ $kyc->formatted_document_expiry }}. Do not approve until the customer provides a valid document.</p></div></div>
            </section>
        @endif

        @if($kyc->rejection_reason)
            <section class="rounded-2xl border border-red-500/20 bg-red-500/[.04] p-4">
                <div class="flex items-start gap-3"><i data-lucide="message-square-warning" class="mt-0.5 h-4 w-4 text-red-500"></i><div><p class="text-[9px] font-semibold uppercase tracking-[.12em] text-red-600">Rejection reason</p><p class="mt-1 text-xs leading-5 text-foreground">{{ $kyc->rejection_reason }}</p></div></div>
            </section>
        @endif

        <section class="grid gap-5 xl:grid-cols-[1fr_350px]">
            <div class="space-y-5">
                <section class="overflow-hidden rounded-2xl border border-border bg-card">
                    <div class="border-b border-border px-5 py-4">
                        <div class="flex items-center gap-2"><span class="flex h-8 w-8 items-center justify-center rounded-lg bg-muted"><i data-lucide="user-round-check" class="h-4 w-4"></i></span><div><h2 class="text-sm font-semibold">Identity profile</h2><p class="mt-0.5 text-[10px] text-muted-foreground">Submitted personal and document information.</p></div></div>
                    </div>
                    <div class="grid gap-px bg-border sm:grid-cols-2 lg:grid-cols-3">
                        @foreach([
                            ['Legal name',$kyc->full_name],
                            ['Date of birth',$kyc->formatted_date_of_birth.($age ? ' · '.$age.' years' : '')],
                            ['Nationality',$kyc->nationality],
                            ['Phone',$kyc->phone_number],
                            ['Document type',$kyc->document_type_label],
                            ['Document number',$kyc->document_number],
                            ['Document expiry',$kyc->formatted_document_expiry],
                            ['Account email',$kyc->user?->email],
                            ['Account ID',$kyc->user_id ? '#'.$kyc->user_id : '—'],
                        ] as $item)
                            <div class="bg-card px-5 py-4">
                                <p class="text-[9px] font-semibold uppercase tracking-[.12em] text-muted-foreground">{{ $item[0] }}</p>
                                <p class="mt-1.5 break-words text-xs font-medium text-foreground">{{ $item[1] ?: '—' }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="overflow-hidden rounded-2xl border border-border bg-card">
                    <div class="border-b border-border px-5 py-4">
                        <div class="flex items-center gap-2"><span class="flex h-8 w-8 items-center justify-center rounded-lg bg-muted"><i data-lucide="images" class="h-4 w-4"></i></span><div><h2 class="text-sm font-semibold">Verification images</h2><p class="mt-0.5 text-[10px] text-muted-foreground">Compare the document sides with the customer's selfie before deciding.</p></div></div>
                    </div>
                    <div class="grid gap-4 p-5 md:grid-cols-3">
                        @foreach([
                            ['Document front',$kyc->document_front_path,'scan'],
                            ['Document back',$kyc->document_back_path,'scan-line'],
                            ['Selfie',$kyc->selfie_path,'camera'],
                        ] as $image)
                            <div class="overflow-hidden rounded-2xl border border-border bg-background">
                                <div class="flex items-center justify-between border-b border-border px-3 py-2.5">
                                    <div class="flex items-center gap-2"><i data-lucide="{{ $image[2] }}" class="h-3.5 w-3.5 text-muted-foreground"></i><span class="text-[10px] font-semibold">{{ $image[0] }}</span></div>
                                    @if($image[1])<a href="{{ asset('storage/'.$image[1]) }}" target="_blank" class="text-muted-foreground hover:text-foreground" title="Open full image"><i data-lucide="expand" class="h-3.5 w-3.5"></i></a>@endif
                                </div>
                                @if($image[1])
                                    <a href="{{ asset('storage/'.$image[1]) }}" target="_blank" class="block bg-muted/20">
                                        <img src="{{ asset('storage/'.$image[1]) }}" alt="{{ $image[0] }}" class="h-52 w-full object-cover transition hover:scale-[1.01]">
                                    </a>
                                @else
                                    <div class="flex h-52 items-center justify-center text-[10px] text-muted-foreground">No image supplied</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="overflow-hidden rounded-2xl border border-border bg-card">
                    <div class="border-b border-border px-5 py-4">
                        <div class="flex items-center gap-2"><i data-lucide="map-pin-house" class="h-4 w-4 text-muted-foreground"></i><h2 class="text-sm font-semibold">Residential address</h2></div>
                    </div>
                    <div class="p-5">
                        <p class="text-xs leading-6 text-foreground">
                            {{ $kyc->address_line_1 }}@if($kyc->address_line_2)<br>{{ $kyc->address_line_2 }}@endif<br>
                            {{ $kyc->city }}, {{ $kyc->state_province }} {{ $kyc->postal_code }}<br>
                            {{ $kyc->country }}
                        </p>
                    </div>
                </section>
            </div>

            <aside class="space-y-4">
                <section class="rounded-2xl border border-border bg-card p-5">
                    <div class="flex items-center gap-3">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-muted text-sm font-semibold">{{ strtoupper(mb_substr($kyc->user?->name ?: $kyc->full_name,0,1)) }}</span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold">{{ $kyc->user?->name ?: $kyc->full_name }}</p>
                            <p class="mt-0.5 truncate text-[10px] text-muted-foreground">{{ $kyc->user?->email }}</p>
                        </div>
                    </div>
                    <div class="mt-4 space-y-2 border-t border-border pt-4 text-[11px]">
                        <div class="flex justify-between gap-4"><span class="text-muted-foreground">Application</span><span class="font-medium">#{{ $kyc->id }}</span></div>
                        <div class="flex justify-between gap-4"><span class="text-muted-foreground">Submitted</span><span class="text-right font-medium">{{ $kyc->formatted_submitted_at ?: '—' }}</span></div>
                        <div class="flex justify-between gap-4"><span class="text-muted-foreground">Verified</span><span class="text-right font-medium">{{ $kyc->formatted_verified_at ?: '—' }}</span></div>
                    </div>
                </section>

                <section class="rounded-2xl border border-border bg-card p-5">
                    <div class="flex items-center gap-2"><i data-lucide="clipboard-check" class="h-4 w-4 text-muted-foreground"></i><h2 class="text-sm font-semibold">Review checklist</h2></div>
                    <div class="mt-4 space-y-3 text-[10px] text-muted-foreground">
                        @foreach([
                            'Name and date of birth match the document.',
                            'Document number and expiry are readable.',
                            'Front and back images are complete and uncropped.',
                            'Selfie reasonably matches the identity document.',
                            'Residential and contact information are plausible.',
                        ] as $check)
                            <div class="flex items-start gap-2"><span class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded border border-border"><i data-lucide="check" class="h-2.5 w-2.5"></i></span><span class="leading-4">{{ $check }}</span></div>
                        @endforeach
                    </div>
                </section>

                @if($kyc->status === 'pending')
                    <section class="rounded-2xl border border-border bg-card p-5">
                        <p class="text-[9px] font-semibold uppercase tracking-[.14em] text-muted-foreground">Decision</p>
                        <p class="mt-1 text-xs leading-5 text-muted-foreground">Approval immediately updates the customer's KYC status. A rejection should explain exactly what needs correction.</p>
                        <div class="mt-4 grid gap-2">
                            <form method="POST" action="{{ route('admin.kyc.approve',$kyc) }}">@csrf<button class="ui-btn ui-btn-primary w-full justify-center"><i data-lucide="badge-check" class="h-4 w-4"></i>Approve identity</button></form>
                            <button type="button" onclick="openRejectModal()" class="ui-btn ui-btn-secondary w-full justify-center text-red-600"><i data-lucide="circle-x" class="h-4 w-4"></i>Reject with reason</button>
                        </div>
                    </section>
                @endif

                <section class="rounded-2xl border border-red-500/15 bg-card p-5">
                    <div class="flex items-center gap-2 text-red-600"><i data-lucide="trash-2" class="h-4 w-4"></i><h2 class="text-xs font-semibold">Delete application</h2></div>
                    <p class="mt-2 text-[10px] leading-4 text-muted-foreground">Use only when the verification record itself should be permanently removed.</p>
                    <form method="POST" action="{{ route('admin.kyc.destroy',$kyc) }}" class="mt-3" onsubmit="return confirm('Permanently delete this KYC application? This cannot be undone.')">
                        @csrf @method('DELETE')
                        <button class="text-[10px] font-semibold text-red-600 hover:underline">Delete KYC record</button>
                    </form>
                </section>
            </aside>
        </section>
    </div>

    <div id="rejectModal" class="fixed inset-0 z-[2000] hidden bg-black/60 p-4 backdrop-blur-sm">
        <div class="mx-auto mt-[12vh] w-full max-w-md rounded-2xl border border-border bg-card p-5 shadow-2xl">
            <div class="flex items-start justify-between gap-4">
                <div><p class="text-[9px] font-semibold uppercase tracking-[.14em] text-red-600">KYC decision</p><h3 class="mt-1 text-base font-semibold">Reject verification</h3></div>
                <button type="button" onclick="closeRejectModal()" class="flex h-8 w-8 items-center justify-center rounded-lg hover:bg-muted"><i data-lucide="x" class="h-4 w-4"></i></button>
            </div>
            <p class="mt-3 text-xs leading-5 text-muted-foreground">Explain what {{ $kyc->user?->name ?: $kyc->full_name }} must correct before resubmitting.</p>
            <form method="POST" action="{{ route('admin.kyc.reject',$kyc) }}" class="mt-4 space-y-4">
                @csrf
                <textarea id="rejection_reason" name="rejection_reason" rows="4" required class="w-full rounded-xl border border-border bg-background px-3.5 py-3 text-sm outline-none focus:ring-2 focus:ring-ring/20" placeholder="Example: The front of the ID is cropped and the document number is not fully readable."></textarea>
                <div class="flex justify-end gap-2"><button type="button" onclick="closeRejectModal()" class="ui-btn ui-btn-secondary">Cancel</button><button class="ui-btn bg-red-600 text-white hover:bg-red-700">Reject application</button></div>
            </form>
        </div>
    </div>

    <script>
        function openRejectModal(){ const modal=document.getElementById('rejectModal'); modal.classList.remove('hidden'); modal.classList.add('block'); document.getElementById('rejection_reason')?.focus(); }
        function closeRejectModal(){ const modal=document.getElementById('rejectModal'); modal.classList.add('hidden'); modal.classList.remove('block'); }
        document.getElementById('rejectModal')?.addEventListener('click', function(event){ if(event.target===this) closeRejectModal(); });
        document.addEventListener('keydown', function(event){ if(event.key==='Escape') closeRejectModal(); });
    </script>
</x-admin-layout>
