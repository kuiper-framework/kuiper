<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace kuiper\db\metadata;

final class NullValue
{
    private static ?NullValue $INSTANCE = null;

    public static function instance(): NullValue
    {
        if (null === self::$INSTANCE) {
            self::$INSTANCE = new self();
        }

        return self::$INSTANCE;
    }
}
