<?php

namespace App\Console\Commands;

use App\Models\ControlledMarketInstrument;
use App\Models\MarketInstrument;
use App\Services\MarketPriceRouter;
use Illuminate\Console\Command;

class InspectMarketPriceAuthority extends Command
{
    protected $signature = 'markets:inspect-price-authority {symbol? : Optional MarketInstrument symbol}';

    protected $description = 'Inspect parent-first Live and Controlled price authority for MarketInstrument parents.';

    public function handle(MarketPriceRouter $prices): int
    {
        $query = MarketInstrument::query()->where('is_active', true)->orderBy('asset_class')->orderBy('symbol');

        if ($symbol = $this->argument('symbol')) {
            $query->where('symbol', strtoupper((string) $symbol));
        }

        $parents = $query->get();

        $rows = [];
        $liveFailures = 0;
        $controlledFailures = 0;
        $missingControlled = 0;

        foreach ($parents as $parent) {
            $controlledRow = ControlledMarketInstrument::query()
                ->where('market_instrument_id', $parent->id)
                ->first();

            if (! $controlledRow) {
                $missingControlled++;
            }

            try {
                $live = $prices->price($parent, 'live');
                $liveText = number_format($live, (int) $parent->price_precision, '.', '');
            } catch (\Throwable $e) {
                $liveFailures++;
                $liveText = 'ERR: '.$e->getMessage();
            }

            try {
                $controlled = $prices->price($parent, 'controlled');
                $controlledText = number_format($controlled, (int) ($controlledRow?->decimal_precision ?? $parent->price_precision), '.', '');
            } catch (\Throwable $e) {
                $controlledFailures++;
                $controlledText = 'ERR: '.$e->getMessage();
            }

            $rows[] = [
                $parent->id,
                strtoupper($parent->asset_class),
                $parent->display_symbol,
                $liveText,
                $controlledText,
                $controlledRow?->market_instrument_id ?: '—',
                $controlledRow?->stock_id ?: '—',
            ];
        }

        $this->table(
            ['Parent', 'Asset', 'Instrument', 'Live', 'Controlled', 'Controlled Parent', 'Stock Compat'],
            $rows
        );

        $active = MarketInstrument::query()->where('is_active', true)->count();
        $controlled = ControlledMarketInstrument::query()->where('is_active', true)->count();
        $stockParents = MarketInstrument::query()->where('asset_class', MarketInstrument::ASSET_STOCK)->count();
        $forexParents = MarketInstrument::query()->where('asset_class', MarketInstrument::ASSET_FOREX)->count();
        $cryptoParents = MarketInstrument::query()->where('asset_class', MarketInstrument::ASSET_CRYPTO)->count();
        $controlledStocks = ControlledMarketInstrument::query()->whereHas('marketInstrument', fn ($q) => $q->where('asset_class', MarketInstrument::ASSET_STOCK))->count();
        $controlledForex = ControlledMarketInstrument::query()->whereHas('marketInstrument', fn ($q) => $q->where('asset_class', MarketInstrument::ASSET_FOREX))->count();
        $controlledCrypto = ControlledMarketInstrument::query()->whereHas('marketInstrument', fn ($q) => $q->where('asset_class', MarketInstrument::ASSET_CRYPTO))->count();
        $orphanControlled = ControlledMarketInstrument::query()->whereNull('market_instrument_id')->count();

        $this->newLine();
        $this->table(
            ['Check', 'Count'],
            [
                ['Active MarketInstrument parents', $active],
                ['Controlled instruments', $controlled],
                ['Stock parents', $stockParents],
                ['Forex parents', $forexParents],
                ['Crypto parents', $cryptoParents],
                ['Controlled Stock parents', $controlledStocks],
                ['Controlled Forex parents', $controlledForex],
                ['Controlled Crypto parents', $controlledCrypto],
                ['Missing Controlled parents', $missingControlled],
                ['Orphan Controlled rows', $orphanControlled],
                ['Live price failures', $liveFailures],
                ['Controlled price failures', $controlledFailures],
            ]
        );

        $green = $parents->isNotEmpty()
            && $missingControlled === 0
            && $orphanControlled === 0
            && $liveFailures === 0
            && $controlledFailures === 0;

        if (! $green) {
            $this->error('MARKET_PRICE_AUTHORITY_M2_NOT_GREEN');
            return self::FAILURE;
        }

        $this->info('MARKET_PRICE_AUTHORITY_M2_OK');
        return self::SUCCESS;
    }
}
