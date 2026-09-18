<?php

namespace App\Services;

use App\Models\Signal;

class SignalTimingService
{
    private const MODERATE_FRACTION = 0.25;

    public function __construct(
        private readonly MarketSessionService $stockSession,
        private readonly ForexSessionService $forexSession
    ) {}

    public function forSignal(Signal $signal, array $analysis): array
    {
        $signal->loadMissing(['targets', 'marketInstrument.forexPair']);

        $price = (float) ($analysis['current_price'] ?? 0);
        $entryMin = (float) $signal->entry_min;
        $entryMax = (float) $signal->entry_max;
        $stop = (float) $signal->stop_loss;
        $targetOne = (float) optional($signal->targets->sortBy('sequence')->first())->price;
        $precision = $signal->price_precision;

        [$moderateLow, $moderateHigh] = $this->moderateBounds(
            (string) $signal->direction,
            $entryMin,
            $entryMax,
            $stop,
            $targetOne
        );

        $session = $this->sessionContext($signal);
        $entry = $this->entryAssessment(
            $signal,
            $price,
            $entryMin,
            $entryMax,
            $moderateLow,
            $moderateHigh,
            $session
        );

        return [
            'entry' => $entry,
            'current_price' => $price,
            'optimal_range' => [
                'min' => $entryMin,
                'max' => $entryMax,
                'label' => $this->range($entryMin, $entryMax, $precision),
            ],
            'moderate_range' => [
                'min' => $moderateLow,
                'max' => $moderateHigh,
                'label' => $this->range($moderateLow, $moderateHigh, $precision),
            ],
            'danger' => [
                'label' => $this->dangerLabel($moderateLow, $moderateHigh, $precision),
                'description' => 'Outside the moderate tolerance band, or outside the preferred trading session, fresh-entry risk is elevated.',
            ],
            'best_timeframe' => strtoupper((string) $signal->timeframe),
            'session' => $session,
            'method' => 'Entry timing compares live price with the Signal entry zone, stop-loss distance, first-target distance and the instrument’s preferred trading session.',
        ];
    }

    private function moderateBounds(string $direction, float $entryMin, float $entryMax, float $stop, float $targetOne): array
    {
        $entryWidth = max($entryMax - $entryMin, 0.00000001);

        if (strtolower($direction) === 'sell') {
            $stopSpan = $stop > $entryMax ? $stop - $entryMax : $entryWidth;
            $targetSpan = $targetOne > 0 && $targetOne < $entryMin ? $entryMin - $targetOne : $entryWidth;
            return [
                max(0, $entryMin - ($targetSpan * self::MODERATE_FRACTION)),
                $entryMax + ($stopSpan * self::MODERATE_FRACTION),
            ];
        }

        $stopSpan = $stop > 0 && $stop < $entryMin ? $entryMin - $stop : $entryWidth;
        $targetSpan = $targetOne > $entryMax ? $targetOne - $entryMax : $entryWidth;
        return [
            max(0, $entryMin - ($stopSpan * self::MODERATE_FRACTION)),
            $entryMax + ($targetSpan * self::MODERATE_FRACTION),
        ];
    }

