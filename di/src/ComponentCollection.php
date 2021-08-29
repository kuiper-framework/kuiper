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
     * @param string             $componentId
     * @param ComponentInterface $annotation
     */
    public static function register(string $componentId, ComponentInterface $annotation): void
    {
        self::$COMPONENTS[get_class($annotation)][$componentId] = $annotation;
    }

    /**
     * @return object[] all annotation objects
     */
    public static function getAnnotations(string $annotationClass): array
    {
        return array_values(self::$COMPONENTS[$annotationClass] ?? []);
    }

    /**
     * @return object|null the annotation object
     */
    public static function getAnnotation(string $annotationClass, string $componentId)
    {
        if (isset(self::$COMPONENTS[$annotationClass])) {
            return self::$COMPONENTS[$annotationClass][$componentId] ?? null;
        }

        return null;
    }
}
