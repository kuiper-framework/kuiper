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
use ReflectionNamedType;
use ReflectionUnionType;

/**
 * @SuppressWarnings("NumberOfChildren")
 */
abstract class ReflectionType implements ReflectionTypeInterface
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
     * @var string[]
     */
    private static array $FILTERS = [
        type\ArrayType::class => filter\ArrayTypeFilter::class,
        type\BooleanType::class => filter\BooleanTypeFilter::class,
        type\CallableType::class => filter\CallableTypeFilter::class,
        type\ClassType::class => filter\ClassTypeFilter::class,
        type\CompositeType::class => filter\CompositeTypeFilter::class,
        type\FloatType::class => filter\FloatTypeFilter::class,
        type\IntegerType::class => filter\IntegerTypeFilter::class,
        type\IterableType::class => filter\IterableTypeFilter::class,
        type\NullType::class => filter\NullTypeFilter::class,
        type\NumberType::class => filter\FloatTypeFilter::class,
        type\ObjectType::class => filter\ObjectTypeFilter::class,
        type\ResourceType::class => filter\ResourceTypeFilter::class,
        type\StringType::class => filter\StringTypeFilter::class,
        type\VoidType::class => filter\NullTypeFilter::class,
    ];

    /**
     * @var ReflectionTypeInterface[]
     */
    private static array $SINGLETONS = [];

    /**
     * ReflectionType constructor.
     */
    public function __construct(private readonly bool $allowsNull = false)
    {
    }

    public function allowsNull(): bool
    {
        return $this->allowsNull;
    }

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

    public static function fromPhpType(?\ReflectionType $type): ReflectionTypeInterface
    {
        if (null === $type) {
            return ReflectionType::forName('mixed');
        }
        if ($type instanceof ReflectionNamedType) {
            return self::forName($type->getName(), $type->allowsNull());
        }

        if ($type instanceof ReflectionUnionType) {
            return new CompositeType(array_map(static function (\ReflectionType $subType) {
                return self::forName((string) $subType);
            }, $type->getTypes()));
        }

        return self::forName((string) $type);
    }

    public static function phpTypeAsString(\ReflectionType $type): string
    {
        return (string) $type;
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
    public static function parse(string $typeString): ReflectionTypeInterface
    {
        if (str_contains($typeString, '|')) {
            $parts = explode('|', $typeString);
            if (2 === count($parts) && in_array('null', $parts, true)) {
                $typeString = str_replace(['|null', 'null|'], '', $typeString);

                return static::forName($typeString, true);
            }

            return new CompositeType(array_map(static function ($typeString): ReflectionTypeInterface {
                return static::forName($typeString);
            }, $parts));
        }

        return static::forName($typeString);
    }

    /**
     * Describes type of value.
     *
     * @param mixed $value
     *
     * @return string
     */
    public static function describe(mixed $value): string
    {
        return get_debug_type($value);
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

    /**
     * checks whether the value is valid.
     *
     * @param mixed $value
     */
    public function isValid(mixed $value): bool
    {
        if (!isset($value) && $this->allowsNull()) {
            return true;
        }
        $filter = self::createFilter($this);
        if (null !== $filter) {
            return $filter->isValid($value);
        }

        return true;
    }

    /**
     * Sanitizes input value.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public function sanitize(mixed $value): mixed
    {
        if (!isset($value) && $this->allowsNull()) {
            return null;
        }
        $filter = self::createFilter($this);

        return null !== $filter ? $filter->sanitize($value) : $value;
    }

    /**
     * @return TypeFilterInterface
     *
     * @SuppressWarnings(PHPMD)
     */
    private static function createFilter(ReflectionTypeInterface $type): ?TypeFilterInterface
    {
        if (isset(self::$FILTERS[get_class($type)])) {
            return new self::$FILTERS[get_class($type)]($type);
        }

        return null;
    }

    protected function getDisplayString(): string
    {
        return $this->getName();
    }

    public function __toString(): string
    {
        return ($this->allowsNull() ? '?' : '').$this->getDisplayString();
    }

    public function isArray(): bool
    {
        return false;
    }

    public function isPrimitive(): bool
    {
        return false;
    }

    public function isScalar(): bool
    {
        return false;
    }

    public function isCompound(): bool
    {
        return false;
    }

    public function isPseudo(): bool
    {
        return false;
    }

    public function isNull(): bool
    {
        return false;
    }

    public function isResource(): bool
    {
        return false;
    }

    public function isClass(): bool
    {
        return false;
    }

    public function isObject(): bool
    {
        return false;
    }

    public function isComposite(): bool
    {
        return false;
    }

    public function isUnknown(): bool
    {
        return false;
    }
}
