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

        // Include next year's holiday calculation because an observed New Year's
        // holiday can fall on Dec 31 of the previous calendar year.
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

    /**
     * Regular U.S. equity-market holidays.
     * This intentionally models the regular NYSE/Nasdaq session used by Rcentz.
     */
    public function holidaysForYear(int $year): array
    {
        return array_values(array_unique([
            $this->observed(Carbon::create($year, 1, 1, 0, 0, 0, self::TIMEZONE))->toDateString(),
            Carbon::create($year, 1, 1, 0, 0, 0, self::TIMEZONE)->nthOfMonth(3, Carbon::MONDAY)->toDateString(), // MLK
            Carbon::create($year, 2, 1, 0, 0, 0, self::TIMEZONE)->nthOfMonth(3, Carbon::MONDAY)->toDateString(), // Presidents
            $this->goodFriday($year)->toDateString(),
            Carbon::create($year, 5, 1, 0, 0, 0, self::TIMEZONE)->lastOfMonth(Carbon::MONDAY)->toDateString(), // Memorial
            $this->observed(Carbon::create($year, 6, 19, 0, 0, 0, self::TIMEZONE))->toDateString(), // Juneteenth
            $this->observed(Carbon::create($year, 7, 4, 0, 0, 0, self::TIMEZONE))->toDateString(), // Independence
            Carbon::create($year, 9, 1, 0, 0, 0, self::TIMEZONE)->firstOfMonth(Carbon::MONDAY)->toDateString(), // Labor
            Carbon::create($year, 11, 1, 0, 0, 0, self::TIMEZONE)->nthOfMonth(4, Carbon::THURSDAY)->toDateString(), // Thanksgiving
            $this->observed(Carbon::create($year, 12, 25, 0, 0, 0, self::TIMEZONE))->toDateString(), // Christmas
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
        // easter_date() is a UTC timestamp; take its calendar date explicitly so
        // converting to New York time cannot shift Easter into the prior day.
        $easterDate = gmdate('Y-m-d', easter_date($year));
        $easter = Carbon::createFromFormat('Y-m-d', $easterDate, self::TIMEZONE)->startOfDay();

        return $easter->subDays(2);
    }
}
