@php
    $localization = app(\App\Services\LocalizationService::class);
    $languageOptions = $localization->enabledLanguages();
    $activeLocale = app()->getLocale();
    $activeLanguage = $languageOptions->firstWhere('code', $activeLocale);

    /*
     * Locale flags are representative visual markers only.
     * Localization authority remains the language code and language database.
     * All configured locales are mapped so a language receives its flag
     * automatically whenever it is enabled later.
     */
    $localeFlags = [
        'en' => 'gb',
        'fr' => 'fr',
        'es' => 'es',
        'pt' => 'pt',
        'ar' => 'ae',
        'zh-CN' => 'cn',
        'de' => 'de',
        'it' => 'it',
        'nl' => 'nl',
        'pl' => 'pl',
        'ro' => 'ro',
        'ru' => 'ru',
        'uk' => 'ua',
        'tr' => 'tr',
        'el' => 'gr',
        'he' => 'il',
        'fa' => 'ir',
        'hi' => 'in',
        'bn' => 'bd',
        'ur' => 'pk',
        'pa' => 'in',
        'ta' => 'in',
        'te' => 'in',
        'mr' => 'in',
        'gu' => 'in',
        'id' => 'id',
        'ms' => 'my',
        'fil' => 'ph',
        'vi' => 'vn',
        'th' => 'th',
        'ja' => 'jp',
        'ko' => 'kr',
        'sw' => 'tz',
        'ha' => 'ng',
        'yo' => 'ng',
        'ig' => 'ng',
        'af' => 'za',
        'zu' => 'za',
        'xh' => 'za',
        'am' => 'et',
        'so' => 'so',
        'ne' => 'np',
        'si' => 'lk',
        'km' => 'kh',
        'lo' => 'la',
        'my' => 'mm',
        'cs' => 'cz',
        'sk' => 'sk',
        'hu' => 'hu',
        'bg' => 'bg',
        'sr' => 'rs',
        'hr' => 'hr',
        'da' => 'dk',
        'sv' => 'se',
        'no' => 'no',
        'fi' => 'fi',
    ];

    $activeFlag = $localeFlags[$activeLocale] ?? null;
@endphp

@if($languageOptions->count() > 1)
<details class="relative" data-language-switcher>
    <summary
        class="list-none cursor-pointer inline-flex h-9 items-center gap-2 rounded-lg border border-border bg-card px-2.5 text-[10px] font-semibold text-muted-foreground transition hover:bg-muted hover:text-foreground"
        title="{{ localize('ui.language', 'Language') }}"
    >
        @if($activeFlag)
            <img
                src="{{ asset('assets/flags/'.$activeFlag.'.svg') }}"
                alt=""
                aria-hidden="true"
                class="h-3.5 w-5 rounded-[3px] object-cover shadow-sm ring-1 ring-black/5 dark:ring-white/10"
            >
        @else
            <i data-lucide="languages" class="h-3.5 w-3.5"></i>
        @endif

        <span class="uppercase">{{ $activeLanguage?->code ?? strtoupper($activeLocale) }}</span>
        <i data-lucide="chevron-down" class="h-3 w-3"></i>
    </summary>

    <div
        class="absolute z-[90] mt-2 w-60 overflow-hidden rounded-xl border border-border bg-card p-1.5 shadow-2xl"
        style="inset-inline-end:0;"
    >
        @foreach($languageOptions as $language)
            @php($flag = $localeFlags[$language->code] ?? null)

            <form method="POST" action="{{ route('locale.update') }}">
                @csrf
                <input type="hidden" name="locale" value="{{ $language->code }}">

                <button
                    type="submit"
                    lang="{{ str_replace('_', '-', $language->code) }}"
                    dir="{{ $language->direction }}"
                    class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-xs transition hover:bg-muted {{ $language->code === $activeLocale ? 'bg-muted font-semibold text-foreground' : 'text-muted-foreground' }}"
                    style="text-align:start;"
                >
                    <span class="flex h-7 w-9 shrink-0 items-center justify-center rounded-lg border border-border bg-background/70">
                        @if($flag)
                            <img
                                src="{{ asset('assets/flags/'.$flag.'.svg') }}"
                                alt=""
                                aria-hidden="true"
                                class="h-4 w-6 rounded-[3px] object-cover shadow-sm ring-1 ring-black/5 dark:ring-white/10"
                            >
                        @else
                            <i data-lucide="languages" class="h-3.5 w-3.5 text-muted-foreground"></i>
                        @endif
                    </span>

                    <span class="min-w-0 flex-1">
                        <span class="block truncate">{{ $language->native_name }}</span>
                        <span class="block truncate text-[9px] opacity-65">{{ $language->name }}</span>
                    </span>

                    @if($language->code === $activeLocale)
                        <i data-lucide="check" class="h-3.5 w-3.5 shrink-0"></i>
                    @endif
                </button>
            </form>
        @endforeach
    </div>
</details>
@endif
