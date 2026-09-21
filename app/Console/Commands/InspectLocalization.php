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
    protected $description = 'Inspect localization registry, default language and translation catalogue health.';

    public function handle(LocalizationService $localization): int
    {
        foreach (['languages', 'language_translations'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->error("Missing required localization table: {$table}");
                return self::FAILURE;
            }
        }

        $languages = Language::query()->count();
        $enabled = Language::query()->where('is_enabled', true)->count();
        $defaults = Language::query()->where('is_default', true)->count();
        $translations = LanguageTranslation::query()->count();
        $missing = LanguageTranslation::query()->where(function ($q) {
            $q->whereNull('translated_text')->orWhere('translated_text', '');
        })->count();

        $this->table(['Authority', 'Value'], [
            ['Registered languages', $languages],
            ['Enabled languages', $enabled],
            ['Default language rows', $defaults],
            ['Translation catalogue rows', $translations],
            ['Missing translation rows', $missing],
            ['Resolved default', $localization->defaultCode()],
        ]);

        if ($languages < 7 || $enabled < 1 || $defaults !== 1 || $translations < 1) {
            $this->error('Localization authority is incomplete.');
            return self::FAILURE;
        }

        $this->info('LOCALIZATION_AUTHORITY_OK');
        return self::SUCCESS;
    }
}
