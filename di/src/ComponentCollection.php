<?php

declare(strict_types=1);

namespace kuiper\di;

use kuiper\di\annotation\ComponentInterface;

class ComponentCollection
{
    /**
     * @var array
     */
    private static $COMPONENTS;

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
