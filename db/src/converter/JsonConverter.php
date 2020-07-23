<?php

declare(strict_types=1);

namespace kuiper\db\orm\serializer;

use kuiper\db\converter\AttributeConverterInterface;
use kuiper\db\metadata\Column;

class JsonConverter implements AttributeConverterInterface
{
    /**
     * @var bool
     */
    private $assoc;
    /**
     * @var int
     */
    private $options;

    public function __construct(bool $assoc = true, ?int $options = null)
    {
        $this->assoc = $assoc;
        $this->options = $options ?? (JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseColumn($attribute, Column $column)
    {
        return json_encode($attribute, $this->options);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToEntityAttribute($dbData, Column $column)
    {
        return json_decode($dbData, $this->assoc);
    }
}
