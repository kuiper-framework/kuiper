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

use BackedEnum;
use InvalidArgumentException;
use kuiper\db\metadata\ColumnInterface;
use kuiper\helper\EnumHelper;
use UnitEnum;

class EnumConverter implements AttributeConverterInterface
{
    public function __construct()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseColumn(mixed $attribute, ColumnInterface $column): mixed
    {
        if ($attribute instanceof BackedEnum) {
            return $attribute->value;
        }

        if ($attribute instanceof UnitEnum) {
            return $attribute->name;
        }
        throw new InvalidArgumentException('attribute is not enum type');
    }

    /**
     * {@inheritdoc}
     */
    public function convertToEntityAttribute(mixed $dbData, ColumnInterface $column): mixed
    {
        if (null === $dbData || '' === $dbData) {
            return null;
        }
        $enumType = $column->getType()->getName();

        if (is_a($enumType, BackedEnum::class, true)) {
            return EnumHelper::tryFrom($enumType, $dbData);
        }

        if (is_a($enumType, UnitEnum::class, true)) {
            return EnumHelper::tryFromName($enumType, $dbData);
        }
        throw new InvalidArgumentException('attribute is not enum type');
    }
}
