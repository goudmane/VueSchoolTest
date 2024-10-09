<?php

namespace App\Utils;

class TimeZoneConfig
{
    /**
     * List of time zones.
     *
     * @var array
     */
    public static array $timezones = [
        'Europe/Berlin',  // Central European Time (CET)
        'America/Chicago', // Central Standard Time (CST)
        'GMT',             // Greenwich Mean Time
        'America/New_York', // Eastern Standard Time (EST)
        'America/Los_Angeles', // Pacific Standard Time (PST)
        'Asia/Kolkata',    // Indian Standard Time (IST)
        'UTC',             // Coordinated Universal Time
        'America/Puerto_Rico', // Atlantic Standard Time (AST)
        'America/Denver',  // Mountain Standard Time (MST)
        'America/Anchorage', // Alaska Standard Time (AKST)
        'Pacific/Honolulu', // Hawaii-Aleutian Standard Time (HST)
        'Australia/Sydney', // Australian Eastern Standard Time (AEST)
        'Australia/Adelaide', // Australian Central Standard Time (ACST)
        'Australia/Perth', // Australian Western Standard Time (AWST)
        'Europe/Helsinki', // Eastern European Time (EET)
        'Europe/Moscow',   // Moscow Standard Time (MSK)
        'Pacific/Auckland', // New Zealand Standard Time (NZST)
    ];

    /**
     * Get all time zones.
     *
     * @return array
     */
    public static function getTimeZones(): array
    {
        return self::$timezones;
    }
}
