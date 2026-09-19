<?php

namespace App\Services;

use App\Models\Signal;
use App\Models\SignalAnalysisRun;
use App\Models\SignalEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SignalReanalysisService
{
    public function __construct(
        private readonly SignalIntelligenceService $intelligence,
        private readonly SignalRevisionService $revisions
    ) {}

    public function reanalyze(
        Signal $signal,
        bool $applyAdjustments = true,
        string $trigger = 'scheduled',
        ?User $actor = null
    ): array {
        $signal->loadMissing(['stock', 'marketInstrument.stock', 'marketInstrument.forexPair', 'marketInstrument.canonicalCryptoPair', 'targets']);

        if (! in_array($signal->status, Signal::OPEN_STATUSES, true)) {
            return [
                'status' => 'skipped',
                'signal_id' => $signal->id,
                'assessment' => 'terminal',
                'revision_id' => null,
                'reason' => 'Terminal Signals are not re-analyzed.',
            ];
        }

        $instrument = $signal->marketInstrument;
        if (! $instrument) {
            throw new \RuntimeException("Signal #{$signal->id} has no market-instrument authority.");
        }

        $result = $this->intelligence->analyzeInstrument(
            $instrument,
            $signal->marketplace,
            $signal->timeframe
        );

        $analysis = $result['analysis'];
        $qualification = $result['qualification'];
        $setup = $result['setup'];

        $analysisRun = SignalAnalysisRun::create([
            'signal_id' => $signal->id,
            'stock_id' => $signal->stock_id,
            'market_instrument_id' => $signal->market_instrument_id,
            'marketplace' => $signal->marketplace,
            'trigger' => $trigger,
            'source' => 'signal_intelligence',
            'timeframe' => $analysis['timeframe'] ?? $signal->timeframe,
            'result' => 'observed',
            'confluence_score' => $analysis['confluence_score'] ?? null,
            'market_price' => $analysis['market_price'] ?? null,
            'context' => $this->compactContext($result['context']),
            'conclusion' => [
                'analysis' => $this->compactAnalysis($analysis),
                'qualification' => $qualification,
                'setup' => $setup,
            ],
            'analyzed_at' => now(),
            'actor_user_id' => $actor?->id,
        ]);

        $newDirection = (string) ($analysis['direction'] ?? 'neutral');
        $oldDirection = (string) $signal->direction;
        $confluence = (float) ($analysis['confluence_score'] ?? 0);
        $oldConfluence = (float) ($signal->confluence_score ?? 0);
        $delta = $confluence - $oldConfluence;

        if (
            $newDirection === 'neutral'
            || $newDirection !== $oldDirection
            || (($qualification['result'] ?? null) === 'reject' && $confluence < 40)
        ) {
            $this->invalidate($signal, $analysisRun, $actor, $newDirection, $confluence);

            return [
                'status' => 'invalidated',
                'signal_id' => $signal->id,
                'assessment' => 'invalidated',
                'revision_id' => null,
                'analysis_run_id' => $analysisRun->id,
                'reason' => 'Fresh analysis no longer supports the original Signal direction.',
            ];
        }

        $assessment = $delta >= 5
            ? 'strengthened'
            : ($delta <= -5 ? 'weakened' : 'no_change');

        $proposal = $setup
            ? $this->adjustmentProposal($signal, $result, $setup, $assessment)
            : [];

        if (! $proposal) {
            $analysisRun->update(['result' => $assessment]);
            $this->recordAssessmentEvent($signal, $analysisRun, $actor, $assessment, $delta);

            return [
                'status' => $assessment,
                'signal_id' => $signal->id,
                'assessment' => $assessment,
                'revision_id' => null,
                'analysis_run_id' => $analysisRun->id,
                'reason' => 'Re-analysis completed with no material Signal contract adjustment.',
            ];
        }

        if (! $applyAdjustments) {
            $analysisRun->update(['result' => 'adjustment_recommended']);

            return [
                'status' => 'adjustment_recommended',
                'signal_id' => $signal->id,
                'assessment' => $assessment,
                'revision_id' => null,
                'analysis_run_id' => $analysisRun->id,
                'reason' => 'Fresh analysis proposes a material Signal adjustment; automatic application was disabled.',
                'proposal' => $proposal,
            ];
        }

        $revision = $this->revisions->apply(
            $signal,
            $proposal,
            $this->adjustmentReason($assessment),
            $analysisRun,
            'automatic',
            $actor?->id
        );

        if ($revision) {
            $analysisRun->update(['result' => 'adjusted']);

            return [
                'status' => 'adjusted',
                'signal_id' => $signal->id,
                'assessment' => $assessment,
                'revision_id' => $revision->id,
                'revision_number' => $revision->revision_number,
                'analysis_run_id' => $analysisRun->id,
                'reason' => 'Material Signal terms were adjusted and recorded as an immutable revision.',
            ];
        }

        $analysisRun->update(['result' => $assessment]);
        $this->recordAssessmentEvent($signal, $analysisRun, $actor, $assessment, $delta);

        return [
            'status' => $assessment,
            'signal_id' => $signal->id,
            'assessment' => $assessment,
            'revision_id' => null,
            'analysis_run_id' => $analysisRun->id,
            'reason' => 'Re-analysis completed; proposed values were not materially different.',
        ];
    }

    private function invalidate(
        Signal $signal,
        SignalAnalysisRun $analysisRun,
        ?User $actor,
        string $newDirection,
        float $confluence
    ): void {
        DB::transaction(function () use ($signal, $analysisRun, $actor, $newDirection, $confluence) {
            $locked = Signal::query()->lockForUpdate()->findOrFail($signal->id);
            if (! in_array($locked->status, Signal::OPEN_STATUSES, true)) {
                return;
            }

            $locked->update([
                'status' => 'invalidated',
                'invalidated_at' => now(),
                'closed_at' => now(),
                'updated_by_user_id' => $actor?->id,
            ]);

            $analysisRun->update(['result' => 'invalidated']);

            SignalEvent::create([
                'signal_id' => $locked->id,
                'analysis_run_id' => $analysisRun->id,
                'actor_user_id' => $actor?->id,
                'type' => 'invalidated',
                'payload' => [
                    'previous_direction' => $locked->direction,
                    'fresh_direction' => $newDirection,
                    'fresh_confluence_score' => $confluence,
                ],
                'occurred_at' => now(),
            ]);
        }, 3);
    }

    private function adjustmentProposal(
        Signal $signal,
        array $result,
        array $setup,
        string $assessment
    ): array {
        $price = (float) ($result['analysis']['market_price'] ?? 0);
        $threshold = max($price * 0.001, 0.00000001);
        $proposal = [];

        if ($signal->status !== 'active') {
            foreach (['entry_min', 'entry_max', 'stop_loss'] as $key) {
                $current = (float) ($signal->{$key} ?? 0);
                $next = (float) ($setup[$key] ?? 0);
                if ($next > 0 && abs($current - $next) > $threshold) {
                    $proposal[$key] = $next;
                }
            }

            $targets = $this->materialTargetAdjustments($signal, $setup, $price, false);
            if ($targets) {
                $proposal['targets'] = $targets;
            }
        } else {
            $safeStop = $this->safeActiveStop($signal, (float) ($setup['stop_loss'] ?? 0), $price);
            if ($safeStop !== null && abs((float) $signal->stop_loss - $safeStop) > $threshold) {
                $proposal['stop_loss'] = $safeStop;
            }

            $targets = $this->materialTargetAdjustments($signal, $setup, $price, true);
            if ($targets) {
                $proposal['targets'] = $targets;
            }
        }

        $freshStrength = $result['qualification']['strength'] ?? $signal->strength;
        $freshConfluence = (float) ($result['analysis']['confluence_score'] ?? $signal->confluence_score ?? 0);

        if ($freshStrength !== $signal->strength || in_array($assessment, ['strengthened', 'weakened'], true)) {
            $proposal['strength'] = $freshStrength;
            $proposal['confluence_score'] = $freshConfluence;
            $proposal['rationale'] = implode(' ', (array) ($result['analysis']['rationale'] ?? []));
            $proposal['analysis_snapshot'] = [
                'context' => $this->compactContext($result['context']),
                'analysis' => $this->compactAnalysis($result['analysis']),
                'qualification' => $result['qualification'],
            ];
        }

        return $proposal;
    }

    private function safeActiveStop(Signal $signal, float $proposed, float $marketPrice): ?float
    {
        if ($proposed <= 0 || $marketPrice <= 0) {
            return null;
        }

        $current = (float) $signal->stop_loss;

        if ($signal->direction === 'buy') {
            if ($proposed <= $current || $proposed >= $marketPrice) {
                return null;
            }
            return $proposed;
        }

        if ($signal->direction === 'sell') {
            if ($proposed >= $current || $proposed <= $marketPrice) {
                return null;
            }
            return $proposed;
        }

        return null;
    }

    private function materialTargetAdjustments(
        Signal $signal,
        array $setup,
        float $marketPrice,
        bool $active
    ): array {
        $proposed = collect((array) ($setup['targets'] ?? []))
            ->mapWithKeys(fn ($target) => [(int) $target['sequence'] => (float) $target['price']]);

        $changes = [];
        foreach ($signal->targets as $target) {
            if ($target->status !== 'pending' || ! $proposed->has((int) $target->sequence)) {
                continue;
            }

            $next = (float) $proposed->get((int) $target->sequence);
            $current = (float) $target->price;
            $threshold = max(abs($current) * 0.001, 0.00000001);

            if (abs($current - $next) <= $threshold) {
                continue;
            }

            if ($active) {
                if ($signal->direction === 'buy' && $next <= $marketPrice) {
                    continue;
                }
                if ($signal->direction === 'sell' && $next >= $marketPrice) {
                    continue;
                }
            }

            $changes[(int) $target->sequence] = $next;
        }

        return $changes;
    }

    private function recordAssessmentEvent(
        Signal $signal,
        SignalAnalysisRun $analysisRun,
        ?User $actor,
        string $assessment,
        float $delta
    ): void {
        if (! in_array($assessment, ['strengthened', 'weakened'], true)) {
            return;
        }

        SignalEvent::create([
            'signal_id' => $signal->id,
            'analysis_run_id' => $analysisRun->id,
            'actor_user_id' => $actor?->id,
            'type' => $assessment,
            'payload' => [
                'confluence_delta' => round($delta, 2),
                'new_confluence_score' => (float) $analysisRun->confluence_score,
            ],
            'occurred_at' => now(),
        ]);
    }

    private function adjustmentReason(string $assessment): string
    {
        return match ($assessment) {
            'strengthened' => 'Automatic re-analysis found stronger market confirmation and materially updated the Signal contract.',
            'weakened' => 'Automatic re-analysis found weaker market confirmation and materially updated the Signal contract.',
            default => 'Automatic re-analysis found a material market-structure change and updated the Signal contract.',
        };
    }

    private function compactContext(array $context): array
    {
        $counts = [];
        foreach ((array) ($context['timeframes'] ?? []) as $name => $rows) {
            $counts[$name] = count((array) $rows);
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
            'market_session' => $context['market_session'] ?? null,
            'active_sessions' => $context['active_sessions'] ?? [],
            'preferred_sessions' => $context['preferred_sessions'] ?? [],
            'preferred_session_active' => $context['preferred_session_active'] ?? null,
            'price_precision' => $context['price_precision'] ?? null,
            'pip_size' => $context['pip_size'] ?? null,
            'captured_at' => $context['captured_at'] ?? null,
            'timeframe_samples' => $counts,
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
}
