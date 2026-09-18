<?php

namespace App\Services;

use App\Models\ForexPair;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class ForexSessionService
{
    private const SESSIONS = [
        'sydney' => ['timezone' => 'Australia/Sydney', 'open' => '08:00', 'close' => '17:00'],
        'tokyo' => ['timezone' => 'Asia/Tokyo', 'open' => '09:00', 'close' => '18:00'],
        'london' => ['timezone' => 'Europe/London', 'open' => '08:00', 'close' => '17:00'],
        'new_york' => ['timezone' => 'America/New_York', 'open' => '08:00', 'close' => '17:00'],
    ];

    public function isMarketOpen(?CarbonInterface $at = null): bool
    {
        $ny = $this->at($at)->setTimezone('America/New_York');
        $weekday = $ny->dayOfWeek;
        $minutes = ($ny->hour * 60) + $ny->minute;

        if ($weekday === Carbon::SATURDAY) {
            return false;
        }

        if ($weekday === Carbon::SUNDAY) {
            return $minutes >= (17 * 60);
        }

        if ($weekday === Carbon::FRIDAY) {
            return $minutes < (17 * 60);
        }

        return true;
    }

    public function activeSessions(?CarbonInterface $at = null): array
    {
        if (! $this->isMarketOpen($at)) {
            return [];
        }

        $moment = $this->at($at);
        $active = [];

        foreach (self::SESSIONS as $name => $session) {
            $local = $moment->copy()->setTimezone($session['timezone']);
            if ($local->isWeekend()) {
                continue;
            }

            [$openHour, $openMinute] = array_map('intval', explode(':', $session['open']));
            [$closeHour, $closeMinute] = array_map('intval', explode(':', $session['close']));
            $open = $local->copy()->setTime($openHour, $openMinute, 0);
            $close = $local->copy()->setTime($closeHour, $closeMinute, 0);

            if ($local->betweenIncluded($open, $close)) {
                $active[] = $name;
            }
        }

        return array_values(array_unique($active));
    }

    public function stateForPair(ForexPair $pair, ?CarbonInterface $at = null): array
    {
        $active = $this->activeSessions($at);
        $preferred = array_values((array) ($pair->preferred_sessions ?? []));
        $isOverlap = in_array('london', $active, true) && in_array('new_york', $active, true);
        $matched = array_values(array_intersect($preferred, $active));

        if ($isOverlap && in_array('london_new_york_overlap', $preferred, true)) {
            $matched[] = 'london_new_york_overlap';
        }

        return [
            'market_open' => $this->isMarketOpen($at),
            'active_sessions' => $active,
            'preferred_sessions' => $preferred,
            'preferred_session_active' => ! empty($matched),
            'matched_preferred_sessions' => array_values(array_unique($matched)),
            'label' => $this->label($active, $isOverlap),
        ];
    }

    private function label(array $active, bool $isOverlap): string
    {
        if (! $active) {
            return 'closed';
        }

        if ($isOverlap) {
            return 'london_new_york_overlap';
        }

        return count($active) === 1 ? $active[0] : implode('_', $active);
    }

    private function at(?CarbonInterface $at = null): Carbon
    {
        return $at
            ? Carbon::instance($at)->copy()->utc()
            : now()->copy()->utc();
    }
}
