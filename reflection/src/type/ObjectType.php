<?php

namespace kuiper\reflection\type;

use kuiper\reflection\ReflectionType;

class ObjectType extends ReflectionType
{
    public function getName(): string
    {
        return 'object';
    }

    public function isCompound(): bool
    {
        return true;
    }

    public function isObject(): bool
    {
        return true;
    }

    public function isPrimitive(): bool
    {
        return true;
    }
}
