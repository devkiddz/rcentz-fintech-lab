<x-user-layout>
    <x-slot name="header">Account Profile</x-slot>

    @php
        $kyc = $user->kyc;
        $primaryMembership = $activeMemberships->first() ?? $membershipStatuses->first();
        $membershipCount = $activeMemberships->count();
        $accountActive = $user->isAccountActive();
        $emailVerified = (bool) $user->email_verified_at;
        $kycLabel = $kyc?->status_label ?? 'Not submitted';
    @endphp

    <div class="ui-page max-w-[1360px]">
        @if(session('status') === 'profile-updated')
            <div class="mb-4 rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-xs font-medium text-emerald-600">Profile updated successfully.</div>
        @endif
        @if(session('status') === 'password-updated')
            <div class="mb-4 rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-xs font-medium text-emerald-600">Password updated successfully.</div>
        @endif

        <section class="ui-panel overflow-hidden">
            <div class="relative p-5 sm:p-6 lg:p-7">
                <div class="pointer-events-none absolute -right-20 -top-24 h-64 w-64 rounded-full bg-red-500/[.07] blur-3xl"></div>
                <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex min-w-0 items-center gap-4 sm:gap-5">
                        <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-border bg-muted text-2xl font-semibold shadow-sm sm:h-24 sm:w-24">
                            @if($user->profile_image)
                                <img src="{{ asset('storage/'.$user->profile_image) }}" alt="{{ $user->name }}" class="h-full w-full object-cover">
                            @else
                                <span>{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <p class="ui-kicker">Customer account</p>
                            <h1 class="mt-1 truncate text-2xl font-semibold tracking-tight sm:text-3xl">{{ $user->name }}</h1>
                            <p class="mt-1 truncate text-sm text-muted-foreground">{{ $user->email }}</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span class="rounded-full border px-2.5 py-1 text-[10px] font-semibold {{ $accountActive ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-600' : 'border-red-500/20 bg-red-500/10 text-red-600' }}">{{ $accountActive ? 'Account active' : ucfirst($user->account_status ?? 'Restricted') }}</span>
                                <span class="rounded-full border px-2.5 py-1 text-[10px] font-semibold {{ $emailVerified ? 'border-sky-500/20 bg-sky-500/10 text-sky-600' : 'border-amber-500/20 bg-amber-500/10 text-amber-600' }}">{{ $emailVerified ? 'Email verified' : 'Email verification pending' }}</span>
                                <span class="rounded-full border border-border bg-muted/40 px-2.5 py-1 text-[10px] font-semibold text-muted-foreground">{{ strtoupper($user->currency ?? 'USD') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('profile.kyc') }}" class="ui-btn ui-btn-secondary"><i data-lucide="shield-check" class="h-4 w-4"></i>KYC</a>
                        <a href="{{ route('memberships.index') }}" class="ui-btn ui-btn-primary"><i data-lucide="badge-check" class="h-4 w-4"></i>Membership</a>
                    </div>
                </div>
            </div>

            <div class="grid border-t border-border sm:grid-cols-2 lg:grid-cols-4">
                @foreach([
                    ['Account', $accountActive ? 'Active' : ucfirst($user->account_status ?? 'Restricted'), 'circle-user-round'],
                    ['KYC', $kycLabel, 'shield-check'],
                    ['Memberships', $membershipCount ? $membershipCount.' active' : 'None active', 'badge-check'],
                    ['Member since', $user->created_at?->format('M Y') ?? '—', 'calendar-days'],
                ] as [$label,$value,$icon])
                    <div class="border-t border-border p-4 first:border-t-0 sm:border-l sm:border-t-0 sm:first:border-l-0 lg:p-5">
                        <div class="flex items-center gap-2 text-muted-foreground"><i data-lucide="{{ $icon }}" class="h-4 w-4"></i><span class="text-[9px] font-semibold uppercase tracking-[.14em]">{{ $label }}</span></div>
                        <p class="mt-2 text-sm font-semibold">{{ $value }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <div class="mt-5 grid gap-5 xl:grid-cols-[1.35fr_.65fr]">
            <div class="space-y-5">
                <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="ui-panel p-5 sm:p-6">
                    @csrf
                    @method('patch')

                    <div class="flex items-start justify-between gap-4 border-b border-border pb-4">
                        <div><p class="ui-kicker">Identity</p><h2 class="mt-1 text-lg font-semibold">Personal information</h2><p class="mt-1 text-xs text-muted-foreground">Keep your customer identity and account preferences current.</p></div>
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-muted"><i data-lucide="user-round" class="h-4 w-4"></i></span>
                    </div>

                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <div><label for="name" class="ui-label">Full name</label><input id="name" name="name" value="{{ old('name',$user->name) }}" class="ui-input mt-1 w-full" required>@error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <div><label for="email" class="ui-label">Email address</label><input id="email" type="email" name="email" value="{{ old('email',$user->email) }}" class="ui-input mt-1 w-full" required>@error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <div><label for="country" class="ui-label">Country</label><select id="country" name="country" class="ui-input mt-1 w-full"><option value="">Select country</option></select>@error('country')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <div><label for="currency" class="ui-label">Preferred currency</label><select id="currency" name="currency" class="ui-input mt-1 w-full">
                            @foreach(['USD'=>'US Dollar','EUR'=>'Euro','GBP'=>'British Pound','JPY'=>'Japanese Yen','AUD'=>'Australian Dollar','CAD'=>'Canadian Dollar','CHF'=>'Swiss Franc','CNY'=>'Chinese Yuan','INR'=>'Indian Rupee','NGN'=>'Nigerian Naira','ZAR'=>'South African Rand','SGD'=>'Singapore Dollar','HKD'=>'Hong Kong Dollar','NZD'=>'New Zealand Dollar','AED'=>'UAE Dirham'] as $code=>$label)
                                <option value="{{ $code }}" @selected(old('currency',$user->currency)===$code)>{{ $code }} · {{ $label }}</option>
                            @endforeach
                        </select>@error('currency')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    </div>

                    <div class="mt-5 rounded-2xl border border-border bg-muted/15 p-4">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                            <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-border bg-background text-lg font-semibold">
                                @if($user->profile_image)<img id="image-preview" src="{{ asset('storage/'.$user->profile_image) }}" alt="{{ $user->name }}" class="h-full w-full object-cover">@else<span id="image-preview-text">{{ strtoupper(substr($user->name,0,1)) }}</span>@endif
                            </div>
                            <div class="min-w-0 flex-1"><label for="profile_image" class="ui-label">Profile image</label><input id="profile_image" type="file" name="profile_image" accept="image/*" class="ui-input mt-1 w-full" onchange="previewProfileImage(this)"><p class="mt-1 text-[10px] text-muted-foreground">JPG, PNG or GIF · maximum 2 MB.</p>@error('profile_image')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        </div>
                    </div>

                    <div class="mt-5 flex flex-col gap-3 border-t border-border pt-4 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-[10px] text-muted-foreground">Last profile update {{ $user->updated_at?->format('M j, Y · H:i') }}</p>
                        <button class="ui-btn ui-btn-primary justify-center"><i data-lucide="save" class="h-4 w-4"></i>Save profile</button>
                    </div>
                </form>

                <section class="ui-panel p-5 sm:p-6">
                    <div class="flex items-start justify-between gap-4 border-b border-border pb-4">
                        <div><p class="ui-kicker">Access</p><h2 class="mt-1 text-lg font-semibold">Memberships</h2><p class="mt-1 text-xs text-muted-foreground">Commercial access attached to your customer account.</p></div>
                        <a href="{{ route('memberships.index') }}" class="ui-btn ui-btn-secondary !h-8"><i data-lucide="settings" class="h-3.5 w-3.5"></i>Manage</a>
                    </div>
                    <div class="mt-4 space-y-2">
                        @forelse($membershipStatuses as $membership)
                            @php
                                $membershipType = $membership->plan?->type;
                                $membershipDisplayStatus = $membership->status === 'active' && ! $membership->is_active ? 'expired' : $membership->status;
                            @endphp
                            <div class="flex flex-col gap-3 rounded-xl border border-border bg-muted/15 p-4 sm:flex-row sm:items-center sm:justify-between">
                                <div><div class="flex flex-wrap items-center gap-2"><p class="text-sm font-semibold">{{ $membershipType?->name ?? 'Membership' }}</p><span class="rounded-full px-2 py-0.5 text-[9px] font-semibold uppercase {{ $membership->is_active ? 'bg-emerald-500/10 text-emerald-600' : 'bg-muted text-muted-foreground' }}">{{ $membershipDisplayStatus }}</span></div><p class="mt-1 text-xs text-muted-foreground">{{ $membership->plan?->name ?? 'Plan unavailable' }}@if($membership->is_active && $membership->ends_at) · until {{ $membership->ends_at->format('M j, Y') }}@endif</p></div>
                                <span class="text-[10px] text-muted-foreground">{{ $membership->is_active ? $membership->plan?->entitlements?->where('enabled',true)->count().' privileges' : 'Inactive' }}</span>
                            </div>
                        @empty
                            <div class="rounded-xl border border-dashed border-border p-5 text-sm text-muted-foreground">No membership history is attached to this account yet.</div>
                        @endforelse
                    </div>
                </section>
            </div>

            <aside class="space-y-5">
                <section class="ui-panel p-5">
                    <p class="ui-kicker">Verification</p><h2 class="mt-1 text-base font-semibold">Identity status</h2>
                    <div class="mt-4 rounded-xl border border-border bg-muted/15 p-4"><div class="flex items-center justify-between gap-3"><div><p class="text-xs font-semibold">KYC verification</p><p class="mt-1 text-[10px] text-muted-foreground">{{ $kyc ? $kycLabel : 'Submit identity documents to complete verification.' }}</p></div><span class="flex h-9 w-9 items-center justify-center rounded-xl {{ $kyc?->isApproved() ? 'bg-emerald-500/10 text-emerald-600' : 'bg-muted text-muted-foreground' }}"><i data-lucide="shield-check" class="h-4 w-4"></i></span></div><a href="{{ route('profile.kyc') }}" class="ui-btn ui-btn-secondary mt-4 w-full justify-center">Open KYC</a></div>
                </section>

                <form method="POST" action="{{ route('password.update') }}" class="ui-panel p-5">
                    @csrf
                    @method('put')
                    <p class="ui-kicker">Security</p><h2 class="mt-1 text-base font-semibold">Change password</h2><p class="mt-1 text-xs text-muted-foreground">Password changes use the dedicated authenticated security authority.</p>
                    <div class="mt-4 space-y-3">
                        <div><label class="ui-label" for="current_password">Current password</label><input class="ui-input mt-1 w-full" id="current_password" name="current_password" type="password" autocomplete="current-password">@error('current_password','updatePassword')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <div><label class="ui-label" for="password">New password</label><input class="ui-input mt-1 w-full" id="password" name="password" type="password" autocomplete="new-password">@error('password','updatePassword')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <div><label class="ui-label" for="password_confirmation">Confirm password</label><input class="ui-input mt-1 w-full" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"></div>
                    </div>
                    <button class="ui-btn ui-btn-secondary mt-4 w-full justify-center"><i data-lucide="key-round" class="h-4 w-4"></i>Update password</button>
                </form>

                <section class="ui-panel border-red-500/15 p-5">
                    <p class="ui-kicker text-red-600">Danger zone</p><h2 class="mt-1 text-base font-semibold">Delete account</h2><p class="mt-1 text-xs leading-5 text-muted-foreground">Permanently removes your customer account. Your current password is required.</p>
                    <form method="POST" action="{{ route('profile.destroy') }}" class="mt-4" onsubmit="return confirm('Permanently delete this account? This action cannot be undone.');">
                        @csrf
                        @method('delete')
                        <label class="ui-label" for="delete_password">Current password</label>
                        <input id="delete_password" name="password" type="password" class="ui-input mt-1 w-full" autocomplete="current-password">
                        @error('password','userDeletion')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        <button class="ui-btn mt-3 w-full justify-center border border-red-500/25 bg-red-500/10 text-red-600 hover:bg-red-500/15"><i data-lucide="trash-2" class="h-4 w-4"></i>Delete account</button>
                    </form>
                </section>
            </aside>
        </div>
    </div>

    <script>
        function previewProfileImage(input) {
            if (!input.files || !input.files[0]) return;
            const reader = new FileReader();
            reader.onload = function (event) {
                const holder = document.getElementById('image-preview');
                const text = document.getElementById('image-preview-text');
                if (holder) {
                    holder.src = event.target.result;
                    return;
                }
                if (text) {
                    const image = document.createElement('img');
                    image.id = 'image-preview';
                    image.src = event.target.result;
                    image.alt = 'Profile preview';
                    image.className = 'h-full w-full object-cover';
                    text.parentNode.replaceChild(image, text);
                }
            };
            reader.readAsDataURL(input.files[0]);
        }

        fetch('/countries.json')
            .then(function (response) { return response.json(); })
            .then(function (countries) {
                const select = document.getElementById('country');
                const current = @json(old('country', $user->country));
                countries.forEach(function (country) {
                    const option = document.createElement('option');
                    option.value = country.code;
                    option.textContent = country.name;
                    if (country.code === current) option.selected = true;
                    select.appendChild(option);
                });
            })
            .catch(function () {});
    </script>
</x-user-layout>
