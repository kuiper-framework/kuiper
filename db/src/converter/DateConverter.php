<?php

declare(strict_types=1);

namespace kuiper\db\converter;

class DateConverter extends AbstractDateTimeConverter
{
    private const DATE_FORMAT = 'Y-m-d';

    protected function format(\DateTime $attribute): string
    {
        return $attribute->format(self::DATE_FORMAT);
    }
}
