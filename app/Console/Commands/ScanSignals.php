<?php

namespace App\Console\Commands;

use App\Services\SignalScannerService;
use Illuminate\Console\Command;

class ScanSignals extends Command
{
    protected $signature = 'signals:scan {symbol? : Optional stock symbol} {--marketplace= : live or controlled} {--limit=25 : Maximum instruments} {--force : Bypass generation cooldown; open-Signal protection still applies}';
    protected $description = 'Scan market instruments and automatically generate qualified Signal records.';

    public function handle(SignalScannerService $scanner): int
    {
        $result = $scanner->scan(
            $this->option('marketplace') ? (string) $this->option('marketplace') : null,
            (int) $this->option('limit'),
            (bool) $this->option('force'),
            $this->argument('symbol') ? (string) $this->argument('symbol') : null,
            'manual_scan'
        );

        $rows = collect($result['items'])->map(fn ($item) => [
            $item['symbol'],
            strtoupper((string) $item['status']),
            $item['signal_id'] ?? '-',
            strtoupper((string) ($item['direction'] ?? '-')),
            strtoupper((string) ($item['strength'] ?? '-')),
            $item['confluence_score'] !== null ? number_format((float) $item['confluence_score'], 2).'%' : '-',
            $item['reason'],
        ])->all();

        $this->table(
            ['Instrument', 'Outcome', 'Signal', 'Direction', 'Strength', 'Confluence', 'Reason'],
            $rows
        );

        $stats = $result['stats'];
        $this->line('Marketplace: '.$result['marketplace']);
        $this->line('Scanned: '.$stats['scanned'].' | Generated: '.$stats['generated'].' | Rejected: '.$stats['rejected'].' | Duplicate open: '.$stats['duplicate_open'].' | Cooldown: '.$stats['cooldown'].' | Failed: '.$stats['failed']);

        if ($stats['failed'] > 0) {
            $this->error('SIGNALS_S3_SCAN_FAILED');
            return self::FAILURE;
        }

        $this->info('SIGNALS_S3_SCAN_OK');
        return self::SUCCESS;
    }
}
