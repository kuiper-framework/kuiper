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

class MixedType extends ReflectionType
{
    public function allowsNull(): bool
    {
        return true;
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    public function getName(): string
    {
        return 'mixed';
    }

    public function isPseudo(): bool
    {
        return true;
    }

    public function isUnknown(): bool
    {
        return true;
    }

    public function isPrimitive(): bool
    {
        return true;
    }
}
