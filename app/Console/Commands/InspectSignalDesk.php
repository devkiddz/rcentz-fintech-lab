<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\Signal;
use App\Models\SignalDelivery;
use App\Models\SignalDistribution;
use App\Models\SignalEvent;
use App\Models\User;
use App\Services\SignalDistributionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use RuntimeException;

class InspectSignalDesk extends Command
{
    protected $signature = 'signals:inspect-desk {--exercise-distribution : Exercise complimentary delivery inside a rolled-back transaction}';
    protected $description = 'Verify the Signals S4 admin desk, distribution authority and notification integration.';

    public function handle(SignalDistributionService $distributionService): int
    {
        $requiredRoutes = [
            'admin.signals.index',
            'admin.signals.candidates',
            'admin.signals.live',
            'admin.signals.recipients',
            'admin.signals.history',
            'admin.signals.activity',
            'admin.signals.show',
            'admin.signals.store',
            'admin.signals.update',
            'admin.signals.reanalyze',
            'admin.signals.publish',
            'admin.signals.distribute',
            'admin.signals.complimentary',
            'admin.signals.cancel',
            'admin.signals.close',
        ];

        foreach ($requiredRoutes as $route) {
            if (! Route::has($route)) {
                $this->error("Missing Signals S4 route: {$route}");
                return self::FAILURE;
            }
        }

        $rows = [
            ['Signals', Signal::query()->count()],
            ['Ready', Signal::query()->where('status', 'ready')->count()],
            ['Published', Signal::query()->where('status', 'published')->count()],
            ['Active', Signal::query()->where('status', 'active')->count()],
            ['Terminal', Signal::query()->whereIn('status', Signal::TERMINAL_STATUSES)->count()],
            ['Distributions', SignalDistribution::query()->count()],
            ['Deliveries', SignalDelivery::query()->count()],
            ['Signal notifications', Notification::query()->where('type', 'signal')->count()],
            ['Admin Signal receipts', Notification::query()->where('type', 'signal_admin')->count()],
            ['Signal events', SignalEvent::query()->count()],
        ];

        $this->table(['Authority', 'Rows'], $rows);

        $signal = Signal::query()->with(['stock', 'marketInstrument.stock', 'marketInstrument.forexPair', 'targets'])->latest('id')->first();
        if (! $signal) {
            $this->error('No Signal exists for S4 desk inspection.');
            return self::FAILURE;
        }

        $this->table(
            ['Latest Signal', 'Value'],
            [
                ['ID', $signal->id],
                ['Instrument', $signal->instrument_symbol],
                ['Asset class', strtoupper((string) $signal->asset_class)],
                ['Marketplace', $signal->marketplace],
                ['Status', $signal->status],
                ['Direction', strtoupper((string) $signal->direction)],
                ['Strength', strtoupper((string) $signal->strength)],
                ['Targets', $signal->targets->count()],
            ]
        );

        if ($this->option('exercise-distribution')) {
            $probe = $this->distributionProbe($signal, $distributionService);
            if (! $probe) {
                return self::FAILURE;
            }
        }

        $this->info('SIGNALS_S4_DESK_OK');
        return self::SUCCESS;
    }

    private function distributionProbe(Signal $signal, SignalDistributionService $distributionService): bool
    {
        $admin = User::query()->where('is_admin', true)->orderBy('id')->first();
        $customer = User::query()->where('is_admin', false)->orderBy('id')->get()->first(fn (User $user) => $user->isAccountActive());

        if (! $admin) {
            $this->error('Distribution probe requires an administrator account.');
            return false;
        }
        if (! $customer) {
            $this->error('Distribution probe requires at least one active customer account.');
            return false;
        }

        $before = [
            'distributions' => SignalDistribution::query()->count(),
            'deliveries' => SignalDelivery::query()->count(),
            'notifications' => Notification::query()->where('type', 'signal')->count(),
            'admin_receipts' => Notification::query()->where('type', 'signal_admin')->count(),
        ];

        $baselineLevel = DB::transactionLevel();
        DB::beginTransaction();

        try {
            $probeSignal = Signal::query()->with(['stock', 'marketInstrument.stock', 'marketInstrument.forexPair', 'targets'])->findOrFail($signal->id);
            if (! in_array($probeSignal->status, ['published', 'active'], true)) {
                $probeSignal->update([
                    'status' => 'published',
                    'published_at' => $probeSignal->published_at ?: now(),
                ]);
            }

            $distribution = $distributionService->distributeComplimentary(
                $probeSignal->fresh(['stock', 'marketInstrument', 'targets']),
                $admin,
                [$customer->id],
                'S4 rolled-back distribution integrity probe.'
            );

            if ($distribution->delivered_count !== 1) {
                throw new RuntimeException('Probe expected exactly one complimentary delivery.');
            }
            if (! SignalDelivery::query()->where('distribution_id', $distribution->id)->where('user_id', $customer->id)->exists()) {
                throw new RuntimeException('Probe delivery row was not created.');
            }
            if (! Notification::query()->where('user_id', $customer->id)->where('type', 'signal')->exists()) {
                throw new RuntimeException('Probe Signal notification was not created.');
            }
            if (! Notification::query()->where('user_id', $admin->id)->where('type', 'signal_admin')->exists()) {
                throw new RuntimeException('Probe administrator delivery receipt was not created.');
            }
        } catch (\Throwable $e) {
            while (DB::transactionLevel() > $baselineLevel) {
                DB::rollBack();
            }
            $this->error('Distribution rollback probe failed: '.$e->getMessage());
            return false;
        }

        while (DB::transactionLevel() > $baselineLevel) {
            DB::rollBack();
        }

        $after = [
            'distributions' => SignalDistribution::query()->count(),
            'deliveries' => SignalDelivery::query()->count(),
            'notifications' => Notification::query()->where('type', 'signal')->count(),
            'admin_receipts' => Notification::query()->where('type', 'signal_admin')->count(),
        ];

        if ($before !== $after) {
            $this->error('Distribution rollback probe left persistent rows behind.');
            return false;
        }

        $this->info('DISTRIBUTION_ROLLBACK_PROBE=PASS');
        return true;
    }
}
