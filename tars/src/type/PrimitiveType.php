<?php

declare(strict_types=1);

namespace kuiper\tars\type;

/**
 * @method static PrimitiveType string(): static
 * @method static PrimitiveType bool(): static
 * @method static PrimitiveType char(): static
 * @method static PrimitiveType int8(): static
 * @method static PrimitiveType double(): static
 * @method static PrimitiveType float(): static
 * @method static PrimitiveType int32(): static
 * @method static PrimitiveType int64(): static
 * @method static PrimitiveType uint8(): static
 * @method static PrimitiveType uint16(): static
 * @method static PrimitiveType uint32(): static
 */
final class PrimitiveType extends AbstractType
{
    public const BOOL = 'bool';
    public const CHAR = 'char';
    public const INT8 = 'int8';
    public const DOUBLE = 'double';
    public const FLOAT = 'float';
    public const SHORT = 'short';
    public const INT32 = 'int32';
    public const INT64 = 'int64';
    public const UINT8 = 'uint8';
    public const UINT16 = 'uint16';
    public const UINT32 = 'uint32';
    public const STRING = 'string';

    /**
     * @var array
     */
    private static $MAP = [
        self::BOOL => Type::INT8,
        self::CHAR => Type::INT8,
        self::INT8 => Type::INT8,
        self::UINT8 => Type::INT16,
        self::SHORT => Type::INT16,
        self::UINT16 => Type::INT32,
        self::INT32 => Type::INT32,
        self::UINT32 => Type::INT64,
        self::INT64 => Type::INT64,
        self::FLOAT => Type::FLOAT,
        self::DOUBLE => Type::DOUBLE,
        self::STRING => Type::STRING4,
    ];

    /**
     * @var array
     */
    private static $INSTANCES = [];

    /**
     * @var string
     */
    private $type;

    /**
     * PrimitiveType constructor.
     */
    public function __construct(string $primitiveType)
    {
        if (!isset(self::$MAP[$primitiveType])) {
            throw new \InvalidArgumentException("unknown primitive tars type $primitiveType");
        }
        $this->type = $primitiveType;
    }

    public static function __callStatic(string $name, array $arguments): self
    {
        return self::of($name);
    }

    public static function of(string $name): self
    {
        $name = strtolower($name);
        if (isset(self::$INSTANCES[$name])) {
            return self::$INSTANCES[$name];
        }

        return self::$INSTANCES[$name] = new self($name);
    }

    public function getTarsType(): int
    {
        return self::$MAP[$this->type];
    }

    public function getPhpType(): string
    {
        return $this->type;
    }

    public function asPrimitiveType(): PrimitiveType
    {
        return $this;
    }

    public function isPrimitive(): bool
    {
        return true;
    }

    public function __toString(): string
    {
        return $this->type;
    }
}
