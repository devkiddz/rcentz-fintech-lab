<?php

namespace App\Console\Commands;

use App\Services\TradingEngineCatalog;
use Illuminate\Console\Command;

class InspectTradingEngines extends Command
{
    protected $signature = 'trading:engines';

    protected $description = 'Show the separated Rcentz trading engines and their responsibilities.';

    public function handle(TradingEngineCatalog $catalog): int
    {
        $this->info('Rcentz trading engines');
        $this->newLine();

        $this->table(
            ['Key', 'Engine', 'Status', 'Financial mutation', 'Price source', 'Purpose'],
            collect($catalog->all())->map(fn (array $engine) => [
                $engine['key'],
                $engine['name'],
                $engine['status'],
                $engine['financial_mutation'],
                $engine['price_source'],
                $engine['purpose'],
            ])->all()
        );

        return self::SUCCESS;
    }
}
