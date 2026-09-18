<?php

namespace App\Console\Commands;

use App\Services\SignalPerformanceService;
use Illuminate\Console\Command;

class InspectSignalPerformance extends Command
{
    protected $signature = 'signals:inspect-performance {--recent=10 : Number of recent terminal Signals to display}';
    protected $description = 'Inspect lifecycle-derived Signal outcome and target performance without inventing trade P&L.';

    public function handle(SignalPerformanceService $performance): int
    {
        $stats = $performance->platform((int) $this->option('recent'));

        $this->table(['Performance authority', 'Value'], [
            ['Signals', $stats['total']],
            ['Open', $stats['open']],
            ['Active', $stats['active']],
            ['Terminal', $stats['terminal']],
            ['Closed - all targets reached', $stats['closed']],
            ['Stopped', $stats['stopped']],
            ['Expired', $stats['expired']],
            ['Invalidated', $stats['invalidated']],
            ['Cancelled', $stats['cancelled']],
            ['Targets reached', $stats['targets_hit'].' / '.$stats['targets_total']],
            ['Target reach rate', $stats['target_reach_rate'] !== null ? number_format($stats['target_reach_rate'], 2).'%' : 'N/A'],
            ['Resolved success rate', $stats['resolved_success_rate'] !== null ? number_format($stats['resolved_success_rate'], 2).'%' : 'N/A'],
            ['Average confluence', $stats['average_confluence'] !== null ? number_format($stats['average_confluence'], 2).'%' : 'N/A'],
        ]);

        if (! empty($stats['recent_terminal'])) {
            $this->table(
                ['Signal', 'Instrument', 'Direction', 'Timeframe', 'Outcome', 'Targets', 'Confluence', 'Ended'],
                collect($stats['recent_terminal'])->map(fn ($row) => [
                    $row['signal_id'],
                    $row['symbol'] ?? '-',
                    strtoupper((string) $row['direction']),
                    strtoupper((string) $row['timeframe']),
                    strtoupper((string) $row['status']),
                    $row['targets_hit'].'/'.$row['targets_total'],
                    $row['confluence_score'] !== null ? number_format($row['confluence_score'], 2).'%' : '-',
                    $row['ended_at']?->format('Y-m-d H:i:s') ?? '-',
                ])->all()
            );
        } else {
            $this->line('No terminal Signal outcomes exist yet; current performance remains unresolved.');
        }

        $this->line('Resolved success rate uses only CLOSED versus STOPPED outcomes. Expired, invalidated and cancelled Signals are reported separately.');
        $this->info('SIGNALS_S5_PERFORMANCE_OK');
        return self::SUCCESS;
    }
}
