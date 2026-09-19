<?php

namespace App\Console\Commands;

use App\Models\ControlledMarketInstrument;
use App\Models\CryptoPair;
use App\Services\ControlledMarketEngine;
use App\Services\MarketPriceRouter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ActivateCryptoRuntime extends Command
{
    protected $signature = 'markets:activate-crypto-runtime {symbol? : Optional crypto symbol such as BTCUSD} {--dry-run : Show the activation plan without mutating runtime state}';
    protected $description = 'Activate only feed-ready Crypto MarketInstrument parents and seed shared Controlled price authority.';

    public function handle(MarketPriceRouter $prices, ControlledMarketEngine $engine): int
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
        $activated = 0;
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
                $rows[] = [$pair->display_symbol, 'FAILED', $dailyCount, 'Missing MarketInstrument parent'];
                continue;
            }

            if (! $feedReady) {
                if ($parent->is_active) {
                    $failed++;
                    $rows[] = [$pair->display_symbol, 'FAILED', $dailyCount, 'Parent is active without ready real history'];
                } else {
                    $skipped++;
                    $rows[] = [$pair->display_symbol, 'SKIPPED', $dailyCount, 'NEEDS_HISTORY'];
                }
                continue;
            }

            if ($dryRun) {
                $rows[] = [
                    $pair->display_symbol,
                    $parent->is_active ? 'ALREADY_ACTIVE' : 'WOULD_ACTIVATE',
                    $dailyCount,
                    number_format((float) $pair->current_rate, (int) $pair->price_precision, '.', ''),
                ];
                continue;
            }

            try {
                DB::transaction(function () use ($pair, $parent, $prices, $engine, &$activated, &$existing) {
                    $live = $prices->price($parent, 'live');
                    if ($live <= 0) {
                        throw new \RuntimeException('Live crypto price is unavailable.');
                    }

                    $controlled = ControlledMarketInstrument::query()
                        ->where('market_instrument_id', $parent->id)
                        ->first();

                    if (! $controlled) {
                        $engine->registerMarketInstrument($parent, $parent->name, $live);
                    } else {
                        $controlled->update([
                            'symbol' => $parent->symbol,
                            'label' => $parent->name,
                            'asset_class' => $parent->asset_class,
                            'is_active' => true,
                        ]);
                    }

                    $metadata = is_array($parent->metadata) ? $parent->metadata : [];
                    $metadata['market_hours'] = '24_7';
                    $metadata['runtime_state'] = 'runtime_ready';
                    $metadata['runtime_activated_at'] = now()->toIso8601String();
                    $metadata['signal_runtime_state'] = 'disabled_until_crypto_signal_adapter';

                    if ($parent->is_active) {
                        $existing++;
                    } else {
                        $activated++;
                    }

                    $parent->update([
                        'is_active' => true,
                        'metadata' => $metadata,
                    ]);
                });

                $rows[] = [
                    $pair->display_symbol,
                    $parent->fresh()->is_active ? 'ACTIVE' : 'FAILED',
                    $dailyCount,
                    number_format((float) $pair->current_rate, (int) $pair->price_precision, '.', ''),
                ];
            } catch (\Throwable $e) {
                $failed++;
                $rows[] = [$pair->display_symbol, 'FAILED', $dailyCount, $e->getMessage()];
            }
        }

        $this->table(['Pair', 'Runtime', 'Daily candles', 'Price / Reason'], $rows);
        $this->newLine();
        $this->table(['Activated', 'Already active', 'Skipped', 'Failed'], [[$activated, $existing, $skipped, $failed]]);

        if ($dryRun) {
            $this->info('CRYPTO_C2_ACTIVATION_DRY_RUN_OK');
            return $failed === 0 ? self::SUCCESS : self::FAILURE;
        }

        if ($failed > 0) {
            $this->error('CRYPTO_C2_ACTIVATION_FAILED='.$failed);
            return self::FAILURE;
        }

        $this->info('CRYPTO_C2_ACTIVATION_OK');
        return self::SUCCESS;
    }
}
