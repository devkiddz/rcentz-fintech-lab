<?php

namespace App\Console\Commands;

use App\Models\CryptoPair;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EnableCryptoSignalWiring extends Command
{
    protected $signature = 'signals:enable-crypto-wiring {symbol? : Optional crypto symbol such as BTCUSD} {--dry-run : Show the enablement plan without mutating Signal authority}';
    protected $description = 'Enable READY-only Crypto Signal authority for feed-ready active Crypto instruments; does not generate, publish, distribute or execute Signals.';

    public function handle(): int
    {
        $query = CryptoPair::query()->with('marketInstrument')->orderBy('id');

        if ($symbol = $this->argument('symbol')) {
            $normalized = strtoupper(str_replace(['/', '-', '_', ' '], '', (string) $symbol));
            $query->where('symbol', $normalized);
        }

        $pairs = $query->get();
        if ($pairs->isEmpty()) {
            $this->error('No matching crypto pairs were found.');
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $enabled = 0;
        $existing = 0;
        $skipped = 0;
        $failed = 0;
        $rows = [];

        foreach ($pairs as $pair) {
            $parent = $pair->marketInstrument;
            $dailyCount = $pair->candles()->where('interval', '1d')->count();
            $feedReady = (bool) $pair->external_feed_enabled
                && (float) ($pair->current_rate ?? 0) > 0
                && $dailyCount >= 2;

            if (! $parent) {
                $failed++;
                $rows[] = [$pair->display_symbol, 'FAILED', $dailyCount, 'Missing MarketInstrument authority'];
                continue;
            }

            if (! $parent->is_active || ! $feedReady) {
                $skipped++;
                $rows[] = [$pair->display_symbol, 'SKIPPED', $dailyCount, ! $parent->is_active ? 'MARKET_RUNTIME_INACTIVE' : 'NEEDS_HISTORY'];
                continue;
            }

            $state = (string) data_get($parent->metadata, 'signal_runtime_state', 'disabled');
            if ($state === 'ready_only') {
                $existing++;
                $rows[] = [$pair->display_symbol, 'ALREADY_ENABLED', $dailyCount, 'READY_ONLY'];
                continue;
            }

            if ($dryRun) {
                $rows[] = [$pair->display_symbol, 'WOULD_ENABLE', $dailyCount, 'READY_ONLY'];
                continue;
            }

            try {
                DB::transaction(function () use ($parent) {
                    $metadata = is_array($parent->metadata) ? $parent->metadata : [];
                    $metadata['signal_runtime_state'] = 'ready_only';
                    $metadata['signal_runtime_enabled_at'] = now()->toIso8601String();
                    $metadata['signal_policy'] = [
                        'generation_status' => 'ready',
                        'auto_publish' => false,
                        'auto_distribution' => false,
                        'trade_execution' => false,
                        'market_hours' => '24_7',
                    ];

                    $parent->update(['metadata' => $metadata]);
                }, 3);

                $enabled++;
                $rows[] = [$pair->display_symbol, 'ENABLED', $dailyCount, 'READY_ONLY'];
            } catch (\Throwable $e) {
                $failed++;
                $rows[] = [$pair->display_symbol, 'FAILED', $dailyCount, $e->getMessage()];
            }
        }

        $this->table(['Pair', 'Signal authority', 'Daily candles', 'Mode / Reason'], $rows);
        $this->table(['Enabled', 'Already enabled', 'Skipped', 'Failed'], [[
            $enabled, $existing, $skipped, $failed,
        ]]);

        if ($failed > 0) {
            $this->error('CRYPTO_C3_SIGNAL_ENABLE_FAILED');
            return self::FAILURE;
        }

        $this->info($dryRun ? 'CRYPTO_C3_SIGNAL_ENABLE_DRY_RUN_OK' : 'CRYPTO_C3_SIGNAL_ENABLE_OK');
        return self::SUCCESS;
    }
}
