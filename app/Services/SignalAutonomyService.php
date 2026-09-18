<?php

namespace App\Services;

use App\Models\Signal;
use App\Models\SignalAnalysisRun;
use Illuminate\Support\Facades\Cache;

class SignalAutonomyService
{
    private const SCAN_INTERVAL_MINUTES = 15;

    public function __construct(
        private readonly SignalScannerService $scanner,
        private readonly SignalReanalysisService $reanalysis,
        private readonly MarketPriceRouter $marketPriceRouter
    ) {}

    public function inspect(int $limit = 25): array
    {
        $marketplace = $this->marketPriceRouter->activeMarketplace();
        $openSignals = Signal::query()
            ->with(['stock', 'marketInstrument.stock', 'marketInstrument.forexPair'])
            ->whereIn('status', Signal::OPEN_STATUSES)
            ->orderBy('id')
            ->get();

        $due = $openSignals
            ->filter(fn (Signal $signal) => $this->reanalysisDue($signal))
            ->values();

        $lastScan = SignalAnalysisRun::query()
            ->where('trigger', 'scheduled_scan')
            ->where('marketplace', $marketplace)
            ->latest('analyzed_at')
            ->first();

        return [
            'marketplace' => $marketplace,
            'limit' => max(1, min(250, $limit)),
            'open_signals' => $openSignals->count(),
            'due_reanalysis' => $due->count(),
            'due_signal_ids' => $due->pluck('id')->all(),
            'scan_due' => $this->scanDue($marketplace),
            'last_scheduled_scan_at' => $lastScan?->analyzed_at,
            'scan_interval_minutes' => self::SCAN_INTERVAL_MINUTES,
        ];
    }

    public function run(int $limit = 25, bool $dryRun = false): array
    {
        $limit = max(1, min(250, $limit));
        $inspection = $this->inspect($limit);

        if ($dryRun) {
            return [
                'status' => 'dry_run',
                'inspection' => $inspection,
                'reanalysis' => [
                    'checked' => $inspection['open_signals'],
                    'due' => $inspection['due_reanalysis'],
                    'processed' => 0,
                    'failed' => 0,
                    'items' => [],
                ],
                'scan' => [
                    'due' => $inspection['scan_due'],
                    'ran' => false,
                    'stats' => null,
                    'items' => [],
                ],
            ];
        }

        $lock = Cache::lock('signals:autonomy:runtime', 240);

        if (! $lock->get()) {
            return [
                'status' => 'locked',
                'inspection' => $inspection,
                'reanalysis' => [
                    'checked' => 0,
                    'due' => 0,
                    'processed' => 0,
                    'failed' => 0,
                    'items' => [],
                ],
                'scan' => [
                    'due' => false,
                    'ran' => false,
                    'stats' => null,
                    'items' => [],
                ],
            ];
        }

        try {
            $reanalysis = $this->runDueReanalysis();
            $scan = $this->runScheduledScanIfDue($inspection['marketplace'], $limit);

            return [
                'status' => 'completed',
                'inspection' => $inspection,
                'reanalysis' => $reanalysis,
                'scan' => $scan,
            ];
        } finally {
            $lock->release();
        }
    }

    private function runDueReanalysis(): array
    {
        $signals = Signal::query()
            ->with(['stock', 'marketInstrument.stock', 'marketInstrument.forexPair', 'targets'])
            ->whereIn('status', Signal::OPEN_STATUSES)
            ->orderBy('id')
            ->get();

        $items = [];
        $due = 0;
        $processed = 0;
        $failed = 0;

        foreach ($signals as $signal) {
            if (! $this->reanalysisDue($signal)) {
                continue;
            }

            $due++;

            try {
                $result = $this->reanalysis->reanalyze(
                    $signal,
                    true,
                    'scheduled_reanalysis',
                    null
                );

                $processed++;
                $items[] = [
                    'signal_id' => $signal->id,
                    'symbol' => $signal->instrument_symbol,
                    'status' => $result['status'] ?? 'unknown',
                    'assessment' => $result['assessment'] ?? null,
                    'reason' => $result['reason'] ?? null,
                ];
            } catch (\Throwable $e) {
                $failed++;
                $items[] = [
                    'signal_id' => $signal->id,
                    'symbol' => $signal->instrument_symbol,
                    'status' => 'failed',
                    'assessment' => null,
                    'reason' => $e->getMessage(),
                ];
            }
        }

        return [
            'checked' => $signals->count(),
            'due' => $due,
            'processed' => $processed,
            'failed' => $failed,
            'items' => $items,
        ];
    }

    private function runScheduledScanIfDue(string $marketplace, int $limit): array
    {
        if (! $this->scanDue($marketplace)) {
            return [
                'due' => false,
                'ran' => false,
                'stats' => null,
                'items' => [],
            ];
        }

        // FX2 keeps unattended discovery stock-only until each Forex pair has
        // verified history. Forex scanning is explicit via signals:scan --asset=forex.
        $result = $this->scanner->scan(
            $marketplace,
            $limit,
            false,
            null,
            'scheduled_scan',
            'stock'
        );

        return [
            'due' => true,
            'ran' => true,
            'stats' => $result['stats'],
            'items' => $result['items'],
        ];
    }

    private function scanDue(string $marketplace): bool
    {
        $last = SignalAnalysisRun::query()
            ->where('trigger', 'scheduled_scan')
            ->where('marketplace', $marketplace)
            ->latest('analyzed_at')
            ->first();

        return ! $last?->analyzed_at
            || $last->analyzed_at->lte(now()->subMinutes(self::SCAN_INTERVAL_MINUTES));
    }

    private function reanalysisDue(Signal $signal): bool
    {
        $last = $signal->analysisRuns()
            ->where('trigger', 'scheduled_reanalysis')
            ->latest('analyzed_at')
            ->first();

        if (! $last?->analyzed_at) {
            return true;
        }

        return $last->analyzed_at->lte(
            now()->subMinutes($this->reanalysisIntervalMinutes((string) $signal->timeframe))
        );
    }

    private function reanalysisIntervalMinutes(string $timeframe): int
    {
        return match (strtolower($timeframe)) {
            '5m' => 5,
            '15m' => 15,
            '1h' => 30,
            '4h' => 60,
            '1d' => 240,
            '1w' => 1440,
            default => 60,
        };
    }
}
