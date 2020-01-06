<?php

namespace kuiper\reflection\type;

use kuiper\reflection\ReflectionType;

class IterableType extends ReflectionType
{
    public function getName(): string
    {
        return 'iterable';
    }

    public function isCompound(): bool
    {
        return true;
    }

    public function isPrimitive(): bool
    {
        return true;
    }
}
