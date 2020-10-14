<?php

declare(strict_types=1);

namespace kuiper\db\converter;

use kuiper\db\metadata\Column;
use Symfony\Component\Serializer\SerializerInterface;

class ObjectConverter implements AttributeConverterInterface
{
    /**
     * @var SerializerInterface
     */
    private $serializer;
    /**
     * @var string
     */
    private $format;

    public function __construct(SerializerInterface $serializer, string $format = 'json')
    {
        $this->serializer = $serializer;
        $this->format = $format;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseColumn($attribute, Column $column)
    {
        return $this->serializer->serialize($attribute, $this->format);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToEntityAttribute($dbData, Column $column)
    {
        return $this->serializer->deserialize($dbData, $column->getType()->getName(), $this->format);
    }
}
