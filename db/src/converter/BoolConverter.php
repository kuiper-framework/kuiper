<?php

declare(strict_types=1);

namespace kuiper\db\converter;

use kuiper\db\metadata\Column;

class BoolConverter implements AttributeConverterInterface
{
    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseColumn($attribute, Column $column)
    {
        return ((bool) $attribute) ? 1 : 0;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToEntityAttribute($dbData, Column $column)
    {
        return (bool) $dbData;
    }
}
