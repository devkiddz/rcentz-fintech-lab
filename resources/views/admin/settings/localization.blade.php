<x-admin-layout>
<div class="ui-page max-w-[1500px]">
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker">Admin · Localization</p>
            <h1 class="ui-heading">Language & Localization</h1>
            <p class="ui-lead max-w-3xl">The platform owns its language registry and translation catalogue. Keep the public selector focused while retaining a broader installed language library underneath.</p>
        </div>
        <form method="POST" action="{{ route('admin.settings.localization.sync') }}">
            @csrf
            <button class="ui-btn ui-btn-secondary"><i data-lucide="refresh-cw" class="h-4 w-4"></i>{{ localize('ui.settings.sync', 'Sync bundled language packs') }}</button>
        </form>
    </section>

    @include('admin.settings.partials.flash')

    <section class="ui-panel mb-4 overflow-hidden">
        <div class="grid gap-px bg-border sm:grid-cols-[1fr_auto]">
            <div class="bg-background p-5">
                <p class="ui-kicker">Protected terminology</p>
                <h2 class="mt-1 text-base font-semibold">Financial glossary authority</h2>
                <p class="mt-1 max-w-3xl text-xs leading-5 text-muted-foreground">Trading, wallet, signal and investment surfaces resolve sensitive financial terms from one canonical glossary so the same concept is translated consistently everywhere.</p>
            </div>
            <div class="flex min-w-40 items-center justify-center bg-background p-5 text-center">
                <div><p class="text-2xl font-semibold tabular-nums">{{ app(\App\Services\FinancialTerminologyService::class)->count() }}</p><p class="mt-1 text-[9px] font-semibold uppercase tracking-[.12em] text-muted-foreground">protected terms</p></div>
            </div>
        </div>
    </section>

    <form method="POST" action="{{ route('admin.settings.localization.update') }}" class="ui-panel overflow-hidden">
        @csrf
        @method('patch')

        <div class="grid gap-px bg-border lg:grid-cols-[1.1fr_.9fr]">
            <div class="bg-background p-5 sm:p-6">
                <p class="ui-kicker">Presentation</p>
                <h2 class="mt-1 text-base font-semibold">{{ localize('ui.settings.active_languages', 'Active languages') }}</h2>
                <p class="mt-1 text-xs text-muted-foreground">Only enabled languages appear to customers. The complete registry remains available here for future markets.</p>
            </div>
            <div class="bg-background p-5 sm:p-6">
                <label for="default_locale" class="ui-label">{{ localize('ui.settings.default_language', 'Default language') }}</label>
                <select id="default_locale" name="default_locale" class="ui-input mt-2 w-full">
                    @foreach($languages->where('is_enabled', true) as $language)
                        <option value="{{ $language->code }}" @selected($language->code === $defaultLocale)>{{ $language->native_name }} · {{ $language->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="border-t border-border">
            <div class="grid gap-px bg-border md:grid-cols-2 xl:grid-cols-3">
                @foreach($languages as $language)
                    <label class="bg-background p-4 transition hover:bg-muted/20">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="truncate text-sm font-semibold">{{ $language->native_name }}</p>
                                    @if($language->is_major)<span class="rounded-full bg-sky-500/10 px-2 py-0.5 text-[8px] font-semibold uppercase text-sky-600">Major</span>@endif
                                    @if($language->direction === 'rtl')<span class="rounded-full bg-violet-500/10 px-2 py-0.5 text-[8px] font-semibold uppercase text-violet-600">RTL</span>@endif
                                </div>
                                <p class="mt-1 text-[10px] text-muted-foreground">{{ $language->name }} · {{ $language->code }}</p>
                            </div>
                            <input type="checkbox" name="enabled_locales[]" value="{{ $language->code }}" class="h-4 w-4 rounded border-border" @checked($language->is_enabled)>
                        </div>
                        <div class="mt-4 flex items-center gap-3">
                            <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-muted"><div class="h-full rounded-full" style="width:{{ min(100,$language->completeness) }}%;background:var(--brand-primary)"></div></div>
                            <span class="w-10 text-right text-[9px] font-semibold text-muted-foreground">{{ number_format($language->completeness, 0) }}%</span>
                        </div>
                        <p class="mt-1 text-[9px] text-muted-foreground">{{ $language->translation_count }}/{{ $language->translation_total }} core strings translated</p>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="flex flex-col gap-3 border-t border-border bg-muted/10 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-[10px] text-muted-foreground">Disabled languages stay installed but never appear in the customer selector. Missing strings safely fall back to English.</p>
            <button class="ui-btn ui-btn-primary"><i data-lucide="save" class="h-4 w-4"></i>{{ localize('ui.settings.save', 'Save changes') }}</button>
        </div>
    </form>
</div>
</x-admin-layout>
