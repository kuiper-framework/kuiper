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
use kuiper\serializer\JsonSerializerInterface;

class JsonObjectConverter implements AttributeConverterInterface
{
    public function __construct(private readonly JsonSerializerInterface $serializer)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseColumn(mixed $attribute, Column $column): mixed
    {
        return $this->serializer->toJson($attribute, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToEntityAttribute(mixed $dbData, Column $column): mixed
    {
        return $this->serializer->fromJson($dbData, $column->getType());
    }
}
