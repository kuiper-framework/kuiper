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

namespace kuiper\db\converter;

use DateTimeInterface;

class DateConverter extends AbstractDateTimeConverter
{
    private const DATE_FORMAT = 'Y-m-d';

    protected function format(DateTimeInterface $attribute): string
    {
        return $attribute->format(self::DATE_FORMAT);
    }
}
