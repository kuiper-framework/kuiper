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

use InvalidArgumentException;
use kuiper\db\metadata\ColumnInterface;
use kuiper\helper\Enum;

class EnumConverter implements AttributeConverterInterface
{
    /**
     * @var bool
     */
    private $ordinal;

    public function __construct(bool $ordinal)
    {
        $this->ordinal = $ordinal;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseColumn($attribute, ColumnInterface $column): int|string
    {
        if ($attribute instanceof Enum) {
            return $this->ordinal ? $attribute->ordinal() : $attribute->name();
        }
        throw new InvalidArgumentException('attribute is not enum type');
    }

    /**
     * {@inheritdoc}
     */
    public function convertToEntityAttribute($dbData, ColumnInterface $column): ?object
    {
        if (null === $dbData || '' === $dbData) {
            return null;
        }
        $enumType = $column->getType()->getName();

        return call_user_func([$enumType, $this->ordinal ? 'fromOrdinal' : 'fromName'], $dbData);
    }
}
