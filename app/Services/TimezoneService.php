<?php

namespace App\Services;

use Carbon\Carbon;

class TimezoneService
{
    /**
     * Convert user input datetime to UTC for database storage
     */
    public static function convertToUtc(string $datetime, string $userTimezone = null): Carbon
    {
        $userTimezone = $userTimezone ?? config('app.timezone');
        
        return Carbon::parse($datetime, $userTimezone)->utc();
    }

    /**
     * Convert UTC datetime from database to user timezone for display
     */
    public static function convertToUserTimezone(Carbon $utcDatetime, string $userTimezone = null): Carbon
    {
        $userTimezone = $userTimezone ?? config('app.timezone');
        
        return $utcDatetime->setTimezone($userTimezone);
    }

    /**
     * Get current time in UTC (for database operations)
     */
    public static function nowUtc(): Carbon
    {
        return Carbon::now('UTC');
    }

    /**
     * Get current time in user timezone (for display)
     */
    public static function nowUserTimezone(string $userTimezone = null): Carbon
    {
        $userTimezone = $userTimezone ?? config('app.timezone');
        
        return Carbon::now($userTimezone);
    }

    /**
     * Check if a UTC datetime is due (past current UTC time)
     */
    public static function isDue(Carbon $utcDatetime): bool
    {
        return $utcDatetime->lte(self::nowUtc());
    }

    /**
     * Format datetime for display in user timezone
     */
    public static function formatForDisplay(Carbon $utcDatetime, string $format = 'Y-m-d H:i:s', string $userTimezone = null): string
    {
        return self::convertToUserTimezone($utcDatetime, $userTimezone)->format($format);
    }
}
