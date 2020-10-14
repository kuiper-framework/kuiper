<?php

declare(strict_types=1);

namespace kuiper\db\metadata;

final class NullValue
{
    /**
     * @var NullValue|null
     */
    private static $INSTANCE;

    public static function instance(): NullValue
    {
        if (null === self::$INSTANCE) {
            self::$INSTANCE = new self();
        }

        return self::$INSTANCE;
    }
}
