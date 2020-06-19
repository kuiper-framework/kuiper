<?php

declare(strict_types=1);

namespace kuiper\di;

class ComponentCollection
{
    /**
     * @var array
     */
    private static $COMPONENTS;

    /**
     * @param object $annotation
     */
    public static function register(string $componentId, $annotation): void
    {
        self::$COMPONENTS[get_class($annotation)][$componentId] = $annotation;
    }

    /**
     * @return string[] all component names with the annotation
     */
    public static function getComponents(string $annotationClass): array
    {
        return array_keys(self::$COMPONENTS[$annotationClass] ?? []);
    }

    /**
     * @return string[] all annotation objects
     */
    public static function getAnnotations(string $annotationClass): array
    {
        return array_values(self::$COMPONENTS[$annotationClass] ?? []);
    }

    /**
     * @return object|null the annotation object
     */
    public static function getAnnotation(string $componentId, string $annotationClass)
    {
        if (isset(self::$COMPONENTS[$annotationClass])) {
            return self::$COMPONENTS[$annotationClass][$componentId] ?? null;
        }

        return null;
    }
}
