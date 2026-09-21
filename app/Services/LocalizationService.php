<?php

namespace App\Services;

use App\Models\Language;
use App\Models\LanguageTranslation;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LocalizationService
{
    public function currentCode(): string
    {
        $configured = (string) Setting::get('default_locale', config('localization.default', 'en'));
        $session = session()->get('locale');
        $user = auth()->user();
        $candidate = $user?->locale ?: $session ?: $configured ?: config('app.locale', 'en');

        return $this->isEnabled($candidate) ? $candidate : $this->defaultCode();
    }

    public function defaultCode(): string
    {
        try {
            if ($this->tablesReady()) {
                $default = Language::query()->where('is_default', true)->value('code');
                if ($default) return (string) $default;
            }
        } catch (\Throwable) {
        }

        return (string) Setting::get('default_locale', config('localization.default', 'en'));
    }

    public function direction(?string $code = null): string
    {
        $code ??= app()->getLocale();
        try {
            if ($this->tablesReady()) {
                return Language::query()->where('code', $code)->value('direction') ?: 'ltr';
            }
        } catch (\Throwable) {
        }

        return (string) data_get(config('localization.languages'), $code.'.direction', 'ltr');
    }

    public function enabledLanguages(): Collection
    {
        try {
            if ($this->tablesReady()) {
                return Language::query()->where('is_enabled', true)->orderBy('sort_order')->get();
            }
        } catch (\Throwable) {
        }

        return collect(config('localization.languages', []))
            ->filter(fn ($meta) => (bool) ($meta['major'] ?? false))
            ->map(fn ($meta, $code) => (object) array_merge($meta, ['code' => $code, 'is_enabled' => true]))
            ->values();
    }

    public function text(string $identity, string $fallback, array $replace = [], ?string $locale = null): string
    {
        [$group, $key] = $this->splitIdentity($identity);
        $locale ??= app()->getLocale();
        $value = null;

        try {
            if ($this->tablesReady()) {
                $version = (int) Cache::get('localization:version', 1);
                $value = Cache::remember(
                    "localization:v{$version}:{$locale}:{$group}:{$key}",
                    3600,
                    function () use ($locale, $group, $key) {
                        return LanguageTranslation::query()
                            ->whereHas('language', fn ($q) => $q->where('code', $locale))
                            ->where('group', $group)
                            ->where('key', $key)
                            ->value('translated_text');
                    }
                );

                if (($value === null || $value === '') && $locale !== 'en') {
                    $value = LanguageTranslation::query()
                        ->whereHas('language', fn ($q) => $q->where('code', 'en'))
                        ->where('group', $group)
                        ->where('key', $key)
                        ->value('translated_text');
                }
            }
        } catch (\Throwable) {
            $value = null;
        }

        $value = filled($value) ? (string) $value : $fallback;
        foreach ($replace as $name => $replacement) {
            $value = str_replace(':'.$name, (string) $replacement, $value);
        }

        return $value;
    }

    public function apply(?string $code = null): string
    {
        $code ??= $this->currentCode();
        if (! $this->isEnabled($code)) $code = $this->defaultCode();
        app()->setLocale($code);
        return $code;
    }

    public function isEnabled(?string $code): bool
    {
        if (! $code) return false;
        try {
            if ($this->tablesReady()) {
                return Language::query()->where('code', $code)->where('is_enabled', true)->exists();
            }
        } catch (\Throwable) {
        }

        return (bool) data_get(config('localization.languages'), $code.'.major', false);
    }

    public function syncBundledPacks(): array
    {
        if (! $this->tablesReady()) {
            throw new \RuntimeException('Localization tables are not available. Run migrations first.');
        }

        $bundle = require database_path('seeders/data/localization_core.php');
        $languages = config('localization.languages', []);
        $majors = config('localization.major_locales', ['en']);
        $languageCount = 0;
        $translationCount = 0;

        DB::transaction(function () use ($languages, $majors, $bundle, &$languageCount, &$translationCount) {
            foreach ($languages as $code => $meta) {
                $language = Language::query()->updateOrCreate(
                    ['code' => $code],
                    [
                        'name' => $meta['name'],
                        'native_name' => $meta['native_name'],
                        'direction' => $meta['direction'] ?? 'ltr',
                        'is_major' => (bool) ($meta['major'] ?? false),
                        'sort_order' => (int) ($meta['sort_order'] ?? 100),
                        'metadata' => ['bundled_pack' => array_key_exists($code, $bundle['translations'] ?? [])],
                    ]
                );

                $languageCount++;
            }

            if (! Language::query()->where('is_default', true)->exists()) {
                Language::query()->where('code', 'en')->update(['is_default' => true]);
            }
            if (! Language::query()->where('is_enabled', true)->exists()) {
                Language::query()->whereIn('code', $majors)->update(['is_enabled' => true]);
            }

            foreach (($bundle['source'] ?? []) as $identity => $sourceText) {
                [$group, $key] = $this->splitIdentity($identity);
                foreach ($languages as $code => $_meta) {
                    $language = Language::query()->where('code', $code)->firstOrFail();
                    $translated = $bundle['translations'][$code][$identity] ?? null;
                    if ($code === 'en') $translated = $sourceText;
                    $status = $code === 'en' ? 'reviewed' : (filled($translated) ? 'generated' : 'missing');

                    $row = LanguageTranslation::query()
                        ->where('language_id', $language->id)
                        ->where('group', $group)
                        ->where('key', $key)
                        ->first();

                    if ($row?->is_locked) continue;

                    LanguageTranslation::query()->updateOrCreate(
                        ['language_id' => $language->id, 'group' => $group, 'key' => $key],
                        [
                            'source_text' => $sourceText,
                            'translated_text' => $translated,
                            'status' => $status,
                        ]
                    );
                    $translationCount++;
                }
            }
        });

        $this->clearCache();
        return ['languages' => $languageCount, 'translations' => $translationCount];
    }

    public function statusRows(): Collection
    {
        $total = LanguageTranslation::query()
            ->whereHas('language', fn ($q) => $q->where('code', 'en'))
            ->count();

        return Language::query()->orderBy('sort_order')->get()->map(function (Language $language) use ($total) {
            $translated = $language->translations()->whereNotNull('translated_text')->where('translated_text', '!=', '')->count();
            $language->setAttribute('translation_total', $total);
            $language->setAttribute('translation_count', $translated);
            $language->setAttribute('completeness', $total > 0 ? round(($translated / $total) * 100, 1) : 0.0);
            return $language;
        });
    }

    public function clearCache(): void
    {
        $nextVersion = ((int) Cache::get('localization:version', 1)) + 1;
        Cache::forever('localization:version', $nextVersion);
    }

    private function splitIdentity(string $identity): array
    {
        $parts = explode('.', $identity, 2);
        return count($parts) === 2 ? [$parts[0], $parts[1]] : ['ui', $identity];
    }

    private function tablesReady(): bool
    {
        try {
            return Schema::hasTable('languages') && Schema::hasTable('language_translations');
        } catch (\Throwable) {
            return false;
        }
    }
}
