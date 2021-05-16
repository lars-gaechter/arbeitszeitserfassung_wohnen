<?php

declare(strict_types=1);

class RIOConfig
{
    public static function isInDebugMode(): bool
    {
        return "true" === $_ENV['DEBUG'];
    }

    public static function isDevelopmentMode(): bool
    {
        return "true" === $_ENV['DEVELOPMENT_MODE'];
    }
}
