<?php

namespace App\Console\Commands;

use App\Models\Language;
use App\Models\LanguageTranslation;
use App\Services\LocalizationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class InspectLocalization extends Command
{
    protected $signature = 'localization:inspect';
    protected $description = 'Inspect localization bundle registration, enabled language coverage and protected financial terminology health.';

    public function handle(LocalizationService $localization): int
    {
        foreach (['languages', 'language_translations'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->error("Missing required localization table: {$table}");
                return self::FAILURE;
            }
        }

        $configuredBundles = collect(config('localization.bundle_files', ['localization_core.php']))
            ->map(fn ($file) => basename((string) $file))
            ->filter()
            ->unique()
            ->values();

        $discoveredBundles = collect(glob(database_path('seeders/data/localization_*.php')) ?: [])
            ->map(fn ($path) => basename((string) $path))
            ->sort()
            ->values();

        $missingConfiguredBundles = $configuredBundles
            ->filter(fn ($file) => ! is_file(database_path('seeders/data/'.$file)))
            ->values();
        $unregisteredBundles = $discoveredBundles->diff($configuredBundles)->values();

        $bundledSource = [];
        $sourceConflicts = [];
        foreach ($configuredBundles as $file) {
            $path = database_path('seeders/data/'.$file);
            if (! is_file($path)) continue;

            $bundle = require $path;
            foreach (($bundle['source'] ?? []) as $identity => $sourceText) {
                if (array_key_exists($identity, $bundledSource) && $bundledSource[$identity] !== $sourceText) {
                    $sourceConflicts[] = $identity;
                    continue;
                }
                $bundledSource[$identity] = $sourceText;
            }
        }

        $languages = Language::query()->count();
        $enabled = Language::query()->where('is_enabled', true)->count();
        $defaults = Language::query()->where('is_default', true)->count();
        $translations = LanguageTranslation::query()->count();
        $missing = LanguageTranslation::query()->where(function ($q) {
            $q->whereNull('translated_text')->orWhere('translated_text', '');
        })->count();

        $bundledSourceCount = count($bundledSource);
        $englishSourceRows = LanguageTranslation::query()
            ->whereHas('language', fn ($q) => $q->where('code', 'en'))
            ->count();
        $enabledTranslated = LanguageTranslation::query()
            ->whereHas('language', fn ($q) => $q->where('is_enabled', true))
            ->whereNotNull('translated_text')
            ->where('translated_text', '!=', '')
            ->count();
        $enabledExpected = $bundledSourceCount * $enabled;
        $enabledMissing = max(0, $enabledExpected - $enabledTranslated);

        $financialTerms = count(config('financial-terminology.terms', []));
        $financialEnglish = LanguageTranslation::query()
            ->whereHas('language', fn ($q) => $q->where('code', 'en'))
            ->where('group', 'finance')
            ->whereNotNull('translated_text')
            ->where('translated_text', '!=', '')
            ->count();
        $financialEnabledTranslated = LanguageTranslation::query()
            ->whereHas('language', fn ($q) => $q->where('is_enabled', true))
            ->where('group', 'finance')
            ->whereNotNull('translated_text')
            ->where('translated_text', '!=', '')
            ->count();
        $financialExpectedEnabled = $financialTerms * $enabled;
        $financialMissingEnabled = max(0, $financialExpectedEnabled - $financialEnabledTranslated);

        $this->table(['Authority', 'Value'], [
            ['Registered languages', $languages],
            ['Enabled languages', $enabled],
            ['Default language rows', $defaults],
            ['Configured bundle files', $configuredBundles->count()],
            ['Unregistered bundle files', $unregisteredBundles->count()],
            ['Missing configured bundle files', $missingConfiguredBundles->count()],
            ['Bundled source identities', $bundledSourceCount],
            ['English source rows', $englishSourceRows],
            ['Translation catalogue rows', $translations],
            ['Missing translation rows', $missing],
            ['Enabled translations missing', $enabledMissing],
            ['Protected financial terms', $financialTerms],
            ['Financial source terms', $financialEnglish],
            ['Enabled financial translations missing', $financialMissingEnabled],
            ['Resolved default', $localization->defaultCode()],
        ]);

        if ($missingConfiguredBundles->isNotEmpty()) {
            $this->error('Configured localization bundle files are missing: '.$missingConfiguredBundles->implode(', '));
            return self::FAILURE;
        }

        if ($unregisteredBundles->isNotEmpty()) {
            $this->error('Localization bundle files exist but are not registered: '.$unregisteredBundles->implode(', '));
            return self::FAILURE;
        }

        if ($sourceConflicts !== []) {
            $this->error('Conflicting bundled localization identities: '.implode(', ', array_unique($sourceConflicts)));
            return self::FAILURE;
        }

        if ($languages < 7 || $enabled < 1 || $defaults !== 1 || $translations < 1 || $bundledSourceCount < 1) {
            $this->error('Localization authority is incomplete.');
            return self::FAILURE;
        }

        if ($englishSourceRows !== $bundledSourceCount || $enabledMissing !== 0) {
            $this->error('Localization catalogue is not synchronized for the enabled language set. Run LocalizationSeeder.');
            return self::FAILURE;
        }

        if ($financialTerms < 1 || $financialEnglish !== $financialTerms || $financialMissingEnabled !== 0) {
            $this->error('Protected financial terminology is incomplete for the enabled language set.');
            return self::FAILURE;
        }

        $this->info('LOCALIZATION_BUNDLE_REGISTRY_OK');
        $this->info('FINANCIAL_TERMINOLOGY_OK');
        $this->info('LOCALIZATION_AUTHORITY_OK');
        return self::SUCCESS;
    }
}
