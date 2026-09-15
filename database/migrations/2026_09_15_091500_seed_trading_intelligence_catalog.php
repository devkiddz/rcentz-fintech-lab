<?php

use Database\Seeders\TradingIntelligenceCatalogSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        (new TradingIntelligenceCatalogSeeder())->run();
    }

    public function down(): void
    {
        // Catalog fixtures are intentionally not deleted on rollback because
        // administrators may have edited them after installation.
    }
};
