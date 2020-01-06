<?php

namespace kuiper\reflection;

use kuiper\reflection\type\ArrayType;
use kuiper\reflection\type\ClassType;
use kuiper\reflection\type\MixedType;

/**
 * @SuppressWarnings("NumberOfChildren")
 */
abstract class ReflectionType implements ReflectionTypeInterface
{
    const CLASS_NAME_REGEX = '/^\\\\?([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\\\\)*[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/';

    private static $TYPES = [
        'bool' => type\BooleanType::class,
        'false' => type\BooleanType::class,
        'true' => type\BooleanType::class,
        'boolean' => type\BooleanType::class,
        'int' => type\IntegerType::class,
        'string' => type\StringType::class,
        'integer' => type\IntegerType::class,
        'float' => type\FloatType::class,
        'double' => type\FloatType::class,
        'resource' => type\ResourceType::class,
        'callback' => type\CallableType::class,
        'callable' => type\CallableType::class,
        'void' => type\VoidType::class,
        'null' => type\NullType::class,
        'object' => type\ObjectType::class,
        'mixed' => type\MixedType::class,
        'number' => type\NumberType::class,
        'iterable' => type\IterableType::class,
    ];

    private static $SINGLETONS = [];

    /**
     * Parses type string to type object.
     *
     * @param string $type
     *
     * @return ReflectionTypeInterface
     *
     * @throws \InvalidArgumentException if type is not valid
     */
    public static function forName(string $type): ReflectionTypeInterface
    {
        if (empty($type)) {
            throw new \InvalidArgumentException('Expected an type string, got empty string');
        }
        if (preg_match('/(\[\])+$/', $type, $matches)) {
            $suffixLength = strlen($matches[0]);

            return new ArrayType(self::forName(substr($type, 0, -1 * $suffixLength)), $suffixLength / 2);
        } elseif (preg_match(self::CLASS_NAME_REGEX, $type)) {
            if ('array' == $type) {
                return new ArrayType(new MixedType());
            } elseif (isset(self::$TYPES[$type])) {
                $className = self::$TYPES[$type];
                if (!isset(self::$SINGLETONS[$className])) {
                    self::$SINGLETONS[$className] = new $className();
                }

                return self::$SINGLETONS[$className];
            } else {
                return new ClassType($type);
            }
        } else {
            throw new \InvalidArgumentException("Expected an type string, got '{$type}'");
        }
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getName();
    }
}
