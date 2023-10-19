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

namespace kuiper\di;

final class ComponentCollection
{
    /**
     * @var Component[][]
     */
    private static array $COMPONENTS;

    public static function clear(string $annotationClass = null): void
    {
        if (null === $annotationClass) {
            self::$COMPONENTS = [];
        } else {
            unset(self::$COMPONENTS[$annotationClass]);
        }
    }

    /**
     * @param Component $annotation
     */
    public static function register(Component $annotation): void
    {
        self::$COMPONENTS[get_class($annotation)][$annotation->getComponentId()] = $annotation;
    }

    /**
     * @template T
     *
     * @param class-string<T> $componentClass
     *
     * @return T[] all annotation objects
     */
    public static function getComponents(string $componentClass): array
    {
        return array_values(self::$COMPONENTS[$componentClass] ?? []);
    }
}
