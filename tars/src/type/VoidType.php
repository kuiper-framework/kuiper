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

namespace kuiper\tars\type;

class VoidType extends AbstractType
{
    private static ?VoidType $INSTANCE = null;

    public static function instance(): self
    {
        if (null === self::$INSTANCE) {
            self::$INSTANCE = new self();
        }

        return self::$INSTANCE;
    }

    public function isVoid(): bool
    {
        return true;
    }

    public function __toString(): string
    {
        return 'void';
    }

    public function getTarsType(): int
    {
        throw new \BadMethodCallException('cannot cast void to tars type');
    }
}
