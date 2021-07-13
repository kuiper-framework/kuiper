<?php

declare(strict_types=1);

namespace kuiper\resilience\core;

class DummyCallback
{
    private static $INSTANCE;

    public static function instance(): DummyCallback
    {
        if (null === self::$INSTANCE) {
            self::$INSTANCE = new self();
        }

        return self::$INSTANCE;
    }

    public function __invoke()
    {
    }
}
