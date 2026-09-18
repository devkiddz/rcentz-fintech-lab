<?php

namespace App\Console\Commands;

use App\Models\Stock;
use App\Services\MarketPriceRouter;
use App\Services\SignalIntelligenceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InspectSignalIntelligence extends Command
{
    protected $signature = 'signals:inspect-intelligence {symbol? : Optional stock symbol} {--marketplace= : live or controlled} {--timeframe= : Preferred analysis timeframe}';
    protected $description = 'Inspect deterministic Signals S2 intelligence and trade-setup construction without creating a Signal.';

    public function handle(SignalIntelligenceService $intelligence, MarketPriceRouter $router): int
    {
        $before = $this->signalRowCounts();
        $marketplace = $this->option('marketplace')
            ? $router->normalizeMarketplace((string) $this->option('marketplace'))
            : $router->activeMarketplace();

        $symbol = strtoupper(trim((string) ($this->argument('symbol') ?? '')));
        $timeframe = $this->option('timeframe') ? (string) $this->option('timeframe') : null;

        try {
            if ($symbol !== '') {
                $stock = Stock::query()->active()->where('symbol', $symbol)->firstOrFail();
                $result = $intelligence->analyzeStock($stock, $marketplace, $timeframe);
            } else {
                [$stock, $result] = $this->bestCandidate($intelligence, $marketplace, $timeframe);
            }
        } catch (\Throwable $e) {
            $this->error('Signals S2 intelligence inspection failed: '.$e->getMessage());
            return self::FAILURE;
        }

        $analysis = $result['analysis'];
        $qualification = $result['qualification'];
        $setup = $result['setup'];

        $this->table(['Intelligence', 'Value'], [
            ['Instrument', $stock->symbol],
            ['Marketplace', $result['context']['marketplace']],
            ['Timeframe', $analysis['timeframe']],
            ['Price', number_format((float) $analysis['market_price'], 8, '.', '')],
            ['Direction', strtoupper($analysis['direction'])],
            ['Directional score', number_format((float) $analysis['directional_score'], 2, '.', '')],
            ['Confluence', number_format((float) $analysis['confluence_score'], 2, '.', '').'%'],
            ['Data quality', number_format((float) $analysis['data_quality'], 2, '.', '').'%'],
            ['Qualification', strtoupper($qualification['result'])],
            ['Strength', strtoupper($qualification['strength'])],
            ['Auto generation ready', $qualification['eligible_for_auto_generation'] ? 'yes' : 'no'],
        ]);

        $componentRows = [];
        foreach ($analysis['components'] as $name => $component) {
            $componentRows[] = [
                $name,
                $component['label'],
                number_format((float) $component['bias'], 4, '.', ''),
                $component['weight'],
                number_format((float) $component['points'], 2, '.', ''),
            ];
        }
        $this->table(['Component', 'Bias', 'Value', 'Weight', 'Points'], $componentRows);

        if ($setup) {
            $this->table(['Setup', 'Value'], [
                ['Direction', strtoupper($setup['direction'])],
                ['Entry', number_format((float) $setup['entry_min'], 8, '.', '').' - '.number_format((float) $setup['entry_max'], 8, '.', '')],
                ['Stop loss', number_format((float) $setup['stop_loss'], 8, '.', '')],
                ['TP1', number_format((float) $setup['targets'][0]['price'], 8, '.', '')],
                ['TP2', number_format((float) $setup['targets'][1]['price'], 8, '.', '')],
                ['TP3', number_format((float) $setup['targets'][2]['price'], 8, '.', '')],
                ['Primary R:R', '1:'.number_format((float) $setup['risk_reward'], 2, '.', '')],
                ['Expires', $setup['expires_at']],
                ['Signal ready', $setup['signal_ready'] ? 'yes' : 'no'],
            ]);
        } else {
            $this->warn('No trade setup was constructed because the analysis has no justified direction.');
        }

        foreach ($analysis['rationale'] as $reason) {
            $this->line(' - '.$reason);
        }

        $after = $this->signalRowCounts();
        if ($before !== $after) {
            $this->error('Signals S2 inspection unexpectedly mutated Signal persistence.');
            return self::FAILURE;
        }

        $this->info('SIGNALS_S2_INTELLIGENCE_OK');
        $this->line('No Signal, trade, wallet, membership or distribution record was created or changed.');

        return self::SUCCESS;
    }

    private function bestCandidate(SignalIntelligenceService $intelligence, string $marketplace, ?string $timeframe): array
    {
        $best = null;

        Stock::query()->active()->orderBy('id')->limit(25)->get()->each(function (Stock $stock) use ($intelligence, $marketplace, $timeframe, &$best) {
            try {
                $result = $intelligence->analyzeStock($stock, $marketplace, $timeframe);
                $score = (float) ($result['analysis']['confluence_score'] ?? 0);
                $directional = $result['analysis']['direction'] !== 'neutral';
                $rank = $score + ($directional ? 1000 : 0);

                if ($best === null || $rank > $best['rank']) {
                    $best = ['rank' => $rank, 'stock' => $stock, 'result' => $result];
                }
            } catch (\Throwable) {
                // Inspection skips instruments that cannot produce trustworthy context.
            }
        });

        if (! $best) {
            throw new \RuntimeException('No active instrument could produce Signals intelligence context.');
        }

        return [$best['stock'], $best['result']];
    }

    private function signalRowCounts(): array
    {
        return [
            'signals' => DB::table('signals')->count(),
            'signal_targets' => DB::table('signal_targets')->count(),
            'signal_analysis_runs' => DB::table('signal_analysis_runs')->count(),
            'signal_revisions' => DB::table('signal_revisions')->count(),
            'signal_events' => DB::table('signal_events')->count(),
            'signal_distributions' => DB::table('signal_distributions')->count(),
            'signal_deliveries' => DB::table('signal_deliveries')->count(),
        ];
    }
}
