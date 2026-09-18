<?php

namespace App\Console\Commands;

use App\Models\SignalEvent;
use App\Services\SignalCustomerNotificationService;
use Illuminate\Console\Command;

class SyncSignalCustomerNotifications extends Command
{
    protected $signature = 'signals:sync-customer-notifications
                            {signal? : Optional Signal id}
                            {--event=adjusted : Material event type to backfill, or all}
                            {--dry-run : Report missing notifications without creating them}';

    protected $description = 'Backfill missing customer Signal lifecycle notifications from immutable SignalEvent records.';

    public function handle(SignalCustomerNotificationService $notifications): int
    {
        $eventFilter = strtolower(trim((string) $this->option('event')));
        $allowed = $notifications->materialEventTypes();

        if ($eventFilter !== 'all' && ! in_array($eventFilter, $allowed, true)) {
            $this->error('Unsupported event filter. Use one of: '.implode(', ', $allowed).', all.');
            return self::FAILURE;
        }

        $query = SignalEvent::query()
            ->with(['signal.stock', 'signal.marketInstrument.stock', 'signal.marketInstrument.forexPair', 'signal.targets', 'target'])
            ->whereHas('signal.deliveries')
            ->when($this->argument('signal'), fn ($q) => $q->where('signal_id', (int) $this->argument('signal')))
            ->when($eventFilter !== 'all', fn ($q) => $q->where('type', $eventFilter))
            ->when($eventFilter === 'all', fn ($q) => $q->whereIn('type', $allowed))
            ->orderBy('id');

        $totals = [
            'events' => 0,
            'deliveries' => 0,
            'created' => 0,
            'would_create' => 0,
            'existing' => 0,
            'failed' => 0,
        ];
        $rows = [];
        $dryRun = (bool) $this->option('dry-run');

        $query->chunkById(100, function ($events) use ($notifications, $dryRun, &$totals, &$rows) {
            foreach ($events as $event) {
                $result = $notifications->notifyForEvent($event, $dryRun);
                $totals['events']++;
                foreach (['deliveries', 'created', 'would_create', 'existing', 'failed'] as $key) {
                    $totals[$key] += (int) ($result[$key] ?? 0);
                }

                $rows[] = [
                    $event->id,
                    $event->signal_id,
                    strtoupper((string) $event->type),
                    $result['deliveries'],
                    $dryRun ? $result['would_create'] : $result['created'],
                    $result['existing'],
                    $result['failed'],
                ];
            }
        });

        if ($rows) {
            $this->table(
                ['Event', 'Signal', 'Type', 'Deliveries', $dryRun ? 'Would create' : 'Created', 'Existing', 'Failed'],
                $rows
            );
        } else {
            $this->warn('No matching delivered Signal events were found.');
        }

        $this->line(
            'Events: '.$totals['events']
            .' | Deliveries: '.$totals['deliveries']
            .' | '.($dryRun ? 'Would create: '.$totals['would_create'] : 'Created: '.$totals['created'])
            .' | Existing: '.$totals['existing']
            .' | Failed: '.$totals['failed']
        );

        if ($totals['failed'] > 0) {
            $this->error('SIGNALS_S5_CUSTOMER_NOTIFICATIONS_FAILED');
            return self::FAILURE;
        }

        $this->info($dryRun
            ? 'SIGNALS_S5_CUSTOMER_NOTIFICATIONS_DRY_RUN_OK'
            : 'SIGNALS_S5_CUSTOMER_NOTIFICATIONS_SYNC_OK');

        return self::SUCCESS;
    }
}
