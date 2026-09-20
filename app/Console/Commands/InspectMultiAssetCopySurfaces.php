<?php

namespace App\Console\Commands;

use App\Models\CopyRelationship;
use App\Models\CopyTradeExecution;
use App\Models\User;
use App\Services\CopyTradingSurfaceService;
use App\Services\TradingPerformanceService;
use Illuminate\Console\Command;

class InspectMultiAssetCopySurfaces extends Command
{
    protected $signature = 'copy-trading:inspect-multi-asset-surfaces';
    protected $description = 'Inspect MS2 Copy Trading multi-asset customer/provider surface readiness';

    public function handle(TradingPerformanceService $performance, CopyTradingSurfaceService $surfaces): int
    {
        $cases = [
            'qa.copy.stock@rcentz.test' => 'STOCK',
            'qa.copy.fx@rcentz.test' => 'FOREX',
            'qa.copy.crypto@rcentz.test' => 'CRYPTO',
        ];

        $rows = [];
        $failed = false;

        foreach ($cases as $email => $expectedAsset) {
            $user = User::query()->where('email', $email)->first();
            if (! $user) {
                $rows[] = [$expectedAsset, $email, 'MISSING', '—', '—', '—'];
                $failed = true;
                continue;
            }

            $relationship = CopyRelationship::query()
                ->with(['strategy', 'provider'])
                ->where('follower_id', $user->id)
                ->latest('id')
                ->first();

            if (! $relationship) {
                $rows[] = [$expectedAsset, $email, 'NO CONTRACT', '—', '—', '—'];
                $failed = true;
                continue;
            }

            $surfaces->decorateRelationship($relationship);
            $metrics = $performance->copyRelationship($relationship);
            $open = $relationship->positions()
                ->whereIn('status', ['open', 'exit_queued'])
                ->where('open_quantity', '>', 0)
                ->count();

            $actualAsset = strtoupper((string) ($relationship->market_asset_class ?: ''));
            if ($actualAsset !== $expectedAsset) {
                $failed = true;
            }

            $rows[] = [
                $actualAsset ?: '—',
                $email,
                $relationship->strategy?->name ?: '—',
                $relationship->market_symbol ?: '—',
                $open,
                format_currency((float) ($metrics['profit_loss'] ?? 0)),
            ];
        }

        $this->line('Persistent Copy Trading browser cases:');
        $this->table(
            ['Asset', 'Follower', 'Strategy', 'Instrument', 'Open Pos', 'Current P/L'],
            $rows
        );

        $checks = [
            'Completed copies missing MarketInstrument' => CopyTradeExecution::query()
                ->where('status', 'completed')
                ->whereNull('market_instrument_id')
                ->count(),
            'New completed copies missing follower BrokerOrder' => CopyTradeExecution::query()
                ->where('status', 'completed')
                ->whereNotNull('provider_broker_order_id')
                ->whereNull('follower_broker_order_id')
                ->count(),
            'New completed copies missing follower unified execution' => CopyTradeExecution::query()
                ->where('status', 'completed')
                ->whereNotNull('provider_market_execution_transaction_id')
                ->whereNull('follower_market_execution_transaction_id')
                ->count(),
        ];

        $this->newLine();
        $this->table(
            ['Surface authority check', 'Count'],
            collect($checks)->map(fn ($count, $label) => [$label, $count])->values()->all()
        );

        if ($failed || collect($checks)->contains(fn ($count) => $count > 0)) {
            $this->error('MS2 Copy Trading surface acceptance has unresolved items.');
            return self::FAILURE;
        }

        $this->info('MS2 Copy Trading multi-asset surfaces are READY for browser acceptance using the three persistent QA accounts.');
        return self::SUCCESS;
    }
}
