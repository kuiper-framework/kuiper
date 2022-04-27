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

class FloatTypeFilter implements TypeFilterInterface
{
    public function isValid(mixed $value): bool
    {
        return false !== filter_var($value, FILTER_VALIDATE_FLOAT);
    }

    public function sanitize(mixed $value): float
    {
        return (float) filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }
}
