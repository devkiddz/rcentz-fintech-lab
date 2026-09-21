@php
    $localization = app(\App\Services\LocalizationService::class);
    $languageOptions = $localization->enabledLanguages();
    $activeLocale = app()->getLocale();
    $activeLanguage = $languageOptions->firstWhere('code', $activeLocale);
@endphp
@if($languageOptions->count() > 1)
<details class="relative" data-language-switcher>
    <summary class="list-none cursor-pointer inline-flex h-9 items-center gap-1.5 rounded-lg border border-border bg-card px-2.5 text-[10px] font-semibold text-muted-foreground transition hover:bg-muted hover:text-foreground" title="{{ localize('ui.language', 'Language') }}">
        <i data-lucide="languages" class="h-3.5 w-3.5"></i>
        <span class="uppercase">{{ $activeLanguage?->code ?? strtoupper($activeLocale) }}</span>
        <i data-lucide="chevron-down" class="h-3 w-3"></i>
    </summary>
    <div class="absolute right-0 z-[90] mt-2 w-56 overflow-hidden rounded-xl border border-border bg-card p-1.5 shadow-2xl">
        @foreach($languageOptions as $language)
            <form method="POST" action="{{ route('locale.update') }}">
                @csrf
                <input type="hidden" name="locale" value="{{ $language->code }}">
                <button type="submit" class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-xs transition hover:bg-muted {{ $language->code === $activeLocale ? 'bg-muted font-semibold text-foreground' : 'text-muted-foreground' }}">
                    <span class="min-w-0"><span class="block truncate">{{ $language->native_name }}</span><span class="block truncate text-[9px] opacity-65">{{ $language->name }}</span></span>
                    @if($language->code === $activeLocale)<i data-lucide="check" class="h-3.5 w-3.5 shrink-0"></i>@endif
                </button>
            </form>
        @endforeach
    </div>
</details>
@endif
