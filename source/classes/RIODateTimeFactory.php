<?php

declare(strict_types=1);

class RIODateTimeFactory
{

    /**
     * @param mixed $datetime
     * @param DateTimeZone|null $timezone
     * @return DateTime
     * @throws Exception
     */
    static function getDateTime(mixed $datetime = 'now', DateTimeZone $timezone = null): DateTime
    {
        return new DateTime($datetime, (null === $timezone) ? new DateTimeZone("Europe/Zurich") : $timezone);
    }
}