<?php


class RIODateTimeFactory
{

    /**
     * @param string $datetime
     * @param DateTimeZone|null $timezone
     * @return DateTime
     * @throws Exception
     */
    static function getDateTime($datetime = 'now', DateTimeZone $timezone = null): DateTime
    {
        return new DateTime($datetime, (null === $timezone) ? new DateTimeZone("Europe/Zurich") : $timezone);
    }
}