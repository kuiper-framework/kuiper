<?php

namespace kuiper\reflection\type;

use kuiper\reflection\ReflectionType;

class CallableType extends ReflectionType
{
    public function getName(): string
    {
        return 'callable';
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
