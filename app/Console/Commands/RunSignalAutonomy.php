<?php

namespace App\Console\Commands;

use App\Services\SignalAutonomyService;
use Illuminate\Console\Command;

class RunSignalAutonomy extends Command
{
    protected $signature = 'signals:run-autonomy {--limit=25 : Maximum instruments considered by the scheduled scanner} {--dry-run : Inspect due work without mutating Signal state}';
    protected $description = 'Run due Signal re-analysis and scheduled market scanning under overlap-safe autonomous authority.';

    public function handle(SignalAutonomyService $service): int
    {
        $result = $service->run(
            (int) $this->option('limit'),
            (bool) $this->option('dry-run')
        );

        $inspection = $result['inspection'];

        $this->table(['Autonomy', 'Value'], [
            ['Status', strtoupper((string) $result['status'])],
            ['Marketplace', strtoupper((string) $inspection['marketplace'])],
            ['Open Signals', $inspection['open_signals']],
            ['Due re-analysis', $inspection['due_reanalysis']],
            ['Scheduled scan due', $inspection['scan_due'] ? 'YES' : 'NO'],
            ['Last scheduled scan', $inspection['last_scheduled_scan_at']?->toDateTimeString() ?? 'Never'],
            ['Scan interval', $inspection['scan_interval_minutes'].' minutes'],
        ]);

        $reanalysis = $result['reanalysis'];
        $this->line('Re-analysis: checked '.$reanalysis['checked'].' | due '.$reanalysis['due'].' | processed '.$reanalysis['processed'].' | failed '.$reanalysis['failed']);

        if (! empty($reanalysis['items'])) {
            $this->table(
                ['Signal', 'Instrument', 'Result', 'Assessment', 'Reason'],
                collect($reanalysis['items'])->map(fn ($item) => [
                    $item['signal_id'],
                    $item['symbol'] ?? '-',
                    strtoupper((string) $item['status']),
                    strtoupper((string) ($item['assessment'] ?? '-')),
                    $item['reason'] ?? '-',
                ])->all()
            );
        }

        $scan = $result['scan'];
        if ($scan['ran']) {
            $stats = $scan['stats'];
            $this->line('Scheduled scan: scanned '.$stats['scanned'].' | generated '.$stats['generated'].' | rejected '.$stats['rejected'].' | duplicate open '.$stats['duplicate_open'].' | cooldown '.$stats['cooldown'].' | failed '.$stats['failed']);
        } else {
            $this->line('Scheduled scan: '.($scan['due'] ? 'due but not executed' : 'not due'));
        }

        if ($result['status'] === 'locked') {
            $this->warn('Another Signal autonomy runtime currently owns the lock.');
            $this->info('SIGNALS_S5_AUTONOMY_LOCKED');
            return self::SUCCESS;
        }

        if (($reanalysis['failed'] ?? 0) > 0 || (($scan['stats']['failed'] ?? 0) > 0)) {
            $this->error('SIGNALS_S5_AUTONOMY_FAILED');
            return self::FAILURE;
        }

        $this->info($result['status'] === 'dry_run' ? 'SIGNALS_S5_AUTONOMY_DRY_RUN_OK' : 'SIGNALS_S5_AUTONOMY_OK');
        return self::SUCCESS;
    }
}
