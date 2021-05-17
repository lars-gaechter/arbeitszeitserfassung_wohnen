<?php

declare(strict_types=1);

class RIODateTimeFactory
{

    /**
     * @param string $datetime
     * @param DateTimeZone|null $timezone
     * @return DateTime
     * @throws Exception
     */
    static function getDateTime(string $datetime = 'now', DateTimeZone $timezone = null): DateTime
    {
        return new DateTime($datetime, (null === $timezone) ? new DateTimeZone("Europe/Zurich") : $timezone);
    }
}