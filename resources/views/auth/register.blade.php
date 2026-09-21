<x-guest-layout>
    <x-slot name="title">{{ localize('ui.r2e.common.create_account', 'Create Account') }}</x-slot>
    <x-slot name="wide">true</x-slot>

    <div class="mx-auto max-w-4xl">
        <section class="overflow-hidden rounded-3xl border border-border bg-card shadow-sm">
            <div class="border-b border-border bg-gradient-to-br from-foreground to-foreground/90 px-6 py-8 text-background sm:px-10">
                <div class="flex items-start justify-between gap-6">
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-[.22em] text-background/60">{{ localize('ui.r2e.register.account_suffix', ':site Account', ['site' => site_name()]) }}</p>
                        <h1 class="mt-2 text-2xl font-semibold">{{ localize('ui.r2e.register.hero', 'Build your account') }}</h1>
                        <p class="mt-2 max-w-xl text-sm leading-6 text-background/70">{{ localize('ui.r2e.register.hero_lead', 'Three short steps. Optional profile details can be skipped.') }}</p>
                    </div>
                    <div class="hidden h-12 w-12 items-center justify-center rounded-full bg-tesla-600 text-lg font-bold text-white sm:flex">{{ strtoupper(substr(trim(site_name()), 0, 1)) ?: 'F' }}</div>
                </div>
                <div class="mt-7 grid grid-cols-3 gap-2">
                    @foreach([1=>localize('ui.r2e.common.identity','Identity'),2=>localize('ui.r2e.common.profile','Profile'),3=>localize('ui.r2e.common.security','Security')] as $step=>$label)
                        <div class="registration-progress rounded-xl border border-background/10 bg-background/5 px-3 py-2" data-progress="{{ $step }}">
                            <p class="text-[9px] uppercase tracking-[.16em] text-background/50">{{ localize('ui.r2e.common.step', 'Step :step', ['step' => $step]) }}</p><p class="mt-1 text-xs font-semibold">{{ $label }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            @if(session('error'))<div class="mx-6 mt-6 rounded-xl border border-red-500/20 bg-red-500/10 p-4 text-sm text-red-600 sm:mx-10">{{ session('error') }}</div>@endif
            @if($errors->any())<div class="mx-6 mt-6 rounded-xl border border-red-500/20 bg-red-500/10 p-4 text-sm text-red-600 sm:mx-10">{{ $errors->first() }}</div>@endif

            <form id="registration-form" method="POST" action="{{ route('register') }}" class="px-6 py-7 sm:px-10">
                @csrf

                <section class="registration-step space-y-5" data-step="1">
                    <div><p class="ui-kicker">{{ localize('ui.r2e.register.account_identity', 'Account identity') }}</p><h2 class="mt-1 text-xl font-semibold">{{ localize('ui.r2e.register.essentials', 'Start with the essentials') }}</h2><p class="mt-1 text-sm text-muted-foreground">{{ localize('ui.r2e.register.essentials_help', 'Only your name, email and 18+ confirmation are required here.') }}</p></div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2"><label class="ui-label">{{ localize('ui.r2e.common.full_name', 'Full name') }}</label><input class="ui-input mt-1 w-full" name="name" value="{{ old('name') }}" autocomplete="name" required></div>
                        <div class="sm:col-span-2"><label class="ui-label">{{ localize('ui.r2e.common.email_address', 'Email address') }}</label><input class="ui-input mt-1 w-full" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required></div>
                        <div><label class="ui-label">{{ localize('ui.r2e.common.date_of_birth', 'Date of birth') }} <span class="font-normal text-muted-foreground">({{ localize('ui.r2e.common.optional', 'optional') }})</span></label><input class="ui-input mt-1 w-full" name="date_of_birth" type="date" max="{{ now()->subYears(18)->toDateString() }}" value="{{ old('date_of_birth') }}"><p class="mt-1 text-[10px] text-muted-foreground">{{ localize('ui.r2e.register.dob_help', 'If supplied, it must confirm age 18+.') }}</p></div>
                        <div><label class="ui-label">{{ localize('ui.r2e.common.country', 'Country') }} <span class="font-normal text-muted-foreground">({{ localize('ui.r2e.common.optional', 'optional') }})</span></label><input class="ui-input mt-1 w-full" name="country" value="{{ old('country') }}" placeholder="{{ localize('ui.r2e.register.country_example', 'e.g. Nigeria') }}"></div>
                        <label class="sm:col-span-2 flex items-start gap-3 rounded-xl border border-border bg-muted/30 p-4">
                            <input type="checkbox" name="age_confirmed" value="1" class="mt-0.5" required @checked(old('age_confirmed'))>
                            <span><span class="block text-sm font-semibold">{{ localize('ui.r2e.register.age_confirm', 'I confirm that I am 18 years or older.') }}</span><span class="mt-1 block text-xs text-muted-foreground">{{ localize('ui.r2e.register.age_help', 'This confirmation is required even when you choose not to provide your date of birth.') }}</span></span>
                        </label>
                    </div>
                </section>

                <section class="registration-step hidden space-y-5" data-step="2">
                    <div><p class="ui-kicker">{{ localize('ui.r2e.register.optional_profile', 'Optional profile') }}</p><h2 class="mt-1 text-xl font-semibold">{{ localize('ui.r2e.register.tell_more', 'Tell us more — only if you want') }}</h2><p class="mt-1 text-sm text-muted-foreground">{{ localize('ui.r2e.register.optional_help', 'Employment and education are optional profile details. You can leave them blank.') }}</p></div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label class="ui-label">{{ localize('ui.r2e.register.occupation', 'Working class / occupation') }} <span class="font-normal text-muted-foreground">({{ localize('ui.r2e.common.optional', 'optional') }})</span></label><select class="ui-input mt-1 w-full" name="employment_class"><option value="">{{ localize('ui.r2e.register.prefer_not', 'Prefer not to say') }}</option>@foreach(['student'=>'Student','employed'=>'Employed','self_employed'=>'Self-employed','business_owner'=>'Business owner','professional'=>'Professional','freelancer'=>'Freelancer','unemployed'=>'Unemployed','retired'=>'Retired','other'=>'Other'] as $v=>$l)<option value="{{ $v }}" @selected(old('employment_class')===$v)>{{ $l }}</option>@endforeach</select></div>
                        <div><label class="ui-label">{{ localize('ui.r2e.register.education', 'Education level') }} <span class="font-normal text-muted-foreground">({{ localize('ui.r2e.common.optional', 'optional') }})</span></label><select class="ui-input mt-1 w-full" name="education_level"><option value="">{{ localize('ui.r2e.register.prefer_not', 'Prefer not to say') }}</option>@foreach(['secondary'=>'Secondary / High school','diploma'=>'Diploma / Technical','undergraduate'=>'Undergraduate','bachelor'=>'Bachelor degree','postgraduate'=>'Postgraduate','masters'=>'Masters','doctorate'=>'Doctorate / PhD','professional'=>'Professional qualification','other'=>'Other'] as $v=>$l)<option value="{{ $v }}" @selected(old('education_level')===$v)>{{ $l }}</option>@endforeach</select></div>
                        <div class="sm:col-span-2"><label class="ui-label">{{ localize('ui.r2e.register.display_currency', 'Display currency') }}</label><select class="ui-input mt-1 w-full" name="currency">@foreach(['USD'=>'US Dollar','NGN'=>'Nigerian Naira','EUR'=>'Euro','GBP'=>'British Pound','CAD'=>'Canadian Dollar','AUD'=>'Australian Dollar','CHF'=>'Swiss Franc','JPY'=>'Japanese Yen','CNY'=>'Chinese Yuan','INR'=>'Indian Rupee','ZAR'=>'South African Rand','SGD'=>'Singapore Dollar'] as $code=>$label)<option value="{{ $code }}" @selected(old('currency','USD')===$code)>{{ $code }} — {{ $label }}</option>@endforeach</select><p class="mt-1 text-[10px] text-muted-foreground">{{ localize('ui.r2e.register.currency_help', 'Change this anytime from your dashboard. Base financial records remain USD.') }}</p></div>
                    </div>
                </section>

                <section class="registration-step hidden space-y-5" data-step="3">
                    <div><p class="ui-kicker">{{ localize('ui.r2e.common.security', 'Security') }}</p><h2 class="mt-1 text-xl font-semibold">{{ localize('ui.r2e.register.protect', 'Protect your account') }}</h2></div>
                    <div class="grid gap-4 sm:grid-cols-2"><div><label class="ui-label">{{ localize('ui.r2e.common.password', 'Password') }}</label><input class="ui-input mt-1 w-full" name="password" type="password" autocomplete="new-password" required></div><div><label class="ui-label">{{ localize('ui.r2e.common.confirm_password', 'Confirm password') }}</label><input class="ui-input mt-1 w-full" name="password_confirmation" type="password" autocomplete="new-password" required></div></div>
                </section>

                <div class="mt-7 flex items-center justify-between border-t border-border pt-5"><button id="registration-back" type="button" class="ui-btn ui-btn-secondary invisible">{{ localize('ui.r2e.common.back', 'Back') }}</button><div class="ml-auto flex gap-2"><button id="registration-next" type="button" class="ui-btn ui-btn-primary">{{ localize('ui.r2e.common.continue', 'Continue') }}</button><button id="registration-submit" type="submit" class="ui-btn ui-btn-primary hidden">{{ localize('ui.r2e.common.create_account', 'Create Account') }}</button></div></div>
            </form>

            <div class="border-t border-border bg-muted/20 px-6 py-4 text-center text-xs text-muted-foreground sm:px-10">{{ localize('ui.r2e.register.already', 'Already registered?') }} <a href="{{ route('login') }}" class="font-semibold text-tesla-600">{{ localize('ui.r2e.common.sign_in', 'Sign in') }}</a></div>
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            let step = 1;
            const steps = [...document.querySelectorAll('.registration-step')];
            const progress = [...document.querySelectorAll('.registration-progress')];
            const back = document.getElementById('registration-back');
            const next = document.getElementById('registration-next');
            const submit = document.getElementById('registration-submit');
            function paint(){steps.forEach(n=>n.classList.toggle('hidden',Number(n.dataset.step)!==step));progress.forEach(n=>{const active=Number(n.dataset.progress)<=step;n.classList.toggle('ring-1',active);n.classList.toggle('ring-tesla-500/60',active);n.classList.toggle('bg-background/10',active)});back.classList.toggle('invisible',step===1);next.classList.toggle('hidden',step===3);submit.classList.toggle('hidden',step!==3)}
            function validateCurrent(){const current=document.querySelector(`.registration-step[data-step="${step}"]`);for(const input of [...current.querySelectorAll('input,select')]){if(!input.checkValidity()){input.reportValidity();return false}}return true}
            next.addEventListener('click',()=>{if(!validateCurrent())return;step=Math.min(3,step+1);paint()});back.addEventListener('click',()=>{step=Math.max(1,step-1);paint()});paint();
        });
    </script>
</x-guest-layout>
