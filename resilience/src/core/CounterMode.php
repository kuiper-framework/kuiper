<?php

declare(strict_types=1);

namespace kuiper\resilience\core;

class CounterMode
{
    private static $BATCH_MODE;

    public static function enable(): void
    {
        self::$BATCH_MODE = true;
    }
}
