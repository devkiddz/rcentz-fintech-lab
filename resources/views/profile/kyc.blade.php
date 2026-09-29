<x-user-layout>
    <x-slot name="header">{{ localize('ui.r2e.kyc.header', 'Identity verification') }}</x-slot>

    @php
        $status = $kyc?->status ?? 'not_submitted';
        $statusMeta = match($status) {
            'approved' => ['label' => 'Verified', 'icon' => 'badge-check', 'tone' => 'emerald'],
            'rejected' => ['label' => 'Action required', 'icon' => 'circle-alert', 'tone' => 'red'],
            'pending' => ['label' => 'Under review', 'icon' => 'clock-3', 'tone' => 'amber'],
            default => ['label' => 'Not submitted', 'icon' => 'shield', 'tone' => 'slate'],
        };
        $inputClass = 'mt-1.5 w-full rounded-xl border border-border bg-background px-3.5 py-2.5 text-sm text-foreground outline-none transition focus:border-foreground/25 focus:ring-2 focus:ring-ring/20';
        $labelClass = 'text-[10px] font-semibold uppercase tracking-[.12em] text-muted-foreground';
    @endphp

    <div class="ui-page max-w-6xl space-y-5">
        <section class="relative overflow-hidden rounded-2xl border border-border bg-card">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-foreground/15 to-transparent"></div>
            <div class="grid gap-5 p-5 sm:p-6 lg:grid-cols-[1fr_300px] lg:items-center">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-border bg-muted/40 px-2.5 py-1 text-[9px] font-semibold uppercase tracking-[.14em] text-muted-foreground">
                            <i data-lucide="shield-check" class="h-3.5 w-3.5"></i>
                            Account security
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-border px-2.5 py-1 text-[9px] font-semibold uppercase tracking-[.14em]">
                            <span class="h-1.5 w-1.5 rounded-full {{ $status === 'approved' ? 'bg-emerald-500' : ($status === 'rejected' ? 'bg-red-500' : ($status === 'pending' ? 'bg-amber-500' : 'bg-muted-foreground')) }}"></span>
                            {{ $statusMeta['label'] }}
                        </span>
                    </div>

                    <h1 class="mt-4 text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">Know Your Customer verification</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground">
                        Confirm your identity before accessing protected financial features. We review your identity document, contact details and selfie against the information you submit.
                    </p>

                    <div class="mt-5 flex flex-wrap gap-2 text-[10px] text-muted-foreground">
                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-muted/45 px-2.5 py-1.5"><i data-lucide="lock-keyhole" class="h-3.5 w-3.5"></i>Secure review</span>
                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-muted/45 px-2.5 py-1.5"><i data-lucide="image-check" class="h-3.5 w-3.5"></i>3 identity images</span>
                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-muted/45 px-2.5 py-1.5"><i data-lucide="clock" class="h-3.5 w-3.5"></i>Typical review: 24–48 hours</span>
                    </div>
                </div>

                <div class="rounded-2xl border border-border bg-background/60 p-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl {{ $status === 'approved' ? 'bg-emerald-500/10 text-emerald-500' : ($status === 'rejected' ? 'bg-red-500/10 text-red-500' : ($status === 'pending' ? 'bg-amber-500/10 text-amber-500' : 'bg-muted text-muted-foreground')) }}">
                            <i data-lucide="{{ $statusMeta['icon'] }}" class="h-5 w-5"></i>
                        </span>
                        <div>
                            <p class="text-[9px] font-semibold uppercase tracking-[.14em] text-muted-foreground">Verification status</p>
                            <p class="mt-1 text-base font-semibold text-foreground">{{ $statusMeta['label'] }}</p>
                        </div>
                    </div>

                    <div class="mt-4 space-y-2 border-t border-border pt-4 text-[11px]">
                        @if($kyc)
                            <div class="flex items-center justify-between gap-4"><span class="text-muted-foreground">Submitted</span><span class="font-medium text-foreground">{{ $kyc->formatted_submitted_at ?: '—' }}</span></div>
                            @if($kyc->verified_at)
                                <div class="flex items-center justify-between gap-4"><span class="text-muted-foreground">Verified</span><span class="font-medium text-foreground">{{ $kyc->formatted_verified_at }}</span></div>
                            @endif
                            <div class="flex items-center justify-between gap-4"><span class="text-muted-foreground">Document</span><span class="font-medium text-foreground">{{ $kyc->document_type_label }}</span></div>
                        @else
                            <p class="leading-5 text-muted-foreground">Start below when you have a valid identity document and a clear selfie ready.</p>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-3 sm:grid-cols-3">
            @foreach([
                ['1','Submit identity','Personal details, address and valid ID.','user-round-check'],
                ['2','Compliance review','Your information is reviewed securely.','scan-search'],
                ['3','Decision','You receive an in-app and email update.','badge-check'],
            ] as $step)
                <div class="rounded-2xl border border-border bg-card p-4">
                    <div class="flex items-start gap-3">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-muted text-xs font-semibold">{{ $step[0] }}</span>
                        <div>
                            <p class="text-xs font-semibold text-foreground">{{ $step[1] }}</p>
                            <p class="mt-1 text-[10px] leading-4 text-muted-foreground">{{ $step[2] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </section>

        @if($kyc && $kyc->isApproved())
            <section class="rounded-2xl border border-emerald-500/20 bg-emerald-500/[.05] p-5">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-500"><i data-lucide="badge-check" class="h-5 w-5"></i></span>
                    <div>
                        <h2 class="text-sm font-semibold text-foreground">Identity verified</h2>
                        <p class="mt-1 text-xs leading-5 text-muted-foreground">Your verification is approved. Protected features that require KYC can now use this verified identity status.</p>
                    </div>
                </div>
            </section>
        @elseif($kyc && $kyc->isPending())
            <section class="rounded-2xl border border-amber-500/20 bg-amber-500/[.05] p-5">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-500"><i data-lucide="hourglass" class="h-5 w-5"></i></span>
                    <div>
                        <h2 class="text-sm font-semibold text-foreground">Review in progress</h2>
                        <p class="mt-1 text-xs leading-5 text-muted-foreground">Your submission is locked for review. You will receive a notification when an administrator approves or rejects it.</p>
                    </div>
                </div>
            </section>
        @elseif($kyc && $kyc->isRejected())
            <section class="rounded-2xl border border-red-500/20 bg-red-500/[.05] p-5">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-500/10 text-red-500"><i data-lucide="circle-alert" class="h-5 w-5"></i></span>
                    <div class="min-w-0">
                        <h2 class="text-sm font-semibold text-foreground">Changes required before verification</h2>
                        <p class="mt-1 text-xs leading-5 text-muted-foreground">{{ $kyc->rejection_reason ?: 'Your submission needs an update before it can be approved.' }}</p>
                        <p class="mt-2 text-[10px] font-medium text-red-500">Update the affected details or documents below and resubmit.</p>
                    </div>
                </div>
            </section>
        @endif

        @if(!$kyc || $kyc->isRejected())
            <section class="overflow-hidden rounded-2xl border border-border bg-card">
                <div class="border-b border-border px-5 py-4 sm:px-6">
                    <p class="text-[9px] font-semibold uppercase tracking-[.15em] text-muted-foreground">{{ $kyc ? 'Resubmission' : 'Application' }}</p>
                    <h2 class="mt-1 text-base font-semibold text-foreground">{{ $kyc ? 'Update your verification' : 'Submit identity verification' }}</h2>
                    <p class="mt-1 text-[11px] leading-5 text-muted-foreground">Use information exactly as it appears on your identity document. All three images must be clear and no larger than 2 MB each.</p>
                </div>

                @if($errors->any())
                    <div class="border-b border-red-500/20 bg-red-500/[.04] px-5 py-3 text-xs text-red-600 sm:px-6">
                        {{ $errors->first() }}
                    </div>
                @endif

                @if($kyc)
                    <form action="{{ route('profile.kyc.update', $kyc) }}" method="POST" enctype="multipart/form-data" class="divide-y divide-border">
                        @method('PATCH')
                @else
                    <form action="{{ route('profile.kyc.store') }}" method="POST" enctype="multipart/form-data" class="divide-y divide-border">
                @endif
                    @csrf

                    <div class="p-5 sm:p-6">
                        <div class="mb-4 flex items-center gap-2"><i data-lucide="user-round" class="h-4 w-4 text-muted-foreground"></i><h3 class="text-sm font-semibold">Personal identity</h3></div>
                        <div class="grid gap-4 md:grid-cols-2">
                            @foreach([
                                ['first_name','First name','text'],
                                ['last_name','Last name','text'],
                            ] as $field)
                                <label><span class="{{ $labelClass }}">{{ $field[1] }}</span><input type="{{ $field[2] }}" name="{{ $field[0] }}" value="{{ old($field[0], data_get($kyc, $field[0])) }}" required class="{{ $inputClass }}"></label>
                            @endforeach
                            <label><span class="{{ $labelClass }}">Date of birth</span><input type="date" name="date_of_birth" value="{{ old('date_of_birth', data_get($kyc, 'date_of_birth')?->format('Y-m-d')) }}" required class="{{ $inputClass }}"></label>
                            <label>
                                <span class="{{ $labelClass }}">Nationality</span>
                                <select name="nationality" required class="{{ $inputClass }}">
                                    <option value="">Select nationality</option>
                                    @foreach(($countries ?? []) as $countryName)
                                        <option value="{{ $countryName }}" {{ old('nationality', data_get($kyc, 'nationality')) === $countryName ? 'selected' : '' }}>{{ $countryName }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label><span class="{{ $labelClass }}">Phone number</span><input type="tel" name="phone_number" value="{{ old('phone_number', data_get($kyc, 'phone_number')) }}" required class="{{ $inputClass }}"></label>
                        </div>
                    </div>

                    <div class="p-5 sm:p-6">
                        <div class="mb-4 flex items-center gap-2"><i data-lucide="contact-round" class="h-4 w-4 text-muted-foreground"></i><h3 class="text-sm font-semibold">Identity document</h3></div>
                        <div class="grid gap-4 md:grid-cols-3">
                            <label>
                                <span class="{{ $labelClass }}">Document type</span>
                                <select name="document_type" required class="{{ $inputClass }}">
                                    <option value="">Choose document</option>
                                    <option value="passport" {{ old('document_type', data_get($kyc, 'document_type')) === 'passport' ? 'selected' : '' }}>Passport</option>
                                    <option value="national_id" {{ old('document_type', data_get($kyc, 'document_type')) === 'national_id' ? 'selected' : '' }}>National ID</option>
                                    <option value="drivers_license" {{ old('document_type', data_get($kyc, 'document_type')) === 'drivers_license' ? 'selected' : '' }}>Driver's license</option>
                                </select>
                            </label>
                            <label><span class="{{ $labelClass }}">Document number</span><input type="text" name="document_number" value="{{ old('document_number', data_get($kyc, 'document_number')) }}" required class="{{ $inputClass }}"></label>
                            <label><span class="{{ $labelClass }}">Expiry date</span><input type="date" name="document_expiry_date" value="{{ old('document_expiry_date', data_get($kyc, 'document_expiry_date')?->format('Y-m-d')) }}" required class="{{ $inputClass }}"></label>
                        </div>
                    </div>

                    <div class="p-5 sm:p-6">
                        <div class="mb-4 flex items-center gap-2"><i data-lucide="map-pin-house" class="h-4 w-4 text-muted-foreground"></i><h3 class="text-sm font-semibold">Residential address</h3></div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="md:col-span-2"><span class="{{ $labelClass }}">Address line 1</span><input type="text" name="address_line_1" value="{{ old('address_line_1', data_get($kyc, 'address_line_1')) }}" required class="{{ $inputClass }}"></label>
                            <label class="md:col-span-2"><span class="{{ $labelClass }}">Address line 2 <span class="normal-case tracking-normal">(optional)</span></span><input type="text" name="address_line_2" value="{{ old('address_line_2', data_get($kyc, 'address_line_2')) }}" class="{{ $inputClass }}"></label>
                            <label><span class="{{ $labelClass }}">City</span><input type="text" name="city" value="{{ old('city', data_get($kyc, 'city')) }}" required class="{{ $inputClass }}"></label>
                            <label><span class="{{ $labelClass }}">State / province</span><input type="text" name="state_province" value="{{ old('state_province', data_get($kyc, 'state_province')) }}" required class="{{ $inputClass }}"></label>
                            <label><span class="{{ $labelClass }}">Postal code</span><input type="text" name="postal_code" value="{{ old('postal_code', data_get($kyc, 'postal_code')) }}" required class="{{ $inputClass }}"></label>
                            <label>
                                <span class="{{ $labelClass }}">Country</span>
                                <select name="country" required class="{{ $inputClass }}">
                                    <option value="">Select country</option>
                                    @foreach(($countries ?? []) as $countryName)
                                        <option value="{{ $countryName }}" {{ old('country', data_get($kyc, 'country')) === $countryName ? 'selected' : '' }}>{{ $countryName }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>
                    </div>

                    <div class="p-5 sm:p-6">
                        <div class="mb-4 flex items-center gap-2"><i data-lucide="images" class="h-4 w-4 text-muted-foreground"></i><h3 class="text-sm font-semibold">Verification images</h3></div>
                        <div class="grid gap-3 lg:grid-cols-3">
                            @foreach([
                                ['document_front','Document front','Full front side, readable and uncropped.','scan'],
                                ['document_back','Document back','Full reverse side, readable and uncropped.','scan-line'],
                                ['selfie','Verification selfie','Clear recent photo of your face.','camera'],
                            ] as $upload)
                                <label class="group cursor-pointer rounded-2xl border border-dashed border-border bg-background/50 p-4 transition hover:border-foreground/25 hover:bg-muted/20">
                                    <div class="flex items-start gap-3">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-muted text-muted-foreground"><i data-lucide="{{ $upload[3] }}" class="h-4 w-4"></i></span>
                                        <div class="min-w-0">
                                            <span class="block text-xs font-semibold text-foreground">{{ $upload[1] }}</span>
                                            <span class="mt-1 block text-[10px] leading-4 text-muted-foreground">{{ $upload[2] }}</span>
                                        </div>
                                    </div>
                                    <input type="file" name="{{ $upload[0] }}" accept=".jpg,.jpeg,.png,image/jpeg,image/png" {{ $kyc ? '' : 'required' }} class="mt-4 block w-full text-[10px] text-muted-foreground file:mr-3 file:rounded-lg file:border-0 file:bg-muted file:px-3 file:py-2 file:text-[10px] file:font-semibold file:text-foreground">
                                    <span class="mt-2 block text-[9px] text-muted-foreground">JPG or PNG · maximum 2 MB</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 bg-muted/15 p-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <p class="max-w-2xl text-[10px] leading-4 text-muted-foreground">By submitting, you confirm the information belongs to you and is accurate. Verification decisions are recorded against your account.</p>
                        <button type="submit" class="ui-btn ui-btn-primary shrink-0">
                            <i data-lucide="{{ $kyc ? 'refresh-cw' : 'shield-check' }}" class="h-4 w-4"></i>
                            {{ $kyc ? 'Resubmit verification' : 'Submit for review' }}
                        </button>
                    </div>
                </form>
            </section>
        @elseif($kyc)
            <section class="grid gap-4 lg:grid-cols-[1fr_320px]">
                <div class="overflow-hidden rounded-2xl border border-border bg-card">
                    <div class="border-b border-border px-5 py-4">
                        <p class="text-[9px] font-semibold uppercase tracking-[.14em] text-muted-foreground">Submitted identity</p>
                        <h2 class="mt-1 text-sm font-semibold">Your verification record</h2>
                    </div>
                    <div class="grid gap-px bg-border sm:grid-cols-2">
                        @foreach([
                            ['Full name', $kyc->full_name],
                            ['Date of birth', $kyc->formatted_date_of_birth],
                            ['Nationality', $kyc->nationality],
                            ['Phone', $kyc->phone_number],
                            ['Document type', $kyc->document_type_label],
                            ['Document number', $kyc->document_number],
                            ['Document expiry', $kyc->formatted_document_expiry],
                            ['Country', $kyc->country],
                        ] as $item)
                            <div class="bg-card px-5 py-4">
                                <p class="text-[9px] font-semibold uppercase tracking-[.12em] text-muted-foreground">{{ $item[0] }}</p>
                                <p class="mt-1.5 break-words text-xs font-medium text-foreground">{{ $item[1] ?: '—' }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
                <aside class="rounded-2xl border border-border bg-card p-5">
                    <div class="flex items-center gap-2"><i data-lucide="map-pin" class="h-4 w-4 text-muted-foreground"></i><h3 class="text-sm font-semibold">Address on file</h3></div>
                    <p class="mt-3 text-xs leading-5 text-foreground">
                        {{ $kyc->address_line_1 }}@if($kyc->address_line_2), {{ $kyc->address_line_2 }}@endif<br>
                        {{ $kyc->city }}, {{ $kyc->state_province }} {{ $kyc->postal_code }}<br>
                        {{ $kyc->country }}
                    </p>
                </aside>
            </section>
        @endif
    </div>
</x-user-layout>
