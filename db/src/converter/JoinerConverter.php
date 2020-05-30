<?php

declare(strict_types=1);

namespace kuiper\db\orm\serializer;

use kuiper\db\converter\AttributeConverterInterface;
use kuiper\db\metadata\Column;

class JoinerConverter implements AttributeConverterInterface
{
    /**
     * @var string
     */
    private $delimiter;
    /**
     * @var bool
     */
    private $around;

    /**
     * ArraySerializer constructor.
     *
     * @param string $delimiter
     */
    public function __construct($delimiter = '|', bool $around = false)
    {
        $this->delimiter = $delimiter;
        $this->around = $around;
    }

    public function convertToDatabaseColumn($attribute, Column $column)
    {
        if (!is_array($attribute)) {
            throw new \InvalidArgumentException('attribute should be array');
        }
        $value = implode($this->delimiter, $attribute);

        return $this->around ? $this->delimiter.$value.$this->delimiter : $value;
    }

    public function convertToEntityAttribute($dbData, Column $column)
    {
        $trim = trim($dbData, $this->delimiter);
        if (empty($trim)) {
            return [];
        }

        return explode($this->delimiter, $trim);
    }
}
