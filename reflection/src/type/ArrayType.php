<?php

declare(strict_types=1);

namespace kuiper\reflection\type;

use kuiper\reflection\ReflectionType;
use kuiper\reflection\ReflectionTypeInterface;

class ArrayType extends ReflectionType
{
    /**
     * @var ReflectionTypeInterface
     */
    private $valueType;

    /**
     * @var int
     */
    private $dimension;

    public function __construct(ReflectionTypeInterface $valueType, int $dimension = 1, bool $allowsNull = false)
    {
        parent::__construct($allowsNull);
        $this->valueType = $valueType;
        $this->dimension = $dimension;
    }

    public function getValueType(): ReflectionTypeInterface
    {
        return $this->valueType;
    }

    public function getDimension(): int
    {
        return $this->dimension;
    }

    public function getName(): string
    {
        return $this->valueType->getName().str_repeat('[]', $this->dimension);
    }

    protected function getDisplayString(): string
    {
        return 1 === $this->dimension && $this->valueType instanceof MixedType ? 'array' : $this->getName();
    }

    public function isArray(): bool
    {
        return true;
    }

    public function isCompound(): bool
    {
        return $this->isUnknown();
    }

    public function isUnknown(): bool
    {
        return $this->valueType instanceof MixedType;
    }

    public function isPrimitive(): bool
    {
        return $this->isCompound();
    }
}
