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
    public static function register(\ReflectionClass $class, $annotation): void
    {
        self::$COMPONENTS[get_class($annotation)][$class->getName()] = $annotation;
    }

    /**
     * @return string[] all class names with the annotation
     */
    public static function getComponents(string $annotationClass): array
    {
        return array_keys(self::$COMPONENTS[$annotationClass] ?? []);
    }

    /**
     * @return object|null the annotation object
     */
    public static function getAnnotation(string $class, string $annotationClass)
    {
        if (isset(self::$COMPONENTS[$annotationClass])) {
            return self::$COMPONENTS[$annotationClass][$class] ?? null;
        }

        return null;
    }
}
