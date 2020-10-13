<?php

declare(strict_types=1);

namespace kuiper\db\converter;

use kuiper\db\metadata\Column;

interface AttributeConverterInterface
{
    /**
     * Converts the value stored in the entity attribute into the
     * data representation to be stored in the database.
     *
     * @param mixed $attribute the entity attribute value to be converted
     *
     * @return string|float|int|null the converted data to be stored in the database column
     */
    public function convertToDatabaseColumn($attribute, Column $column);

    /**
     * Converts the data stored in the database column into the
     * value to be stored in the entity attribute.
     * Note that it is the responsibility of the converter writer to
     * specify the correct <code>dbData</code> type for the corresponding
     * column for use by the JDBC driver: i.e., persistence providers are
     * not expected to do such type conversion.
     *
     * @param string|int|null $dbData the data from the database column to be converted
     *
     * @return mixed the converted value to be stored in the entity attribute
     */
    public function convertToEntityAttribute($dbData, Column $column);
}
