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
    public const STRING = 'string';
    public const INT = 'int';
    public const LONG = 'long';
    public const FLOAT_TYPE = 'float';
    public const DOUBLE_TYPE = 'double';

    /**
     * @var int[]
     */
    private static $PRIMITIVES = [
        self::BOOL => Type::INT8,
        'boolean' => Type::INT8,
        'byte' => Type::INT8,
        self::CHAR => Type::INT8,
        'unsigned byte' => Type::INT16,
        'unsigned char' => Type::INT16,
        'short' => Type::INT16,
        'unsigned short' => Type::INT32,
        self::INT => Type::INT32,
        'unsigned int' => Type::INT64,
        self::LONG => Type::INT64,
        self::FLOAT_TYPE => Type::FLOAT,
        self::DOUBLE_TYPE => Type::DOUBLE,
        self::STRING => Type::STRING4,
    ];

    /**
     * @var string[]
     */
    private static $ALIAS = [
        'boolean' => self::BOOL,
        'byte' => self::CHAR,
        'unsigned byte' => 'unsigned char',
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
    private function __construct(string $primitiveType)
    {
        if (!self::has($primitiveType)) {
            throw new \InvalidArgumentException("unknown primitive tars type $primitiveType");
        }
        $this->type = $primitiveType;
    }

    public static function __callStatic(string $name, array $arguments): self
    {
        return self::of($name);
    }

    public static function has(string $name): bool
    {
        return isset(self::$PRIMITIVES[strtolower($name)]);
    }

    public static function of(string $name): self
    {
        $name = strtolower($name);
        if (isset(self::$ALIAS[$name])) {
            $name = self::$ALIAS[$name];
        }
        if (!isset(self::$INSTANCES[$name])) {
            self::$INSTANCES[$name] = new self($name);
        }

        return self::$INSTANCES[$name];
    }

    public function getTarsType(): int
    {
        return self::$PRIMITIVES[$this->type];
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
