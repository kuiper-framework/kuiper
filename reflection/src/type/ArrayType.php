<?php

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

    public function __construct(ReflectionTypeInterface $valueType, int $dimension = 1)
    {
        $this->valueType = $valueType;
        $this->dimension = $dimension;
    }

    /**
     * @return ReflectionTypeInterface
     */
    public function getValueType(): ReflectionTypeInterface
    {
        return $this->valueType;
    }

    /**
     * @return int
     */
    public function getDimension(): int
    {
        return $this->dimension;
    }

    public function getName(): string
    {
        return $this->valueType->getName().str_repeat('[]', $this->dimension);
    }

    public function __toString(): string
    {
        return 1 == $this->dimension && $this->valueType instanceof MixedType ? 'array' : $this->getName();
    }
}
