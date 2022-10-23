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

namespace kuiper\reflection\type;

use kuiper\reflection\ReflectionType;
use kuiper\reflection\ReflectionTypeInterface;

class ArrayType extends ReflectionType
{
    public function __construct(
        private readonly ReflectionTypeInterface $valueType,
        private readonly int $dimension = 1,
        bool $allowsNull = false)
    {
        parent::__construct($allowsNull);
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
