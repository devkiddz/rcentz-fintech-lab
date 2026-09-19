<?php

namespace App\Console\Commands;

use App\Models\Signal;
use App\Models\SignalAnalysisRun;
use App\Models\SignalRevision;
use App\Services\SignalRevisionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InspectSignalAutomation extends Command
{
    protected $signature = 'signals:inspect-automation {--exercise-revision : Exercise the production revision service inside a rolled-back transaction}';
    protected $description = 'Inspect S3 Signal automation persistence, duplicate protection and revision integrity.';

    public function handle(SignalRevisionService $revisions): int
    {
        $duplicateGroups = DB::table('signals')
            ->select('market_instrument_id', 'marketplace', DB::raw('COUNT(*) as aggregate'))
            ->whereIn('status', Signal::OPEN_STATUSES)
            ->groupBy('market_instrument_id', 'marketplace')
            ->havingRaw('COUNT(*) > 1');

        $openDuplicates = DB::query()
            ->fromSub($duplicateGroups, 'duplicate_signal_groups')
            ->count();

        $counts = [
            ['Signals', DB::table('signals')->count()],
            ['Targets', DB::table('signal_targets')->count()],
            ['Analysis runs', DB::table('signal_analysis_runs')->count()],
            ['Revisions', DB::table('signal_revisions')->count()],
            ['Events', DB::table('signal_events')->count()],
            ['Open duplicate groups', $openDuplicates],
        ];

        $this->table(['Authority', 'Rows'], $counts);

        if ($openDuplicates > 0) {
            $this->error('Duplicate open Signal groups detected.');
            return self::FAILURE;
        }

        $latest = Signal::query()->with(['stock', 'marketInstrument.stock', 'marketInstrument.forexPair', 'targets'])->latest('id')->first();

        if ($latest) {
            $this->table(['Latest Signal', 'Value'], [
                ['ID', $latest->id],
                ['Instrument', $latest->instrument_symbol],
                ['Asset class', strtoupper((string) $latest->asset_class)],
                ['Marketplace', $latest->marketplace],
                ['Status', $latest->status],
                ['Direction', strtoupper((string) $latest->direction)],
                ['Strength', strtoupper((string) $latest->strength)],
                ['Targets', $latest->targets->count()],
                ['Analysis runs', $latest->analysisRuns()->count()],
                ['Revisions', $latest->revisions()->count()],
                ['Events', $latest->events()->count()],
            ]);
        } else {
            $this->warn('No Signal has been generated yet.');
        }

        if ($this->option('exercise-revision')) {
            if (! $latest) {
                $this->error('Revision exercise requires at least one Signal. Run signals:scan first.');
                return self::FAILURE;
            }

            $beforeRevisionCount = SignalRevision::query()->count();
            $beforeEventCount = DB::table('signal_events')->count();
            $originalConfluence = (float) ($latest->confluence_score ?? 0);

            DB::beginTransaction();
            try {
                $run = SignalAnalysisRun::create([
                    'signal_id' => $latest->id,
                    'stock_id' => $latest->stock_id,
                    'market_instrument_id' => $latest->market_instrument_id,
                    'marketplace' => $latest->marketplace,
                    'trigger' => 'inspection_probe',
                    'source' => 's3_revision_probe',
                    'timeframe' => $latest->timeframe,
                    'result' => 'probe',
                    'confluence_score' => $originalConfluence,
                    'market_price' => $latest->entry_min,
                    'context' => ['probe' => true],
                    'conclusion' => ['probe' => true],
                    'analyzed_at' => now(),
                ]);

                $revision = $revisions->apply(
                    $latest,
                    ['confluence_score' => $originalConfluence + 0.5],
                    'S3 rollback-only revision integrity probe.',
                    $run,
                    'inspection'
                );

                if (! $revision) {
                    throw new \RuntimeException('Revision service did not create a probe revision.');
                }

                if (SignalRevision::query()->count() !== $beforeRevisionCount + 1) {
                    throw new \RuntimeException('Probe revision count did not increase inside transaction.');
                }

                if (DB::table('signal_events')->count() !== $beforeEventCount + 1) {
                    throw new \RuntimeException('Probe adjustment event count did not increase inside transaction.');
                }

                DB::rollBack();
            } catch (\Throwable $e) {
                if (DB::transactionLevel() > 0) {
                    DB::rollBack();
                }
                $this->error('Revision rollback probe failed: '.$e->getMessage());
                return self::FAILURE;
            }

            $latest->refresh();

            if (
                SignalRevision::query()->count() !== $beforeRevisionCount
                || DB::table('signal_events')->count() !== $beforeEventCount
                || abs((float) $latest->confluence_score - $originalConfluence) > 0.000001
            ) {
                $this->error('Revision rollback probe leaked persistent mutation.');
                return self::FAILURE;
            }

            $this->info('REVISION_ROLLBACK_PROBE=PASS');
        }

        $this->info('SIGNALS_S3_AUTOMATION_OK');
        return self::SUCCESS;
    }
}
