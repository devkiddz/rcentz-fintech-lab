<?php

namespace App\Console\Commands;

use App\Models\ControlledMarketInstrument;
use App\Models\CryptoPair;
use App\Services\MarketInstrumentAnalysisService;
use App\Services\MarketPriceRouter;
use Illuminate\Console\Command;

class InspectCryptoRuntime extends Command
{
    protected $signature = 'markets:inspect-crypto-runtime {symbol? : Optional crypto symbol such as BTCUSD}';
    protected $description = 'Verify feed-ready Crypto parents own Live, Controlled and shared analysis authority while incomplete feeds remain inactive.';

    public function handle(MarketPriceRouter $prices, MarketInstrumentAnalysisService $analysis): int
    {
        $query = CryptoPair::query()->with('marketInstrument')->orderBy('id');

        if ($symbol = $this->argument('symbol')) {
            $normalized = strtoupper(str_replace(['/', '-', '_', ' '], '', (string) $symbol));
            $query->where('symbol', $normalized);
        }

        $pairs = $query->get();
        if ($pairs->isEmpty()) {
            $this->error('No matching crypto pairs were found.');
            return self::FAILURE;
        }

        $readyFeeds = 0;
        $foundationFeeds = 0;
        $readyInactive = 0;
        $incompleteActive = 0;
        $missingControlled = 0;
        $priceFailures = 0;
        $analysisFailures = 0;
        $contractFailures = 0;
        $rows = [];

        foreach ($pairs as $pair) {
            $parent = $pair->marketInstrument;
            $dailyCount = $pair->candles()->where('interval', '1d')->count();
            $feedReady = (bool) $pair->external_feed_enabled
                && (float) ($pair->current_rate ?? 0) > 0
                && $dailyCount >= 2;

            if ($feedReady) $readyFeeds++; else $foundationFeeds++;

            if (! $parent) {
                $contractFailures++;
                $rows[] = ['—', $pair->display_symbol, $feedReady ? 'READY' : 'NEEDS_HISTORY', 'MISSING_PARENT', '—', '—', '—'];
                continue;
            }

            if ($feedReady && ! $parent->is_active) $readyInactive++;
            if (! $feedReady && $parent->is_active) $incompleteActive++;

            $controlled = ControlledMarketInstrument::query()
                ->where('market_instrument_id', $parent->id)
                ->where('is_active', true)
                ->first();

            $liveText = '—';
            $controlledText = '—';
            $analysisText = 'FOUNDATION';

            if ($feedReady && $parent->is_active) {
                if (! $controlled) {
                    $missingControlled++;
                }

                try {
                    $live = $prices->price($parent, 'live');
                    $liveText = number_format($live, (int) $parent->price_precision, '.', '');
                } catch (\Throwable $e) {
                    $priceFailures++;
                    $liveText = 'ERR';
                }

                try {
                    $controlledPrice = $prices->price($parent, 'controlled');
                    $controlledText = number_format($controlledPrice, (int) ($controlled?->decimal_precision ?? $parent->price_precision), '.', '');
                } catch (\Throwable $e) {
                    $priceFailures++;
                    $controlledText = 'ERR';
                }

                try {
                    $liveAnalysis = $analysis->forInstrument($parent, 'live');
                    $controlledAnalysis = $analysis->forInstrument($parent, 'controlled');

                    if (! $this->validContract($parent->id, $liveAnalysis, 'live')
                        || ! $this->validContract($parent->id, $controlledAnalysis, 'controlled')) {
                        $contractFailures++;
                        $analysisText = 'CONTRACT_ERR';
                    } else {
                        $analysisText = ($liveAnalysis['source'] ?? 'live').' / '.($controlledAnalysis['source'] ?? 'controlled');
                    }
                } catch (\Throwable $e) {
                    $analysisFailures++;
                    $analysisText = 'ERR';
                }
            }

            $rows[] = [
                $parent->id,
                $pair->display_symbol,
                $feedReady ? 'READY' : 'NEEDS_HISTORY',
                $parent->is_active ? 'ACTIVE' : 'FOUNDATION',
                $liveText,
                $controlledText,
                $analysisText,
            ];
        }

        $this->table(['Parent', 'Pair', 'Feed', 'Runtime', 'Live', 'Controlled', 'Analysis'], $rows);
        $this->newLine();
        $this->table(['Check', 'Count'], [
            ['Crypto pairs inspected', $pairs->count()],
            ['Ready real-history feeds', $readyFeeds],
            ['Foundation / incomplete feeds', $foundationFeeds],
            ['Ready feeds still inactive', $readyInactive],
            ['Incomplete feeds active', $incompleteActive],
            ['Missing Controlled rows', $missingControlled],
            ['Price failures', $priceFailures],
            ['Analysis failures', $analysisFailures],
            ['Contract mismatches', $contractFailures],
        ]);

        $green = $readyFeeds > 0
            && $readyInactive === 0
            && $incompleteActive === 0
            && $missingControlled === 0
            && $priceFailures === 0
            && $analysisFailures === 0
            && $contractFailures === 0;

        if (! $green) {
            $this->error('CRYPTO_C2_RUNTIME_NOT_GREEN');
            return self::FAILURE;
        }

        $this->info('CRYPTO_C2_RUNTIME_OK');
        return self::SUCCESS;
    }

    private function validContract(int $parentId, array $payload, string $marketplace): bool
    {
        return (int) ($payload['market_instrument_id'] ?? 0) === $parentId
            && (string) ($payload['asset_class'] ?? '') === 'crypto'
            && (string) ($payload['marketplace'] ?? '') === $marketplace
            && (float) ($payload['current_price'] ?? 0) > 0
            && is_array($payload['timeframes'] ?? null)
            && array_key_exists('has_chart', $payload);
    }
}