    private function entryAssessment(
        Signal $signal,
        float $price,
        float $entryMin,
        float $entryMax,
        float $moderateLow,
        float $moderateHigh,
        array $session
    ): array {
        if (in_array($signal->status, Signal::TERMINAL_STATUSES, true)) {
            return ['state' => 'inactive', 'label' => 'Signal closed', 'description' => 'This Signal is terminal. Its entry window is no longer active.', 'tone' => 'neutral'];
        }

        if ($price <= 0) {
            return ['state' => 'unavailable', 'label' => 'Timing unavailable', 'description' => 'A valid current market price is not available for a live timing classification.', 'tone' => 'neutral'];
        }

        if (! ($session['market_open'] ?? true)) {
            return ['state' => 'dangerous', 'label' => 'Dangerous timing', 'description' => 'This market is currently closed. Fresh execution should wait for the market to reopen.', 'tone' => 'negative'];
        }

        $insideOptimal = $price >= $entryMin && $price <= $entryMax;
        $insideModerate = $price >= $moderateLow && $price <= $moderateHigh;
        $preferred = (bool) ($session['preferred_session_active'] ?? false);

        if ($insideOptimal && $preferred) {
            return ['state' => 'optimal', 'label' => 'Optimal entry window', 'description' => 'Current price is inside the Signal entry zone during a preferred trading session.', 'tone' => 'positive'];
        }

        if ($insideOptimal && ! $preferred) {
            return ['state' => 'moderate', 'label' => 'Moderate timing', 'description' => 'Price is inside the entry zone, but the instrument is outside its preferred trading session. Entry quality is acceptable with extra caution.', 'tone' => 'warning'];
        }

        if ($insideModerate) {
            $relation = $price < $entryMin ? 'below' : 'above';
            $sessionNote = $preferred ? '' : ' The preferred session is also not active.';
            return ['state' => 'moderate', 'label' => 'Moderate timing', 'description' => "Current price is {$relation} the original entry zone but remains inside the moderate tolerance band.{$sessionNote}", 'tone' => 'warning'];
        }

        return ['state' => 'dangerous', 'label' => 'Dangerous timing', 'description' => 'Current price is materially outside the Signal entry tolerance and no longer matches the original risk/reward contract for a fresh entry.', 'tone' => 'negative'];
    }

    private function sessionContext(Signal $signal): array
    {
        $instrument = $signal->marketInstrument;

        if ($instrument?->isForex() && $instrument->forexPair) {
            $state = $this->forexSession->stateForPair($instrument->forexPair);
            $preferred = (array) ($state['preferred_sessions'] ?? []);
            $active = (array) ($state['active_sessions'] ?? []);

            return [
                'status' => $state['label'],
                'label' => $this->humanizeSession((string) $state['label']),
                'best' => $preferred ? implode(', ', array_map([$this, 'humanizeSession'], $preferred)) : 'Liquid forex sessions',
                'window' => '24/5 market · preferred session windows vary by pair',
                'market_time' => now()->utc()->format('H:i'),
                'timezone' => 'UTC',
                'market_open' => (bool) ($state['market_open'] ?? false),
                'preferred_session_active' => (bool) ($state['preferred_session_active'] ?? false),
                'active_sessions' => $active,
            ];
        }

        if (strtolower((string) $signal->marketplace) !== 'live') {
            return [
                'status' => 'controlled',
                'label' => 'Controlled market',
                'best' => 'Controlled engine session',
                'window' => 'Engine runtime',
                'market_time' => now()->format('H:i'),
                'timezone' => config('app.timezone', 'UTC'),
                'market_open' => true,
                'preferred_session_active' => true,
                'active_sessions' => ['controlled'],
            ];
        }

        $status = $this->stockSession->status();
        $marketTime = $this->stockSession->now();

        return [
            'status' => $status,
            'label' => match ($status) {
                'open' => 'Regular session open',
                'pre_market' => 'Pre-market',
                'after_hours' => 'After-hours',
                default => 'Market closed',
            },
            'best' => 'Regular market session',
            'window' => MarketSessionService::OPEN.'–'.MarketSessionService::CLOSE.' ET',
            'market_time' => $marketTime->format('H:i'),
            'timezone' => 'ET',
            'market_open' => $status === 'open',
            'preferred_session_active' => $status === 'open',
            'active_sessions' => $status === 'open' ? ['new_york'] : [],
        ];
    }

    private function humanizeSession(string $session): string
    {
        return ucwords(str_replace('_', ' ', $session));
    }

    private function range(float $min, float $max, int $precision): string
    {
        return number_format($min, $precision).' – '.number_format($max, $precision);
    }

    private function dangerLabel(float $moderateLow, float $moderateHigh, int $precision): string
    {
        return 'Below '.number_format($moderateLow, $precision).' or above '.number_format($moderateHigh, $precision);
    }
}
