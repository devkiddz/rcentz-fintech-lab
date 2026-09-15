<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class MarketSessionService
{
    public const TIMEZONE = 'America/New_York';
    public const OPEN = '09:30';
    public const CLOSE = '16:00';

    public function now(): Carbon
    {
        return now()->copy()->setTimezone(self::TIMEZONE);
    }

    public function isOpen(?CarbonInterface $at = null): bool
    {
        $marketTime = $this->marketTime($at);

        if (! $this->isTradingDay($marketTime)) {
            return false;
        }

        $open = $marketTime->copy()->setTime(9, 30);
        $close = $marketTime->copy()->setTime(16, 0);

        return $marketTime->betweenIncluded($open, $close);
    }

    public function isTradingDay(?CarbonInterface $at = null): bool
    {
        $marketTime = $this->marketTime($at);

        if ($marketTime->isWeekend()) {
            return false;
        }

        $date = $marketTime->toDateString();

        $holidays = array_merge(
            $this->holidaysForYear($marketTime->year),
            $this->holidaysForYear($marketTime->year + 1)
        );

        return ! in_array($date, $holidays, true);
    }

    public function status(?CarbonInterface $at = null): string
    {
        $marketTime = $this->marketTime($at);

        if (! $this->isTradingDay($marketTime)) {
            return 'closed';
        }

        $open = $marketTime->copy()->setTime(9, 30);
        $close = $marketTime->copy()->setTime(16, 0);

        if ($marketTime->lt($open)) {
            return 'pre_market';
        }

        if ($marketTime->lte($close)) {
            return 'open';
        }

        return 'after_hours';
    }

    public function marketTime(?CarbonInterface $at = null): Carbon
    {
        return $at
            ? Carbon::instance($at)->copy()->setTimezone(self::TIMEZONE)
            : $this->now();
    }

    public function regularCloseFor(CarbonInterface $at): Carbon
    {
        $marketTime = $this->marketTime($at);

        return $marketTime->copy()->setTime(16, 0);
    }

    /**
     * Every long position has an effective end time.
     *
     * If the requested duration ends before 16:00 ET, duration wins.
     * If 16:00 ET arrives first, market close wins.
     * If no duration is supplied, the regular session close is the end.
     */
    public function effectiveEnd(
        CarbonInterface $openedAt,
        ?int $durationMinutes
    ): array {
        $openedEt = $this->marketTime($openedAt);
        $sessionCloseEt = $this->regularCloseFor($openedEt);

        $requestedEt = $durationMinutes && $durationMinutes > 0
            ? $openedEt->copy()->addMinutes($durationMinutes)
            : $sessionCloseEt->copy();

        $marketCloseWins = $requestedEt->gte($sessionCloseEt);
        $effectiveEt = $marketCloseWins
            ? $sessionCloseEt
            : $requestedEt;

        return [
            'effective_at' => $effectiveEt->copy()->setTimezone(config('app.timezone', 'UTC')),
            'effective_at_et' => $effectiveEt,
            'requested_at_et' => $requestedEt,
            'session_close_et' => $sessionCloseEt,
            'reason' => $marketCloseWins ? 'market_close' : 'time_expiry',
        ];
    }

    public function holidaysForYear(int $year): array
    {
        return array_values(array_unique([
            $this->observed(Carbon::create($year, 1, 1, 0, 0, 0, self::TIMEZONE))->toDateString(),
            Carbon::create($year, 1, 1, 0, 0, 0, self::TIMEZONE)->nthOfMonth(3, Carbon::MONDAY)->toDateString(),
            Carbon::create($year, 2, 1, 0, 0, 0, self::TIMEZONE)->nthOfMonth(3, Carbon::MONDAY)->toDateString(),
            $this->goodFriday($year)->toDateString(),
            Carbon::create($year, 5, 1, 0, 0, 0, self::TIMEZONE)->lastOfMonth(Carbon::MONDAY)->toDateString(),
            $this->observed(Carbon::create($year, 6, 19, 0, 0, 0, self::TIMEZONE))->toDateString(),
            $this->observed(Carbon::create($year, 7, 4, 0, 0, 0, self::TIMEZONE))->toDateString(),
            Carbon::create($year, 9, 1, 0, 0, 0, self::TIMEZONE)->firstOfMonth(Carbon::MONDAY)->toDateString(),
            Carbon::create($year, 11, 1, 0, 0, 0, self::TIMEZONE)->nthOfMonth(4, Carbon::THURSDAY)->toDateString(),
            $this->observed(Carbon::create($year, 12, 25, 0, 0, 0, self::TIMEZONE))->toDateString(),
        ]));
    }

    private function observed(Carbon $holiday): Carbon
    {
        if ($holiday->isSaturday()) {
            return $holiday->subDay();
        }

        if ($holiday->isSunday()) {
            return $holiday->addDay();
        }

        return $holiday;
    }

    private function goodFriday(int $year): Carbon
    {
        $easterDate = gmdate('Y-m-d', easter_date($year));
        $easter = Carbon::createFromFormat('Y-m-d', $easterDate, self::TIMEZONE)->startOfDay();

        return $easter->subDays(2);
    }
}
