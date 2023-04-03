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

use kuiper\reflection\type\CompositeType;
use ReflectionNamedType;
use ReflectionUnionType;

abstract class ReflectionType implements ReflectionTypeInterface
{
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

    private static ?TypeParserInterface $PARSER = null;

    public function __construct(private bool $allowsNull = false)
    {
    }

    public function withAllowsNull(bool $allowsNull): ReflectionTypeInterface
    {
        if ($this->allowsNull === $allowsNull) {
            return $this;
        }
        $type = clone $this;
        $type->allowsNull = $allowsNull;

        return $type;
    }

    public function allowsNull(): bool
    {
        return $this->allowsNull;
    }

    public static function fromPhpType(?\ReflectionType $type): ReflectionTypeInterface
    {
        if (null === $type) {
            return self::parse('mixed');
        }
        if ($type instanceof ReflectionNamedType) {
            return self::parse(($type->allowsNull() ? '?' : '').$type->getName());
        }

        if ($type instanceof ReflectionUnionType) {
            return new CompositeType(array_map(static function (\ReflectionType $subType) {
                return self::fromPhpType($subType);
            }, $type->getTypes()));
        }

        return self::parse((string) $type);
    }

    public static function phpTypeAsString(\ReflectionType $type): string
    {
        return (string) $type;
    }

    public static function parse(string $typeString): ReflectionTypeInterface
    {
        return self::getParser()->parse($typeString);
    }

    public static function getParser(): TypeParserInterface
    {
        if (null === self::$PARSER) {
            self::$PARSER = new PhpstanTypeParser();
        }

        return self::$PARSER;
    }

    public static function setParser(TypeParserInterface $parser): void
    {
        self::$PARSER = $parser;
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
