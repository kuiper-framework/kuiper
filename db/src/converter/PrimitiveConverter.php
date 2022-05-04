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
use kuiper\reflection\ReflectionType;
use kuiper\reflection\ReflectionTypeInterface;

class PrimitiveConverter implements AttributeConverterInterface
{
    public function __construct(private readonly ReflectionTypeInterface $type)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseColumn(mixed $attribute, Column $column): mixed
    {
        if (isset($attribute) && !is_scalar($attribute)) {
            throw new \InvalidArgumentException(sprintf('Cannot convert %s to %s', ReflectionType::describe($attribute), $this->type->getName()));
        }

        return $attribute;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToEntityAttribute(mixed $dbData, Column $column): mixed
    {
        return $this->type->sanitize($dbData);
    }
}
