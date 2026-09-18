<?php

namespace App\Services;

use App\Models\Signal;
use App\Models\SignalAnalysisRun;
use App\Models\SignalEvent;
use App\Models\SignalRevision;
use Illuminate\Support\Facades\DB;

class SignalRevisionService
{
    public function apply(
        Signal $signal,
        array $newValues,
        string $reason,
        ?SignalAnalysisRun $analysisRun = null,
        string $changeSource = 'automatic',
        ?int $changedByUserId = null
    ): ?SignalRevision {
        return DB::transaction(function () use (
            $signal,
            $newValues,
            $reason,
            $analysisRun,
            $changeSource,
            $changedByUserId
        ) {
            $signal = Signal::query()->with('targets')->lockForUpdate()->findOrFail($signal->id);

            $previous = $this->snapshot($signal);
            $normalized = $this->normalizeNewValues($signal, $newValues);
            $next = array_replace($previous, $normalized);

            if (! $this->hasMaterialDifference($previous, $next)) {
                return null;
            }

            $revisionNumber = ((int) SignalRevision::query()
                ->where('signal_id', $signal->id)
                ->lockForUpdate()
                ->max('revision_number')) + 1;

            $signalFields = array_intersect_key($normalized, array_flip([
                'direction',
                'timeframe',
                'strength',
                'confluence_score',
                'entry_min',
                'entry_max',
                'stop_loss',
                'risk_reward',
                'rationale',
                'analysis_snapshot',
                'expires_at',
            ]));

            if ($changedByUserId !== null) {
                $signalFields['updated_by_user_id'] = $changedByUserId;
            }

            if ($signalFields) {
                $signal->update($signalFields);
            }

            if (isset($normalized['targets']) && is_array($normalized['targets'])) {
                foreach ($normalized['targets'] as $sequence => $price) {
                    $target = $signal->targets->firstWhere('sequence', (int) $sequence);
                    if ($target && $target->status === 'pending') {
                        $target->update(['price' => (float) $price]);
                    }
                }
            }

            $revision = SignalRevision::create([
                'signal_id' => $signal->id,
                'revision_number' => $revisionNumber,
                'change_source' => $changeSource,
                'previous_values' => $previous,
                'new_values' => $this->snapshot($signal->fresh(['targets'])),
                'reason' => $reason,
                'analysis_run_id' => $analysisRun?->id,
                'changed_by_user_id' => $changedByUserId,
            ]);

            SignalEvent::create([
                'signal_id' => $signal->id,
                'analysis_run_id' => $analysisRun?->id,
                'actor_user_id' => $changedByUserId,
                'type' => 'adjusted',
                'payload' => [
                    'revision_number' => $revisionNumber,
                    'change_source' => $changeSource,
                    'reason' => $reason,
                ],
                'occurred_at' => now(),
            ]);

            return $revision;
        }, 3);
    }

    private function snapshot(Signal $signal): array
    {
        $signal->loadMissing('targets');

        return [
            'direction' => $signal->direction,
            'timeframe' => $signal->timeframe,
            'strength' => $signal->strength,
            'confluence_score' => $signal->confluence_score !== null ? (float) $signal->confluence_score : null,
            'entry_min' => $signal->entry_min !== null ? (float) $signal->entry_min : null,
            'entry_max' => $signal->entry_max !== null ? (float) $signal->entry_max : null,
            'stop_loss' => $signal->stop_loss !== null ? (float) $signal->stop_loss : null,
            'risk_reward' => $signal->risk_reward !== null ? (float) $signal->risk_reward : null,
            'rationale' => $signal->rationale,
            'analysis_snapshot' => $signal->analysis_snapshot,
            'expires_at' => $signal->expires_at?->toIso8601String(),
            'targets' => $signal->targets
                ->sortBy('sequence')
                ->mapWithKeys(fn ($target) => [(int) $target->sequence => (float) $target->price])
                ->all(),
        ];
    }

    private function normalizeNewValues(Signal $signal, array $values): array
    {
        $normalized = $values;

        foreach (['confluence_score', 'entry_min', 'entry_max', 'stop_loss', 'risk_reward'] as $key) {
            if (array_key_exists($key, $normalized) && $normalized[$key] !== null) {
                $normalized[$key] = (float) $normalized[$key];
            }
        }

        if (isset($normalized['targets']) && is_array($normalized['targets'])) {
            $targets = [];
            foreach ($normalized['targets'] as $sequence => $price) {
                if (is_array($price)) {
                    $sequence = (int) ($price['sequence'] ?? $sequence);
                    $price = $price['price'] ?? null;
                }
                if ($price !== null) {
                    $targets[(int) $sequence] = (float) $price;
                }
            }
            $normalized['targets'] = $targets;
        }

        if (isset($normalized['expires_at']) && $normalized['expires_at'] instanceof \DateTimeInterface) {
            $normalized['expires_at'] = $normalized['expires_at']->format(DATE_ATOM);
        }

        return $normalized;
    }

    private function hasMaterialDifference(array $before, array $after): bool
    {
        foreach ($after as $key => $value) {
            if (! array_key_exists($key, $before)) {
                continue;
            }

            if ($key === 'targets') {
                if ($this->arraysDiffer((array) $before[$key], (array) $value)) {
                    return true;
                }
                continue;
            }

            if (is_float($value) || is_int($value)) {
                $left = (float) ($before[$key] ?? 0);
                $right = (float) $value;
                $tolerance = max(abs($left) * 0.000001, 0.00000001);
                if (abs($left - $right) > $tolerance) {
                    return true;
                }
                continue;
            }

            if ($before[$key] != $value) {
                return true;
            }
        }

        return false;
    }

    private function arraysDiffer(array $before, array $after): bool
    {
        ksort($before);
        ksort($after);

        if (array_keys($before) !== array_keys($after)) {
            return true;
        }

        foreach ($after as $key => $value) {
            $left = (float) ($before[$key] ?? 0);
            $right = (float) $value;
            $tolerance = max(abs($left) * 0.000001, 0.00000001);
            if (abs($left - $right) > $tolerance) {
                return true;
            }
        }

        return false;
    }
}
