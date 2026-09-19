<?php

namespace App\Console\Commands;

use App\Models\MarketInstrument;
use App\Services\MarketInstrumentAnalysisService;
use Illuminate\Console\Command;

class InspectMarketAnalysisAuthority extends Command
{
    protected $signature = 'markets:inspect-analysis-authority {symbol? : Optional MarketInstrument symbol}';

    protected $description = 'Verify MarketInstrument parents resolve the shared Live/Controlled analysis contract used by charts.';

    public function handle(MarketInstrumentAnalysisService $analysis): int
    {
        $query = MarketInstrument::query()
            ->where('is_active', true)
            ->orderBy('asset_class')
            ->orderBy('symbol');

        if ($symbol = $this->argument('symbol')) {
            $query->where('symbol', strtoupper((string) $symbol));
        }

        $parents = $query->get();
        $rows = [];
        $liveFailures = 0;
        $controlledFailures = 0;
        $contractFailures = 0;

        foreach ($parents as $parent) {
            $liveSource = 'ERR';
            $controlledSource = 'ERR';
            $livePoints = 0;
            $controlledPoints = 0;

            try {
                $live = $analysis->forInstrument($parent, 'live');
                $liveSource = (string) ($live['source'] ?? 'unknown');
                $livePoints = count($live['series'] ?? []);
                if (! $this->validContract($parent, $live, 'live')) {
                    $contractFailures++;
                    $liveSource = 'CONTRACT_ERR';
                }
            } catch (\Throwable $e) {
                $liveFailures++;
                $liveSource = 'ERR: '.$e->getMessage();
            }

            try {
                $controlled = $analysis->forInstrument($parent, 'controlled');
                $controlledSource = (string) ($controlled['source'] ?? 'unknown');
                $controlledPoints = count($controlled['series'] ?? []);
                if (! $this->validContract($parent, $controlled, 'controlled')) {
                    $contractFailures++;
                    $controlledSource = 'CONTRACT_ERR';
                }
            } catch (\Throwable $e) {
                $controlledFailures++;
                $controlledSource = 'ERR: '.$e->getMessage();
            }

            $rows[] = [
                $parent->id,
                strtoupper($parent->asset_class),
                $parent->display_symbol,
                $liveSource,
                $livePoints,
                $controlledSource,
                $controlledPoints,
            ];
        }

        $this->table(
            ['Parent', 'Asset', 'Instrument', 'Live Source', 'Live Points', 'Controlled Source', 'Controlled Points'],
            $rows
        );

        $this->newLine();
        $this->table(['Check', 'Count'], [
            ['Active parents inspected', $parents->count()],
            ['Live analysis failures', $liveFailures],
            ['Controlled analysis failures', $controlledFailures],
            ['Contract mismatches', $contractFailures],
        ]);

        $green = $parents->isNotEmpty()
            && $liveFailures === 0
            && $controlledFailures === 0
            && $contractFailures === 0;

        if (! $green) {
            $this->error('MARKET_ANALYSIS_AUTHORITY_M3_NOT_GREEN');
            return self::FAILURE;
        }

        $this->info('MARKET_ANALYSIS_AUTHORITY_M3_OK');
        return self::SUCCESS;
    }

    private function validContract(MarketInstrument $parent, array $payload, string $marketplace): bool
    {
        return (int) ($payload['market_instrument_id'] ?? 0) === (int) $parent->id
            && (string) ($payload['asset_class'] ?? '') === (string) $parent->asset_class
            && (string) ($payload['marketplace'] ?? '') === $marketplace
            && (float) ($payload['current_price'] ?? 0) > 0
            && is_array($payload['timeframes'] ?? null)
            && array_key_exists('has_chart', $payload);
    }
}
