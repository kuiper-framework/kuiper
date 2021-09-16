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

namespace kuiper\tars\type;

class MapType extends AbstractType
{
    /**
     * @var Type
     */
    private $keyType;
    /**
     * @var Type
     */
    private $valueType;

    /**
     * MapType constructor.
     */
    public function __construct(Type $keyType, Type $valueType)
    {
        $this->keyType = $keyType;
        $this->valueType = $valueType;
    }

    public function getKeyType(): Type
    {
        return $this->keyType;
    }

    public function getValueType(): Type
    {
        return $this->valueType;
    }

    public function isMap(): bool
    {
        return true;
    }

    public function asMapType(): MapType
    {
        return $this;
    }

    public function __toString(): string
    {
        return sprintf('map<%s, %s>', (string) $this->keyType, (string) $this->valueType);
    }

    public function getTarsType(): int
    {
        return Type::MAP;
    }

    public static function arrayMap(Type $valueType, PrimitiveType $keyType = null): MapType
    {
        return new self($keyType ?? PrimitiveType::string(), $valueType);
    }

    public static function byteArrayMap(): self
    {
        static $byteArrayMapType;
        if (null === $byteArrayMapType) {
            $byteArrayMapType = new self(PrimitiveType::string(), VectorType::byteVector());
        }

        return $byteArrayMapType;
    }

    public static function stringMap(): self
    {
        static $stringMapType;
        if (null === $stringMapType) {
            $stringMapType = new self(PrimitiveType::string(), PrimitiveType::string());
        }

        return $stringMapType;
    }
}
