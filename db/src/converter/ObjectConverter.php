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

use kuiper\db\metadata\Column;
use Symfony\Component\Serializer\SerializerInterface;

class ObjectConverter implements AttributeConverterInterface
{
    public function __construct(private readonly SerializerInterface $serializer,
                                private readonly string $format = 'json')
    {
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseColumn(mixed $attribute, Column $column): mixed
    {
        return $this->serializer->serialize($attribute, $this->format);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToEntityAttribute(mixed $dbData, Column $column): mixed
    {
        return $this->serializer->deserialize($dbData, $column->getType()->getName(), $this->format);
    }
}
