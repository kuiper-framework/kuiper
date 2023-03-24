<?php

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
     * @param class-string<T> $enumClass
     * @param string|int      $value
     *
     * @return T
     *
     * @throws ReflectionException
     */
    public static function tryFrom(string $enumClass, string|int $value): mixed
    {
        $reflectionEnum = new ReflectionEnum($enumClass);
        if (!$reflectionEnum->isBacked()) {
            throw new InvalidArgumentException("Enum $enumClass is not backed");
        }
        if ('string' === (string) $reflectionEnum->getBackingType()) {
            return $enumClass::tryFrom((string) $value);
        }

        return $enumClass::tryFrom((int) $value);
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
     * @param class-string<T> $enumClass
     * @param string          $name
     *
     * @return T|null
     *
     * @throws ReflectionException
     */
    public static function tryFromName(string $enumClass, string $name)
    {
        $reflectionEnum = new ReflectionEnum($enumClass);
        if ($reflectionEnum->hasCase($name)) {
            return $reflectionEnum->getCase($name)->getValue();
        }

        return null;
    }

    public static function hasName(string $enumClass, string $name): bool
    {
        return (new ReflectionEnum($enumClass))->hasCase($name);
    }
}
