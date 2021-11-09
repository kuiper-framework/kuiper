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

use kuiper\di\annotation\ComponentInterface;

class ComponentCollection
{
    /**
     * @var array
     */
    private static $COMPONENTS;

    public static function clear(string $annotationClass = null): void
    {
        if (null === $annotationClass) {
            self::$COMPONENTS = [];
        } else {
            unset(self::$COMPONENTS[$annotationClass]);
        }
    }

    /**
     * @param ComponentInterface $annotation
     */
    public static function register(ComponentInterface $annotation): void
    {
        self::$COMPONENTS[get_class($annotation)][] = $annotation;
    }

    /**
     * @return object[] all annotation objects
     */
    public static function getAnnotations(string $annotationClass): array
    {
        return array_values(self::$COMPONENTS[$annotationClass] ?? []);
    }
}
