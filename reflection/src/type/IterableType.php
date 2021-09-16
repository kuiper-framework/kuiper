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

namespace kuiper\reflection\type;

use kuiper\reflection\ReflectionType;

class IterableType extends ReflectionType
{
    public function getName(): string
    {
        return 'iterable';
    }

    public function isCompound(): bool
    {
        return true;
    }

    public function isPrimitive(): bool
    {
        return true;
    }
}
