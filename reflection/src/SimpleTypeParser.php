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

namespace kuiper\reflection;

use InvalidArgumentException;
use kuiper\reflection\type\ArrayType;
use kuiper\reflection\type\ClassType;
use kuiper\reflection\type\CompositeType;
use kuiper\reflection\type\MixedType;

class SimpleTypeParser implements TypeParserInterface
{
    private const CLASS_NAME_REGEX = '/^\\\\?([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\\\\)*[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/';

    /**
     * @var string[]
     */
    private static array $TYPES = [
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

    /**
     * @var ReflectionTypeInterface[]
     */
    private static array $SINGLETONS = [];

    /**
     * Parses type string to type object.
     *
     * @param string $type
     * @param bool   $allowsNull
     *
     * @return ReflectionTypeInterface
     */
    public static function forName(string $type, bool $allowsNull = false): ReflectionTypeInterface
    {
        if (empty($type)) {
            throw new InvalidArgumentException('Expected an type string, got empty string');
        }
        if (str_starts_with($type, '?')) {
            $type = substr($type, 1);
            $allowsNull = true;
        }

        if (preg_match('/(\[])+$/', $type, $matches)) {
            $suffixLength = strlen($matches[0]);

            return new ArrayType(self::forName(substr($type, 0, -1 * $suffixLength)), $suffixLength / 2, $allowsNull);
        }

        if (self::isClassName($type)) {
            return self::getSingletonType($type, $allowsNull);
        }

        throw new InvalidArgumentException("Expected an type string, got '{$type}'");
    }

    public static function isClassName(string $identifier): bool
    {
        return (bool) preg_match(self::CLASS_NAME_REGEX, $identifier);
    }

    /**
     * Parses type string to type objects.
     *
     * type-expression          = 1*(array-of-type-expression|array-of-type|type ["|"])
     * array-of-type-expression = "(" type-expression ")[]"
     * array-of-type            = type "[]"
     * type                     = class-name|keyword
     * class-name               = 1*CHAR
     * keyword                  = "string"|"integer"|"int"|"boolean"|"bool"|"float"
     *                            |"double"|"object"|"mixed"|"array"|"resource"
     *                            |"void"|"null"|"callback"|"false"|"true"|"self"
     *
     * @see https://phpdoc.org/docs/latest/references/phpdoc/types.html
     *
     * 目前只实现解析不能包含括号的简单类型.
     *
     * @throws InvalidArgumentException
     */
    public function parse(string $typeString): ReflectionTypeInterface
    {
        if (str_contains($typeString, '|')) {
            $parts = explode('|', $typeString);

            return CompositeType::create(array_map(static function ($typeString): ReflectionTypeInterface {
                return static::forName($typeString);
            }, $parts));
        }

        return static::forName($typeString);
    }

    private static function getSingletonType(string $typeName, bool $allowsNull): ReflectionTypeInterface
    {
        if (!isset(self::$SINGLETONS[$typeName][$allowsNull])) {
            if ('array' === $typeName) {
                $type = new ArrayType(new MixedType(), 1, $allowsNull);
            } elseif (isset(self::$TYPES[$typeName])) {
                $className = self::$TYPES[$typeName];
                $type = new $className($allowsNull);
            } else {
                $type = new ClassType($typeName, $allowsNull);
            }
            self::$SINGLETONS[$typeName][$allowsNull] = $type;
        }

        return self::$SINGLETONS[$typeName][$allowsNull];
    }
}
