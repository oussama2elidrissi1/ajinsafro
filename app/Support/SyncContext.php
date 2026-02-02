<?php

namespace App\Support;

class SyncContext
{
    private static ?string $origin = null;

    public static function setOrigin(?string $origin): void
    {
        self::$origin = $origin ? strtolower($origin) : null;
    }

    public static function getOrigin(): ?string
    {
        return self::$origin;
    }

    public static function isFromWp(): bool
    {
        return self::$origin === 'wp';
    }

    public static function clear(): void
    {
        self::$origin = null;
    }
}
