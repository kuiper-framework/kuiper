<?php

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
