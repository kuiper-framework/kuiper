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
use kuiper\db\metadata\ColumnInterface;
use UnitEnum;

class EnumConverter implements AttributeConverterInterface
{
    private static array $ENUM_CASES = [];

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
        throw new \InvalidArgumentException('attribute is not enum type');
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
            return $enumType::tryFrom($dbData);
        }

        if (is_a($enumType, UnitEnum::class, true)) {
            return self::tryFromEnum($enumType, $dbData);
        }
        throw new \InvalidArgumentException('attribute is not enum type');
    }

    /**
     * @param class-string<UnitEnum> $enumType
     * @param string $enumName
     * @return UnitEnum|null
     */
    private static function tryFromEnum(string $enumType, string $enumName): ?UnitEnum
    {
        if (!isset(self::$ENUM_CASES[$enumType])) {
            foreach ($enumType::cases() as $enum) {
                self::$ENUM_CASES[$enumType][$enum->name] = $enum;
            }
        }

        return self::$ENUM_CASES[$enumType][$enumName] ?? null;
    }
}
