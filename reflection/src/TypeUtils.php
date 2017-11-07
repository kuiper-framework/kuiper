<?php

namespace kuiper\reflection;

use kuiper\reflection\type\ArrayType;
use kuiper\reflection\type\BooleanType;
use kuiper\reflection\type\CallableType;
use kuiper\reflection\type\ClassType;
use kuiper\reflection\type\CompositeType;
use kuiper\reflection\type\FloatType;
use kuiper\reflection\type\IntegerType;
use kuiper\reflection\type\IterableType;
use kuiper\reflection\type\MixedType;
use kuiper\reflection\type\NullType;
use kuiper\reflection\type\NumberType;
use kuiper\reflection\type\ObjectType;
use kuiper\reflection\type\ResourceType;
use kuiper\reflection\type\StringType;
use kuiper\reflection\type\VoidType;

abstract class TypeUtils
{
    private static $FILTERS = [
        ArrayType::class => filter\ArrayTypeFilter::class,
        BooleanType::class => filter\BooleanTypeFilter::class,
        CallableType::class => filter\CallableTypeFilter::class,
        ClassType::class => filter\ClassTypeFilter::class,
        CompositeType::class => filter\CompositeTypeFilter::class,
        FloatType::class => filter\FloatTypeFilter::class,
        IntegerType::class => filter\IntegerTypeFilter::class,
        IterableType::class => filter\IterableTypeFilter::class,
        NullType::class => filter\NullTypeFilter::class,
        NumberType::class => filter\FloatTypeFilter::class,
        ObjectType::class => filter\ObjectTypeFilter::class,
        ResourceType::class => filter\ResourceTypeFilter::class,
        StringType::class => filter\StringTypeFilter::class,
        VoidType::class => filter\NullTypeFilter::class,
    ];

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
     * @param string $typeString
     *
     * @return ReflectionTypeInterface
     *
     * @throws \InvalidArgumentException
     */
    public static function parse(string $typeString): ReflectionTypeInterface
    {
        if (strpos($typeString, '|') !== false) {
            return new CompositeType(array_map(function ($typeString) {
                return ReflectionType::forName($typeString);
            }, explode('|', $typeString)));
        } else {
            return ReflectionType::forName($typeString);
        }
    }

    /**
     * @param ReflectionTypeInterface $type
     *
     * @return bool return true when type is an array
     */
    public static function isArray(ReflectionTypeInterface $type): bool
    {
        return $type instanceof ArrayType;
    }

    /**
     * @param ReflectionTypeInterface $type
     *
     * @return bool return true if the type is one of scalar type: boolean, string, float, integer
     */
    public static function isScalar(ReflectionTypeInterface $type): bool
    {
        return $type instanceof BooleanType
            || $type instanceof IntegerType
            || $type instanceof StringType
            || $type instanceof FloatType;
    }

    /**
     * @param ReflectionTypeInterface $type
     *
     * @return bool return true if the type is one of array, object, callable, iterable
     */
    public static function isCompound(ReflectionTypeInterface $type): bool
    {
        if ($type instanceof ArrayType) {
            return (string) $type == 'array';
        } else {
            return $type instanceof ObjectType
                || $type instanceof CallableType
                || $type instanceof IterableType;
        }
    }

    /**
     * @param ReflectionTypeInterface $type
     *
     * @return bool return true if the type is one of mixed, number, void, callback
     */
    public static function isPseudo(ReflectionTypeInterface $type): bool
    {
        return $type instanceof MixedType
            || $type instanceof NumberType
            || $type instanceof VoidType;
    }

    /**
     * @param ReflectionTypeInterface $type
     *
     * @return bool return true if the type is null type
     */
    public static function isNull(ReflectionTypeInterface $type): bool
    {
        return $type instanceof NullType;
    }

    /**
     * @param ReflectionTypeInterface $type
     *
     * @return bool return true if the type is resource type
     */
    public static function isResource(ReflectionTypeInterface $type): bool
    {
        return $type instanceof ResourceType;
    }

    /**
     * @param ReflectionTypeInterface $type
     *
     * @return bool return true if type is class type
     */
    public static function isClass(ReflectionTypeInterface $type): bool
    {
        return $type instanceof ClassType;
    }

    /**
     * @param ReflectionTypeInterface $type
     *
     * @return bool return true if type is composited type
     */
    public static function isComposite(ReflectionTypeInterface $type): bool
    {
        return $type instanceof CompositeType;
    }

    /**
     * @param ReflectionTypeInterface $type
     *
     * @return bool
     */
    public static function isUnknown(ReflectionTypeInterface $type)
    {
        return $type instanceof MixedType
            || (self::isArray($type) && $type->getValueType() instanceof MixedType);
    }

    /**
     * @param ReflectionTypeInterface $type
     *
     * @return bool return true if the type is one of:
     *              - scalar type: boolean, string, float, integer
     *              - special type: resource, null
     *              - compound type: object, array, callable, iterable
     *              - pseudo-types: mixed, number, void, callback
     */
    public static function isPrimitive(ReflectionTypeInterface $type): bool
    {
        return self::isScalar($type)
            || self::isCompound($type)
            || self::isPseudo($type)
            || self::isResource($type)
            || self::isNull($type);
    }

    /**
     * Describes type of value.
     *
     * @param mixed $value
     *
     * @return string
     */
    public static function describe($value)
    {
        $type = gettype($value);
        if ($type === 'object') {
            return get_class($value);
        } elseif (in_array($type, ['array', 'resource', 'unknown type'])) {
            return $type;
        } else {
            return json_encode($value);
        }
    }

    /**
     * checks whether the value is valid.
     *
     * @param ReflectionTypeInterface $type
     * @param mixed                   $value
     *
     * @return bool
     */
    public static function validate(ReflectionTypeInterface $type, $value)
    {
        $filter = self::createFilter($type);

        return $filter ? $filter->validate($value) : true;
    }

    /**
     * Sanitizes input value.
     *
     * @param ReflectionTypeInterface $type
     * @param mixed                   $value
     *
     * @return mixed
     */
    public static function sanitize(ReflectionTypeInterface $type, $value)
    {
        $filter = self::createFilter($type);

        return $filter ? $filter->sanitize($value) : $value;
    }

    /**
     * @param ReflectionTypeInterface $type
     *
     * @return TypeFilterInterface
     *
     * @SuppressWarnings(PHPMD)
     */
    private static function createFilter(ReflectionTypeInterface $type)
    {
        if (isset(self::$FILTERS[get_class($type)])) {
            return new self::$FILTERS[get_class($type)]($type);
        }

        return null;
    }
}
