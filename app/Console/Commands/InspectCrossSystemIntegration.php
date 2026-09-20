<?php

namespace App\Console\Commands;

use App\Models\ForexPair;
use App\Models\User;
use App\Services\FeatureAccessService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Console\Command;

class InspectCrossSystemIntegration extends Command
{
    protected $signature = 'integration:inspect-ms8';
    protected $description = 'Inspect MS8 membership feature gates and Forex daily-history readiness before scheduler acceptance.';

    public function handle(FeatureAccessService $features): int
    {
        $paid = User::query()->where('email', 'qa.membership.paid@rcentz.test')->first();
        $denied = User::query()->where('email', 'qa.membership.denied@rcentz.test')->first();

        if (! $paid || ! $denied) {
            $this->error('MS5 Membership QA users are missing. Run membership:seed-ms5-real-cases first.');
            return self::FAILURE;
        }

        $contracts = [
            'Signals' => FeatureAccessService::SIGNALS,
            'Bot Trader' => FeatureAccessService::BOT_TRADER,
            'Copy Trading' => FeatureAccessService::COPY_TRADER,
            'Private Investments' => FeatureAccessService::INVESTMENTS,
        ];

        $featureRows = [];
        $paidFailures = 0;
        $deniedLeaks = 0;
        $requireFailures = 0;

        foreach ($contracts as $label => $key) {
            $paidAllowed = $features->allows($paid, $key);
            $deniedAllowed = $features->allows($denied, $key);

            if (! $paidAllowed) $paidFailures++;
            if ($deniedAllowed) $deniedLeaks++;

            $deniedRejected = false;
            try {
                $features->require($denied, $key);
            } catch (AuthorizationException) {
                $deniedRejected = true;
            }

            if (! $deniedRejected) $requireFailures++;

            $featureRows[] = [
                $label,
                $key,
                $paidAllowed ? 'ALLOW' : 'FAIL',
                $deniedAllowed ? 'LEAK' : 'DENY',
                $deniedRejected ? 'PASS' : 'FAIL',
            ];
        }

        $this->line('MS8 cross-system commercial access contracts');
        $this->table(['Feature', 'Entitlement', 'Paid member', 'No membership', 'Require gate'], $featureRows);

        $pairs = ForexPair::query()
            ->active()
            ->where('external_feed_enabled', true)
            ->orderBy('symbol')
            ->get();

        $missingHistory = 0;
        $staleHistory = 0;
        $staleBefore = now()->subDays(5)->startOfDay();
        $forexRows = [];

        foreach ($pairs as $pair) {
            $daily = $pair->candles()->where('interval', '1d');
            $count = (clone $daily)->count();
            $latest = (clone $daily)->latest('timestamp')->first();
            $missing = $count < 2 || ! $latest;
            $stale = ! $missing && $latest->timestamp && $latest->timestamp->lt($staleBefore);

            if ($missing) $missingHistory++;
            if ($stale) $staleHistory++;

            $forexRows[] = [
                $pair->display_symbol,
                $count,
                $latest?->timestamp?->toDateString() ?? 'NONE',
                $missing ? 'MISSING' : ($stale ? 'STALE' : 'READY'),
            ];
        }

        $this->newLine();
        $this->line('MS8 Forex daily-history readiness');
        $this->table(['Pair', 'Daily candles', 'Latest candle', 'State'], $forexRows);

        $checks = [
            ['Paid membership feature-access failures', $paidFailures],
            ['Non-member feature-access leaks', $deniedLeaks],
            ['Feature require() rejection failures', $requireFailures],
            ['Active Forex pairs missing daily history', $missingHistory],
            ['Active Forex pairs stale beyond 5 days', $staleHistory],
        ];

        $this->newLine();
        $this->table(['MS8 integration readiness check', 'Count'], $checks);

        if (collect($checks)->contains(fn ($row) => (int) $row[1] !== 0)) {
            $this->error('MS8_CROSS_SYSTEM_INTEGRATION_NOT_READY');
            return self::FAILURE;
        }

        $this->info('MS8 Cross-System Integration is READY: membership entitlements gate new premium exposure while existing ownership/exit paths remain domain-authoritative, and Forex daily history is fresh enough for Signal analysis.');
        return self::SUCCESS;
    }
}
