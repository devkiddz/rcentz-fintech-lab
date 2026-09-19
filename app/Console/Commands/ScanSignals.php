<?php

namespace App\Console\Commands;

use App\Services\SignalScannerService;
use Illuminate\Console\Command;

class ScanSignals extends Command
{
    protected $signature = 'signals:scan
        {symbol? : Optional instrument symbol, e.g. AAPL or EURUSD}
        {--marketplace= : live or controlled; Forex and Crypto Signals are live-only}
        {--asset=stock : stock, forex, crypto or all}
        {--limit=25 : Maximum instruments}
        {--force : Bypass generation cooldown; open-Signal protection still applies}';

    protected $description = 'Scan multi-asset market instruments and generate qualified READY Signal records without publishing or execution.';

    public function handle(SignalScannerService $scanner): int
    {
        $result = $scanner->scan(
            $this->option('marketplace') ? (string) $this->option('marketplace') : null,
            (int) $this->option('limit'),
            (bool) $this->option('force'),
            $this->argument('symbol') ? (string) $this->argument('symbol') : null,
            'manual_scan',
            (string) $this->option('asset')
        );

        $rows = collect($result['items'])->map(fn ($item) => [
            $item['symbol'],
            strtoupper((string) ($item['asset_class'] ?? '-')),
            strtoupper((string) $item['status']),
            $item['signal_id'] ?? '-',
            strtoupper((string) ($item['direction'] ?? '-')),
            strtoupper((string) ($item['strength'] ?? '-')),
            $item['confluence_score'] !== null ? number_format((float) $item['confluence_score'], 2).'%' : '-',
            $item['reason'],
        ])->all();

        $this->table(
            ['Instrument', 'Asset', 'Outcome', 'Signal', 'Direction', 'Strength', 'Confluence', 'Reason'],
            $rows
        );

        $stats = $result['stats'];
        $this->line('Asset class: '.strtoupper((string) $result['asset_class']).' | Marketplace: '.strtoupper((string) $result['marketplace']));
        $this->line('Scanned: '.$stats['scanned'].' | Generated: '.$stats['generated'].' | Rejected: '.$stats['rejected'].' | Duplicate open: '.$stats['duplicate_open'].' | Cooldown: '.$stats['cooldown'].' | Failed: '.$stats['failed']);

        if ($stats['failed'] > 0) {
            $this->error('SIGNALS_FX2_SCAN_FAILED');
            return self::FAILURE;
        }

        $this->info('SIGNALS_FX2_SCAN_OK');
        return self::SUCCESS;
    }
}
