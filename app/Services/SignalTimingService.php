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
        $signal->loadMissing(['targets', 'marketInstrument.forexPair', 'marketInstrument.canonicalCryptoPair']);

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
            $stop,
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
            'early' => [
                'label' => $this->earlyLabel((string) $signal->direction, $moderateLow, $moderateHigh, $stop, $precision),
                'description' => 'Price is still on the pre-entry side of the setup. Wait for the market to reach the planned tolerance band before considering a fresh entry.',
            ],
            'too_late' => [
                'label' => $this->tooLateLabel((string) $signal->direction, $moderateLow, $moderateHigh, $precision),
                'description' => 'Price has already moved beyond the acceptable entry tolerance in the Signal direction. Avoid chasing the move.',
            ],
            'danger' => [
                'label' => $this->dangerLabel((string) $signal->direction, $stop, $precision),
                'description' => 'The stop boundary has been breached, the market is closed, or the original fresh-entry risk contract is no longer valid.',
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
        float $stop,
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

        $direction = strtolower((string) $signal->direction);

        if ($direction === 'sell') {
            if ($stop > 0 && $price >= $stop) {
                return ['state' => 'dangerous', 'label' => 'Dangerous timing', 'description' => 'Current price has crossed the Signal stop boundary. The original risk contract is no longer valid for a fresh entry.', 'tone' => 'negative'];
            }

            if ($price > $moderateHigh) {
                return ['state' => 'early', 'label' => 'Early timing', 'description' => 'Price has not yet reached the planned SELL entry window. Waiting for price to return into the tolerance band avoids anticipating the setup.', 'tone' => 'info'];
            }

            if ($price < $moderateLow) {
                return ['state' => 'too_late', 'label' => 'Too late for fresh entry', 'description' => 'Price has already moved beyond the acceptable SELL entry tolerance. A fresh entry here would be chasing the move.', 'tone' => 'negative'];
            }
        } else {
            if ($stop > 0 && $price <= $stop) {
                return ['state' => 'dangerous', 'label' => 'Dangerous timing', 'description' => 'Current price has crossed the Signal stop boundary. The original risk contract is no longer valid for a fresh entry.', 'tone' => 'negative'];
            }

            if ($price < $moderateLow) {
                return ['state' => 'early', 'label' => 'Early timing', 'description' => 'Price has not yet reached the planned BUY entry window. Waiting for price to return into the tolerance band avoids anticipating the setup.', 'tone' => 'info'];
            }

            if ($price > $moderateHigh) {
                return ['state' => 'too_late', 'label' => 'Too late for fresh entry', 'description' => 'Price has already moved beyond the acceptable BUY entry tolerance. A fresh entry here would be chasing the move.', 'tone' => 'negative'];
            }
        }

        return ['state' => 'dangerous', 'label' => 'Dangerous timing', 'description' => 'Current market conditions no longer fit the original fresh-entry risk contract.', 'tone' => 'negative'];
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

        if ($instrument?->isCrypto() && strtolower((string) $signal->marketplace) === 'live') {
            return [
                'status' => '24_7',
                'label' => '24/7 crypto market',
                'best' => '24/7 market',
                'window' => 'Always open',
                'market_time' => now()->utc()->format('H:i'),
                'timezone' => 'UTC',
                'market_open' => true,
                'preferred_session_active' => true,
                'active_sessions' => ['24_7'],
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

    private function earlyLabel(string $direction, float $moderateLow, float $moderateHigh, float $stop, int $precision): string
    {
        if (strtolower($direction) === 'sell') {
            $upper = $stop > $moderateHigh ? number_format($stop, $precision) : 'the stop boundary';
            return 'Above '.number_format($moderateHigh, $precision).' and below '.$upper;
        }

        $lower = $stop > 0 && $stop < $moderateLow ? number_format($stop, $precision) : 'the stop boundary';
        return 'Above '.$lower.' and below '.number_format($moderateLow, $precision);
    }

    private function tooLateLabel(string $direction, float $moderateLow, float $moderateHigh, int $precision): string
    {
        return strtolower($direction) === 'sell'
            ? 'Below '.number_format($moderateLow, $precision).' — do not chase'
            : 'Above '.number_format($moderateHigh, $precision).' — do not chase';
    }

    private function dangerLabel(string $direction, float $stop, int $precision): string
    {
        if ($stop <= 0) {
            return 'Stop boundary breached or market unavailable';
        }

        return strtolower($direction) === 'sell'
            ? 'At or above stop '.number_format($stop, $precision)
            : 'At or below stop '.number_format($stop, $precision);
    }

}
