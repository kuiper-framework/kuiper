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

namespace kuiper\helper;

use InvalidArgumentException;
use ReflectionEnum;
use ReflectionException;

class EnumHelper
{
    /**
     * @template T
     *
     * @param class-string $enumClass
     * @param string|int   $value
     * @param T|null       $default
     *
     * @return T|null
     *
     * @throws ReflectionException
     */
    public static function tryFrom(string $enumClass, string|int $value, $default = null)
    {
        $reflectionEnum = new ReflectionEnum($enumClass);
        if (!$reflectionEnum->isBacked()) {
            throw new InvalidArgumentException("Enum $enumClass is not backed");
        }
        $backingType = (string) $reflectionEnum->getBackingType();
        if (is_string($value) && 'string' === $backingType) {
            return $enumClass::tryFrom($value);
        }
        if (is_int($value) && 'int' === $backingType) {
            return $enumClass::tryFrom($value);
        }

        return $default;
    }

    /**
     * @template T
     *
     * @param class-string<T> $enumClass
     * @param string          $name
     *
     * @return T
     *
     * @throws ReflectionException
     */
    public static function fromName(string $enumClass, string $name)
    {
        $result = self::tryFromName($enumClass, $name);
        if (null === $result) {
            throw new InvalidArgumentException("Enum $enumClass has no case $name");
        }

        return $result;
    }

    /**
     * @template T
     *
     * @param class-string $enumClass
     * @param string       $name
     * @param T|null       $default
     *
     * @return T|null
     *
     * @throws ReflectionException
     */
    public static function tryFromName(string $enumClass, string $name, $default = null)
    {
        $reflectionEnum = new ReflectionEnum($enumClass);
        if ($reflectionEnum->hasCase($name)) {
            return $reflectionEnum->getCase($name)->getValue();
        }

        return $default;
    }

    public static function hasName(string $enumClass, string $name): bool
    {
        return (new ReflectionEnum($enumClass))->hasCase($name);
    }
}
