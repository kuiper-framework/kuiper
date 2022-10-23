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

namespace kuiper\reflection\filter;

use kuiper\reflection\type\ClassType;
use kuiper\reflection\TypeFilterInterface;

class ClassTypeFilter implements TypeFilterInterface
{
    public function __construct(private readonly ClassType $type)
    {
    }

    public function isValid(mixed $value): bool
    {
        $className = $this->type->getName();

        return $value instanceof $className;
    }

    public function sanitize(mixed $value): mixed
    {
        return $value;
    }
}
