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

namespace kuiper\tars\type;

use BackedEnum;

/**
 * @template T
 */
class EnumType extends AbstractType
{
    /**
     * @param class-string<T> $className
     */
    public function __construct(private readonly string $className)
    {
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function asEnumType(): EnumType
    {
        return $this;
    }

    public function isEnum(): bool
    {
        return true;
    }

    public function __toString(): string
    {
        return $this->className;
    }

    public function getEnumValue(BackedEnum|int $enumObj): ?int
    {
        return is_object($enumObj) ? $enumObj->value : $enumObj;
    }

    /**
     * @param int $value
     *
     * @return T
     */
    public function createEnum(int $value)
    {
        $enumClass = $this->className;

        return $enumClass::from($value);
    }

    public function getTarsType(): int
    {
        return Type::INT64;
    }
}
