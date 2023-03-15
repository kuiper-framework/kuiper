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
     * @return T
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
}
