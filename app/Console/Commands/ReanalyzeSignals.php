<?php

namespace App\Console\Commands;

use App\Models\Signal;
use App\Services\SignalReanalysisService;
use Illuminate\Console\Command;

class ReanalyzeSignals extends Command
{
    protected $signature = 'signals:reanalyze {signal? : Signal id; defaults to latest open Signal} {--all : Re-analyze every open Signal} {--no-adjust : Report recommended changes without applying revisions}';
    protected $description = 'Re-analyze open multi-asset Signals and apply safe automatic adjustments through immutable revisions.';

    public function handle(SignalReanalysisService $service): int
    {
        $signals = $this->signals();

        if ($signals->isEmpty()) {
            $this->warn('No open Signal is available for re-analysis.');
            $this->info('SIGNALS_FX2_REANALYSIS_OK');
            return self::SUCCESS;
        }

        $rows = [];
        $failed = 0;

        foreach ($signals as $signal) {
            try {
                $result = $service->reanalyze($signal, ! $this->option('no-adjust'), 'manual_reanalysis');
                $rows[] = [
                    $signal->id,
                    $signal->instrument_symbol,
                    strtoupper((string) $signal->asset_class),
                    strtoupper((string) $result['status']),
                    strtoupper((string) ($result['assessment'] ?? '-')),
                    $result['revision_number'] ?? '-',
                    $result['analysis_run_id'] ?? '-',
                    $result['reason'],
                ];
            } catch (\Throwable $e) {
                $failed++;
                $rows[] = [$signal->id, $signal->instrument_symbol, strtoupper((string) $signal->asset_class), 'FAILED', '-', '-', '-', $e->getMessage()];
            }
        }

        $this->table(
            ['Signal', 'Instrument', 'Asset', 'Result', 'Assessment', 'Revision', 'Analysis run', 'Reason'],
            $rows
        );

        if ($failed > 0) {
            $this->error('SIGNALS_FX2_REANALYSIS_FAILED');
            return self::FAILURE;
        }

        $this->info('SIGNALS_FX2_REANALYSIS_OK');
        return self::SUCCESS;
    }

    private function signals()
    {
        $query = Signal::query()
            ->with(['stock', 'marketInstrument.stock', 'marketInstrument.forexPair', 'marketInstrument.canonicalCryptoPair', 'targets'])
            ->whereIn('status', Signal::OPEN_STATUSES);

        if ($this->option('all')) return $query->orderBy('id')->get();
        if ($this->argument('signal')) return $query->whereKey((int) $this->argument('signal'))->get();

        $latest = $query->latest('id')->first();
        return $latest ? collect([$latest]) : collect();
    }
}
