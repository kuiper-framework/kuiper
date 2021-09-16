<?php

declare(strict_types=1);

namespace kuiper\db\converter;

use kuiper\db\metadata\Column;
use kuiper\serializer\JsonSerializerInterface;

class JsonObjectConverter implements AttributeConverterInterface
{
    /**
     * @var JsonSerializerInterface
     */
    private $serializer;

    public function __construct(JsonSerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseColumn($attribute, Column $column)
    {
        return $this->serializer->toJson($attribute, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToEntityAttribute($dbData, Column $column)
    {
        return $this->serializer->fromJson($dbData, $column->getType());
    }
}
