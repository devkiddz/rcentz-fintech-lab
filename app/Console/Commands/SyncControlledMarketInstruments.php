<?php

namespace App\Console\Commands;

use App\Models\ControlledMarketInstrument;
use App\Models\MarketInstrument;
use App\Services\ControlledMarketEngine;
use App\Services\MarketPriceRouter;
use Illuminate\Console\Command;

class SyncControlledMarketInstruments extends Command
{
    protected $signature = 'markets:sync-controlled {symbol? : Optional MarketInstrument symbol} {--asset= : stock|forex|crypto|all}';

    protected $description = 'Ensure active MarketInstrument parents have Controlled/Internal price authority seeded from their current Live authority.';

    public function handle(MarketPriceRouter $prices, ControlledMarketEngine $engine): int
    {
        $asset = strtolower((string) ($this->option('asset') ?: 'all'));

        if (! in_array($asset, ['stock', 'forex', 'crypto', 'all'], true)) {
            $this->error('Asset must be stock, forex, crypto or all.');
            return self::FAILURE;
        }

        $query = MarketInstrument::query()->where('is_active', true)->orderBy('id');

        if ($asset !== 'all') {
            $query->where('asset_class', $asset);
        }

        if ($symbol = $this->argument('symbol')) {
            $query->where('symbol', strtoupper((string) $symbol));
        }

        $parents = $query->get();
        $created = 0;
        $existing = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($parents as $parent) {
            $controlled = ControlledMarketInstrument::query()
                ->where('market_instrument_id', $parent->id)
                ->first();

            if ($controlled) {
                $existing++;
                $this->line(sprintf(
                    '%-8s %-6s EXISTING %s',
                    $parent->symbol,
                    strtoupper($parent->asset_class),
                    number_format((float) $controlled->current_price, (int) $controlled->decimal_precision, '.', '')
                ));
                continue;
            }

            try {
                $live = $prices->price($parent, 'live');

                if ($live <= 0) {
                    $skipped++;
                    $this->warn($parent->symbol.' skipped: Live price unavailable.');
                    continue;
                }

                $controlled = $engine->registerMarketInstrument($parent, $parent->name, $live);
                $created++;

                $this->info(sprintf(
                    '%-8s %-6s CREATED  %s',
                    $parent->symbol,
                    strtoupper($parent->asset_class),
                    number_format((float) $controlled->current_price, (int) $controlled->decimal_precision, '.', '')
                ));
            } catch (\Throwable $e) {
                $failed++;
                $this->error($parent->symbol.' failed: '.$e->getMessage());
            }
        }

        $this->newLine();
        $this->table(
            ['Created', 'Existing', 'Skipped', 'Failed'],
            [[$created, $existing, $skipped, $failed]]
        );

        if ($failed > 0) {
            return self::FAILURE;
        }

        $this->info('MARKET_CONTROLLED_SYNC_OK');
        return self::SUCCESS;
    }
}
