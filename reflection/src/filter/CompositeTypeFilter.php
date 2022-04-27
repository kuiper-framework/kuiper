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

use kuiper\reflection\type\CompositeType;
use kuiper\reflection\TypeFilterInterface;

class CompositeTypeFilter implements TypeFilterInterface
{
    public function __construct(private CompositeType $type)
    {
    }

    public function isValid(mixed $value): bool
    {
        foreach ($this->type->getTypes() as $type) {
            if ($type->isValid($value)) {
                return true;
            }
        }

        return false;
    }

    public function sanitize(mixed $value): mixed
    {
        return $value;
    }
}
