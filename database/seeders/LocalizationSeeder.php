<?php

namespace Database\Seeders;

use App\Services\LocalizationService;
use Illuminate\Database\Seeder;

class LocalizationSeeder extends Seeder
{
    public function run(): void
    {
        app(LocalizationService::class)->syncBundledPacks();
    }
}
