<?php

namespace App\Console\Commands;

use App\Models\ForexPair;
use App\Models\MarketInstrument;
use App\Services\ForexMarketDataService;
use App\Services\ForexSessionService;
use Illuminate\Console\Command;

class InspectForexMarket extends Command
{
    protected $signature = 'forex:inspect';
    protected $description = 'Inspect the forex pair catalog, generic MarketInstrument registry, stored spot rates, sessions and OHLC readiness.';

    public function handle(ForexMarketDataService $marketData, ForexSessionService $sessions): int
    {
        $pairs = ForexPair::query()->withCount('candles')->orderBy('id')->get();

        $this->table(
            ['Pair', 'Spot', 'Candles', 'Feed', 'Session', 'Preferred', 'Featured'],
            $pairs->map(function (ForexPair $pair) use ($marketData, $sessions) {
                $rate = $marketData->currentRate($pair);
                $session = $sessions->stateForPair($pair);

                return [
                    $pair->display_symbol,
                    $rate ? number_format($rate, (int) $pair->price_precision, '.', '') : '-',
                    $pair->candles_count,
                    $pair->candles_count >= 20 ? 'READY' : 'NEEDS_HISTORY',
                    strtoupper(str_replace('_', ' ', $session['label'])),
                    implode(', ', (array) $pair->preferred_sessions),
                    $pair->is_featured ? 'YES' : 'NO',
                ];
            })->all()
        );

        $stockInstruments = MarketInstrument::query()->assetClass(MarketInstrument::ASSET_STOCK)->count();
        $forexInstruments = MarketInstrument::query()->assetClass(MarketInstrument::ASSET_FOREX)->count();

        $this->line("Market instruments: stock {$stockInstruments} | forex {$forexInstruments}");

        if ($pairs->isEmpty() || $forexInstruments !== $pairs->count()) {
            $this->error('FOREX_FX1_FOUNDATION_FAILED');
            return self::FAILURE;
        }

        $this->info('FOREX_FX1_FOUNDATION_OK');
        return self::SUCCESS;
    }
}
