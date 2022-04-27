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

use kuiper\reflection\TypeFilterInterface;

class IterableTypeFilter implements TypeFilterInterface
{
    public function isValid(mixed $value): bool
    {
        return is_iterable($value);
    }

    public function sanitize(mixed $value): mixed
    {
        return $value;
    }
}
