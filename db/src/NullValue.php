<?php

declare(strict_types=1);

namespace kuiper\db;

final class NullValue
{
    private static $INSTANCE;

    public static function instance(): NullValue
    {
        if (!self::$INSTANCE) {
            self::$INSTANCE = new self();
        }

        return self::$INSTANCE;
    }
}
