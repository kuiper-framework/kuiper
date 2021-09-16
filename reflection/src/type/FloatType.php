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

class FloatType extends ReflectionType
{
    public function getName(): string
    {
        return 'float';
    }

    public function isPrimitive(): bool
    {
        return true;
    }

    public function isScalar(): bool
    {
        return true;
    }
}
