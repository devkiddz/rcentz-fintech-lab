<?php

namespace App\Console\Commands;

use App\Services\SignalLifecycleService;
use Illuminate\Console\Command;

class ProcessSignalLifecycle extends Command
{
    protected $signature = 'signals:process-lifecycle {signal? : Optional Signal id}';
    protected $description = 'Process stock/forex Signal entry activation, targets, stop-loss and expiry against current market authority.';

    public function handle(SignalLifecycleService $service): int
    {
        $result = $service->process($this->argument('signal') ? (int) $this->argument('signal') : null);

        $this->table(
            ['Signal', 'Instrument', 'Status', 'Price', 'Result'],
            collect($result['items'])->map(fn ($item) => [
                $item['signal_id'],
                $item['symbol'] ?? '-',
                strtoupper((string) $item['status']),
                $item['price'] !== null ? number_format((float) $item['price'], 8, '.', '') : '-',
                $item['message'],
            ])->all()
        );

        $stats = $result['stats'];
        $this->line('Checked: '.$stats['checked'].' | Activated: '.$stats['activated'].' | Target hits: '.$stats['target_hits'].' | Closed: '.$stats['closed'].' | Stopped: '.$stats['stopped'].' | Expired: '.$stats['expired'].' | Unchanged: '.$stats['unchanged'].' | Failed: '.$stats['failed']);

        if ($stats['failed'] > 0) {
            $this->error('SIGNALS_FX2_LIFECYCLE_FAILED');
            return self::FAILURE;
        }

        $this->info('SIGNALS_FX2_LIFECYCLE_OK');
        return self::SUCCESS;
    }
}
