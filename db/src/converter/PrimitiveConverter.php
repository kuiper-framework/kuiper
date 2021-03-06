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
    /**
     * @var ReflectionTypeInterface
     */
    private $type;

    public function __construct(ReflectionTypeInterface $type)
    {
        $this->type = $type;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseColumn($attribute, Column $column)
    {
        if (isset($attribute) && !is_scalar($attribute)) {
            throw new \InvalidArgumentException(sprintf('Cannot convert %s to %s', ReflectionType::describe($attribute), $this->type->getName()));
        }

        return $attribute;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToEntityAttribute($dbData, Column $column)
    {
        return $this->type->sanitize($dbData);
    }
}
