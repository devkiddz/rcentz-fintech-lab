<?php

namespace App\Services;

use App\Models\MarketInstrument;
use App\Models\Signal;
use App\Models\SignalAnalysisRun;
use App\Models\SignalEvent;
use App\Models\Stock;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SignalGenerationService
{
    public function __construct(
        private readonly SignalIntelligenceService $intelligence,
        private readonly MarketPriceRouter $marketPriceRouter
    ) {}

    public function generateForInstrument(
        MarketInstrument $instrument,
        ?string $marketplace = null,
        bool $force = false,
        string $trigger = 'scheduled'
    ): array {
        if (! $instrument->is_active) {
            return $this->outcome('skipped', $instrument, null, 'Instrument is inactive.');
        }

        if (! in_array($instrument->asset_class, [
            MarketInstrument::ASSET_STOCK,
            MarketInstrument::ASSET_FOREX,
            MarketInstrument::ASSET_CRYPTO,
        ], true)) {
            return $this->outcome('skipped', $instrument, null, 'Signal runtime is not enabled for this asset class.');
        }

        if ($instrument->isCrypto() && data_get($instrument->metadata, 'signal_runtime_state') !== 'ready_only') {
            return $this->outcome('skipped', $instrument, null, 'Crypto Signal runtime is not enabled for this instrument.');
        }

        $marketplace = ($instrument->isForex() || $instrument->isCrypto())
            ? 'live'
            : $this->marketPriceRouter->normalizeMarketplace($marketplace ?: $this->marketPriceRouter->activeMarketplace());

        $lock = Cache::lock("signals:generation:instrument:{$instrument->id}:{$marketplace}", 30);

        if (! $lock->get()) {
            return $this->outcome('locked', $instrument, null, 'Generation lock is already held for this instrument.');
        }

        try {
            $result = $this->intelligence->analyzeInstrument($instrument, $marketplace);
            $analysis = $result['analysis'];
            $qualification = $result['qualification'];
            $setup = $result['setup'];

            $analysisRun = SignalAnalysisRun::create([
                'signal_id' => null,
                'stock_id' => $instrument->stock_id,
                'market_instrument_id' => $instrument->id,
                'marketplace' => $marketplace,
                'trigger' => $trigger,
                'source' => 'signal_intelligence',
                'timeframe' => $analysis['timeframe'] ?? null,
                'result' => $qualification['result'] ?? 'observed',
                'confluence_score' => $analysis['confluence_score'] ?? null,
                'market_price' => $analysis['market_price'] ?? null,
                'context' => $this->compactContext($result['context']),
                'conclusion' => $this->compactConclusion($result),
                'analyzed_at' => now(),
                'actor_user_id' => null,
            ]);

            if (! $setup || ! ($qualification['eligible_for_auto_generation'] ?? false)) {
                return $this->outcome(
                    'rejected',
                    $instrument,
                    null,
                    $qualification['reason'] ?? 'Analysis did not qualify for automatic generation.',
                    $analysisRun,
                    $result
                );
            }

            return DB::transaction(function () use (
                $instrument,
                $marketplace,
                $force,
                $analysisRun,
                $result,
                $analysis,
                $qualification,
                $setup,
                $trigger
            ) {
                $openSignal = Signal::query()
                    ->where('market_instrument_id', $instrument->id)
                    ->where('marketplace', $marketplace)
                    ->whereIn('status', Signal::OPEN_STATUSES)
                    ->lockForUpdate()
                    ->latest('id')
                    ->first();

                if ($openSignal) {
                    $analysisRun->update([
                        'signal_id' => $openSignal->id,
                        'result' => 'duplicate_open',
                    ]);

                    return $this->outcome(
                        'duplicate_open',
                        $instrument,
                        $openSignal,
                        'An open Signal already exists for this instrument and marketplace.',
                        $analysisRun,
                        $result
                    );
                }

                if (! $force) {
                    $latest = Signal::query()
                        ->where('market_instrument_id', $instrument->id)
                        ->where('marketplace', $marketplace)
                        ->whereNotNull('generated_at')
                        ->latest('generated_at')
                        ->lockForUpdate()
                        ->first();

                    $cooldownMinutes = $this->cooldownMinutes((string) ($setup['timeframe'] ?? '15m'));

                    if ($latest?->generated_at && $latest->generated_at->gt(now()->subMinutes($cooldownMinutes))) {
                        $analysisRun->update([
                            'signal_id' => $latest->id,
                            'result' => 'cooldown',
                        ]);

                        return $this->outcome(
                            'cooldown',
                            $instrument,
                            $latest,
                            "Generation cooldown is still active for {$cooldownMinutes} minutes.",
                            $analysisRun,
                            $result
                        );
                    }
                }

                $signal = Signal::create([
                    'stock_id' => $instrument->stock_id,
                    'market_instrument_id' => $instrument->id,
                    'marketplace' => $marketplace,
                    'source' => 'auto_analysis',
                    'direction' => $setup['direction'],
                    'timeframe' => $setup['timeframe'],
                    'status' => 'ready',
                    'strength' => $qualification['strength'] ?? null,
                    'confluence_score' => $analysis['confluence_score'] ?? null,
                    'entry_min' => $setup['entry_min'],
                    'entry_max' => $setup['entry_max'],
                    'stop_loss' => $setup['stop_loss'],
                    'risk_reward' => $setup['risk_reward'],
                    'rationale' => implode(' ', (array) ($analysis['rationale'] ?? [])),
                    'analysis_snapshot' => [
                        'context' => $this->compactContext($result['context']),
                        'analysis' => $this->compactAnalysis($analysis),
                        'qualification' => $qualification,
                    ],
                    'generated_at' => now(),
                    'expires_at' => CarbonImmutable::parse($setup['expires_at']),
                ]);

                foreach ((array) ($setup['targets'] ?? []) as $target) {
                    $signal->targets()->create([
                        'sequence' => (int) $target['sequence'],
                        'price' => (float) $target['price'],
                        'status' => 'pending',
                    ]);
                }

                $analysisRun->update([
                    'signal_id' => $signal->id,
                    'result' => 'generated',
                ]);

                SignalEvent::create([
                    'signal_id' => $signal->id,
                    'analysis_run_id' => $analysisRun->id,
                    'type' => 'generated',
                    'payload' => [
                        'trigger' => $trigger,
                        'asset_class' => $instrument->asset_class,
                        'strength' => $signal->strength,
                        'confluence_score' => (float) $signal->confluence_score,
                        'timeframe' => $signal->timeframe,
                    ],
                    'occurred_at' => now(),
                ]);

                return $this->outcome(
                    'generated',
                    $instrument,
                    $signal->fresh(['targets', 'marketInstrument']),
                    'Qualified Signal generated and placed in ready state.',
                    $analysisRun->fresh(),
                    $result
                );
            }, 3);
        } catch (\Throwable $e) {
            throw new RuntimeException("Signal generation failed for {$instrument->display_symbol}: {$e->getMessage()}", 0, $e);
        } finally {
            $lock->release();
        }
    }

    public function generateForStock(
        Stock $stock,
        ?string $marketplace = null,
        bool $force = false,
        string $trigger = 'scheduled'
    ): array {
        $instrument = MarketInstrument::query()->where('stock_id', $stock->id)->first();
        if (! $instrument) {
            throw new RuntimeException("MarketInstrument registry is missing stock {$stock->symbol}.");
        }

        return $this->generateForInstrument($instrument, $marketplace, $force, $trigger);
    }

    private function cooldownMinutes(string $timeframe): int
    {
        return match (strtolower($timeframe)) {
            '5m' => 15,
            '15m' => 45,
            '1h' => 180,
            '4h' => 720,
            '1d' => 1440,
            '1w' => 10080,
            default => 60,
        };
    }

    private function outcome(
        string $status,
        MarketInstrument $instrument,
        ?Signal $signal,
        string $reason,
        ?SignalAnalysisRun $analysisRun = null,
        ?array $intelligence = null
    ): array {
        return [
            'status' => $status,
            'market_instrument_id' => $instrument->id,
            'stock_id' => $instrument->stock_id,
            'asset_class' => $instrument->asset_class,
            'symbol' => $instrument->display_symbol,
            'signal_id' => $signal?->id,
            'signal_status' => $signal?->status,
            'reason' => $reason,
            'analysis_run_id' => $analysisRun?->id,
            'strength' => $intelligence['qualification']['strength'] ?? $signal?->strength,
            'confluence_score' => $intelligence['analysis']['confluence_score'] ?? $signal?->confluence_score,
            'direction' => $intelligence['analysis']['direction'] ?? $signal?->direction,
        ];
    }

    private function compactContext(array $context): array
    {
        $timeframes = [];
        foreach ((array) ($context['timeframes'] ?? []) as $name => $rows) {
            $timeframes[$name] = count((array) $rows);
        }

        return [
            'asset_class' => $context['asset_class'] ?? 'stock',
            'market_instrument_id' => $context['market_instrument_id'] ?? null,
            'symbol' => $context['symbol'] ?? null,
            'display_symbol' => $context['display_symbol'] ?? $context['symbol'] ?? null,
            'marketplace' => $context['marketplace'] ?? null,
            'current_price' => $context['current_price'] ?? null,
            'analysis_source' => $context['analysis_source'] ?? null,
            'trend' => $context['trend'] ?? null,
            'momentum_percent' => $context['momentum_percent'] ?? null,
            'support' => $context['support'] ?? null,
            'resistance' => $context['resistance'] ?? null,
            'sma20' => $context['sma20'] ?? null,
            'sma50' => $context['sma50'] ?? null,
            'sma200' => $context['sma200'] ?? null,
            'risk_reward' => $context['risk_reward'] ?? null,
            'default_timeframe' => $context['default_timeframe'] ?? null,
            'market_session' => $context['market_session'] ?? null,
            'active_sessions' => $context['active_sessions'] ?? [],
            'preferred_sessions' => $context['preferred_sessions'] ?? [],
            'preferred_session_active' => $context['preferred_session_active'] ?? null,
            'captured_at' => $context['captured_at'] ?? null,
            'price_precision' => $context['price_precision'] ?? null,
            'pip_size' => $context['pip_size'] ?? null,
            'timeframe_samples' => $timeframes,
        ];
    }

    private function compactAnalysis(array $analysis): array
    {
        return [
            'timeframe' => $analysis['timeframe'] ?? null,
            'market_price' => $analysis['market_price'] ?? null,
            'direction' => $analysis['direction'] ?? null,
            'directional_score' => $analysis['directional_score'] ?? null,
            'confluence_score' => $analysis['confluence_score'] ?? null,
            'data_quality' => $analysis['data_quality'] ?? null,
            'sample_count' => $analysis['sample_count'] ?? null,
            'atr' => $analysis['atr'] ?? null,
            'components' => $analysis['components'] ?? [],
            'rationale' => $analysis['rationale'] ?? [],
        ];
    }

    private function compactConclusion(array $result): array
    {
        return [
            'analysis' => $this->compactAnalysis($result['analysis']),
            'qualification' => $result['qualification'],
            'setup' => $result['setup'],
        ];
    }
}
